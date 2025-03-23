<?php

use Illuminate\Support\Facades\Route;

// Show API landing page
Route::get('/', function () {
    return view('api');
});