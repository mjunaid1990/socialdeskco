<?php

namespace App\Services\SocialMedia;

use App\Models\SocialAccount;
use Exception;

class SocialMediaServiceFactory
{
    private static array $services = [
        'facebook' => FacebookService::class,
        'instagram' => InstagramService::class,
        'x' => XService::class,
        'linkedin' => LinkedInService::class,
        'tiktok' => TikTokService::class,
        'youtube' => YouTubeService::class,
    ];

    public static function create(string $platform): SocialMediaPlatformInterface
    {
        if (!isset(self::$services[$platform])) {
            throw new Exception("Unsupported platform: {$platform}");
        }

        $serviceClass = self::$services[$platform];
        $service = new $serviceClass();
        
        // Debug log to see what service is being created
        \Illuminate\Support\Facades\Log::info("SocialMediaServiceFactory: Created service", [
            'platform' => $platform,
            'service_class' => $serviceClass,
            'service_instance' => get_class($service)
        ]);
        
        return $service;
    }

    public static function createForAccount(SocialAccount $account): SocialMediaPlatformInterface
    {
        return self::create($account->platform);
    }

    public static function getSupportedPlatforms(): array
    {
        return array_keys(self::$services);
    }

    public static function registerPlatform(string $platform, string $serviceClass): void
    {
        if (!is_subclass_of($serviceClass, SocialMediaPlatformInterface::class)) {
            throw new Exception("Service class must implement SocialMediaPlatformInterface");
        }

        self::$services[$platform] = $serviceClass;
    }
}
