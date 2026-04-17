<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('PromotionalWebsite.welcome');
});

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

Route::get('/inquiry', [App\Http\Controllers\PromotionalWebsite\InquiryController::class, 'show']);
Route::post('/inquiry', [App\Http\Controllers\PromotionalWebsite\InquiryController::class, 'store']);
