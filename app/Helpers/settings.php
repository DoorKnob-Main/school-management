<?php

use App\Services\SettingService;

if (!function_exists('setting')) {
    /**
     * Get / set setting values.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed|SettingService
     */
    function setting($key = null, $default = null)
    {
        $service = app(SettingService::class);

        if (is_null($key)) {
            return $service;
        }

        return $service->get($key, $default);
    }
}

if (!function_exists('setting_asset')) {
    /**
     * Get image URL for setting file, or fallback to default asset.
     *
     * @param string $key
     * @param string|null $defaultAsset
     * @return string|null
     */
    function setting_asset(string $key, ?string $defaultAsset = null): ?string
    {
        $value = setting($key);
        if ($value && \Illuminate\Support\Facades\Storage::disk('public')->exists($value)) {
            return asset('storage/' . $value);
        }

        if ($value && (str_startsWith($value, 'http://') || str_startsWith($value, 'https://') || str_startsWith($value, '/'))) {
            return asset($value);
        }

        return $defaultAsset ? asset($defaultAsset) : null;
    }
}
