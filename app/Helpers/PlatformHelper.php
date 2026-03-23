<?php

namespace App\Helpers;

class PlatformHelper
{
    /**
     * Get all available social media platforms
     */
    public static function getPlatforms()
    {
        return [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'x' => 'X',
            'linkedin' => 'LinkedIn',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
        ];
    }

    /**
     * Get platforms as options for dropdown
     */
    public static function getPlatformOptions()
    {
        return self::getPlatforms();
    }

    /**
     * Get platform label by key
     */
    public static function getPlatformLabel($key)
    {
        $platforms = self::getPlatforms();
        return $platforms[$key] ?? ucfirst($key);
    }

    /**
     * Get platforms that support lead capture
     */
    public static function getLeadCapturePlatforms()
    {
        return [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'x' => 'X',
            'linkedin' => 'LinkedIn',
        ];
    }

    /**
     * Get platforms that support messaging
     */
    public static function getMessagingPlatforms()
    {
        return [
            'facebook' => 'Facebook Messenger',
            'instagram' => 'Instagram Direct',
            'x' => 'X DMs',
            'linkedin' => 'LinkedIn Messages',
        ];
    }

    /**
     * Get platforms that support posting
     */
    public static function getPostingPlatforms()
    {
        return [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'x' => 'X',
            'linkedin' => 'LinkedIn',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
        ];
    }

    /**
     * Get source types for leads
     */
    public static function getSourceTypes()
    {
        return [
            'comment' => 'Comment',
            'dm' => 'Direct Message',
            'lead_form' => 'Lead Form',
            'engagement' => 'Engagement',
            'mention' => 'Mention',
            'share' => 'Share',
            'like' => 'Like',
            'follow' => 'Follow',
        ];
    }

    /**
     * Get source type label by key
     */
    public static function getSourceTypeLabel($key)
    {
        $sourceTypes = self::getSourceTypes();
        return $sourceTypes[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Get lead statuses
     */
    public static function getLeadStatuses()
    {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'converted' => 'Converted',
            'lost' => 'Lost',
        ];
    }

    /**
     * Get lead qualifications
     */
    public static function getLeadQualifications()
    {
        return [
            'cold' => 'Cold',
            'warm' => 'Warm',
            'hot' => 'Hot',
        ];
    }
}
