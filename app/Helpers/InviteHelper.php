<?php

use Illuminate\Http\Request;
use App\Models\Invite;

if (! function_exists('ensure_invite_access')) {
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

        $file = base_path('Accessdenied.html');
        if (file_exists($file)) {
            return response()->file($file)->setStatusCode(403);
        }
        return response()->view('invite_denied', ['message' => 'This site is private. You need an invite link to access.'], 403);
    }
}
