<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\TouristPlaceController;
use App\Http\Controllers\Admin\TouristPlaceCategoryController;


// Route::middleware(AuthenticateAdmin::class)->group(function () {


    Route::prefix('admin')->group(function () {
        Route::apiResource('tourist-place-categories', TouristPlaceCategoryController::class);
        Route::prefix('tourist-places')->group(function () {
            Route::get('/', [TouristPlaceController::class, 'index']);
            Route::post('/', [TouristPlaceController::class, 'store']);
            Route::get('/{id}', [TouristPlaceController::class, 'show']);
            Route::put('/{id}', [TouristPlaceController::class, 'update']);
            Route::delete('/{id}', [TouristPlaceController::class, 'destroy']);
        });
            // Admin Route: List all galleries (pagination)
            Route::get('galleries', [GalleryController::class, 'index']);

            // Admin Route: Create new galleries (multiple files)
            Route::post('galleries', [GalleryController::class, 'store']);

            // Admin Route: Update an existing gallery
            Route::put('galleries/{id}', [GalleryController::class, 'update']);

            // Admin Route: Delete a gallery
            Route::delete('galleries/{id}', [GalleryController::class, 'destroy']);


    });



// });

