<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Services\AiService;
use App\Jobs\PublishPostJob;
use App\Jobs\DeletePostJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::with('socialAccount', 'user')->where('user_id', Auth::id())->paginate(10);
        return view('backend.posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $socialAccounts = SocialAccount::where('user_id', Auth::id())->where('is_active', true)->get();
        $post = new Post();
        
        // Create account to platform mapping for frontend
        $accountPlatforms = [];
        foreach ($socialAccounts as $account) {
            $accountPlatforms[$account->id] = $account->platform;
        }
        
        return view('backend.posts.create', compact('socialAccounts', 'post', 'accountPlatforms'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Debug logging
        Log::info('Post creation request data:', [
            'all_data' => $request->all(),
            'has_files' => $request->hasFile('media'),
            'files_count' => $request->hasFile('media') ? (is_array($request->file('media')) ? count($request->file('media')) : 1) : 0,
            'files' => $request->hasFile('media') ? array_map(function($file) {
                return [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'is_valid' => $file->isValid(),
                    'error' => $file->getError()
                ];
            }, is_array($request->file('media')) ? $request->file('media') : [$request->file('media')]) : [],
            'social_accounts' => $request->social_accounts ?? []
        ]);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'exists:social_accounts,id',
            'status' => 'required|in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date',
            'media' => 'nullable|array',
            'media.*' => 'file|max:10240', // Max 10MB per file
            'facebook_settings' => 'nullable|array',
            'instagram_settings' => 'nullable|array',
            'x_settings' => 'nullable|array',
            'linkedin_settings' => 'nullable|array',
            'youtube_settings' => 'nullable|array',
            'youtube_settings.category' => 'nullable|in:1,2,10,15,17,19,20,22,23,24,25,26,27,28,29',
            'youtube_settings.privacy' => 'nullable|in:public,unlisted,private',
            'youtube_settings.tags' => 'nullable|string|max:500',
            'tiktok_settings' => 'nullable|array',
            'tiktok_settings.caption' => 'nullable|string|max:2200',
            'tiktok_settings.privacy' => 'nullable|in:PUBLIC_TO_EVERYONE,FRIENDS_AND_FAMILY,SELF_ONLY',
            'tiktok_settings.disable_duet' => 'boolean|nullable',
            'tiktok_settings.disable_stitch' => 'boolean|nullable',
            'tiktok_settings.disable_comments' => 'boolean|nullable',
            'other_platform_settings' => 'nullable|array',
        ]);

        Log::info('Validation passed, proceeding with post creation');

        // Convert scheduled_at to UTC if provided
        $scheduledAt = $request->scheduled_at;
        if ($scheduledAt) {
            $scheduledAt = Auth::user()->convertFromUserTimezone(
                new \DateTime($scheduledAt)
            );
        }

        try {
            Log::info('Creating new post object');
            $post = new Post();
            $post->user_id = Auth::id();
            $post->title = $request->title;
            $post->content = $request->description;
            $post->status = $request->status;
            $post->scheduled_at = $scheduledAt;
            
            // Add platform-specific settings
            $post->facebook_settings = $request->facebook_settings;
            $post->instagram_settings = $request->instagram_settings;
            $post->x_settings = $request->x_settings;
            $post->linkedin_settings = $request->linkedin_settings;
            $post->youtube_settings = $request->youtube_settings;
            $post->tiktok_settings = $request->tiktok_settings;
            $post->other_platform_settings = $request->other_platform_settings;
            // social_account_id is now nullable - we'll use many-to-many relationship instead
            
            Log::info('Post object created, processing media files', [
                'post_data' => [
                    'user_id' => $post->user_id,
                    'title' => $post->title,
                    'content' => $post->content,
                    'status' => $post->status,
                    'scheduled_at' => $post->scheduled_at
                ]
            ]);
            
            // Handle media file uploads
            $mediaFiles = [];
            if ($request->hasFile('media')) {
                $files = $request->file('media');
                // Ensure we have an array even for single file uploads
                if (!is_array($files)) {
                    $files = [$files];
                }
                
                Log::info('Processing media files', ['files_count' => count($files)]);
                
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        // Store file in public storage
                        $path = $file->store('media', 'public');
                        // Get full URL for the file
                        $url = asset('storage/' . $path);
                        $mediaFiles[] = $url;
                        
                        Log::info('Media file uploaded:', [
                            'original_name' => $file->getClientOriginalName(),
                            'path' => $path,
                            'url' => $url,
                            'size' => $file->getSize()
                        ]);
                    }
                }
            }
            
            $post->media_files = $mediaFiles;
            
            Log::info('Saving post to database', [
                'media_files' => $mediaFiles,
                'media_files_count' => count($mediaFiles)
            ]);
            
            if(!$post->save()) {
                Log::error('Failed to save post to database');
                return redirect()->back()->with('error', 'Failed to create post. Please try again.')->withInput();
            }
            
            Log::info('Post saved successfully with ID: ' . $post->id, [
                'media_files_count' => count($mediaFiles),
                'media_files' => $mediaFiles
            ]);

            // Attach social accounts to post (many-to-many relationship)
            $socialAccountIds = $request->platforms;
            Log::info('Attaching social accounts to post', [
                'post_id' => $post->id,
                'social_account_ids' => $socialAccountIds
            ]);
            
            $post->socialAccounts()->attach($socialAccountIds);

            // Handle immediate publishing
            if ($request->status === 'published') {
                // Dispatch job immediately for published posts
                PublishPostJob::dispatch($post);
                \Log::info('PublishPostJob dispatched immediately for post ID: ' . $post->id);
            }
            // If status is scheduled and time is now or past, dispatch job
            elseif ($request->status === 'scheduled' && $scheduledAt && $scheduledAt->isPast()) {
                PublishPostJob::dispatch($post);
                $post->update(['status' => 'processing']);
                \Log::info('PublishPostJob dispatched for post ID: ' . $post->id);
            }

            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Post created successfully!',
                    'redirect' => route('admin.posts.index')
                ]);
            }
            
            return redirect()->route('admin.posts.index')->with('success', 'Post created successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Error creating post: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while creating the post: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'An error occurred while creating the post.')->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        // Authorization check
        if ($post->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        return view('backend.posts.show', compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        // Authorization check
        if ($post->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $socialAccounts = SocialAccount::where('user_id', Auth::id())->get();
        return view('backend.posts.edit', compact('post', 'socialAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        // Authorization check
        if ($post->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }

        // Debug logging
        Log::info('Post update request data:', [
            'post_id' => $post->id,
            'all_data' => $request->all(),
            'has_files' => $request->hasFile('media'),
            'files_count' => $request->hasFile('media') ? (is_array($request->file('media')) ? count($request->file('media')) : 1) : 0,
            'files' => $request->hasFile('media') ? array_map(function($file) {
                return [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'is_valid' => $file->isValid(),
                    'error' => $file->getError()
                ];
            }, is_array($request->file('media')) ? $request->file('media') : [$request->file('media')]) : []
        ]);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'social_account_id' => 'required|exists:social_accounts,id',
            'status' => 'required|in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date',
            'media' => 'nullable|array',
            'media.*' => 'file|max:10240', // Simplified validation for debugging
            'facebook_settings' => 'nullable|array',
            'instagram_settings' => 'nullable|array',
            'x_settings' => 'nullable|array',
            'linkedin_settings' => 'nullable|array',
            'youtube_settings' => 'nullable|array',
            'youtube_settings.category' => 'nullable|in:1,2,10,15,17,19,20,22,23,24,25,26,27,28,29',
            'youtube_settings.privacy' => 'nullable|in:public,unlisted,private',
            'youtube_settings.tags' => 'nullable|string|max:500',
            'tiktok_settings' => 'nullable|array',
            'tiktok_settings.caption' => 'nullable|string|max:2200',
            'tiktok_settings.privacy' => 'nullable|in:PUBLIC_TO_EVERYONE,FRIENDS_AND_FAMILY,SELF_ONLY',
            'tiktok_settings.disable_duet' => 'boolean|nullable',
            'tiktok_settings.disable_stitch' => 'boolean|nullable',
            'tiktok_settings.disable_comments' => 'boolean|nullable',
            'other_platform_settings' => 'nullable|array',
        ]);

        $post->social_account_id = $request->social_account_id;
        $post->title = $request->title;
        $post->content = $request->content;
        $post->status = $request->status;
        $post->scheduled_at = $request->scheduled_at;
        
        // Update platform-specific settings
        $post->facebook_settings = $request->facebook_settings;
        $post->instagram_settings = $request->instagram_settings;
        $post->x_settings = $request->x_settings;
        $post->linkedin_settings = $request->linkedin_settings;
        $post->youtube_settings = $request->youtube_settings;
        $post->tiktok_settings = $request->tiktok_settings;
        $post->other_platform_settings = $request->other_platform_settings;
        
        // Handle media file uploads and existing media files
        $mediaFiles = [];
        
        // First, handle existing media files that weren't removed
        $existingMediaFiles = $request->input('existing_media_files', []);
        if (is_array($existingMediaFiles)) {
            $mediaFiles = $existingMediaFiles;
        }
        
        // Then, handle newly uploaded files
        if ($request->hasFile('media')) {
            $files = $request->file('media');
            // Ensure we have an array even for single file uploads
            if (!is_array($files)) {
                $files = [$files];
            }
            
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    // Store file in public storage
                    $path = $file->store('media', 'public');
                    // Get full URL for the file
                    $url = asset('storage/' . $path);
                    $mediaFiles[] = $url;
                    
                    Log::info('Media file uploaded during update:', [
                        'post_id' => $post->id,
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'url' => $url,
                        'size' => $file->getSize()
                    ]);
                }
            }
        }
        
        $post->media_files = $mediaFiles;
        $post->save();

        return redirect()->route('admin.posts.index')->with('success', 'Post updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        // Authorization check
        if ($post->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }
        
        // Delete from all connected social media platforms if published
        if ($post->status === 'published') {
            $socialAccounts = $post->socialAccounts;
            foreach ($socialAccounts as $account) {
                // Dispatch delete job for each platform
                DeletePostJob::dispatch($post, $account);
                Log::info("DeletePostJob dispatched for post {$post->id} from {$account->platform}");
            }
        }
        
        // Delete from local database
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted successfully. Social media deletion is processing in the background.');
    }

    /**
     * Generate post content using AI
     */
    public function generateWithAi(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:500',
            'platform' => 'nullable|string',
            'tone' => 'nullable|string',
            'max_length' => 'nullable|integer|min:50|max:5000',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string',
        ]);

        try {
            $content = $this->aiService->generatePost(
                $request->topic,
                $request->platform,
                $request->tone,
                $request->max_length,
                $request->keywords ?? []
            );

            return response()->json(['content' => $content]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate content: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate multiple post variations using AI
     */
    public function generateVariationsWithAi(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:500',
            'platform' => 'nullable|string',
            'tone' => 'nullable|string',
            'max_length' => 'nullable|integer|min:50|max:5000',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string',
            'count' => 'nullable|integer|min:1|max:5'
        ]);

        try {
            $variations = $this->aiService->generatePostVariations(
                $request->topic,
                $request->platform,
                $request->tone,
                $request->max_length,
                $request->keywords ?? [],
                $request->count ?? 3
            );

            return response()->json(['variations' => $variations]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate variations: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate an image using AI based on post content
     */
    public function generateImageWithAi(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'size' => 'nullable|in:256x256,512x512,1024x1024',
            'style' => 'nullable|in:vivid,natural',
            'quality' => 'nullable|in:standard,hd',
        ]);

        try {
            $imageData = $this->aiService->generateImage(
                $request->prompt,
                $request->size ?? '1024x1024',
                $request->style ?? 'vivid',
                $request->quality ?? 'standard'
            );

            return response()->json(['image' => $imageData]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate image: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate an image using AI based on post content and topic
     */
    public function generateImageForPostWithAi(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'topic' => 'required|string|max:500',
            'platform' => 'nullable|string',
            'size' => 'nullable|in:256x256,512x512,1024x1024',
        ]);

        try {
            $imageData = $this->aiService->generateImageForPost(
                $request->content,
                $request->topic,
                $request->platform,
                $request->size ?? '1024x1024'
            );

            return response()->json(['image' => $imageData]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate image: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for bulk uploading posts.
     */
    public function bulkUpload()
    {
        $socialAccounts = SocialAccount::where('user_id', Auth::id())->get();
        return view('backend.posts.bulk-upload', compact('socialAccounts'));
    }

    /**
     * Process bulk upload of posts.
     */
    public function processBulkUpload(Request $request)
    {
        // Debug: Log incoming data
        \Log::info('Bulk upload request data:', [
            'posts' => $request->posts,
            'all_data' => $request->all(),
            'files' => $request->allFiles()
        ]);

        $request->validate([
            'posts' => 'required|array|min:1|max:50',
            'posts.*.title' => 'required|string|max:255',
            'posts.*.content' => 'required|string',
            'posts.*.social_account_id' => 'required|exists:social_accounts,id',
            'posts.*.status' => 'required|in:draft,scheduled,published',
            'posts.*.scheduled_at' => 'nullable|date',
            'posts.*.media_files' => 'nullable|array',
            'posts.*.media_files.*' => 'string',
        ]);

        $user = Auth::user();
        $processedPosts = [];
        $errors = [];

        foreach ($request->posts as $index => $postData) {
            try {
                \Log::info("Processing post {$index}:", $postData);
                
                // Validate social account ownership
                $socialAccount = SocialAccount::findOrFail($postData['social_account_id']);
                if ($socialAccount->user_id !== $user->id) {
                    $errors[$index] = 'Unauthorized social account';
                    continue;
                }

                // Convert scheduled_at to UTC if provided
                $scheduledAt = null;
                if (!empty($postData['scheduled_at'])) {
                    $scheduledAt = $user->convertFromUserTimezone(
                        new \DateTime($postData['scheduled_at'])
                    );
                }

                $post = Post::create([
                    'user_id' => $user->id,
                    'social_account_id' => $postData['social_account_id'],
                    'title' => $postData['title'],
                    'content' => $postData['content'],
                    'status' => $postData['status'],
                    'scheduled_at' => $scheduledAt,
                    'media_files' => $postData['media_files'] ?? null,
                ]);

                $processedPosts[] = $post;
                \Log::info("Post {$index} created successfully with ID: {$post->id}");

                // If status is scheduled and time is now or past, dispatch job
                if ($postData['status'] === 'scheduled' && $scheduledAt && $scheduledAt->isPast()) {
                    PublishPostJob::dispatch($post);
                    $post->update(['status' => 'processing']);
                    \Log::info("Job dispatched for post {$post->id}");
                }

            } catch (\Exception $e) {
                \Log::error("Error processing post {$index}: " . $e->getMessage());
                $errors[$index] = $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'processed' => count($processedPosts),
            'errors' => $errors,
            'posts' => $processedPosts
        ]);
    }
}