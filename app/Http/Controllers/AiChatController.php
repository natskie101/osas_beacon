<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\NvidiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    public function __construct(
        protected NvidiaService $nvidia
    ) {
    }

    /**
     * Standard JSON chat response.
     */
    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:4000',
        ]);

        if (!$this->nvidia->isConfigured()) {
            return response()->json([
                'status' => 'error',
                'message' => 'NVIDIA_API_KEY is not configured in .env',
            ], 503);
        }

        // The model is never taken from user input: callers could otherwise
        // request arbitrary (more expensive) models from the upstream API.
        $reply = $this->nvidia->chat(
            messages: [
                ['role' => 'system', 'content' => 'You are an intelligent assistant for the BEACON OSAS Student Portal.'],
                ['role' => 'user', 'content' => $validated['prompt']],
            ]
        );

        return response()->json([
            'status' => 'success',
            'reply' => $reply,
        ]);
    }

    /**
     * Real-time streaming response via Server-Sent Events (SSE).
     */
    public function stream(Request $request): StreamedResponse
    {
        // Clamp untrusted GET input to the same limit the JSON endpoint enforces.
        $prompt = mb_substr(trim((string) $request->query('prompt', 'Hello')), 0, 4000) ?: 'Hello';

        if (!$this->nvidia->isConfigured()) {
            return response()->stream(function () {
                echo "data: " . json_encode(['error' => 'NVIDIA_API_KEY is not configured in .env']) . "\n\n";
            }, 503, [
                'Content-Type' => 'text/event-stream',
            ]);
        }

        return response()->stream(function () use ($prompt) {
            $this->nvidia->streamChat(
                messages: [
                    ['role' => 'system', 'content' => 'You are an intelligent assistant for the BEACON OSAS Student Portal.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                onChunk: function (string $chunk) {
                    echo "data: " . json_encode(['text' => $chunk]) . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            );
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
