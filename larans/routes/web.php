<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController; //this is important when you create route from controller
Route::get('/', function () {
    return view('welcome');
});

    //this one is create route in route file directly
// Route::get('/hello', function () {
//     return view('hello');
// });

// this single line path create method work from controller and public function in controller
Route::get('/hello', [PageController::class, 'index']);


