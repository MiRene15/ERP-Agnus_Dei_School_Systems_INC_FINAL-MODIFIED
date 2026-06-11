<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PromotionalWebsite\HomeController;
use App\Http\Controllers\PromotionalWebsite\InquiryController;

Route::get('/', [HomeController::class, 'index']);

Route::get('/vision', function () {
    return view('PromotionalWebsite.vision');
});

Route::get('/mission', function () {
    return view('PromotionalWebsite.mission');
});

Route::get('/academics', function () {
    return view('PromotionalWebsite.academics');
});

Route::get('/admissions', function () {
    return view('PromotionalWebsite.admissions');
});

Route::get('/inquiry', [InquiryController::class, 'show']);
Route::post('/inquiry', [InquiryController::class, 'store']);
