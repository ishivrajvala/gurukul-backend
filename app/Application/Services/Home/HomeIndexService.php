<?php

namespace App\Application\Services\Home;

use App\Models\Testimonial;
use App\Models\Theme;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class HomeIndexService
{
    /**
     * @return array{
     *     theme: array<string, mixed>|null,
     *     featured_testimonials: Collection<int, Testimonial>
     * }
     */
    public function execute(): array
    {
        return Cache::remember('api:v1:home', now()->addMinutes(5), function (): array {
            $today = Carbon::now()->toDateString();

            $theme = Theme::query()
                ->where($this->themeActiveColumn(), true)
                ->where('is_home_active', true)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->orderByDesc('start_date')
                ->first(['title', 'slug', 'cover_image', 'banner_text', 'banner_cta_text', 'banner_cta_link']);

            $featuredTestimonials = Testimonial::query()
                ->where('is_featured', true)
                ->when(
                    Schema::hasColumn('testimonials', 'active'),
                    fn ($query) => $query->where('active', true),
                    fn ($query) => $query->where('status', true)
                )
                ->latest()
                ->take(6)
                ->get();

            return [
                'theme' => $theme ? [
                    'title' => $theme->title,
                    'slug' => $theme->slug,
                    'cover_image' => $theme->cover_image,
                    'banner' => [
                        'text' => $theme->banner_text,
                        'cta_text' => $theme->banner_cta_text,
                        'cta_link' => $theme->banner_cta_link,
                    ],
                ] : null,
                'featured_testimonials' => $featuredTestimonials,
            ];
        });
    }

    private function themeActiveColumn(): string
    {
        return Schema::hasColumn('themes', 'is_active') ? 'is_active' : 'status';
    }
}
