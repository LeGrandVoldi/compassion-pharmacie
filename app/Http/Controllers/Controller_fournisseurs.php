<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Controller_fournisseurs extends Controller
{
    public function index()
    {
        $nom = session('nom');
        $email = session('email');
        $password = session('password');
        $id = session('id');
        $role = session('role');

        $providers = DB::table('providers')
                    ->orderBy('name')
                    ->paginate(10);
        return view('principale.fournisseurs.index', compact('providers','nom',
            'email',
            'password',
            'id',
            'role'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'phone' => ['nullable','string','max:255'],
            'email' => ['nullable','email','max:255'],
            'address' => ['nullable','string','max:255'],
        ]);

        Provider::create($data);

        return redirect()->route('fournisseurs.index')->with('success', 'Fournisseur ajouté avec succès.');
    }

    public function showJson(Provider $provider)
    {
        return response()->json($provider);
    }

    public function update(Request $request, Provider $provider)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'phone' => ['nullable','string','max:255'],
            'email' => ['nullable','email','max:255'],
            'address' => ['nullable','string','max:255'],
        ]);

        $provider->update($data);

        return redirect()->route('fournisseurs.index')->with('success', 'Fournisseur modifié avec succès.');
    }

    public function destroy(Provider $provider)
    {
        Provider::query()
                 ->where('id', $provider->id)->delete();
        return redirect()->route('fournisseurs.index')->with('success', 'Fournisseur supprimé.');
    }
}
