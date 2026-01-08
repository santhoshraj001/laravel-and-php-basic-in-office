<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\StudentController;

Route::get('/student', [StudentController::class, 'create']);
Route::post('/student/store', [StudentController::class, 'store']);

Route::get('/', function () {
    return view('welcome');
});
