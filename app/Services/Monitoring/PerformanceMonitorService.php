<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PerformanceMonitorService
{
    private array $metrics = [];
    private float $startTime;

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    public function startTimer(string $name): void
    {
        $this->metrics[$name] = [
            'start_time' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    public function endTimer(string $name): float
    {
        if (!isset($this->metrics[$name])) {
            return 0;
        }

        $endTime = microtime(true);
        $duration = $endTime - $this->metrics[$name]['start_time'];
        $memoryUsed = memory_get_usage(true) - $this->metrics[$name]['memory_start'];

        $this->metrics[$name] = array_merge($this->metrics[$name], [
            'end_time' => $endTime,
            'duration' => $duration,
            'memory_used' => $memoryUsed,
        ]);

        $this->logMetric($name, $duration, $memoryUsed);
        
        return $duration;
    }

    public function measureCallback(string $name, callable $callback): mixed
    {
        $this->startTimer($name);
        
        try {
            $result = $callback();
            $this->endTimer($name);
            return $result;
        } catch (\Exception $e) {
            $this->endTimer($name);
            throw $e;
        }
    }

    public function recordMetric(string $name, float $value, array $tags = []): void
    {
        $metric = [
            'name' => $name,
            'value' => $value,
            'timestamp' => now()->toISOString(),
            'tags' => $tags,
        ];

        $this->logMetric($name, $value, 0, $tags);
        $this->storeMetric($metric);
    }

    public function recordSlowQuery(string $query, float $duration, array $bindings = []): void
    {
        if ($duration > 1.0) { // Log queries taking more than 1 second
            Log::warning('Slow Database Query Detected', [
                'query' => $query,
                'duration' => $duration,
                'bindings' => $bindings,
                'timestamp' => now()->toISOString(),
            ]);

            $this->recordMetric('database.slow_query', $duration, [
                'query_hash' => md5($query),
            ]);
        }
    }

    public function recordApiCall(string $endpoint, string $method, int $statusCode, float $duration): void
    {
        $tags = [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
        ];

        $this->recordMetric('api.request_duration', $duration, $tags);

        if ($statusCode >= 400) {
            $this->recordMetric('api.error_count', 1, $tags);
        }
    }

    public function recordJobProcessing(string $jobClass, float $duration, bool $success): void
    {
        $tags = [
            'job_class' => $jobClass,
            'success' => $success,
        ];

        $this->recordMetric('job.processing_duration', $duration, $tags);
        
        if (!$success) {
            $this->recordMetric('job.failure_count', 1, $tags);
        }
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getSystemMetrics(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'cpu_usage' => $this->getCpuUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'request_duration' => microtime(true) - $this->startTime,
        ];
    }

    private function getCpuUsage(): float
    {
        // sys_getloadavg() is not available on Windows
        if (function_exists('sys_getloadavg')) {
            return sys_getloadavg()[0] ?? 0;
        }
        
        // For Windows systems, return 0 as fallback
        // In a real production environment, you might want to implement
        // Windows-specific CPU monitoring using COM objects or other methods
        return 0;
    }

    private function logMetric(string $name, float $value, int $memoryUsed, array $tags = []): void
    {
        $context = [
            'metric_name' => $name,
            'value' => $value,
            'memory_used' => $memoryUsed,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($tags)) {
            $context['tags'] = $tags;
        }

        Log::info('Performance Metric', $context);
    }

    private function storeMetric(array $metric): void
    {
        $key = 'performance_metrics:' . date('Y-m-d-H');
        $metrics = Cache::get($key, []);
        
        $metrics[] = $metric;
        
        // Keep only last 1000 metrics per hour
        if (count($metrics) > 1000) {
            $metrics = array_slice($metrics, -1000);
        }
        
        Cache::put($key, $metrics, now()->addHours(2));
    }

    private function getDiskUsage(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;

        return [
            'total' => $total,
            'used' => $used,
            'free' => $free,
            'percentage_used' => $total > 0 ? round(($used / $total) * 100, 2) : 0,
        ];
    }

    public function getMetricsSummary(): array
    {
        $key = 'performance_metrics:' . date('Y-m-d-H');
        $metrics = Cache::get($key, []);

        $summary = [
            'total_requests' => 0,
            'avg_response_time' => 0,
            'error_rate' => 0,
            'slow_queries' => 0,
        ];

        if (empty($metrics)) {
            return $summary;
        }

        $totalDuration = 0;
        $errorCount = 0;
        $slowQueryCount = 0;

        foreach ($metrics as $metric) {
            if ($metric['name'] === 'api.request_duration') {
                $summary['total_requests']++;
                $totalDuration += $metric['value'];
                
                if (isset($metric['tags']['status_code']) && $metric['tags']['status_code'] >= 400) {
                    $errorCount++;
                }
            }
            
            if ($metric['name'] === 'database.slow_query') {
                $slowQueryCount++;
            }
        }

        $summary['avg_response_time'] = $summary['total_requests'] > 0 ? $totalDuration / $summary['total_requests'] : 0;
        $summary['error_rate'] = $summary['total_requests'] > 0 ? ($errorCount / $summary['total_requests']) * 100 : 0;
        $summary['slow_queries'] = $slowQueryCount;

        return $summary;
    }
}
