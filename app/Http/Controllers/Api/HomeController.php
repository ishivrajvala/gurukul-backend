<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\Home\HomeIndexService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomeIndexService $homeIndexService
    ) {
    }

    public function index(): JsonResponse
    {
        $data = $this->homeIndexService->execute();

        return response()->json([
            'theme' => $data['theme'],
            'featured_testimonials' => $data['featured_testimonials']
                ->map(fn ($testimonial): array => [
                    'title' => $testimonial->title,
                    'name' => $testimonial->name,
                    'tags' => $testimonial->tags,
                    'content' => $testimonial->content,
                    'video_url' => $testimonial->video_url,
                    'thumbnail' => $testimonial->thumbnail,
                ])
                ->values(),
        ]);
    }
}
