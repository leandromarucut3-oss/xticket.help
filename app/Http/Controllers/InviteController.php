<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InviteController extends Controller
{
    // Admin creates an invite
    public function store(Request $request)
    {
        $request->validate([
            'username' => ['nullable', 'string', 'max:255'],
            'expires_days' => ['nullable', 'integer'],
        ]);

        $token = Str::random(40);

        $invite = Invite::create([
            'token' => $token,
            'username' => $request->input('username'),
            'created_by' => $request->user()?->email ?? 'admin',
            'expires_at' => $request->filled('expires_days') ? now()->addDays((int) $request->input('expires_days')) : null,
        ]);

        $link = url('/invite/' . $invite->token);

        return response()->json(['link' => $link]);
    }

    // Show claim form for the invite token
    public function showClaimForm($token)
    {
        // Auto-accept the invite: validate token, mark used, and redirect to site
        $invite = Invite::where('token', $token)->first();

        if (! $invite) {
            return response()->view('invite_denied', ['message' => 'Invalid invite token'], 404);
        }

        if ($invite->used_at) {
            return response()->view('invite_denied', ['message' => 'This invite has already been used.'], 403);
        }

        if ($invite->expires_at && $invite->expires_at->isPast()) {
            return response()->view('invite_denied', ['message' => 'This invite has expired.'], 403);
        }

        // mark used immediately and set session so user can access the site
        $invite->used_at = now();
        $invite->save();

        session(['invite_token' => $invite->token, 'invite_username' => $invite->username]);

        // Show a short wait/splash page then redirect to the site
        return view('invite_wait', ['redirect' => url('/'), 'seconds' => 3]);
    }

    // Claim (bind) the invite to a visitor session
    public function claim(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'username' => ['required', 'string', 'max:255'],
        ]);

        $invite = Invite::where('token', $request->input('token'))->first();

        if (! $invite) {
            return redirect('/')->with('invite_error', 'Invalid invite token.');
        }

        if ($invite->used_at) {
            return redirect('/')->with('invite_error', 'This invite was already used.');
        }

        if ($invite->expires_at && $invite->expires_at->isPast()) {
            return redirect('/')->with('invite_error', 'This invite has expired.');
        }

        if ($invite->username && $invite->username !== $request->input('username')) {
            return back()->withErrors(['username' => 'Username does not match the invited username.']);
        }

        // mark used
        $invite->used_at = now();
        $invite->save();

        // set session to allow access
        session(['invite_token' => $invite->token, 'invite_username' => $request->input('username')]);

        return redirect('/');
    }
}
