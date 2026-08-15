<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Translation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'column',
        'value',
        'source_hash',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
