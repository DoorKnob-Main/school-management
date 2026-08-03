<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminController extends Controller
{
    /**
     * Switch current impersonated role for Super Admin.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switchRole(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'role' => 'required|string|in:admin,teacher,student,super_admin',
        ]);

        if ($request->role === 'super_admin') {
            session()->forget('impersonated_role');
            return redirect()->back()->with('success', 'Returned to Super Admin mode.');
        }

        session(['impersonated_role' => $request->role]);

        return redirect()->back()->with('success', 'Switched context to role: ' . ucfirst($request->role));
    }

    /**
     * Exit role impersonation and return to Super Admin context.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exitImpersonation()
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        session()->forget('impersonated_role');

        return redirect()->back()->with('success', 'Exited impersonation. You are now in Super Admin mode.');
    }
}
