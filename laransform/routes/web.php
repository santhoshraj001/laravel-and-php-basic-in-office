<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\FormController;
// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/var', [PageController::class, 'index']);

Route::post('/submit', [FormController::class, 'form']);
