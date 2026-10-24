<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/** The photo / video / audio a quiz question can show above its text. */
class QuestionMedia
{
    public const TYPES = ['photo', 'video', 'audio'];

    /** Upload rules for the three kinds. */
    public static function rules(string $type): array
    {
        return match ($type) {
            'photo' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'audio' => ['file', 'mimes:mp3,m4a,aac,wav,ogg,oga', 'max:30720'],
            'video' => ['file', 'mimes:mp4,mov,webm,m4v', 'max:51200'],
            default => ['prohibited'],
        };
    }

    public static function url(?string $path): ?string
    {
        return $path ? asset('storage/'.$path) : null;
    }

    /** The two keys every API payload adds to a question. */
    public static function payload(?string $type, ?string $path): array
    {
        $has = $type && $path;

        return ['media_type' => $has ? $type : null, 'media_url' => $has ? self::url($path) : null];
    }
}
