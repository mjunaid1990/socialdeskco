<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $socialAccounts = SocialAccount::where('user_id', Auth::id())->get();
        $post = new Post();
        return view('backend.posts.create', compact('socialAccounts', 'post'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'social_account_id' => 'required|exists:social_accounts,id',
            'status' => 'required|in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date',
            'media_files' => 'nullable|array',
            'media_files.*' => 'string',
        ]);

        // Convert scheduled_at to UTC if provided
        $scheduledAt = $request->scheduled_at;
        if ($scheduledAt) {
            $scheduledAt = Auth::user()->convertFromUserTimezone(
                new \DateTime($scheduledAt)
            );
        }

        $post = new Post();
        $post->user_id = Auth::id();
        $post->social_account_id = $request->social_account_id;
        $post->title = $request->title;
        $post->content = $request->content;
        $post->status = $request->status;
        $post->scheduled_at = $scheduledAt;
        $post->media_files = $request->media_files;
        $post->save();

        // If status is scheduled and time is now or past, dispatch job
        if ($request->status === 'scheduled' && $scheduledAt && $scheduledAt->isPast()) {
            PublishPostJob::dispatch($post);
            $post->update(['status' => 'processing']);
        }

        return redirect()->route('admin.posts.index')->with('success', 'Post created successfully.');
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

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'social_account_id' => 'required|exists:social_accounts,id',
            'status' => 'required|in:draft,scheduled,published',
            'scheduled_at' => 'nullable|date',
            'media_files' => 'nullable|array',
            'media_files.*' => 'string',
        ]);

        $post->social_account_id = $request->social_account_id;
        $post->title = $request->title;
        $post->content = $request->content;
        $post->status = $request->status;
        $post->scheduled_at = $request->scheduled_at;
        $post->media_files = $request->media_files;
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

        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted successfully.');
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