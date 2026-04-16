<?php

use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\LandingPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/blogs', [BlogController::class, 'index']);
    Route::get('/blogs/{slug}', [BlogController::class, 'show']);
    Route::get('/landing/{slug}', [LandingPageController::class, 'show']);
});
