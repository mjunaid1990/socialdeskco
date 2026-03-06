<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateQueueConfiguration extends Command
{
    protected $signature = 'queue:validate';
    protected $description = 'Validate queue configuration and database setup';

    public function handle()
    {
        $this->info('Validating queue configuration...');
        
        $queueConnection = config('queue.default');
        $this->line("Queue connection: <info>{$queueConnection}</info>");
        
        if ($queueConnection === 'database') {
            $this->validateDatabaseQueue();
        } elseif ($queueConnection === 'redis') {
            $this->validateRedisQueue();
        } else {
            $this->warn("Unsupported queue connection: {$queueConnection}");
        }
        
        $this->info('Queue validation complete!');
    }

    private function validateDatabaseQueue()
    {
        $this->line('');
        $this->info('Checking database queue configuration...');
        
        try {
            // Check if jobs table exists
            $jobsTable = config('queue.connections.database.table', 'jobs');
            
            $tableExists = DB::connection()->getSchemaBuilder()->hasTable($jobsTable);
            
            if ($tableExists) {
                $this->line("<fg=green>✓</> Jobs table '<info>{$jobsTable}</info>' exists");
                
                // Count pending jobs
                $pendingJobs = DB::table($jobsTable)->whereNull('reserved_at')->count();
                $this->line("Pending jobs: <comment>{$pendingJobs}</comment>");
                
                // Count reserved (processing) jobs
                $reservedJobs = DB::table($jobsTable)->whereNotNull('reserved_at')->count();
                $this->line("Reserved jobs: <comment>{$reservedJobs}</comment>");
                
            } else {
                $this->error("✘ Jobs table '{$jobsTable}' does not exist!");
                $this->line('Run: <info>php artisan queue:table && php artisan migrate</info>');
            }
            
            // Check failed_jobs table
            $failedTable = 'failed_jobs';
            $failedTableExists = DB::connection()->getSchemaBuilder()->hasTable($failedTable);
            
            if ($failedTableExists) {
                $this->line("<fg=green>✓</> Failed jobs table exists");
                
                $failedCount = DB::table($failedTable)->count();
                if ($failedCount > 0) {
                    $this->warn("Warning: {$failedCount} failed jobs detected!");
                    $this->line('Run: <info>php artisan queue:retry all</info> to retry failed jobs');
                } else {
                    $this->line("Failed jobs: <info>0</info>");
                }
            } else {
                $this->warn("✘ Failed jobs table does not exist");
                $this->line('Run: <info>php artisan queue:failed-table && php artisan migrate</info>');
            }
        } catch (\Exception $e) {
            $this->error("Error validating database queue: " . $e->getMessage());
        }
    }

    private function validateRedisQueue()
    {
        $this->line('');
        $this->info('Checking Redis queue configuration...');
        
        try {
            $redis = app('redis');
            $redis->ping();
            $this->line('<fg=green>✓</> Redis connection successful');
        } catch (\Exception $e) {
            $this->error('✘ Redis connection failed: ' . $e->getMessage());
            $this->line('Ensure Redis is installed and running');
        }
    }
}
