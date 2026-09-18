<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/news/{news:slug}', [NewsController::class, 'show'])->name('news.show');
Route::middleware('guest')->group(function () { Route::get('/login', [AuthController::class, 'create'])->name('login'); Route::post('/login', [AuthController::class, 'store'])->name('login.store'); });
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () { Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard'); Route::get('/submit', [NewsController::class, 'create'])->name('submit.create'); Route::post('/submit', [NewsController::class, 'store'])->name('submit.store'); });
Route::prefix('admin')->name('admin.')->middleware(['auth','admin'])->group(function () { Route::get('/', [DashboardController::class, 'index'])->name('dashboard'); Route::get('/news', [AdminNewsController::class, 'index'])->name('news.index'); Route::patch('/news/{news}/publish', [AdminNewsController::class, 'publish'])->name('news.publish'); Route::delete('/news/{news}', [AdminNewsController::class, 'destroy'])->name('news.destroy'); });
