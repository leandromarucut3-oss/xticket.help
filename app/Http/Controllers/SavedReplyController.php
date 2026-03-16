<?php

namespace App\Http\Controllers;

use App\Models\SavedReply;
use Illuminate\Http\Request;

class SavedReplyController extends Controller
{
    public function index()
    {
        return response()->json(SavedReply::orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'text' => ['required', 'string', 'max:2000'],
        ]);

        $reply = SavedReply::create([
            'title' => $request->input('title'),
            'text' => $request->input('text'),
            'created_by' => $request->user()?->email ?? 'admin',
        ]);

        return response()->json($reply, 201);
    }

    public function destroy($id)
    {
        $reply = SavedReply::find($id);
        if (! $reply) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $reply->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
