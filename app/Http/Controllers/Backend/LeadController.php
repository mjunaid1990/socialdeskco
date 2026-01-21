<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leads = Lead::with('socialAccount', 'user')->where('user_id', Auth::id())->paginate(10);
        return view('backend.leads.index', compact('leads'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $socialAccounts = SocialAccount::where('user_id', Auth::id())->get();
        return view('backend.leads.create', compact('socialAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'social_account_id' => 'required|exists:social_accounts,id',
            'platform' => 'required|string|max:255',
            'platform_lead_id' => 'required|string|max:255',
            'source_type' => 'required|string|max:255',
            'message' => 'nullable|string',
            'status' => 'required|in:new,contacted,converted,lost',
            'custom_fields' => 'nullable|array',
        ]);

        $lead = new Lead();
        $lead->user_id = Auth::id();
        $lead->social_account_id = $request->social_account_id;
        $lead->first_name = $request->first_name;
        $lead->last_name = $request->last_name;
        $lead->email = $request->email;
        $lead->phone = $request->phone;
        $lead->platform = $request->platform;
        $lead->platform_lead_id = $request->platform_lead_id;
        $lead->source_type = $request->source_type;
        $lead->message = $request->message;
        $lead->status = $request->status;
        $lead->custom_fields = $request->custom_fields;
        $lead->save();

        return redirect()->route('admin.leads.index')->with('success', 'Lead created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lead $lead)
    {
        // Authorization check
        if ($lead->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return view('backend.leads.show', compact('lead'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lead $lead)
    {
        // Authorization check
        if ($lead->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $socialAccounts = SocialAccount::where('user_id', Auth::id())->get();
        return view('backend.leads.edit', compact('lead', 'socialAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lead $lead)
    {
        // Authorization check
        if ($lead->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'social_account_id' => 'required|exists:social_accounts,id',
            'platform' => 'required|string|max:255',
            'platform_lead_id' => 'required|string|max:255',
            'source_type' => 'required|string|max:255',
            'message' => 'nullable|string',
            'status' => 'required|in:new,contacted,converted,lost',
            'custom_fields' => 'nullable|array',
        ]);

        $lead->social_account_id = $request->social_account_id;
        $lead->first_name = $request->first_name;
        $lead->last_name = $request->last_name;
        $lead->email = $request->email;
        $lead->phone = $request->phone;
        $lead->platform = $request->platform;
        $lead->platform_lead_id = $request->platform_lead_id;
        $lead->source_type = $request->source_type;
        $lead->message = $request->message;
        $lead->status = $request->status;
        $lead->custom_fields = $request->custom_fields;
        $lead->save();

        return redirect()->route('admin.leads.index')->with('success', 'Lead updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lead $lead)
    {
        // Authorization check
        if ($lead->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $lead->delete();

        return redirect()->route('admin.leads.index')->with('success', 'Lead deleted successfully.');
    }
}