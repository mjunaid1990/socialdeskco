<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Monitoring\PerformanceMonitorService;

class PerformanceMonitoring
{
    public function handle(Request $request, Closure $next)
    {
        $monitor = new PerformanceMonitorService();
        $monitor->startTimer('request_processing');

        $response = $next($request);

        $duration = $monitor->endTimer('request_processing');
        
        // Record API call metrics
        $monitor->recordApiCall(
            $request->path(),
            $request->method(),
            $response->getStatusCode(),
            $duration
        );

        // Log slow requests
        if ($duration > 2.0) {
            Log::warning('Slow Request Detected', [
                'path' => $request->path(),
                'method' => $request->method(),
                'duration' => $duration,
                'status_code' => $response->getStatusCode(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);
        }

        // Add performance headers
        $response->headers->set('X-Response-Time', round($duration * 1000, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB');

        return $response;
    }
}
