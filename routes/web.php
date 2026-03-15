<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Invite;

function ensure_invite_access(Request $request)
{
    // Admin path is open
    if ($request->is('admin') || $request->is('admin/*')) {
        return null;
    }

    $sessionToken = session('invite_token');

    if ($sessionToken) {
        $invite = Invite::where('token', $sessionToken)->first();
        if ($invite && (! $invite->expires_at || $invite->expires_at->isFuture() || $invite->used_at)) {
            return null; // allowed via session
        }
    }

    // if token present in query, forward to claim page
    $token = $request->query('token') ?? $request->query('invite');
    if ($token) {
        return redirect('/invite/' . $token);
    }

    return response()->view('invite_denied', ['message' => 'This site is private. You need an invite link to access.'], 403);
}

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
