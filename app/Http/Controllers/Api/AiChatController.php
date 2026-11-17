<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiAssistantService;
use Illuminate\Http\Request;
use Throwable;

class AiChatController extends Controller
{
    public function chat(Request $request, AiAssistantService $assistant)
    {
        $data = $request->validate([
            'message'           => 'required|string|max:500',
            'history'           => 'nullable|array|max:10',
            'history.*.role'    => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:1000',
        ]);

        try {
            $reply = $assistant->reply($data['message'], $data['history'] ?? []);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'The assistant is unavailable right now. Please try again.',
                'errors'  => (object) [],
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => ['reply' => $reply],
        ]);
    }
}
