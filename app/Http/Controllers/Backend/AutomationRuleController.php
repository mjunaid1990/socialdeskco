<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutomationRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $automationRules = AutomationRule::with('socialAccount', 'user')->where('user_id', Auth::id())->paginate(10);
        return view('backend.automation-rules.index', compact('automationRules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $socialAccounts = SocialAccount::where('user_id', Auth::id())->get();
        return view('backend.automation-rules.create', compact('socialAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'social_account_id' => 'required|exists:social_accounts,id',
            'trigger_type' => 'required|string|max:255',
            'trigger_conditions' => 'required|array',
            'action_type' => 'required|string|max:255',
            'action_parameters' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $rule = new AutomationRule();
        $rule->user_id = Auth::id();
        $rule->social_account_id = $request->social_account_id;
        $rule->name = $request->name;
        $rule->trigger_type = $request->trigger_type;
        $rule->trigger_conditions = $request->trigger_conditions;
        $rule->action_type = $request->action_type;
        $rule->action_parameters = $request->action_parameters;
        $rule->is_active = $request->has('is_active');
        $rule->save();

        return redirect()->route('admin.automation-rules.index')->with('success', 'Automation rule created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AutomationRule $automationRule)
    {
        // Authorization check
        if ($automationRule->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return view('backend.automation-rules.show', compact('automationRule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AutomationRule $automationRule)
    {
        // Authorization check
        if ($automationRule->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $socialAccounts = SocialAccount::where('user_id', Auth::id())->get();
        return view('backend.automation-rules.edit', compact('automationRule', 'socialAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AutomationRule $automationRule)
    {
        // Authorization check
        if ($automationRule->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'social_account_id' => 'required|exists:social_accounts,id',
            'trigger_type' => 'required|string|max:255',
            'trigger_conditions' => 'required|array',
            'action_type' => 'required|string|max:255',
            'action_parameters' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $automationRule->social_account_id = $request->social_account_id;
        $automationRule->name = $request->name;
        $automationRule->trigger_type = $request->trigger_type;
        $automationRule->trigger_conditions = $request->trigger_conditions;
        $automationRule->action_type = $request->action_type;
        $automationRule->action_parameters = $request->action_parameters;
        $automationRule->is_active = $request->has('is_active');
        $automationRule->save();

        return redirect()->route('admin.automation-rules.index')->with('success', 'Automation rule updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AutomationRule $automationRule)
    {
        // Authorization check
        if ($automationRule->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $automationRule->delete();

        return redirect()->route('admin.automation-rules.index')->with('success', 'Automation rule deleted successfully.');
    }
}