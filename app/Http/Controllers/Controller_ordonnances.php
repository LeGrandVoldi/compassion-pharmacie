<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Controller_ordonnances extends Controller
{
    public function index(){

        $nom = session('nom');
        $email = session('email');
        $password = session('password');
        $id = session('id');
        $role = session('role');

       return view('principale.ordonnances.index', compact(
            'nom',
            'email',
            'password',
            'id',
            'role'
        ));
    }
}
