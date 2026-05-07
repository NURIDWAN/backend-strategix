<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
    ];

    public $timestamps = true;

    /**
     * Get the typed value.
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->value) ? (float) $this->value : 0,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set the typed value.
     */
    public function setTypedValue($val): void
    {
        $this->value = match ($this->type) {
            'boolean' => $val ? 'true' : 'false',
            'json' => is_string($val) ? $val : json_encode($val),
            default => (string) $val,
        };
    }

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, $value): void
    {
        $setting = self::where('key', $key)->first();
        if ($setting) {
            $setting->setTypedValue($value);
            $setting->save();
        }
    }
}
