<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SettingService;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Display the settings management view.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access the settings module.');
        }

        $settings = $this->settingService->all();
        return view('settings.index', compact('settings'));
    }

    /**
     * Update application settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can modify white-label settings.');
        }

        $settingsData = $request->except(['_token', '_method', 'active_tab']);
        $files = $request->allFiles();
        $groupMapping = $request->input('_groups', []);

        // Filter out non-setting inputs
        unset($settingsData['_groups']);

        // Process text, select, boolean values
        $textSettings = [];
        foreach ($settingsData as $key => $value) {
            if (!in_array($key, array_keys($files))) {
                $textSettings[$key] = is_array($value) ? json_encode($value) : (string)$value;
            }
        }

        $this->settingService->updateBatch($textSettings, $groupMapping, $files);

        $activeTab = $request->input('active_tab', 'branding');

        return redirect()->route('settings.index', ['tab' => $activeTab])
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Export settings to JSON backup file.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportJson()
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $json = $this->settingService->exportJson();
        $filename = 'doorknob_settings_backup_' . date('Y-m-d_H-i-s') . '.json';

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import settings from uploaded JSON file.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importJson(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'settings_json' => 'required|file|mimes:json,txt|max:2048',
        ]);

        $file = $request->file('settings_json');
        $content = file_get_contents($file->getRealPath());

        if ($this->settingService->importJson($content)) {
            return redirect()->route('settings.index', ['tab' => 'advanced'])
                ->with('success', 'Settings imported successfully.');
        }

        return redirect()->route('settings.index', ['tab' => 'advanced'])
            ->with('error', 'Failed to import settings. Invalid JSON structure.');
    }
}
