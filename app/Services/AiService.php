<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AiService
{
    protected $client;
    protected $model;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key');
        $apiBase = config('services.openai.base_uri') ?: 'https://api.openai.com/v1';
        
        // Check if API key exists
        if (!$apiKey) {
            throw new \Exception('OpenAI API key is not configured. Please set OPENAI_API_KEY in your environment configuration.');
        }
        
        // Create the OpenAI client with the base URI and API key
        $this->client = OpenAI::factory()
            ->withApiKey($apiKey)
            ->withBaseUri($apiBase)
            ->make();
            
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Generate social media post content using AI
     *
     * @param string $topic Topic or subject for the post
     * @param string|null $platform Target platform (optional)
     * @param string|null $tone Tone of the post (casual, professional, etc.)
     * @param int|null $maxLength Maximum length of the post
     * @param array $keywords Keywords to include in the post
     * @return string Generated post content
     */
    public function generatePost(
        string $topic, 
        string $platform = null, 
        string $tone = null, 
        int $maxLength = null,
        array $keywords = []
    ): string {
        $prompt = $this->buildPrompt($topic, $platform, $tone, $maxLength, $keywords);
        
        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => $maxLength ? intval($maxLength * 1.5) : 500, // Estimate tokens from characters
            ]);

            return $response->choices[0]->message->content ?? '';
        } catch (\Exception $e) {
            \Log::error('OpenAI Content Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate multiple variations of a social media post
     *
     * @param string $topic Topic or subject for the post
     * @param string|null $platform Target platform (optional)
     * @param string|null $tone Tone of the post (casual, professional, etc.)
     * @param int|null $maxLength Maximum length of the post
     * @param array $keywords Keywords to include in the post
     * @param int $count Number of variations to generate
     * @return array Array of generated post variations
     */
    public function generatePostVariations(
        string $topic, 
        string $platform = null, 
        string $tone = null, 
        int $maxLength = null,
        array $keywords = [],
        int $count = 3
    ): array {
        $variations = [];
        
        for ($i = 0; $i < $count; $i++) {
            try {
                $variations[] = $this->generatePost($topic, $platform, $tone, $maxLength, $keywords);
            } catch (\Exception $e) {
                \Log::error('OpenAI Variation Generation Error: ' . $e->getMessage());
                // On error, return a default message
                $variations[] = "Error generating content: " . $e->getMessage();
            }
        }
        
        return $variations;
    }

    /**
     * Optimize existing content for a specific platform
     *
     * @param string $content Original content
     * @param string $platform Target platform
     * @param string|null $tone Desired tone
     * @return string Optimized content
     */
    public function optimizeContent(string $content, string $platform, string $tone = null): string
    {
        $prompt = "Please optimize the following content for {$platform}";
        
        if ($tone) {
            $prompt .= " with a {$tone} tone";
        }
        
        $prompt .= ". ";
        
        if ($platform === 'twitter' || $platform === 'x') {
            $prompt .= "Ensure it fits within Twitter's character limits and is engaging for that platform. ";
        } elseif ($platform === 'linkedin') {
            $prompt .= "Make it professional and suitable for a LinkedIn audience. ";
        } elseif ($platform === 'instagram') {
            $prompt .= "Make it casual and engaging with potential for likes and comments. Consider adding relevant hashtags. ";
        } elseif ($platform === 'facebook') {
            $prompt .= "Make it friendly and shareable for Facebook. ";
        }
        
        $prompt .= "Original content: {$content}";
        
        try {
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.6,
                'max_tokens' => 500,
            ]);

            return $response->choices[0]->message->content ?? '';
        } catch (\Exception $e) {
            \Log::error('OpenAI Content Optimization Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build the prompt for AI content generation
     */
    private function buildPrompt(
        string $topic, 
        string $platform = null, 
        string $tone = null, 
        int $maxLength = null,
        array $keywords = []
    ): string {
        $prompt = "Generate a social media post about: {$topic}.";
        
        if ($platform) {
            $platformInstructions = $this->getPlatformSpecificInstructions($platform);
            $prompt .= " {$platformInstructions}";
        }
        
        if ($tone) {
            $prompt .= " The tone should be {$tone}.";
        }
        
        if ($maxLength) {
            $prompt .= " Keep the post under {$maxLength} characters.";
        }
        
        if (!empty($keywords)) {
            $prompt .= " Include these keywords: " . implode(', ', $keywords) . ".";
        }
        
        $prompt .= " Make it engaging and suitable for sharing.";
        
        return $prompt;
    }

    /**
     * Get platform-specific instructions
     */
    private function getPlatformSpecificInstructions(string $platform): string
    {
        switch (strtolower($platform)) {
            case 'twitter':
            case 'x':
                return "Optimize for Twitter/X - keep it concise, engaging, and under 280 characters. Consider adding relevant hashtags.";
            case 'linkedin':
                return "Make it professional and informative, suitable for a LinkedIn audience. Focus on insights and value-add.";
            case 'instagram':
                return "Create a casual, visually appealing caption that encourages likes and comments. Consider suggesting relevant hashtags.";
            case 'facebook':
                return "Make it friendly and shareable, good for a diverse Facebook audience.";
            case 'tiktok':
                return "Make it trendy, youthful, and catchy. Suitable for TikTok's younger demographic.";
            case 'pinterest':
                return "Create descriptive, inspiring content that works well with visuals.";
            default:
                return "Make it suitable for general social media sharing.";
        }
    }

    /**
     * Generate an image based on a text prompt using DALL-E
     *
     * @param string $prompt Description of the image to generate
     * @param string $size Size of the image (256x256, 512x512, or 1024x1024)
     * @param string $style Style of the image (natural, vivid)
     * @param string $quality Quality of the image (standard, hd)
     * @return array Image data including URL and filename
     */
    public function generateImage(
        string $prompt,
        string $size = '1024x1024',
        string $style = 'vivid',
        string $quality = 'standard'
    ): array {
        try {
            // Validate inputs
            $validSizes = ['256x256', '512x512', '1024x1024'];
            $validStyles = ['vivid', 'natural'];
            $validQualities = ['standard', 'hd'];

            if (!in_array($size, $validSizes)) {
                $size = '1024x1024';
            }

            if (!in_array($style, $validStyles)) {
                $style = 'vivid';
            }

            if (!in_array($quality, $validQualities)) {
                $quality = 'standard';
            }

            $response = $this->client->images()->create([
                'model' => 'dall-e-3',
                'prompt' => $prompt,
                'n' => 1,
                'size' => $size,
                'style' => $style,
                'quality' => $quality,
            ]);

            $imageUrl = $response->data[0]->url ?? null;

            if (!$imageUrl) {
                throw new \Exception('Failed to generate image: No URL returned');
            }

            // Download and store the image locally
            $imageContent = Http::get($imageUrl)->body();
            
            if (!$imageContent) {
                throw new \Exception('Failed to download generated image');
            }

            // Generate a unique filename
            $filename = 'generated-images/' . Str::random(40) . '.png';
            
            // Store the image in the public disk
            Storage::disk('public')->put($filename, $imageContent);

            $fullPath = Storage::disk('public')->path($filename);
            $url = Storage::url($filename);

            return [
                'url' => $url,
                'filename' => $filename,
                'full_path' => $fullPath,
                'original_url' => $imageUrl,
            ];

        } catch (\Exception $e) {
            \Log::error('Image generation failed: ' . $e->getMessage());
            throw new \Exception('Image generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate an image based on a text prompt and post content
     *
     * @param string $postContent Content of the post to generate image for
     * @param string $topic Topic or subject
     * @param string|null $platform Target platform (optional)
     * @param string $size Size of the image (256x256, 512x512, or 1024x1024)
     * @return array Image data including URL and filename
     */
    public function generateImageForPost(
        string $postContent,
        string $topic,
        string $platform = null,
        string $size = '1024x1024'
    ): array {
        $imagePrompt = $this->buildImagePrompt($postContent, $topic, $platform);
        return $this->generateImage($imagePrompt, $size);
    }

    /**
     * Build the prompt for image generation based on post content
     */
    private function buildImagePrompt(
        string $postContent,
        string $topic,
        string $platform = null
    ): string {
        $prompt = "Create an eye-catching, professional image representing: {$topic}. ";
        $prompt .= "The image should be appropriate for social media and complement this content: {$postContent}. ";

        if ($platform) {
            switch (strtolower($platform)) {
                case 'instagram':
                    $prompt .= "Optimize for Instagram with vibrant colors and visual appeal. ";
                    break;
                case 'facebook':
                    $prompt .= "Create a friendly, shareable image suitable for Facebook. ";
                    break;
                case 'twitter':
                case 'x':
                    $prompt .= "Design an engaging image that works well with Twitter/X posts. ";
                    break;
                case 'linkedin':
                    $prompt .= "Create a professional image suitable for LinkedIn. ";
                    break;
                case 'pinterest':
                    $prompt .= "Design a visually appealing, pin-worthy image for Pinterest. ";
                    break;
                case 'tiktok':
                    $prompt .= "Create a trendy, youthful image that appeals to TikTok's audience. ";
                    break;
            }
        }

        $prompt .= "The image should be high quality, visually striking, and relevant to the topic. Avoid text overlay unless specifically requested.";

        return $prompt;
    }
}