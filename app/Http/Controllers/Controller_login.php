<?php

namespace App\Http\Controllers;

use App\Models\MediaUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use App\Models\userAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use App\Models\Role;

class Controller_login extends Controller
{
    public function index(){
        if (session()->has('id')) { // adapte selon ton système
            return redirect()->route('dashboard');
        }
       return view('login.index');
    }
    public function inscription(){
       return view('login.inscription');
    }
    public function connexion(Request $request){

        $user = User::query()
                ->where('email', $request->email)
                ->first();

        if (!$user){
            return response()->json([
                'status' => false,
                'message' => 'Adresse mail ou mot de passe non valide.'
            ]);
        }
        $passwordHash = Crypt::decrypt($user->password);
        if($passwordHash != $request->password ){
            return response()->json([
                'status' => false,
                'message' => 'Adresse mail ou mot de passe non valide.'
            ]);
        }else{

            // $code = random_int(10000, 99999);
            // $data = [
            //     'nom'     => $user->name,
            //     'email'   => $user->email,
            //     'password' => $user->password,
            //     'code' => $code,
            // ];

            // // Mail de confirmation vers l'utilisateur
            // Mail::send('emails.confirmation', $data, function ($mail) use ($data) {
            //     $mail->to($data['email'])
            //         ->subject('Code à 5 chiffres - COMPASSION PHARMACIE')
            //         ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            // });

            return response()->json([
                    'status' => true,
                    'message' => 'OK',
                    'code' => 1000,
            ]);

        }

    }

    public function register(Request $request)
    {

        // Vérification email dans user_admins
        $admin = userAdmin::query()
                ->where('email', $request->email)
                ->first();
        $admin1 = User::query()
                ->where('email', $request->email)
                ->first();

        // Si email introuvable
        if (!$admin) {

            return response()->json([
                'status' => false,
                'message' => 'Vous ne pouvez pas vous enregistrer avec cette adresse mail.'
            ]);
        }
        if($admin1) {

            return response()->json([
                'status' => false,
                'message' => 'Adresse mail déjà dans le système.'
            ]);

        }

        $code = random_int(10000, 99999);
        $data = [
            'nom'     => $request->prenom,
            'email'   => $request->email,
            'password' => $request->password,
            'confirmPassword'   => $request->confirmPassword,
            'code' => $code,
        ];
        // Mail de confirmation vers l'utilisateur
        Mail::send('emails.confirmation', $data, function ($mail) use ($data) {
            $mail->to($data['email'])
                ->subject('Code à 5 chiffres - COMPASSION PHARMACIE')
                ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        });

        return response()->json([
                'status' => true,
                'message' => 'OK',
                'code' => $code,
        ]);
    }
    public function validation_inscription(Request $request){

        $existingUser = User::query()
                        ->where('email', $request->email)
                        ->first();

        // Connexion
        if ($existingUser) {
            $id = $existingUser->id;

            $permissions = [];

            if ($existingUser->role === 'admin') {
                $permissions = ['all' => 1];

                session([
                    'nom' => $existingUser->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'id' => $id,
                    'role' => $existingUser->role,
                    'permissions' => $permissions,
                ]);

                return redirect()->route('dashboard');
            } else {
                $roles = Role::query()
                         ->where('user_id', $id)->get();

                foreach ($roles as $roleRow) {
                    $permissions[$roleRow->module] = [
                        'add' => (int) $roleRow->add,
                        'edit' => (int) $roleRow->edit,
                        'delete' => (int) $roleRow->delete,
                        'view' => (int) $roleRow->view,
                        'download' => (int) $roleRow->download,
                    ];
                }

                $hasPermission = false;
                foreach ($permissions as $modulePermissions) {
                    foreach ($modulePermissions as $permission) {
                        if ($permission == 1) {
                            $hasPermission = true;
                            break 2;
                        }
                    }
                }

                if (!$hasPermission) {
                    return redirect()
                        ->route('login')
                        ->with('error', 'Votre compte n\'a aucune permission attribuée.');
                }

                session([
                    'nom' => $existingUser->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'id' => $id,
                    'role' => $existingUser->role,
                    'permissions' => $permissions,
                ]);

                return redirect()->route('dashboard');
            }


        }


        // Inscription
        $user = User::create([
            'name' => $request->nom,
            'email' => $request->email,
            'password' => Crypt::encrypt($request->password),
            'role' => 'admin'
        ]);

        $id = $user->id;

        session([
            'nom' => $request->nom,
            'email' => $request->email,
            'password' => $request->password,
            'id' => $id,
            'role' => 'admin',
        ]);

        return redirect()->route('dashboard');

    }
    public function confirmEmail(Request $request)
    {

    $existingUser = User::query()
                            ->where('email', $request->email)
                            ->first();
    $id = $existingUser->id;
    $url = MediaUser::query()
                ->where('user_id', $id)
                ->first();

            // Connexion
            if ($existingUser) {


                $permissions = [];

                if ($existingUser->role === 'admin') {
                    $permissions = ['all' => 1];

                    session([
                        'nom' => $existingUser->name,
                        'email' => $request->email,
                        'password' => $request->password,
                        'id' => $id,
                        'role' => $existingUser->role,
                        'permissions' => $permissions,
                        'profileImage' =>$url->path,
                    ]);

                    return redirect()->route('dashboard');
                } else {
                    $roles = Role::query()
                            ->where('user_id', $id)->get();

                    foreach ($roles as $roleRow) {
                        $permissions[$roleRow->module] = [
                            'add' => (int) $roleRow->add,
                            'edit' => (int) $roleRow->edit,
                            'delete' => (int) $roleRow->delete,
                            'view' => (int) $roleRow->view,
                            'download' => (int) $roleRow->download,
                        ];
                    }

                    $hasPermission = false;
                    foreach ($permissions as $modulePermissions) {
                        foreach ($modulePermissions as $permission) {
                            if ($permission == 1) {
                                $hasPermission = true;
                                break 2;
                            }
                        }
                    }

                    if (!$hasPermission) {
                        return redirect()
                            ->route('login')
                            ->with('error', 'Votre compte n\'a aucune permission attribuée.');
                    }

                    session([
                        'nom' => $existingUser->name,
                        'email' => $request->email,
                        'password' => $request->password,
                        'id' => $id,
                        'role' => $existingUser->role,
                        'permissions' => $permissions,
                        'profileImage' =>$url->path,

                    ]);

                    return redirect()->route('dashboard');
                }


            }

        //  return redirect()->route('confirmEmail1', [
        //                                         'nom' =>Crypt::encrypt($request->nom),
        //                                         'email' => Crypt::encrypt($request->email),
        //                                         'password' => Crypt::encrypt($request->password),
        //                                         'code' => Crypt::encrypt($request->code),
        //                                     ]);


        // // Inscription
        // $user = User::create([
        //     'name' => $request->nom,
        //     'email' => $request->email,
        //     'password' => Crypt::encrypt($request->password),
        //     'role' => 'admin'
        // ]);

        // $id = $user->id;

        // session([
        //     'nom' => $request->nom,
        //     'email' => $request->email,
        //     'password' => $request->password,
        //     'id' => $id,
        //     'role' => 'admin',
        // ]);

        // return redirect()->route('dashboard');

    }
    public function confirmEmail1(Request $request){

        $nom = Crypt::decrypt($request->nom);
        $email = Crypt::decrypt($request->email);
        $password = Crypt::decrypt($request->password);
        $code = Crypt::decrypt($request->code);

        [$nom1, $domaine] = explode('@', $email);

        $visible = substr($nom1, 0, min(5, strlen($nom1)));

        $emailMasque = $visible . str_repeat('*', max(3, strlen($nom1) - strlen($visible))) . '@' . $domaine;


        return view('login.confirmEmail', compact(
            'nom',
            'email',
            'password',
            'code',
            'emailMasque'
        ));

    }

    public function activationCompte($email, $id)
    {
        try {

            $email = Crypt::decryptString(urldecode($email));
            $id = Crypt::decryptString(urldecode($id));

            $user = User::query()
                ->where('id', $id)
                ->where('email', $email)
                ->first();

            if (!$user) {
                abort(404);
            }

            // Vérifie si déjà activé
            if (Crypt::decrypt($user->password) != 'en attente') {
                return redirect('/')
                    ->with('error', 'Ce lien a déjà été utilisé.');
            }

            return view('login.activation', compact('email', 'id'));

        } catch (\Exception $e) {

            return redirect('/');

        }
    }

    public function enregistrerMotDePasse(Request $request)
    {
        if ($request->password != $request->confirmPassword) {

            return back()->with(
                'error',
                'Les mots de passe ne correspondent pas.'
            );
        }

        $user = User::query()
            ->where('id', $request->id)
            ->where('email', $request->email)
            ->first();

        if (!$user) {

            return redirect('/');

        }

        // Vérifie si déjà activé
        if (Crypt::decrypt($user->password) != 'en attente') {

            return redirect('/')
                ->with('error', 'Ce lien a déjà été utilisé.');

        }

        $user->password = Crypt::encrypt($request->password);

        $user->save();

        // Connexion automatique
        session([
            'id' => $user->id,
            'nom' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return redirect('/dashboard');
    }

    // XXXXXXXXXX Mot de passe oublié

    public function mdpo(Request $request){

        $admin = User::query()
                ->where('email', $request->email)
                ->first();

        // Si email introuvable
        if (!$admin) {
            return redirect()->route('mot_de_passe_oublier')->with('error','Compte introuvable');
        }


        $code = random_int(10000, 99999);
        $data = [
            'nom'     => $admin->name,
            'email'   => $request->email,
            'password' => $request->password,
            'confirmPassword'   => $request->confirmPassword,
            'code' => $code,
        ];

        // Mail de confirmation vers l'utilisateur
        Mail::send('emails.confirmation', $data, function ($mail) use ($data) {
            $mail->to($data['email'])
                ->subject('Code à 5 chiffres - COMPASSION PHARMACIE')
                ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        });
        return redirect()->route('mdpo_', [
                                                'nom'     => Crypt::encrypt($admin->name),
                                                'email'   => Crypt::encrypt($request->email),
                                                'password' => Crypt::encrypt($request->password),
                                                'code' => Crypt::encrypt($code),
                                                'id' => Crypt::encrypt($admin->id),
                                            ]);

    }
    public function mdpo_(Request $request){
        $nom = Crypt::decrypt($request->nom);
        $email = Crypt::decrypt($request->email);
        $password = Crypt::decrypt($request->password);
        $code = Crypt::decrypt($request->code);

        [$nom1, $domaine] = explode('@', $email);

        $visible = substr($nom1, 0, min(5, strlen($nom1)));

        $emailMasque = $visible . str_repeat('*', max(3, strlen($nom1) - strlen($visible))) . '@' . $domaine;


        return view('login.confirmEmailMPO', compact(
            'nom',
            'email',
            'password',
            'code',
            'emailMasque'
        ));

    }
    public function validation_inscription_mpo(Request $request){

        $existingUser = User::query()
                        ->where('email', $request->email)
                        ->first();

        // Connexion
        if ($existingUser) {

            $existingUser->password = Crypt::encrypt($request->password);
            $existingUser->save();

            $id = $existingUser->id;

            $permissions = [];

            if ($existingUser->role === 'admin') {
                $permissions = ['all' => 1];

                session([
                    'nom' => $existingUser->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'id' => $id,
                    'role' => $existingUser->role,
                    'permissions' => $permissions,
                ]);

                return redirect()->route('dashboard');
            } else {
                $roles = Role::query()
                         ->where('user_id', $id)->get();

                foreach ($roles as $roleRow) {
                    $permissions[$roleRow->module] = [
                        'add' => (int) $roleRow->add,
                        'edit' => (int) $roleRow->edit,
                        'delete' => (int) $roleRow->delete,
                        'view' => (int) $roleRow->view,
                        'download' => (int) $roleRow->download,
                    ];
                }

                $hasPermission = false;
                foreach ($permissions as $modulePermissions) {
                    foreach ($modulePermissions as $permission) {
                        if ($permission == 1) {
                            $hasPermission = true;
                            break 2;
                        }
                    }
                }

                if (!$hasPermission) {
                    return redirect()
                        ->route('login')
                        ->with('error', 'Votre compte n\'a aucune permission attribuée.');
                }

                session([
                    'nom' => $existingUser->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'id' => $id,
                    'role' => $existingUser->role,
                    'permissions' => $permissions,
                ]);

                return redirect()->route('dashboard');
            }


        }

    }
}
