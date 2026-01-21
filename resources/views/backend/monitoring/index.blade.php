@extends('backend.layouts.app')

@section('title', 'Performance Monitoring')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Performance Monitoring</h1>
            <p class="mt-2 text-gray-600">Monitor system performance, API metrics, and application health</p>
        </div>

        <!-- System Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-9h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ round($system_metrics['memory_usage'] / 1024 / 1024, 2) }} MB</h3>
                        <p class="text-gray-600 text-sm">Memory Usage</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $metrics_summary['total_requests'] }}</h3>
                        <p class="text-gray-600 text-sm">Total Requests</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ round($metrics_summary['avg_response_time'] * 1000, 2) }} ms</h3>
                        <p class="text-gray-600 text-sm">Avg Response Time</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ round($metrics_summary['error_rate'], 2) }}%</h3>
                        <p class="text-gray-600 text-sm">Error Rate</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- System Resources -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">System Resources</h2>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Memory Usage</span>
                            <span class="text-gray-900">{{ round($system_metrics['memory_usage'] / 1024 / 1024, 2) }} MB / {{ round($system_metrics['memory_peak'] / 1024 / 1024, 2) }} MB</span>
                        </div>
                        <div class="mt-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(($system_metrics['memory_usage'] / $system_metrics['memory_peak']) * 100, 100) }}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Disk Usage</span>
                            <span class="text-gray-900">{{ $system_metrics['disk_usage']['percentage_used'] }}%</span>
                        </div>
                        <div class="mt-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $system_metrics['disk_usage']['percentage_used'] }}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">CPU Load</span>
                            <span class="text-gray-900">{{ round($system_metrics['cpu_usage'], 2) }}</span>
                        </div>
                        <div class="mt-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ min($system_metrics['cpu_usage'] * 10, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Summary -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Performance Summary</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Slow Queries</span>
                        <span class="text-gray-900 font-semibold">{{ $metrics_summary['slow_queries'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Request Duration</span>
                        <span class="text-gray-900 font-semibold">{{ round($system_metrics['request_duration'] * 1000, 2) }} ms</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Peak Memory</span>
                        <span class="text-gray-900 font-semibold">{{ round($system_metrics['memory_peak'] / 1024 / 1024, 2) }} MB</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Free Disk Space</span>
                        <span class="text-gray-900 font-semibold">{{ round($system_metrics['disk_usage']['free'] / 1024 / 1024 / 1024, 2) }} GB</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Activity (Last 24 Hours)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hour</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requests</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Errors</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Response Time</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recent_metrics as $hour => $metrics)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $hour }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $metrics['requests'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $metrics['errors'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $metrics['avg_response_time'] > 0 ? round($metrics['avg_response_time'] * 1000, 2) . ' ms' : 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Auto-refresh indicator -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>Data auto-refreshes every 30 seconds</p>
            <button onclick="location.reload()" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                Refresh Now
            </button>
        </div>
    </div>
</div>

<script>
    // Auto-refresh every 30 seconds
    setTimeout(() => {
        location.reload();
    }, 30000);
</script>
@endsection
