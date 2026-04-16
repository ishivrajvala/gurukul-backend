<?php

namespace App\Services;

use App\Models\Theme;
use Illuminate\Support\Facades\DB;

class ThemeService
{
    public function activateCurrentTheme(): void
    {
        $today = now()->toDateString();

        $currentTheme = Theme::query()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('start_date')
            ->first();

        if (! $currentTheme) {
            return;
        }

        DB::transaction(function () use ($currentTheme): void {
            Theme::query()->update([
                'is_active' => false,
                'is_home_active' => false,
            ]);

            $currentTheme->forceFill([
                'is_active' => true,
                'is_home_active' => true,
            ])->save();
        });
    }
}
