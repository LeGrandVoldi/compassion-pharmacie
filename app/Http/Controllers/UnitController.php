<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ProductUnit;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    public function index()
    {
        $nom = session('nom');
        $email = session('email');
        $password = session('password');
        $id = session('id');
        $role = session('role');
        $units = Unit::orderByDesc('id')->paginate(10);

        return view('principale.units.index', compact('nom',
            'email',
            'password',
            'id',
            'role','units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:units,name'],
        ]);

        Unit::create([
            'name' => trim($validated['name']),
        ]);

        return redirect()
            ->route('units.index')
            ->with('success', 'Unité ajoutée avec succès.');
    }

    public function json(Unit $unit)
    {
        return response()->json($unit);
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'name')->ignore($unit->id),
            ],
        ]);

        DB::transaction(function () use ($unit, $validated) {
            $oldName = $unit->name;
            $newName = trim($validated['name']);

            $unit->update([
                'name' => $newName,
            ]);

            ProductUnit::query()->where('name', $oldName)->update([
                'name' => $newName,
            ]);
        });

        return redirect()
            ->route('units.index')
            ->with('success', 'Unité modifiée avec succès et mise à jour dans les produits.');
    }
}
