<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Controller_paiements extends Controller
{
    public function index(){

        $nom = session('nom');
        $email = session('email');
        $password = session('password');
        $id = session('id');
        $role = session('role');

       return view('principale.paiements.index', compact(
            'nom',
            'email',
            'password',
            'id',
            'role'
        ));
    }
}
