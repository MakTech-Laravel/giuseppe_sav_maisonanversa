<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use App\Support\Imagery;
use Database\Factories\DressingItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DressingItem extends Model
{
    /** @use HasFactory<DressingItemFactory> */
    use HasFactory, TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    protected array $translatable = [
        'name',
        'category',
        'description',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'image_key',
        'image_path',
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

    /**
     * Browser-ready URL for the item's cover: an uploaded image takes
     * priority over the seeded Imagery asset key.
     */
    public function resolvedImageUrl(): ?string
    {
        if ($this->image_path !== null && $this->image_path !== '') {
            return Storage::disk('public')->url($this->image_path);
        }

        if ($this->image_key !== null && $this->image_key !== '') {
            return Imagery::assetUrl($this->image_key);
        }

        return null;
    }
}
