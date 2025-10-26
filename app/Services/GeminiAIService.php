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
        $lines[] = 'SVARBU: Santrauka turi būti VIENAS LOGIŠKAS TEKSTAS, ne atskirų komentarų sąrašas.';
        $lines[] = '';
        $lines[] = 'Santrauka turi:';
        $lines[] = '1. Apjungti visus komentarus į vieną logiką diskusijos eigą';
        $lines[] = '2. Rodyti diskusijos raidą: kas pradėjo, kas pritarė, kas abejojo, kas papildė';
        $lines[] = '3. Jei yra atsakymai į komentarus (pažymėti "atsakymas į komentarą #X"), juos integruoti į bendrą naratyvą';
        $lines[] = '4. Jei keli dalyviai išsako panašias nuomones, apjungti jas (pvz., "V. P. ir R. S. pritarė...")';
        $lines[] = '5. Būti formali ir neutrali, vengti interpretacijų';
        $lines[] = '6. Jei naudojami stiprūs žodžiai, pakeisti silpnesniais';
        $lines[] = '';
        $lines[] = 'BLOGAS pavyzdys (atskirų komentarų sąrašas):';
        $lines[] = 'V. P. pažymi, kad... V. P. abejoja... R. S. komentuoja...';
        $lines[] = '';
        $lines[] = 'GERAS pavyzdys (vientisas naratyvas):';
        $lines[] = 'V. P. pažymėjo, kad tikslinga patvirtinti studento prašymą. Diskusijos metu jis išsakė abejonių dėl tam tikrų aspektų, į kuriuos atsakydamas R. S. pabrėžė precedento sukūrimo svarbą. Dalyviai sutarė dėl...';
        $lines[] = '';
        $lines[] = 'Rašyk lietuvių kalba, naudok būtąjį laiką (pažymėjo, išsakė, pasiūlė).';
        $lines[] = '';
        $lines[] = 'Klausimas: ' . $questionTitle;
        $lines[] = '';
        $lines[] = 'Komentarai chronologine tvarka:';
        
        $systemPrompt = implode("\n", $lines);

        $commentsText = '';
        foreach ($comments as $comment) {
            $commentId = $comment['id'] ?? 'unknown';
            
            // If this is a reply to another comment, indicate that
            if (!empty($comment['parent_id'])) {
                $commentsText .= "\n[Komentaras #{$commentId}] [{$comment['name']}] (atsakymas į komentarą #{$comment['parent_id']})\n{$comment['content']}\n";
            } else {
                $commentsText .= "\n[Komentaras #{$commentId}] [{$comment['name']}]\n{$comment['content']}\n";
            }
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
