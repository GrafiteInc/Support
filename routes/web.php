<?php

use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return view('support::up');
});