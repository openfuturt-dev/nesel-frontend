<?php

use App\Http\Controllers\ContactRequestController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/services', 'pages.services')->name('services');
Route::view('/offres', 'pages.offres')->name('offers');
Route::view('/domiciliation-marrakech', 'pages.domiciliation-marrakech')->name('domiciliation.marrakech');
Route::view('/domiciliation-casablanca', 'pages.domiciliation-casablanca')->name('domiciliation.casablanca');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::post('/demandes-de-contact', [ContactRequestController::class, 'store'])
    ->middleware('throttle:contact-requests')
    ->name('contact-requests.store');
