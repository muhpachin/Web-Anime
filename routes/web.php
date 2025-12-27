<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DetailController;
use App\Http\Controllers\WatchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VideoProxyController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CommentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public Routes (No Auth Required)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');
Route::get('/anime/{anime:slug}', [DetailController::class, 'show'])->name('detail');
Route::get('/watch/{episode:slug}', [WatchController::class, 'show'])->name('watch');

// Video Proxy Routes
Route::get('/api/video/proxy/animesail/{playerType}', [VideoProxyController::class, 'proxyAnimeSail'])->name('video.proxy.animesail');
Route::get('/api/video/proxy/external', [VideoProxyController::class, 'proxyExternal'])->name('video.proxy.external');

// Auth Routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
});

// Profile Routes (Auth Required)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
    
    // Comment Routes
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    
    // Watch Progress
    Route::post('/watch/{episode:slug}/progress', [WatchController::class, 'updateProgress'])->name('watch.progress');
});
