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
     * Handle OAuth callback from provider
     */
    public function oauthCallback($platform, Request $request)
    {
        $platform = strtolower($platform);
        
        try {
            $tokenData = $this->exchangeCodeForToken($platform, $request->code);
            $profileData = $this->getUserProfile($platform, $tokenData['access_token']);

            // Create or update social account
            $socialAccount = SocialAccount::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'platform' => $platform,
                    'account_id' => $profileData['id'],
                ],
                [
                    'account_name' => $profileData['name'] ?? $profileData['username'] ?? 'Unknown',
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in']) 
                        ? now()->addSeconds($tokenData['expires_in']) 
                        : null,
                    'profile_data' => $profileData,
                    'is_active' => true,
                    'last_sync_at' => now(),
                ]
            );

            return redirect()
                ->route('admin.social-accounts.index')
                ->with('success', "Your {$platform} account has been connected successfully!");

        } catch (\Exception $e) {
            \Log::error("OAuth callback error for {$platform}: " . $e->getMessage());
            return redirect()
                ->route('admin.social-accounts.index')
                ->with('error', 'Failed to connect your account. Please try again.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'platform' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_id' => 'required|string|max:255',
            'access_token' => 'required|string',
        ]);

        $socialAccount = new SocialAccount();
        $socialAccount->user_id = Auth::id();
        $socialAccount->platform = $request->platform;
        $socialAccount->account_name = $request->account_name;
        $socialAccount->account_id = $request->account_id;
        $socialAccount->access_token = $request->access_token;
        $socialAccount->refresh_token = $request->refresh_token;
        $socialAccount->token_expires_at = $request->token_expires_at;
        $socialAccount->is_active = $request->has('is_active');
        $socialAccount->save();

        return redirect()->route('admin.social-accounts.index')->with('success', 'Social account created successfully.');
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

    /**
     * Check if platform credentials are configured
     */
    private function arePlatformCredentialsConfigured($platform)
    {
        $clientId = SettingsHelper::getSocialMediaSetting($platform, 'client_id');
        $clientSecret = SettingsHelper::getSocialMediaSetting($platform, 'client_secret');
        
        return !empty($clientId) && !empty($clientSecret);
    }

    /**
     * Get OAuth URL for platform
     */
    private function getOAuthUrl($platform)
    {
        $clientId = SettingsHelper::getSocialMediaSetting($platform, 'client_id');
        $redirectUri = route('admin.social-accounts.callback', $platform);
        $state = Str::random(40);
        
        // Store state in session for security
        session(['oauth_state_' . $platform => $state]);

        switch ($platform) {
            case 'facebook':
            case 'instagram':
                $scope = 'pages_manage_posts,pages_read_engagement,instagram_basic,instagram_content_publish';
                return "https://www.facebook.com/v18.0/dialog/oauth?" . http_build_query([
                    'client_id' => $clientId,
                    'redirect_uri' => $redirectUri,
                    'scope' => $scope,
                    'response_type' => 'code',
                    'state' => $state,
                ]);

            case 'linkedin':
                return "https://www.linkedin.com/oauth/v2/authorization?" . http_build_query([
                    'response_type' => 'code',
                    'client_id' => $clientId,
                    'redirect_uri' => $redirectUri,
                    'scope' => 'r_liteprofile r_emailaddress w_member_social',
                    'state' => $state,
                ]);

            case 'twitter':
                // Twitter OAuth 1.0a flow is more complex
                // This is a simplified version
                return "https://twitter.com/i/oauth2/authorize?" . http_build_query([
                    'response_type' => 'code',
                    'client_id' => $clientId,
                    'redirect_uri' => $redirectUri,
                    'scope' => 'tweet.read tweet.write users.read offline.access',
                    'state' => $state,
                    'code_challenge' => 'challenge_code',
                    'code_challenge_method' => 'plain',
                ]);

            default:
                return null;
        }
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken($platform, $code)
    {
        $clientId = SettingsHelper::getSocialMediaSetting($platform, 'client_id');
        $clientSecret = SettingsHelper::getSocialMediaSetting($platform, 'client_secret');
        $redirectUri = route('admin.social-accounts.callback', $platform);

        $client = new Client();

        switch ($platform) {
            case 'facebook':
            case 'instagram':
                $response = $client->post("https://graph.facebook.com/v18.0/oauth/access_token", [
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'redirect_uri' => $redirectUri,
                        'code' => $code,
                    ]
                ]);
                break;

            case 'linkedin':
                $response = $client->post("https://www.linkedin.com/oauth/v2/accessToken", [
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'redirect_uri' => $redirectUri,
                        'code' => $code,
                    ]
                ]);
                break;

            case 'twitter':
                $response = $client->post("https://api.twitter.com/2/oauth2/token", [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Authorization' => 'Basic ' . base64_encode("$clientId:$clientSecret"),
                    ],
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'code' => $code,
                        'redirect_uri' => $redirectUri,
                        'code_verifier' => 'challenge_code',
                    ]
                ]);
                break;

            default:
                throw new \Exception("Unsupported platform for token exchange");
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get user profile from platform
     */
    private function getUserProfile($platform, $accessToken)
    {
        $client = new Client();

        switch ($platform) {
            case 'facebook':
                $response = $client->get("https://graph.facebook.com/v18.0/me", [
                    'query' => [
                        'fields' => 'id,name,email,picture',
                        'access_token' => $accessToken,
                    ]
                ]);
                break;

            case 'instagram':
                $response = $client->get("https://graph.facebook.com/v18.0/me", [
                    'query' => [
                        'fields' => 'id,username,account_type',
                        'access_token' => $accessToken,
                    ]
                ]);
                break;

            case 'linkedin':
                $response = $client->get("https://api.linkedin.com/v2/people/~:(id,firstName,lastName,profilePicture(displayImage~:playableStreams))", [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                    ]
                ]);
                break;

            case 'twitter':
                $response = $client->get("https://api.twitter.com/2/users/me", [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                    ],
                    'query' => [
                        'user.fields' => 'id,name,username,profile_image_url',
                    ]
                ]);
                break;

            default:
                throw new \Exception("Unsupported platform for profile fetch");
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}