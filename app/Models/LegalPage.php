<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\LegalPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    /** @use HasFactory<LegalPageFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'body',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'body',
        'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function translationUsesHtml(): bool
    {
        return true;
    }
}
