<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\LeadCaptureService;
use App\Services\WhatsAppAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LeadAutomationController extends Controller
{
    protected $leadCaptureService;
    protected $whatsappService;

    public function __construct(LeadCaptureService $leadCaptureService, WhatsAppAutomationService $whatsappService)
    {
        $this->leadCaptureService = $leadCaptureService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Show lead automation dashboard
     */
    public function index()
    {
        $leads = Lead::with('socialAccount')
            ->where('user_id', Auth::id())
            ->orderBy('score', 'desc')
            ->paginate(20);

        $stats = [
            'total' => Lead::where('user_id', Auth::id())->count(),
            'new' => Lead::where('user_id', Auth::id())->where('status', 'new')->count(),
            'contacted' => Lead::where('user_id', Auth::id())->where('status', 'contacted')->count(),
            'converted' => Lead::where('user_id', Auth::id())->where('status', 'converted')->count(),
            'hot' => Lead::where('user_id', Auth::id())->where('qualification', 'hot')->count(),
            'warm' => Lead::where('user_id', Auth::id())->where('qualification', 'warm')->count(),
            'cold' => Lead::where('user_id', Auth::id())->where('qualification', 'cold')->count(),
        ];

        return view('backend.lead-automation.index', compact('leads', 'stats'));
    }

    /**
     * Show lead details with automation options
     */
    public function show(Lead $lead)
    {
        // Authorization check
        if ($lead->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        // Calculate lead score and qualification
        $score = $this->leadCaptureService->calculateLeadScore($lead);
        $qualification = $this->leadCaptureService->qualifyLead($lead);

        // Update lead score if different
        if ($lead->score !== $score) {
            $lead->score = $score;
            $lead->qualification = $qualification;
            $lead->save();
        }

        return view('backend.lead-automation.show', compact('lead', 'score', 'qualification'));
    }

    /**
     * Send custom WhatsApp message
     */
    public function sendWhatsAppMessage(Request $request, Lead $lead)
    {
        // Authorization check
        if ($lead->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        try {
            $success = $this->whatsappService->sendCustomMessage($lead, $request->message);

            if ($success) {
                // Update follow-up tracking
                $lead->last_followup_at = now();
                $lead->followup_count = $lead->followup_count + 1;
                
                // Update automation history
                $automationHistory = $lead->automation_history ?? [];
                $automationHistory[] = [
                    'type' => 'custom_whatsapp',
                    'message' => $request->message,
                    'sent_at' => now()->toISOString(),
                    'sent_by' => Auth::user()->name
                ];
                $lead->automation_history = $automationHistory;
                
                $lead->save();

                return redirect()->back()->with('success', 'WhatsApp message sent successfully!');
            } else {
                return redirect()->back()->with('error', 'Failed to send WhatsApp message. Please check the lead\'s phone number and your WhatsApp configuration.');
            }

        } catch (\Exception $e) {
            Log::error('Error sending custom WhatsApp message', [
                'error' => $e->getMessage(),
                'lead_id' => $lead->id
            ]);

            return redirect()->back()->with('error', 'An error occurred while sending the message.');
        }
    }

    /**
     * Send broadcast message
     */
    public function sendBroadcast(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'filters' => 'nullable|array'
        ]);

        try {
            $result = $this->whatsappService->sendBroadcast(
                Auth::id(),
                $request->message,
                $request->filters ?? []
            );

            if ($result['success']) {
                return redirect()->back()->with('success', 
                    "Broadcast sent successfully! {$result['sent']} messages sent, {$result['failed']} failed."
                );
            } else {
                return redirect()->back()->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp broadcast', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()->with('error', 'An error occurred while sending the broadcast.');
        }
    }

    /**
     * Update lead qualification manually
     */
    public function updateQualification(Request $request, Lead $lead)
    {
        // Authorization check
        if ($lead->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'qualification' => 'required|in:hot,warm,cold'
        ]);

        $lead->qualification = $request->qualification;
        $lead->save();

        return redirect()->back()->with('success', 'Lead qualification updated successfully!');
    }

    /**
     * Trigger manual lead scoring
     */
    public function rescoreLead(Lead $lead)
    {
        // Authorization check
        if ($lead->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $score = $this->leadCaptureService->calculateLeadScore($lead);
        $qualification = $this->leadCaptureService->qualifyLead($lead);

        $lead->score = $score;
        $lead->qualification = $qualification;
        $lead->save();

        return redirect()->back()->with('success', 
            "Lead rescored! Score: {$score}/100, Qualification: {$qualification}"
        );
    }

    /**
     * Show broadcast form
     */
    public function broadcastForm()
    {
        $leadCounts = [
            'total' => Lead::where('user_id', Auth::id())->whereNotNull('phone')->count(),
            'new' => Lead::where('user_id', Auth::id())->where('status', 'new')->whereNotNull('phone')->count(),
            'contacted' => Lead::where('user_id', Auth::id())->where('status', 'contacted')->whereNotNull('phone')->count(),
            'hot' => Lead::where('user_id', Auth::id())->where('qualification', 'hot')->whereNotNull('phone')->count(),
            'warm' => Lead::where('user_id', Auth::id())->where('qualification', 'warm')->whereNotNull('phone')->count(),
        ];

        return view('backend.lead-automation.broadcast', compact('leadCounts'));
    }

    /**
     * Get lead statistics for dashboard
     */
    public function getStats()
    {
        $userId = Auth::id();

        $stats = [
            'total_leads' => Lead::where('user_id', $userId)->count(),
            'new_leads_today' => Lead::where('user_id', $userId)
                ->where('created_at', '>=', today())
                ->count(),
            'contacted_leads' => Lead::where('user_id', $userId)->where('status', 'contacted')->count(),
            'converted_leads' => Lead::where('user_id', $userId)->where('status', 'converted')->count(),
            'hot_leads' => Lead::where('user_id', $userId)->where('qualification', 'hot')->count(),
            'avg_score' => Lead::where('user_id', $userId)->avg('score') ?? 0,
            'conversion_rate' => $this->calculateConversionRate($userId),
        ];

        return response()->json($stats);
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate($userId)
    {
        $totalLeads = Lead::where('user_id', $userId)->count();
        $convertedLeads = Lead::where('user_id', $userId)->where('status', 'converted')->count();

        if ($totalLeads === 0) {
            return 0;
        }

        return round(($convertedLeads / $totalLeads) * 100, 2);
    }
}
