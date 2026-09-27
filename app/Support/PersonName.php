<?php

namespace App\Support;

use App\Enums\RegisterVisibility;
use App\Models\User;

class PersonName
{
    /**
     * @return array{first_name: string, last_name: string}
     */
    public static function split(string $name): array
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));

        if ($name === '') {
            return ['first_name' => '', 'last_name' => ''];
        }

        $position = mb_strrpos($name, ' ');

        if ($position === false) {
            return ['first_name' => $name, 'last_name' => ''];
        }

        return [
            'first_name' => mb_substr($name, 0, $position),
            'last_name' => mb_substr($name, $position + 1),
        ];
    }

    public static function compose(string $firstName, string $lastName): string
    {
        return trim($firstName.' '.$lastName);
    }

    public static function given(User $user): string
    {
        if (filled($user->first_name) || filled($user->last_name)) {
            return trim((string) $user->first_name);
        }

        return self::split($user->name)['first_name'];
    }

    public static function family(User $user): string
    {
        if (filled($user->first_name) || filled($user->last_name)) {
            return trim((string) $user->last_name);
        }

        return self::split($user->name)['last_name'];
    }

    public static function initial(string $lastName, string $firstName): string
    {
        $source = $lastName !== '' ? $lastName : $firstName;
        $letter = mb_substr($source, 0, 1);

        if ($letter === '') {
            return '';
        }

        return mb_strtoupper($letter).'.';
    }

    /**
     * Account-derived public name. Private listings return an empty string;
     * the presenter uses the Privélid label instead.
     */
    public static function publicLabel(User $user, RegisterVisibility $visibility): string
    {
        $first = self::given($user);
        $last = self::family($user);

        return match ($visibility) {
            RegisterVisibility::Full => self::compose($first, $last),
            RegisterVisibility::Initial => trim($first.' '.self::initial($last, $first)),
            RegisterVisibility::Private => '',
        };
    }
}
