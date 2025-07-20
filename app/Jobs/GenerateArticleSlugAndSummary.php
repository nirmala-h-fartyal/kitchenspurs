<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateArticleSlugAndSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 3;

    public function __construct(
        private Article $article
    ) {}

    public function handle(GeminiService $geminiService): void
    {
        try {
            Log::info('Starting slug and summary generation for article ID: ' . $this->article->id);

            $slug = $geminiService->generateSlug($this->article->title, $this->article->content);
            
            $originalSlug = $slug;
            $counter = 1;
            while (Article::where('slug', $slug)->where('id', '!=', $this->article->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $summary = $geminiService->generateSummary($this->article->content);

            $this->article->update([
                'slug' => $slug,
                'summary' => $summary,
            ]);

            Log::info('Successfully generated slug and summary for article ID: ' . $this->article->id);
            Log::info('Generated slug: ' . $slug);
            Log::info('Generated summary: ' . substr($summary, 0, 100) . '...');

        } catch (\Exception $e) {
            Log::error('Error generating slug and summary for article ID: ' . $this->article->id);
            Log::error('Error: ' . $e->getMessage());
            
            $this->article->update([
                'slug' => \Str::slug($this->article->title),
                'summary' => substr($this->article->content, 0, 150) . '...',
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job failed for article ID: ' . $this->article->id);
        Log::error('Error: ' . $exception->getMessage());
        
        $this->article->update([
            'slug' => \Str::slug($this->article->title),
            'summary' => substr($this->article->content, 0, 150) . '...',
        ]);
    }
} 