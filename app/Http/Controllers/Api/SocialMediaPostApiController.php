<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialMediaPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SocialMediaPostApiController extends Controller
{
    /**
     * Display a listing of social media posts.
     */
    public function index(): JsonResponse
    {
        $posts = SocialMediaPost::query()
            ->latest('id')
            ->paginate(15);

        return response()->json($posts);
    }

    /**
     * Store a newly created social media post in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:5000',
            'platform_contents' => 'nullable|array',
            'platform_contents.*' => 'nullable|string|max:5000',
            'platforms' => 'required|array',
            'platforms.*' => 'string|in:facebook,pinterest,instagram,x,youtube,linkedin',
            'scheduled_at' => 'nullable|date',
            'status' => 'nullable|string|in:draft,scheduled,published,failed',
            'media_urls' => 'nullable|array',
            'media_urls.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        if (! isset($validated['status'])) {
            $validated['status'] = 'draft';
        }

        $post = SocialMediaPost::create($validated);

        return response()->json([
            'message' => 'Social media post created successfully',
            'data' => $post,
        ], 201);
    }

    /**
     * Display the specified social media post.
     */
    public function show(SocialMediaPost $post): JsonResponse
    {
        return response()->json([
            'data' => $post,
        ]);
    }

    /**
     * Update the specified social media post in storage.
     */
    public function update(Request $request, SocialMediaPost $post): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:5000',
            'platform_contents' => 'nullable|array',
            'platform_contents.*' => 'nullable|string|max:5000',
            'platforms' => 'nullable|array',
            'platforms.*' => 'string|in:facebook,pinterest,instagram,x,youtube,linkedin',
            'scheduled_at' => 'nullable|date',
            'status' => 'nullable|string|in:draft,scheduled,published,failed',
            'media_urls' => 'nullable|array',
            'media_urls.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $post->update(array_filter($validator->validated()));

        return response()->json([
            'message' => 'Social media post updated successfully',
            'data' => $post,
        ]);
    }

    /**
     * Remove the specified social media post from storage.
     */
    public function destroy(SocialMediaPost $post): JsonResponse
    {
        $post->delete();

        return response()->json([
            'message' => 'Social media post deleted successfully',
        ]);
    }
}
