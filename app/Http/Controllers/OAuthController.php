<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Helpers\SettingsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class OAuthController extends Controller
{
    /**
     * Redirect to OAuth provider for authentication
     */
    public function redirect($platform)
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
        $redirectUri = route('oauth.callback', $platform);
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
     * Handle OAuth callback from provider
     */
    public function callback($platform, Request $request)
    {
        $platform = strtolower($platform);
        
        try {
            // Validate state parameter for security
            $storedState = session('oauth_state_' . $platform);
            if (!$storedState || $storedState !== $request->state) {
                return redirect()
                    ->route('admin.social-accounts.index')
                    ->with('error', 'Invalid OAuth state. Security validation failed.');
            }
            
            // Clear the state from session
            session()->forget('oauth_state_' . $platform);
            
            // Check for OAuth errors
            if ($request->has('error')) {
                $errorMessage = $request->error_description ?? $request->error;
                return redirect()
                    ->route('admin.social-accounts.index')
                    ->with('error', "OAuth error: {$errorMessage}");
            }
            
            // Exchange authorization code for access token
            $tokenData = $this->exchangeCodeForToken($platform, $request->code);
            
            if (!isset($tokenData['access_token'])) {
                throw new \Exception('Failed to obtain access token');
            }
            
            // Get user profile
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
            \Log::error("OAuth callback error for {$platform}: " . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            
            return redirect()
                ->route('admin.social-accounts.index')
                ->with('error', 'Failed to connect your account. Please try again.');
        }
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken($platform, $code)
    {
        $clientId = SettingsHelper::getSocialMediaSetting($platform, 'client_id');
        $clientSecret = SettingsHelper::getSocialMediaSetting($platform, 'client_secret');
        $redirectUri = route('oauth.callback', $platform);

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
                // Get basic profile information
                $profileResponse = $client->get("https://api.linkedin.com/v2/people/~:(id,firstName,lastName,profilePicture(displayImage~:playableStreams))", [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                    ]
                ]);
                
                // Get email address
                $emailResponse = $client->get("https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))", [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                    ]
                ]);
                
                $profileData = json_decode($profileResponse->getBody()->getContents(), true);
                $emailData = json_decode($emailResponse->getBody()->getContents(), true);
                
                // Format the response
                $firstName = $profileData['firstName']['localized']['en_US'] ?? $profileData['firstName']['localized'][array_key_first($profileData['firstName']['localized'])] ?? '';
                $lastName = $profileData['lastName']['localized']['en_US'] ?? $profileData['lastName']['localized'][array_key_first($profileData['lastName']['localized'])] ?? '';
                
                return [
                    'id' => $profileData['id'],
                    'name' => trim($firstName . ' ' . $lastName),
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $emailData['elements'][0]['handle~']['emailAddress'] ?? null,
                    'profilePicture' => $profileData['profilePicture']['displayImage~']['elements'][0]['identifiers'][0]['identifier'] ?? null,
                    'raw' => array_merge($profileData, ['emailData' => $emailData])
                ];

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
