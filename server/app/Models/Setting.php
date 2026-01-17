<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue, 'type' => $type, 'group' => $group]
        );

        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllSettings(): array
    {
        $settings = Cache::remember('settings.all', 3600, function () {
            return self::all();
        });

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Get settings by group
     */
    public static function getByGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Cast value based on type
     */
    protected static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Seed default settings
     */
    public static function seedDefaults(): void
    {
        $defaults = [
            // General
            ['key' => 'platform_name', 'value' => 'Event Reliability Platform', 'type' => 'string', 'group' => 'general', 'description' => 'Platform display name'],
            ['key' => 'support_email', 'value' => 'support@eventreliability.com', 'type' => 'string', 'group' => 'general', 'description' => 'Support email address'],
            ['key' => 'support_phone', 'value' => '+91 1800-123-4567', 'type' => 'string', 'group' => 'general', 'description' => 'Support phone number'],

            // Financial
            ['key' => 'assurance_fee_percent', 'value' => '5', 'type' => 'float', 'group' => 'financial', 'description' => 'Assurance fee percentage'],
            ['key' => 'commission_percent', 'value' => '10', 'type' => 'float', 'group' => 'financial', 'description' => 'Platform commission percentage'],
            ['key' => 'advance_percent', 'value' => '30', 'type' => 'float', 'group' => 'financial', 'description' => 'Advance payment percentage'],

            // Emergency
            ['key' => 'emergency_bonus_multiplier', 'value' => '1.5', 'type' => 'float', 'group' => 'emergency', 'description' => 'Emergency bonus multiplier for vendors'],
            ['key' => 'emergency_window_hours', 'value' => '4', 'type' => 'integer', 'group' => 'emergency', 'description' => 'Hours for emergency response'],
            ['key' => 'max_backup_assignments', 'value' => '3', 'type' => 'integer', 'group' => 'emergency', 'description' => 'Maximum backup vendors per booking'],

            // Vendor
            ['key' => 'default_reliability_score', 'value' => '5.0', 'type' => 'float', 'group' => 'vendor', 'description' => 'Default reliability score for new vendors'],
            ['key' => 'min_backup_score', 'value' => '4.0', 'type' => 'float', 'group' => 'vendor', 'description' => 'Minimum score for backup eligibility'],
            ['key' => 'auto_verify_vendors', 'value' => 'false', 'type' => 'boolean', 'group' => 'vendor', 'description' => 'Auto-verify new vendors'],
        ];

        foreach ($defaults as $setting) {
            self::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        Cache::forget('settings.all');
    }
}
