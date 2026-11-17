<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiAssistantService
{
    public function reply(string $message, array $history = []): string
    {
        $key = config('services.gemini.key');
        $model = config('services.gemini.model');

        if (! $key) {
            throw new RuntimeException('AI is not configured');
        }

        $contents = [];
        foreach ($history as $item) {
            $contents[] = [
                'role'  => $item['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $item['content']]],
            ];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        $response = Http::withHeaders(['x-goog-api-key' => $key])
            ->timeout(25)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'systemInstruction' => ['parts' => [['text' => $this->systemPrompt()]]],
                'contents'          => $contents,
                'generationConfig'  => ['temperature' => 0.5, 'maxOutputTokens' => 600],
            ]);

        if ($response->failed()) {
            logger()->error('Gemini error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('AI provider error');
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! $text) {
            throw new RuntimeException('Empty AI response');
        }

        return trim($text);
    }

    private function systemPrompt(): string
    {
        $catalog = Service::with('category:id,name')
            ->where('is_active', true)
            ->get()
            ->map(fn($s) => sprintf(
                '- %s (category: %s, price: $%s, duration: %d min): %s',
                $s->name,
                $s->category?->name ?? 'General',
                number_format($s->price, 2),
                $s->duration_minutes,
                $s->description
            ))
            ->implode("\n");

        return <<<PROMPT
You are the friendly assistant of a service booking app. Help users learn about our services and choose the right one.

Rules:
- Use ONLY the services listed below. Never invent services, prices or durations.
- If the user asks for something we don't offer, say so politely and suggest the closest available service.
- Recommend 1 to 3 services based on the user's needs, with price and duration.
- Keep answers short (under 120 words) and plain text.
- To book, tell the user to open the service in the app and tap Book Now.
- Politely decline questions unrelated to our services.

Available services:
{$catalog}
PROMPT;
    }
}
