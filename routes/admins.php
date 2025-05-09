<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Admin\TouristPlaceController;
use App\Http\Controllers\Admin\TouristPlaceCategoryController;


// Route::middleware(AuthenticateAdmin::class)->group(function () {


    Route::prefix('admin')->group(function () {
        Route::apiResource('tourist-place-categories', TouristPlaceCategoryController::class);
        Route::apiResource('tourist-places', TouristPlaceController::class);
    });



// });

