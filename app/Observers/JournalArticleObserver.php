<?php

namespace App\Observers;

use App\Models\JournalArticle;
use Illuminate\Support\Facades\Cache;

class JournalArticleObserver
{
    public function saved(JournalArticle $article): void
    {
        Cache::forget('maison:sitemap:xml');
    }

    public function deleted(JournalArticle $article): void
    {
        Cache::forget('maison:sitemap:xml');
    }
}
