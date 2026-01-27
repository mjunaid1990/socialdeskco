<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class SettingsHelper
{
    // Define all available settings with their configuration
    public const GENERAL_SETTINGS = [
        'app_name' => [
            'type' => 'text',
            'label' => 'App Name',
            'placeholder' => 'Enter application name',
            'description' => 'The name of your application',
            'group' => 'general',
        ],
        'app_description' => [
            'type' => 'textarea',
            'label' => 'App Description',
            'placeholder' => 'Enter application description',
            'description' => 'Short description of your application',
            'group' => 'general',
        ],
        'timezone' => [
            'type' => 'select',
            'label' => 'Timezone',
            'options' => [
                'UTC' => 'UTC',
                'America/New_York' => 'Eastern Time',
                'America/Chicago' => 'Central Time',
                'America/Denver' => 'Mountain Time',
                'America/Los_Angeles' => 'Pacific Time',
                'Europe/London' => 'GMT/BST',
                'Europe/Paris' => 'Central European Time',
                'Asia/Tokyo' => 'Japan Standard Time',
                'Asia/Shanghai' => 'China Standard Time',
            ],
            'description' => 'Default timezone for the application',
            'group' => 'general',
        ],
        'default_language' => [
            'type' => 'select',
            'label' => 'Default Language',
            'options' => [
                'en' => 'English',
                'es' => 'Spanish',
                'fr' => 'French',
                'de' => 'German',
                'it' => 'Italian',
                'pt' => 'Portuguese',
            ],
            'description' => 'Default language for application',
            'group' => 'general',
        ],
        'app_logo' => [
            'type' => 'file',
            'label' => 'App Logo',
            'description' => 'Upload your application logo (recommended size: 200x50px, max 2MB)',
            'group' => 'general',
        ],
        'app_favicon' => [
            'type' => 'file',
            'label' => 'Favicon',
            'description' => 'Upload your favicon (recommended: 32x32px .ico or .png, max 1MB)',
            'group' => 'general',
        ],
        'seo_title' => [
            'type' => 'text',
            'label' => 'Default SEO Title',
            'placeholder' => 'My Awesome Application',
            'description' => 'Default title for SEO meta tags (max 60 characters)',
            'group' => 'general',
        ],
        'seo_description' => [
            'type' => 'textarea',
            'label' => 'Default SEO Description',
            'placeholder' => 'A brief description of your application',
            'description' => 'Default description for SEO meta tags (max 160 characters)',
            'group' => 'general',
        ],
        'seo_keywords' => [
            'type' => 'text',
            'label' => 'Default SEO Keywords',
            'placeholder' => 'app, automation, social media, marketing',
            'description' => 'Comma-separated keywords for SEO meta tags',
            'group' => 'general',
        ],
        'notification_email' => [
            'type' => 'email',
            'label' => 'Notification Email',
            'placeholder' => 'notifications@example.com',
            'description' => 'Email address for system notifications',
            'group' => 'notifications',
        ],
        'auto_backup_enabled' => [
            'type' => 'checkbox',
            'label' => 'Enable Auto Backup',
            'description' => 'Automatically backup data daily',
            'group' => 'system',
        ],
        'backup_retention_days' => [
            'type' => 'number',
            'label' => 'Backup Retention Days',
            'placeholder' => '30',
            'description' => 'Number of days to keep backups',
            'group' => 'system',
        ],
    ];

    public const SOCIAL_MEDIA_SETTINGS = [
        // Facebook settings
        'facebook_client_id' => [
            'type' => 'text',
            'label' => 'Facebook Client ID',
            'placeholder' => 'Enter Facebook App ID',
            'description' => 'Client ID from Facebook Developer Portal',
            'group' => 'facebook',
        ],
        'facebook_client_secret' => [
            'type' => 'password',
            'label' => 'Facebook Client Secret',
            'placeholder' => 'Enter Facebook App Secret',
            'description' => 'Client Secret from Facebook Developer Portal',
            'group' => 'facebook',
        ],
        'facebook_api_version' => [
            'type' => 'text',
            'label' => 'Facebook API Version',
            'placeholder' => 'v18.0',
            'description' => 'API version to use for Facebook',
            'group' => 'facebook',
        ],
        
        // Instagram settings
        'instagram_client_id' => [
            'type' => 'text',
            'label' => 'Instagram Client ID',
            'placeholder' => 'Enter Instagram App ID',
            'description' => 'Client ID from Instagram Developer Portal',
            'group' => 'instagram',
        ],
        'instagram_client_secret' => [
            'type' => 'password',
            'label' => 'Instagram Client Secret',
            'placeholder' => 'Enter Instagram App Secret',
            'description' => 'Client Secret from Instagram Developer Portal',
            'group' => 'instagram',
        ],
        'instagram_api_version' => [
            'type' => 'text',
            'label' => 'Instagram API Version',
            'placeholder' => 'v18.0',
            'description' => 'API version to use for Instagram',
            'group' => 'instagram',
        ],
        
        // LinkedIn settings
        'linkedin_client_id' => [
            'type' => 'text',
            'label' => 'LinkedIn Client ID',
            'placeholder' => 'Enter LinkedIn App ID',
            'description' => 'Client ID from LinkedIn Developer Portal',
            'group' => 'linkedin',
        ],
        'linkedin_client_secret' => [
            'type' => 'password',
            'label' => 'LinkedIn Client Secret',
            'placeholder' => 'Enter LinkedIn App Secret',
            'description' => 'Client Secret from LinkedIn Developer Portal',
            'group' => 'linkedin',
        ],
        'linkedin_api_version' => [
            'type' => 'text',
            'label' => 'LinkedIn API Version',
            'placeholder' => 'v2',
            'description' => 'API version to use for LinkedIn',
            'group' => 'linkedin',
        ],
        
        // X (Twitter) settings
        'x_client_id' => [
            'type' => 'text',
            'label' => 'X Client ID',
            'placeholder' => 'Enter X App ID',
            'description' => 'Client ID from X Developer Portal',
            'group' => 'x',
        ],
        'x_client_secret' => [
            'type' => 'password',
            'label' => 'X Client Secret',
            'placeholder' => 'Enter X App Secret',
            'description' => 'Client Secret from X Developer Portal',
            'group' => 'x',
        ],
        'x_api_version' => [
            'type' => 'text',
            'label' => 'X API Version',
            'placeholder' => '2',
            'description' => 'API version to use for X',
            'group' => 'x',
        ],
        
        // WhatsApp settings
        'whatsapp_business_account_id' => [
            'type' => 'text',
            'label' => 'WhatsApp Business Account ID',
            'placeholder' => 'Enter WhatsApp Business Account ID',
            'description' => 'WhatsApp Business Account ID from Meta Business Manager',
            'group' => 'whatsapp',
        ],
        'whatsapp_access_token' => [
            'type' => 'password',
            'label' => 'WhatsApp Access Token',
            'placeholder' => 'Enter WhatsApp Access Token',
            'description' => 'Access token for WhatsApp Business API',
            'group' => 'whatsapp',
        ],
        'whatsapp_phone_number_id' => [
            'type' => 'text',
            'label' => 'Phone Number ID',
            'placeholder' => 'Enter Phone Number ID',
            'description' => 'Phone Number ID from Meta Business Manager',
            'group' => 'whatsapp',
        ],
        'whatsapp_api_version' => [
            'type' => 'text',
            'label' => 'WhatsApp API Version',
            'placeholder' => 'v18.0',
            'description' => 'API version to use for WhatsApp',
            'group' => 'whatsapp',
        ],
        
        // TikTok settings
        'tiktok_client_id' => [
            'type' => 'text',
            'label' => 'TikTok Client ID',
            'placeholder' => 'Enter TikTok App ID',
            'description' => 'Client ID from TikTok Developer Portal',
            'group' => 'tiktok',
        ],
        'tiktok_client_secret' => [
            'type' => 'password',
            'label' => 'TikTok Client Secret',
            'placeholder' => 'Enter TikTok App Secret',
            'description' => 'Client Secret from TikTok Developer Portal',
            'group' => 'tiktok',
        ],
        
        // YouTube settings
        'youtube_client_id' => [
            'type' => 'text',
            'label' => 'YouTube Client ID',
            'placeholder' => 'Enter YouTube App ID',
            'description' => 'Client ID from Google Developer Console',
            'group' => 'youtube',
        ],
        'youtube_client_secret' => [
            'type' => 'password',
            'label' => 'YouTube Client Secret',
            'placeholder' => 'Enter YouTube App Secret',
            'description' => 'Client Secret from Google Developer Console',
            'group' => 'youtube',
        ],
        
        // Snapchat settings
        'snapchat_client_id' => [
            'type' => 'text',
            'label' => 'Snapchat Client ID',
            'placeholder' => 'Enter Snapchat App ID',
            'description' => 'Client ID from Snapchat Developer Portal',
            'group' => 'snapchat',
        ],
        'snapchat_client_secret' => [
            'type' => 'password',
            'label' => 'Snapchat Client Secret',
            'placeholder' => 'Enter Snapchat App Secret',
            'description' => 'Client Secret from Snapchat Developer Portal',
            'group' => 'snapchat',
        ],
        
        // Pinterest settings
        'pinterest_client_id' => [
            'type' => 'text',
            'label' => 'Pinterest Client ID',
            'placeholder' => 'Enter Pinterest App ID',
            'description' => 'Client ID from Pinterest Developer Portal',
            'group' => 'pinterest',
        ],
        'pinterest_client_secret' => [
            'type' => 'password',
            'label' => 'Pinterest Client Secret',
            'placeholder' => 'Enter Pinterest App Secret',
            'description' => 'Client Secret from Pinterest Developer Portal',
            'group' => 'pinterest',
        ],
        'pinterest_api_version' => [
            'type' => 'text',
            'label' => 'Pinterest API Version',
            'placeholder' => 'v5',
            'description' => 'API version to use for Pinterest',
            'group' => 'pinterest',
        ],
        
        // Reddit settings
        'reddit_client_id' => [
            'type' => 'text',
            'label' => 'Reddit Client ID',
            'placeholder' => 'Enter Reddit App ID',
            'description' => 'Client ID from Reddit Developer Portal',
            'group' => 'reddit',
        ],
        'reddit_client_secret' => [
            'type' => 'password',
            'label' => 'Reddit Client Secret',
            'placeholder' => 'Enter Reddit App Secret',
            'description' => 'Client Secret from Reddit Developer Portal',
            'group' => 'reddit',
        ],
        
        // Telegram settings
        'telegram_bot_token' => [
            'type' => 'password',
            'label' => 'Telegram Bot Token',
            'placeholder' => 'Enter Telegram Bot Token',
            'description' => 'Bot token from Telegram BotFather',
            'group' => 'telegram',
        ],
        
        // Discord settings
        'discord_client_id' => [
            'type' => 'text',
            'label' => 'Discord Client ID',
            'placeholder' => 'Enter Discord App ID',
            'description' => 'Client ID from Discord Developer Portal',
            'group' => 'discord',
        ],
        'discord_client_secret' => [
            'type' => 'password',
            'label' => 'Discord Client Secret',
            'placeholder' => 'Enter Discord App Secret',
            'description' => 'Client Secret from Discord Developer Portal',
            'group' => 'discord',
        ],
        
        // Tumblr settings
        'tumblr_consumer_key' => [
            'type' => 'text',
            'label' => 'Tumblr Consumer Key',
            'placeholder' => 'Enter Tumblr Consumer Key',
            'description' => 'Consumer Key from Tumblr Developer Portal',
            'group' => 'tumblr',
        ],
        'tumblr_consumer_secret' => [
            'type' => 'password',
            'label' => 'Tumblr Consumer Secret',
            'placeholder' => 'Enter Tumblr Consumer Secret',
            'description' => 'Consumer Secret from Tumblr Developer Portal',
            'group' => 'tumblr',
        ],
        
        // Flickr settings
        'flickr_api_key' => [
            'type' => 'text',
            'label' => 'Flickr API Key',
            'placeholder' => 'Enter Flickr API Key',
            'description' => 'API Key from Flickr Developer Portal',
            'group' => 'flickr',
        ],
        'flickr_shared_secret' => [
            'type' => 'password',
            'label' => 'Flickr Shared Secret',
            'placeholder' => 'Enter Flickr Shared Secret',
            'description' => 'Shared Secret from Flickr Developer Portal',
            'group' => 'flickr',
        ],
        
        // Vimeo settings
        'vimeo_client_id' => [
            'type' => 'text',
            'label' => 'Vimeo Client ID',
            'placeholder' => 'Enter Vimeo App ID',
            'description' => 'Client ID from Vimeo Developer Portal',
            'group' => 'vimeo',
        ],
        'vimeo_client_secret' => [
            'type' => 'password',
            'label' => 'Vimeo Client Secret',
            'placeholder' => 'Enter Vimeo App Secret',
            'description' => 'Client Secret from Vimeo Developer Portal',
            'group' => 'vimeo',
        ],
        
        // Twitch settings
        'twitch_client_id' => [
            'type' => 'text',
            'label' => 'Twitch Client ID',
            'placeholder' => 'Enter Twitch App ID',
            'description' => 'Client ID from Twitch Developer Portal',
            'group' => 'twitch',
        ],
        'twitch_client_secret' => [
            'type' => 'password',
            'label' => 'Twitch Client Secret',
            'placeholder' => 'Enter Twitch App Secret',
            'description' => 'Client Secret from Twitch Developer Portal',
            'group' => 'twitch',
        ],
    ];

    /**
     * Get all available settings grouped by type
     */
    public static function getAllSettings(): array
    {
        return array_merge(self::GENERAL_SETTINGS, self::SOCIAL_MEDIA_SETTINGS);
    }

    /**
     * Get settings by group
     */
    public static function getSettingsByGroup(string $group): array
    {
        $allSettings = self::getAllSettings();
        $groupedSettings = [];
        
        foreach ($allSettings as $key => $config) {
            if (isset($config['group']) && $config['group'] === $group) {
                $groupedSettings[$key] = $config;
            }
        }
        
        return $groupedSettings;
    }

    /**
     * Get all groups
     */
    public static function getGroups(): array
    {
        $allSettings = self::getAllSettings();
        $groups = [];
        
        foreach ($allSettings as $config) {
            if (isset($config['group']) && !in_array($config['group'], $groups)) {
                $groups[] = $config['group'];
            }
        }
        
        return $groups;
    }

    /**
     * Get a setting value for the authenticated user
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        // if (!Auth::check()) {
        //     return $default;
        // }

        $setting = Setting::where('user_id', Auth::id())
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Get a setting value for the authenticated user
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getAdmin($key, $default = null)
    {
        if (!Auth::check()) {
            return $default;
        }

        $setting = Setting::where('user_id', 1)
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Set a setting value for the authenticated user
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set($key, $value, $type = 'general')
    {
        if (!Auth::check()) {
            return false;
        }

        $setting = Setting::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'setting_key' => $key,
            ],
            [
                'setting_value' => $value,
                'setting_type' => $type,
            ]
        );

        return $setting !== null;
    }

    /**
     * Get all social media settings for the authenticated user
     *
     * @return array
     */
    public static function getSocialMediaSettings()
    {
        if (!Auth::check()) {
            return [];
        }

        $settings = Setting::where('user_id', Auth::id())
            ->whereIn('setting_key', array_keys(self::SOCIAL_MEDIA_SETTINGS))
            ->get()
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        return $settings;
    }

    /**
     * Check if all required social media settings are configured
     *
     * @param array $requiredKeys
     * @return bool
     */
    public static function hasRequiredSettings($requiredKeys = [])
    {
        if (empty($requiredKeys)) {
            $requiredKeys = ['facebook_client_id', 'facebook_client_secret'];
        }

        $settings = self::getSocialMediaSettings();

        foreach ($requiredKeys as $key) {
            if (empty($settings[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a specific social media setting
     *
     * @param string $platform
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getSocialMediaSetting($platform, $key, $default = null)
    {
        $settingKey = $platform . '_' . $key;
        $value = self::getAdmin($settingKey, $default);
        
        // Handle JSON field - if it's a JSON string, decode it first
        if (is_string($value)) {
            // Try to decode if it looks like JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
                $value = $decoded;
            }
            
            // Strip quotes from the final string value
            $value = trim($value, '"\'');
        }
        
        return $value;
    }

    /**
     * Get all social media platform groups
     * This is used in blade templates to iterate through all social media settings groups
     *
     * @return array
     */
    public static function getSocialMediaPlatformGroups(): array
    {
        return ['facebook', 'instagram', 'linkedin', 'x', 'whatsapp', 'tiktok', 'youtube', 'snapchat', 'pinterest', 'reddit', 'telegram', 'discord', 'tumblr', 'flickr', 'vimeo', 'twitch'];
    }
}