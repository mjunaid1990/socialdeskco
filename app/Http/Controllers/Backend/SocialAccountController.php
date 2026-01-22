<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Helpers\SettingsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class SocialAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $socialAccounts = SocialAccount::with('user')->where('user_id', Auth::id())->paginate(10);
        return view('backend.social-accounts.index', compact('socialAccounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.social-accounts.create');
    }

    /**
     * Redirect to OAuth provider for authentication
     */
    public function oauthRedirect($platform)
    {
        $platform = strtolower($platform);
        
        // Check if platform is supported
        if (!array_key_exists($platform, \App\Helpers\PlatformHelper::getPlatforms())) {
            return redirect()->back()->with('error', 'Unsupported platform.');
        }

        // Check if platform credentials are configured
        if (!$this->arePlatformCredentialsConfigured($platform)) {
            return redirect()->back()->with('error', "Platform credentials for {$platform} are not configured. Please contact administrator.");
        }

        $oauthUrl = $this->getOAuthUrl($platform);
        
        if ($oauthUrl) {
            return redirect($oauthUrl);
        }

        return redirect()->back()->with('error', 'OAuth integration not available for this platform yet.');
    }


    /**
     * Display the specified resource.
     */
    public function show(SocialAccount $socialAccount)
    {
        // Authorization check
        if ($socialAccount->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return view('backend.social-accounts.show', compact('socialAccount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SocialAccount $socialAccount)
    {
        // Authorization check
        if ($socialAccount->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return view('backend.social-accounts.edit', compact('socialAccount'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SocialAccount $socialAccount)
    {
        // Authorization check
        if ($socialAccount->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'platform' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_id' => 'required|string|max:255',
            'access_token' => 'required|string',
        ]);

        $socialAccount->platform = $request->platform;
        $socialAccount->account_name = $request->account_name;
        $socialAccount->account_id = $request->account_id;
        $socialAccount->access_token = $request->access_token;
        $socialAccount->refresh_token = $request->refresh_token;
        $socialAccount->token_expires_at = $request->token_expires_at;
        $socialAccount->is_active = $request->has('is_active');
        $socialAccount->save();

        return redirect()->route('admin.social-accounts.index')->with('success', 'Social account updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SocialAccount $socialAccount)
    {
        // Authorization check
        if ($socialAccount->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $socialAccount->delete();

        return redirect()->route('admin.social-accounts.index')->with('success', 'Social account deleted successfully.');
    }

}