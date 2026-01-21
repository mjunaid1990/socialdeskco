<?php

namespace App\Jobs;

use App\Models\Engagement;
use App\Models\Lead;
use App\Services\AutomationService;
use App\Services\WhatsAppAutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutomationRuleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;

    protected $engagement;
    protected $lead;
    protected $automationRule;

    /**
     * Create a new job instance.
     */
    public function __construct($automationRule, $target = null)
    {
        $this->automationRule = $automationRule;
        
        if ($target instanceof Engagement) {
            $this->engagement = $target;
        } elseif ($target instanceof Lead) {
            $this->lead = $target;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(AutomationService $automationService, WhatsAppAutomationService $whatsappService)
    {
        try {
            if ($this->engagement) {
                // Process the engagement with automation rules
                $automationService->processEngagement($this->engagement);
                
                // Update the engagement to mark it as processed
                $this->engagement->update([
                    'processed_at' => now(),
                ]);
                
                Log::info("Successfully processed automation for engagement {$this->engagement->id}");
            } elseif ($this->lead) {
                // Process the lead with automation rules
                $this->processLeadAutomation($whatsappService);
                
                Log::info("Successfully processed automation for lead {$this->lead->id}");
            }
            
        } catch (\Exception $e) {
            $targetId = $this->engagement?->id ?? $this->lead?->id;
            $targetType = $this->engagement ? 'engagement' : 'lead';
            
            Log::error("Error processing automation for {$targetType} {$targetId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->fail($e);
        }
    }

    /**
     * Process lead automation rules
     */
    private function processLeadAutomation(WhatsAppAutomationService $whatsappService)
    {
        $rule = $this->automationRule;
        $lead = $this->lead;

        // Process different action types
        switch ($rule->action_type) {
            case 'send_whatsapp':
                if ($lead->phone) {
                    $whatsappService->sendCustomMessage($lead, $rule->action_data['message'] ?? 'Thank you for your interest!');
                }
                break;

            case 'send_email':
                // Email automation would go here
                Log::info('Email automation triggered for lead', ['lead_id' => $lead->id]);
                break;

            case 'assign_tag':
                $customFields = $lead->custom_fields ?? [];
                $customFields['tags'] = array_merge($customFields['tags'] ?? [], [$rule->action_data['tag'] ?? 'automated']);
                $lead->custom_fields = $customFields;
                $lead->save();
                break;

            case 'change_status':
                $lead->status = $rule->action_data['status'] ?? 'contacted';
                $lead->save();
                break;

            case 'notify_admin':
                // Admin notification would go here
                Log::info('Admin notification triggered for lead', ['lead_id' => $lead->id]);
                break;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception = null)
    {
        $targetId = $this->engagement?->id ?? $this->lead?->id;
        $targetType = $this->engagement ? 'engagement' : 'lead';
        
        Log::error("ProcessAutomationRuleJob failed for {$targetType} {$targetId}", [
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString()
        ]);
    }
}