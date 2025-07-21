<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// redirect to users
Route::redirect('/', '/users');

Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/list', [UserController::class, 'list'])->name('users.list');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
