<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** Admin-editable settings (key => text). Reads fall back to the default if the table is not migrated yet. */
class AppSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function read(string $key, ?string $default = null): ?string
    {
        try {
            if (! Schema::hasTable('app_settings')) {
                return $default;
            }

            return static::query()->whereKey($key)->value('value') ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function put(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
