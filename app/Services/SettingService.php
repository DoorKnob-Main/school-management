<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class SettingService
{
    const CACHE_KEY = 'app_settings_dict';

    /**
     * Get all cached settings as an associative key => value dictionary.
     *
     * @return array
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                return Setting::all()->pluck('value', 'key')->toArray();
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    /**
     * Get a setting value by key with an optional default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->all();
        if (array_key_exists($key, $settings) && !is_null($settings[$key])) {
            return $settings[$key];
        }

        return $default;
    }

    /**
     * Set a single setting value.
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @param string $type
     * @return Setting
     */
    public function set(string $key, $value, string $group = 'general', string $type = 'text'): Setting
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group,
                'type'  => $type,
            ]
        );

        $this->clearCache();
        return $setting;
    }

    /**
     * Batch update text & file settings.
     *
     * @param array $settingsKeyValue [key => value]
     * @param array $groupMapping [key => group]
     * @param array $files [key => UploadedFile]
     * @return void
     */
    public function updateBatch(array $settingsKeyValue, array $groupMapping = [], array $files = []): void
    {
        foreach ($settingsKeyValue as $key => $value) {
            $group = $groupMapping[$key] ?? 'general';
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => $group,
                ]
            );
        }

        // Handle file uploads
        foreach ($files as $key => $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $group = $groupMapping[$key] ?? 'branding';
                $this->uploadFileSetting($key, $file, $group);
            }
        }

        $this->clearCache();
    }

    /**
     * Upload an image file for a setting key, deleting any previous file.
     *
     * @param string $key
     * @param UploadedFile $file
     * @param string $group
     * @return string
     */
    public function uploadFileSetting(string $key, UploadedFile $file, string $group = 'branding'): string
    {
        $existingSetting = Setting::where('key', $key)->first();
        if ($existingSetting && $existingSetting->value) {
            // Delete old file if present in storage
            if (Storage::disk('public')->exists($existingSetting->value)) {
                Storage::disk('public')->delete($existingSetting->value);
            }
        }

        $filename = $key . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('settings', $filename, 'public');

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $path,
                'group' => $group,
                'type'  => 'file',
            ]
        );

        $this->clearCache();
        return $path;
    }

    /**
     * Clear settings cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Export all settings as a JSON string for backup.
     *
     * @return string
     */
    public function exportJson(): string
    {
        $settings = Setting::all(['group', 'key', 'value', 'type'])->toArray();
        return json_encode([
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0',
            'settings' => $settings,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Import settings from JSON data.
     *
     * @param string $jsonContent
     * @return bool
     */
    public function importJson(string $jsonContent): bool
    {
        $data = json_decode($jsonContent, true);
        if (!$data || !isset($data['settings']) || !is_array($data['settings'])) {
            return false;
        }

        foreach ($data['settings'] as $item) {
            if (isset($item['key'])) {
                Setting::updateOrCreate(
                    ['key' => $item['key']],
                    [
                        'group' => $item['group'] ?? 'general',
                        'value' => $item['value'] ?? '',
                        'type'  => $item['type'] ?? 'text',
                    ]
                );
            }
        }

        $this->clearCache();
        return true;
    }
}
