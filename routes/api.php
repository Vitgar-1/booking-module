<?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\BookingController;

    Route::get('/rooms/available', [BookingController::class, 'availableRooms']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);


