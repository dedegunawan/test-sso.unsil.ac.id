<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('welcome');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard')->middleware(\App\Http\Middleware\AuthSimakMiddleware::class);


Route::get('/callback', [
    \App\Http\Controllers\CallbackController::class,
    'index'
]);

Route::get('/login', [
    \App\Http\Controllers\CallbackController::class,
    'login'
])->name('login');
