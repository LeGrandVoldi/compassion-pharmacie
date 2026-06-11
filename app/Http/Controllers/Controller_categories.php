<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class Controller_categories extends Controller
{
    public function index(){

        $nom = session('nom');
        $email = session('email');
        $password = session('password');
        $id = session('id');
        $role = session('role');
        $categories = DB::table('categories')
                    ->orderBy('name')
                    ->paginate(10);

       return view('principale.categories.index', compact(
            'nom',
            'email',
            'password',
            'id',
            'role',
            'categories'
        ));
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255','unique:categories,name'],
        ]);

        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'Catégorie ajoutée avec succès.');
    }

    public function showJson(Category $category)
    {
        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255','unique:categories,name,'.$category->id],
        ]);

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Catégorie modifiée avec succès.');
    }

    public function destroy(Category $category)
    {
        Category::query()
                 ->where('id', $category->id)->delete();
        return redirect()->route('categories.index')->with('success', 'Catégorie supprimée.');
    }

}
