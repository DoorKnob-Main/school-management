<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SettingService;

class EnsureBiometricEnabled
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $enabled = $this->settingService->get('biometric_attendance_enabled', '1');

        if ($enabled !== '1' && $enabled !== 1 && $enabled !== true && $enabled !== 'true') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Biometric Attendance feature is currently disabled by Super Admin.',
                ], 403);
            }

            return redirect()->route('home')->with('error', 'Biometric Attendance module is currently disabled by Super Admin.');
        }

        return $next($request);
    }
}
