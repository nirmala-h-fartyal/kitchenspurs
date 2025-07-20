<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', 'AIzaSyAI8xEFv1bNqddCfz2GStu8Xn_-9DUAR6A');
        $this->apiUrl = env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent');
    }

    public function generateSlug(string $title, string $content): string
    {
        $prompt = "Generate a unique, SEO-friendly URL slug (max 60 characters) for an article with the following title and content. The slug should be lowercase, use hyphens instead of spaces, and be descriptive. Only return the slug, nothing else.\n\nTitle: {$title}\n\nContent: " . substr($content, 0, 500) . "...";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $this->apiKey
            ])->post($this->apiUrl, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            Log::info('Gemini API Slug Response Status: ' . $response->status());

            if ($response->successful()) {
                $data = $response->json();
                $slug = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
                Log::info('Extracted Slug: ' . $slug);
                
                $slug = strtolower($slug);
                $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
                $slug = preg_replace('/-+/', '-', $slug);
                $slug = trim($slug, '-');
                
                if (!empty($slug) && $slug !== \Str::slug($title)) {
                    return $slug;
                }
            } else {
                Log::error('Gemini API Slug failed with status: ' . $response->status());
                Log::error('Gemini API Slug error response: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Gemini API error for slug generation: ' . $e->getMessage());
        }

        return \Str::slug($title);
    }

    public function generateSummary(string $content): string
    {
        $prompt = "Generate a brief, engaging summary (2-3 sentences, max 200 characters) of the following article content. Focus on the main points and make it compelling for readers. Only return the summary, nothing else.\n\nContent: " . substr($content, 0, 1000) . "...";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $this->apiKey
            ])->post($this->apiUrl, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            Log::info('Gemini API Response Status: ' . $response->status());
            Log::info('Gemini API Response Body: ' . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Gemini API JSON Response: ' . json_encode($data));
                
                $summary = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
                Log::info('Extracted Summary: ' . $summary);
                
                $summary = strip_tags($summary);
                $summary = preg_replace('/\s+/', ' ', $summary);
                
                if (!empty($summary) && $summary !== substr($content, 0, 150) . '...') {
                    return $summary;
                }
            } else {
                Log::error('Gemini API failed with status: ' . $response->status());
                Log::error('Gemini API error response: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Gemini API error for summary generation: ' . $e->getMessage());
            Log::error('Gemini API error trace: ' . $e->getTraceAsString());
        }

        return substr($content, 0, 150) . '...';
    }
} 