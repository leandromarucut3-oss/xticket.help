<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->file(public_path('US TICKET.html'));
});

Route::get('/chat', function () {
    return view('chat');
});

Route::get('/admin', function () {
    return view('admin');
});
