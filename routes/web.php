<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Invite;

Route::get('/', function (Request $request) {
    $check = ensure_invite_access($request);
    if ($check) {
        return $check;
    }
    return response()->file(public_path('US TICKET.html'));
});

Route::get('/chat', function (Request $request) {
    $check = ensure_invite_access($request);
    if ($check) {
        return $check;
    }
    return view('chat');
});

Route::get('/admin', function () {
    return view('admin');
});

// Invite routes
Route::post('/admin/invites', [\App\Http\Controllers\InviteController::class, 'store']);
Route::get('/invite/{token}', [\App\Http\Controllers\InviteController::class, 'showClaimForm']);
Route::post('/invite/claim', [\App\Http\Controllers\InviteController::class, 'claim']);

// Saved replies
Route::get('/admin/saved-replies', [\App\Http\Controllers\SavedReplyController::class, 'index']);
Route::post('/admin/saved-replies', [\App\Http\Controllers\SavedReplyController::class, 'store']);
Route::delete('/admin/saved-replies/{id}', [\App\Http\Controllers\SavedReplyController::class, 'destroy']);
