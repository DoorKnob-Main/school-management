<?php

namespace App\Http\Controllers;

use App\Models\FeeComponentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeComponentTypeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $types = FeeComponentType::withCount('structureComponents')->get();
        return view('finance.fee-component-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'calc_type' => 'required|in:fixed,percentage',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->has('is_active');
        FeeComponentType::create($validated);

        return redirect()->route('finance.fee-component-types.index')->with('success', 'Fee component created.');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $type = FeeComponentType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'calc_type' => 'required|in:fixed,percentage',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $type->update($validated);

        return redirect()->route('finance.fee-component-types.index')->with('success', 'Fee component updated.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isAdminOrSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $type = FeeComponentType::findOrFail($id);
        $type->delete();

        return redirect()->route('finance.fee-component-types.index')->with('success', 'Fee component deleted.');
    }
}
