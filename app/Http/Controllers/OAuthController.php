<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Helpers\SettingsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

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
                $scope = 'public_profile,pages_show_list,pages_manage_metadata,pages_read_engagement,pages_manage_posts';
                return "https://www.facebook.com/v21.0/dialog/oauth?" . http_build_query([
                    'client_id' => $clientId,
                    'redirect_uri' => $redirectUri,
                    'scope' => $scope,
                    'response_type' => 'code',
                    'state' => $state,
                ]);

            case 'instagram':
                $scope = 'instagram_business_basic,instagram_business_content_publish';
                $oauthUrl = 'https://api.instagram.com/oauth/authorize?' . http_build_query([
                    'client_id' => $clientId,
                    'redirect_uri' => $redirectUri,
                    'scope' => $scope,
                    'response_type' => 'code',
                    'state' => $state,
                ]);
                
                // Debug Instagram OAuth URL
                Log::info('=== Instagram OAuth URL Debug ===');
                Log::info('Client ID: ' . $clientId);
                Log::info('Redirect URI: ' . $redirectUri);
                Log::info('Scope: ' . $scope);
                Log::info('State: ' . $state);
                Log::info('Full OAuth URL: ' . $oauthUrl);
                Log::info('=== Instagram OAuth URL Debug End ===');
                
                return $oauthUrl;

            case 'linkedin':
                return "https://www.linkedin.com/oauth/v2/authorization?" . http_build_query([
                    'response_type' => 'code',
                    'client_id' => $clientId,
                    'redirect_uri' => $redirectUri,
                    'scope' => 'openid profile email w_member_social',
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

            case 'youtube':
                // YouTube uses Google OAuth 2.0
                return "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
                    'client_id' => $clientId,
                    'redirect_uri' => $redirectUri,
                    'scope' => 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube.readonly',
                    'response_type' => 'code',
                    'state' => $state,
                    'access_type' => 'offline',
                    'prompt' => 'consent',
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
        
        // Debug callback request
        Log::info("=== OAuth Callback Debug for {$platform} ===");
        Log::info('Request Data: ', $request->all());
        Log::info('Has Code: ' . ($request->has('code') ? 'YES' : 'NO'));
        Log::info('Has Error: ' . ($request->has('error') ? 'YES' : 'NO'));
        Log::info('Has State: ' . ($request->has('state') ? 'YES' : 'NO'));
        
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
                // Return error callback view
                return view('oauth-callback')->with('error', "OAuth error: {$errorMessage}");
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

            // Return OAuth callback view that closes popup
            return view('oauth-callback');

        } catch (\Exception $e) {
            \Log::error("OAuth callback error for {$platform}: " . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            
            // Return error callback view
            return view('oauth-callback')->with('error', 'Failed to connect your account. Please try again.');
        }
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken($platform, $code)
    {
        $clientId = SettingsHelper::getSocialMediaSetting($platform, 'client_id');
        $clientSecret = SettingsHelper::getSocialMediaSetting($platform, 'client_secret');
        $redirectUri = rtrim(route('oauth.callback', $platform), '/');

        $client = new Client();

        switch ($platform) {
            case 'facebook':
                $response = $client->post("https://graph.facebook.com/v21.0/oauth/access_token", [
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'redirect_uri' => $redirectUri,
                        'code' => $code,
                    ]
                ]);
                break;

            case 'instagram':
                $tokenUrl = "https://api.instagram.com/oauth/access_token";
                $params = [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ];
                
                // Comprehensive debugging
                Log::info('=== Instagram OAuth Debug Start ===');
                Log::info('Token Exchange URL: ' . $tokenUrl);
                Log::info('Client ID: ' . $clientId);
                Log::info('Client Secret Length: ' . strlen($clientSecret));
                Log::info('Redirect URI: ' . $redirectUri);
                Log::info('Authorization Code: ' . $code);
                Log::info('Authorization Code Length: ' . strlen($code));
                Log::info('Grant Type: ' . $params['grant_type']);
                Log::info('Full Parameters: ', $params);
                
                try {
                    $response = $client->post($tokenUrl, [
                        'form_params' => $params
                    ]);
                    
                    $responseBody = $response->getBody()->getContents();
                    Log::info('Instagram Token Response: ' . $responseBody);
                    Log::info('=== Instagram OAuth Debug End ===');
                    
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    $responseBody = $e->getResponse()->getBody()->getContents();
                    Log::error('Instagram OAuth Error Response: ' . $responseBody);
                    Log::error('HTTP Status: ' . $e->getResponse()->getStatusCode());
                    Log::error('=== Instagram OAuth Debug End ===');
                    throw $e;
                }
                break;

            case 'linkedin':
                $response = $client->post("https://www.linkedin.com/oauth/v2/accessToken", [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'code' => $code,
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'redirect_uri' => $redirectUri,
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

            case 'youtube':
                // YouTube uses Google OAuth 2.0 token endpoint
                $response = $client->post("https://oauth2.googleapis.com/token", [
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'redirect_uri' => $redirectUri,
                        'grant_type' => 'authorization_code',
                        'code' => $code,
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
                $response = $client->get("https://graph.facebook.com/v21.0/me", [
                    'query' => [
                        'fields' => 'id,name,email,picture,accounts',
                        'access_token' => $accessToken,
                    ]
                ]);
                break;

            case 'instagram':
                // Use Instagram Graph API directly for Business Login
                $response = $client->get("https://graph.instagram.com/me", [
                    'query' => [
                        'fields' => 'id,username,name,profile_picture_url,followers_count,media_count,account_type',
                        'access_token' => $accessToken,
                    ]
                ]);
                break;

            case 'linkedin':

                // Get basic profile information using LinkedIn v2 API
                $response = $client->get("https://api.linkedin.com/v2/userinfo", [
                    'headers' => [
                        'Authorization' => "Bearer $accessToken",
                    ],
                ]);
                
                $profileData = json_decode($response->getBody()->getContents(), true);

                // Format response based on v2 API structure
                return [
                    'id' => $profileData['sub'] ?? '',
                    'name' => trim(($profileData['given_name'] ?? '') . ' ' . ($profileData['family_name'] ?? '')),
                    'firstName' => $profileData['given_name'] ?? '',
                    'lastName' => $profileData['family_name'] ?? '',
                    'email' => $profileData['email'] ?? null,
                    'profilePicture' => $profileData['picture'] ?? null,
                    'raw' => $profileData
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

            case 'youtube':
                // Get YouTube channel information using Google API
                $response = $client->get("https://www.googleapis.com/youtube/v3/channels", [
                    'query' => [
                        'part' => 'snippet,statistics',
                        'mine' => 'true',
                        'access_token' => $accessToken,
                    ]
                ]);
                
                $channelData = json_decode($response->getBody()->getContents(), true);
                
                if (isset($channelData['items'][0])) {
                    $channel = $channelData['items'][0];
                    return [
                        'id' => $channel['id'],
                        'name' => $channel['snippet']['title'],
                        'username' => $channel['snippet']['customUrl'] ?? $channel['id'],
                        'email' => null, // YouTube API doesn't provide email in channels endpoint
                        'profilePicture' => $channel['snippet']['thumbnails']['default']['url'] ?? null,
                        'subscriberCount' => $channel['statistics']['subscriberCount'] ?? 0,
                        'videoCount' => $channel['statistics']['videoCount'] ?? 0,
                        'raw' => $channelData
                    ];
                } else {
                    throw new \Exception('Unable to fetch YouTube channel information');
                }
                break;

            default:
                throw new \Exception("Unsupported platform for profile fetch");
        }

        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }
}
