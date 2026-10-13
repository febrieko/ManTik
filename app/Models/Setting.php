<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group'];

    /**
     * Get a setting by key with optional default
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if ($setting === null) {
            return $default;
        }

        $val = $setting->value;
        if ($val === 'true') return true;
        if ($val === 'false') return false;

        return $val;
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $group = 'general'): self
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    /**
     * Get multiple settings as key-value pairs
     */
    public static function getMany(array $keysWithDefaults): array
    {
        $results = [];
        foreach ($keysWithDefaults as $key => $default) {
            $results[$key] = self::get($key, $default);
        }
        return $results;
    }

    /**
     * Set multiple settings
     */
    public static function setMany(array $settings, string $group = 'general'): void
    {
        foreach ($settings as $key => $value) {
            self::set($key, $value, $group);
        }
    }
}
