<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Engagement;
use App\Models\SocialAccount;
use App\Jobs\ProcessAutomationRuleJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EngagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $engagements = Engagement::with('socialAccount', 'user')->where('user_id', Auth::id())->paginate(10);
        return view('backend.engagements.index', compact('engagements'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $socialAccounts = SocialAccount::where('user_id', Auth::id())->get();
        return view('backend.engagements.create', compact('socialAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'social_account_id' => 'required|exists:social_accounts,id',
            'post_id' => 'nullable|exists:posts,id',
            'engagement_type' => 'required|string|max:255',
            'platform' => 'required|string|max:255',
            'platform_engagement_id' => 'required|string|max:255',
            'actor_username' => 'required|string|max:255',
            'actor_full_name' => 'nullable|string|max:255',
            'actor_platform_id' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_automated_response' => 'boolean',
        ]);

        $engagement = new Engagement();
        $engagement->user_id = Auth::id();
        $engagement->social_account_id = $request->social_account_id;
        $engagement->post_id = $request->post_id;
        $engagement->engagement_type = $request->engagement_type;
        $engagement->platform = $request->platform;
        $engagement->platform_engagement_id = $request->platform_engagement_id;
        $engagement->actor_username = $request->actor_username;
        $engagement->actor_full_name = $request->actor_full_name;
        $engagement->actor_platform_id = $request->actor_platform_id;
        $engagement->content = $request->content;
        $engagement->is_automated_response = $request->has('is_automated_response');
        $engagement->save();

        // Dispatch job to process automation rules for this engagement
        ProcessAutomationRuleJob::dispatch($engagement);

        return redirect()->route('admin.engagements.index')->with('success', 'Engagement created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Engagement $engagement)
    {
        // Authorization check
        if ($engagement->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return view('backend.engagements.show', compact('engagement'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Engagement $engagement)
    {
        // Authorization check
        if ($engagement->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $socialAccounts = SocialAccount::where('user_id', Auth::id())->get();
        return view('backend.engagements.edit', compact('engagement', 'socialAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Engagement $engagement)
    {
        // Authorization check
        if ($engagement->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'social_account_id' => 'required|exists:social_accounts,id',
            'post_id' => 'nullable|exists:posts,id',
            'engagement_type' => 'required|string|max:255',
            'platform' => 'required|string|max:255',
            'platform_engagement_id' => 'required|string|max:255',
            'actor_username' => 'required|string|max:255',
            'actor_full_name' => 'nullable|string|max:255',
            'actor_platform_id' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_automated_response' => 'boolean',
        ]);

        $engagement->social_account_id = $request->social_account_id;
        $engagement->post_id = $request->post_id;
        $engagement->engagement_type = $request->engagement_type;
        $engagement->platform = $request->platform;
        $engagement->platform_engagement_id = $request->platform_engagement_id;
        $engagement->actor_username = $request->actor_username;
        $engagement->actor_full_name = $request->actor_full_name;
        $engagement->actor_platform_id = $request->actor_platform_id;
        $engagement->content = $request->content;
        $engagement->is_automated_response = $request->has('is_automated_response');
        $engagement->save();

        return redirect()->route('admin.engagements.index')->with('success', 'Engagement updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Engagement $engagement)
    {
        // Authorization check
        if ($engagement->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $engagement->delete();

        return redirect()->route('admin.engagements.index')->with('success', 'Engagement deleted successfully.');
    }
}