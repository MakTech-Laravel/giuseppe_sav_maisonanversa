<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use Database\Factories\PartnerClubFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerClub extends Model
{
    /** @use HasFactory<PartnerClubFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'city',
        'country',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'city',
        'country',
        'status',
        'sort_order',
        'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }
}
