<?php

use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\SentimenController;
use App\Http\Controllers\WelcomeController;

Route::get('/', function () {
    return redirect()->route('welcome');
});

Auth::routes();

Route::get('/welcome', [WelcomeController::class, 'index'])->name('welcome');

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

// Route untuk menampilkan halaman chatbot
Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot');

// Route untuk menangani permintaan POST dari JavaScript
Route::post('/chatbot-response', [ChatbotController::class, 'handleQuery'])->name('chatbot.response');

Route::get('/sentimen-analisis', [SentimenController::class, 'index'])->name('sentimen');
Route::post('/sentimen-analisis/predict', [SentimenController::class, 'predict'])->name('sentimen.predict');
Route::post('/sentimen-analisis/save', [SentimenController::class, 'save'])->name('sentimen.save');

Route::get('/news', [NewsController::class, 'index'])->name('news');
Route::get('/news/{article_id}', [NewsController::class, 'show'])->name('news_show');
