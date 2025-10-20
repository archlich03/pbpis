<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAIService
{
    private string $apiKey;
    private string $model = 'gemini-2.0-flash-lite';
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Generate meeting summary from discussion comments.
     *
     * @param array $comments Array of comments with 'name' and 'content' keys
     * @param string $questionTitle The question title for context
     * @return array ['success' => bool, 'summary' => string|null, 'error' => string|null]
     */
    public function generateMeetingSummary(array $comments, string $questionTitle): array
    {
        if (empty($this->apiKey)) {
            $error = 'Gemini API key not configured';
            Log::error($error);
            return ['success' => false, 'summary' => null, 'error' => $error];
        }

        if (empty($comments)) {
            $error = 'No comments provided';
            return ['success' => false, 'summary' => null, 'error' => $error];
        }

        // Build the prompt
        $prompt = $this->buildPrompt($comments, $questionTitle);

        try {
            $response = Http::timeout(30)
                ->post($this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 1024,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $summary = trim($data['candidates'][0]['content']['parts'][0]['text']);
                    return ['success' => true, 'summary' => $summary, 'error' => null];
                }
                
                $error = 'Invalid response structure from Gemini API';
                Log::error($error, ['response' => $data]);
                return ['success' => false, 'summary' => null, 'error' => $error];
            }

            $error = 'Gemini API request failed: HTTP ' . $response->status();
            Log::error($error, [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['success' => false, 'summary' => null, 'error' => $error . ' - ' . substr($response->body(), 0, 200)];
        } catch (\Exception $e) {
            $error = 'Gemini API exception: ' . $e->getMessage();
            Log::error($error, [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'summary' => null, 'error' => $error];
        }
    }

    /**
     * Build the prompt for AI summary generation.
     *
     * @param array $comments
     * @param string $questionTitle
     * @return string
     */
    private function buildPrompt(array $comments, string $questionTitle): string
    {
        $lines = [];
        $lines[] = 'Tu esi posėdžio sekretorius. Tavo užduotis – parengti santrauką, tinkamą įtraukti tiesiai į posėdžio protokolą.';
        $lines[] = '';
        $lines[] = 'Santrauka turi būti:';
        $lines[] = '- formali ir neutrali;';
        $lines[] = '- išlaikyti diskusijos struktūrą: kas ką pasakė, ką pasiūlė, kam pritarė ar nepritarė;';
        $lines[] = '- vengti interpretacijų (pvz., "diskusija buvo neigiama");';
        $lines[] = '- jei dalyviai mini konkrečius aspektus, juos trumpai paminėk;';
        $lines[] = '- jei komentarai rodo nesutarimą, tiesiog pažymėk tai neutraliu tonu (pvz., "dalyviai išsakė skirtingas nuomones").';
        $lines[] = '- jei naudojami stiprūs / grubūs žodžiai, pakeisk silpnesniais';
        $lines[] = '';
        $lines[] = 'Naudok formatą:';
        $lines[] = 'Vardas Pavardė pažymi / siūlo / pritaria / abejoja / komentuoja, kad ... (gale nauja eilutė)';
        $lines[] = 'Atsakyk lietuvių kalba.';
        $lines[] = '';
        $lines[] = 'Interpretuok šiuos komentarus:';
        
        $systemPrompt = implode("\n", $lines);

        $commentsText = '';
        foreach ($comments as $comment) {
            $commentsText .= "\n[{$comment['name']}]\n{$comment['content']}\n";
        }

        return $systemPrompt . $commentsText;
    }

    /**
     * Truncate summary to fit database column limit.
     *
     * @param string $summary
     * @param int $maxLength
     * @return string
     */
    public function truncateSummary(string $summary, int $maxLength = 5000): string
    {
        if (mb_strlen($summary) <= $maxLength) {
            return $summary;
        }

        // Truncate and add ellipsis
        return mb_substr($summary, 0, $maxLength - 3) . '...';
    }
}
