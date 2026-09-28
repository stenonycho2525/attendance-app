<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('user.user-login');
});

Route::middleware('auth')->group(function () {
    Route::get('/attendance/list', fn() => 'タスク一覧（準備中）')->name('tasks.index');
    Route::get('/categories', fn() => 'カテゴリー一覧（準備中）')->name('categories.index');
});
