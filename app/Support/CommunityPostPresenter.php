<?php

namespace App\Support;

use Illuminate\Support\Str;

final class CommunityPostPresenter
{
    public const EXCERPT_LIMIT = 150;

    public static function excerpt(string $content, int $limit = self::EXCERPT_LIMIT): string
    {
        return Str::limit(trim($content), $limit);
    }

    public static function isTruncated(string $content, int $limit = self::EXCERPT_LIMIT): bool
    {
        return mb_strlen(trim($content)) > $limit;
    }
}
