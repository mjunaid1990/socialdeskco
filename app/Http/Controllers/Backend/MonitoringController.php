<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Monitoring\PerformanceMonitorService;
use Illuminate\Support\Facades\Cache;

class MonitoringController extends Controller
{
    public function index()
    {
        $monitor = new PerformanceMonitorService();
        
        $data = [
            'system_metrics' => $monitor->getSystemMetrics(),
            'metrics_summary' => $monitor->getMetricsSummary(),
            'recent_metrics' => $this->getRecentMetrics(),
            'performance_trends' => $this->getPerformanceTrends(),
        ];

        return view('backend.monitoring.index', $data);
    }

    public function apiMetrics()
    {
        $monitor = new PerformanceMonitorService();
        
        return response()->json([
            'system_metrics' => $monitor->getSystemMetrics(),
            'metrics_summary' => $monitor->getMetricsSummary(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function getRecentMetrics(): array
    {
        $metrics = [];
        
        // Get metrics from the last 24 hours
        for ($i = 0; $i < 24; $i++) {
            $hour = now()->subHours($i);
            $key = 'performance_metrics:' . $hour->format('Y-m-d-H');
            $hourMetrics = Cache::get($key, []);
            
            $metrics[$hour->format('H:00')] = [
                'requests' => 0,
                'avg_response_time' => 0,
                'errors' => 0,
            ];

            foreach ($hourMetrics as $metric) {
                if ($metric['name'] === 'api.request_duration') {
                    $metrics[$hour->format('H:00')]['requests']++;
                    
                    if (isset($metric['tags']['status_code']) && $metric['tags']['status_code'] >= 400) {
                        $metrics[$hour->format('H:00')]['errors']++;
                    }
                }
            }
        }

        return array_reverse($metrics);
    }

    private function getPerformanceTrends(): array
    {
        $trends = [
            'response_times' => [],
            'error_rates' => [],
            'request_volumes' => [],
        ];

        // Get data for the last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayMetrics = $this->getDayMetrics($date);
            
            $trends['response_times'][] = $dayMetrics['avg_response_time'];
            $trends['error_rates'][] = $dayMetrics['error_rate'];
            $trends['request_volumes'][] = $dayMetrics['total_requests'];
        }

        return $trends;
    }

    private function getDayMetrics(string $date): array
    {
        $totalRequests = 0;
        $totalDuration = 0;
        $errorCount = 0;

        // Get all hourly metrics for the day
        for ($hour = 0; $hour < 24; $hour++) {
            $key = 'performance_metrics:' . $date . '-' . str_pad($hour, 2, '0', STR_PAD_LEFT);
            $hourMetrics = Cache::get($key, []);

            foreach ($hourMetrics as $metric) {
                if ($metric['name'] === 'api.request_duration') {
                    $totalRequests++;
                    $totalDuration += $metric['value'];
                    
                    if (isset($metric['tags']['status_code']) && $metric['tags']['status_code'] >= 400) {
                        $errorCount++;
                    }
                }
            }
        }

        return [
            'total_requests' => $totalRequests,
            'avg_response_time' => $totalRequests > 0 ? $totalDuration / $totalRequests : 0,
            'error_rate' => $totalRequests > 0 ? ($errorCount / $totalRequests) * 100 : 0,
        ];
    }
}
