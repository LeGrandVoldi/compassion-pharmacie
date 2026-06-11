<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class Controller_utilisateurs extends Controller
{
    public function index(Request $request){

        $nom = session('nom');
        $email = session('email');
        $password = session('password');
        $id = session('id');
        $role = session('role');

        $query = User::with('roles')
                ->where('role', '!=', 'admin')
                ->latest();

        $users = $query->get()->filter(function ($user) use ($request) {
            $q = trim((string) $request->query('q', ''));
            $status = $request->query('status', 'all');

            $searchOk = true;
            if ($q !== '') {
                $text = mb_strtolower($user->name . ' ' . $user->email);
                $searchOk = str_contains($text, mb_strtolower($q));
            }

            $statusOk = true;
            if ($status === 'active') {
                $statusOk = $user->status_label === 'Actif';
            } elseif ($status === 'inactive') {
                $statusOk = $user->status_label === 'Inactif';
            }

            return $searchOk && $statusOk;
        })->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;

        $paginated = new LengthAwarePaginator(
            $users->slice(($page - 1) * $perPage, $perPage)->values(),
            $users->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

       return view('principale.utilisateurs.index', compact(
            'nom',
            'email',
            'password',
            'id',
            'role',
            'paginated',
            'users'
        ));
    }

    // XXXXXXXXXXXXXXXXXXXXXXXXXXXX

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'permissions' => 'array',
            'role' => 'required|string|max:50',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->nom,
                'email' => $request->email,
                'password' => Crypt::encrypt('en attente'),
                'role' => $request->role,
            ]);

            $userId = $user->id;
            $emailCrypte = urlencode(Crypt::encryptString($request->email));
            $idCrypte = urlencode(Crypt::encryptString($userId));

            $lienValidation =
            "https://compassion-pharmacie.capcongo.online/activation/".
            $emailCrypte."/".
            $idCrypte;

            // Mail de confirmation vers l'utilisateur
            $data = [
                'nom'     => $request->nom,
                'email'   => $request->email,
                'id' => $userId,
                'lienValidation' => $lienValidation,
            ];
            Mail::send('emails.validation_compte', $data, function ($mail) use ($data) {
                $mail->to($data['email'])
                    ->subject('Validation Compte - COMPASSION PHARMACIE')
                    ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });

            $this->syncRolesFromPermissions($user, $request->permissions ?? []);
        });

        return response()->json([
            'status' => true,
            'message' => 'Utilisateur ajouté avec succès.',
        ]);
    }

    public function showJson(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'email' => $user->email,
            'status_label' => $user->status_label,
            'permissions' => $this->permissionsForUser($user),
        ]);
    }

    public function update(Request $request, User $user)
    {

        $request->validate([
            'nom' => 'required|string|max:255',
            'role' => 'required|string|max:50',
            'permissions' => 'array',
        ]);

        DB::transaction(function () use ($request, $user) {
            $user->update([
                'name' => $request->nom,
                'role' => $request->role,
            ]);

            $this->syncRolesFromPermissions($user, $request->permissions ?? []);
        });

        return response()->json([
            'status' => true,
            'message' => 'Utilisateur modifié avec succès.',
        ]);
    }

    public function destroy(User $user)
    {

        DB::transaction(function () use ($user) {

            $user->roles()->delete();

            User::query()
                  ->where('id', $user->id)->delete();

        });

        return response()->json([
            'status' => true,
            'message' => 'Utilisateur supprimé avec succès.',
        ]);
    }

    private function syncRolesFromPermissions(User $user, array $permissions): void
    {
        $map = $this->permissionMap();
        $selected = array_flip($permissions);

        $rows = [];

        foreach ($map as $module => $columns) {
            $row = [
                'user_id' => $user->id,
                'module' => $module,
                'add' => false,
                'edit' => false,
                'delete' => false,
                'view' => false,
                'download' => false,
            ];

            foreach ($columns as $column => $permissionKey) {
                if (isset($selected[$permissionKey])) {
                    $row[$column] = true;
                }
            }

            if ($row['add'] || $row['edit'] || $row['delete'] || $row['view'] || $row['download']) {
                $rows[] = $row;
            }
        }

        $user->roles()->delete();

        if (!empty($rows)) {
            Role::insert($rows);
        }
    }

    private function permissionsForUser(User $user): array
    {
        $permissions = [];

        foreach ($user->roles as $role) {
            if ($role->module === 'commandes') {
                if ($role->add) $permissions[] = 'commandes_ajouter';
                if ($role->edit) $permissions[] = 'commandes_modifier';
            }

            if ($role->module === 'stock') {
                if ($role->add) $permissions[] = 'stock_ajouter';
                if ($role->edit) $permissions[] = 'stock_modifier';
                if ($role->delete) $permissions[] = 'stock_supprimer';
            }

            if ($role->module === 'ventes') {
                if ($role->add) $permissions[] = 'ventes_ajouter';
                if ($role->edit) $permissions[] = 'ventes_modifier';
                if ($role->delete) $permissions[] = 'ventes_supprimer';
            }

            if ($role->module === 'clients') {
                if ($role->add) $permissions[] = 'clients_ajouter';
                if ($role->edit) $permissions[] = 'clients_modifier';
                if ($role->delete) $permissions[] = 'clients_supprimer';
            }

            if ($role->module === 'partenaires') {
                if ($role->add) $permissions[] = 'partenaires_ajouter';
                if ($role->edit) $permissions[] = 'partenaires_modifier';
                if ($role->delete) $permissions[] = 'partenaires_supprimer';
            }

            if ($role->module === 'fournisseurs') {
                if ($role->add) $permissions[] = 'fournisseurs_ajouter';
                if ($role->edit) $permissions[] = 'fournisseurs_modifier';
                if ($role->delete) $permissions[] = 'fournisseurs_supprimer';
            }

            if ($role->module === 'ordonnances') {
                if ($role->add) $permissions[] = 'ordonnances_ajouter';
                if ($role->edit) $permissions[] = 'ordonnances_modifier';
                if ($role->delete) $permissions[] = 'ordonnances_supprimer';
            }

            if ($role->module === 'statistiques' && $role->view) {
                $permissions[] = 'statistiques_voir';
            }

            if ($role->module === 'rapports') {
                if ($role->view) $permissions[] = 'rapports_voir';
                if ($role->download) $permissions[] = 'rapports_telecharger';
            }

            if ($role->module === 'utilisateurs') {
                if ($role->add) $permissions[] = 'utilisateurs_ajouter';
                if ($role->edit) $permissions[] = 'utilisateurs_modifier';
                if ($role->delete) $permissions[] = 'utilisateurs_supprimer';
            }

            if ($role->module === 'admins') {
                if ($role->add) $permissions[] = 'admins_ajouter';
                if ($role->edit) $permissions[] = 'admins_modifier';
                if ($role->delete) $permissions[] = 'admins_supprimer';
            }

            if ($role->module === 'categories') {
                if ($role->add) $permissions[] = 'categories_ajouter';
                if ($role->edit) $permissions[] = 'categories_modifier';
            }

            if ($role->module === 'paiements') {
                if ($role->view) $permissions[] = 'paiements_voir';
                if ($role->download) $permissions[] = 'paiements_telecharger';
            }
        }

        return array_values(array_unique($permissions));
    }

    private function permissionMap(): array
    {
        return [
            'commandes' => [
                'add' => 'commandes_ajouter',
                'edit' => 'commandes_modifier',
            ],
            'stock' => [
                'add' => 'stock_ajouter',
                'edit' => 'stock_modifier',
                'delete' => 'stock_supprimer',
            ],
            'ventes' => [
                'add' => 'ventes_ajouter',
                'edit' => 'ventes_modifier',
                'delete' => 'ventes_supprimer',
            ],
            'clients' => [
                'add' => 'clients_ajouter',
                'edit' => 'clients_modifier',
                'delete' => 'clients_supprimer',
            ],
            'partenaires' => [
                'add' => 'partenaires_ajouter',
                'edit' => 'partenaires_modifier',
                'delete' => 'partenaires_supprimer',
            ],
            'fournisseurs' => [
                'add' => 'fournisseurs_ajouter',
                'edit' => 'fournisseurs_modifier',
                'delete' => 'fournisseurs_supprimer',
            ],
            'ordonnances' => [
                'add' => 'ordonnances_ajouter',
                'edit' => 'ordonnances_modifier',
                'delete' => 'ordonnances_supprimer',
            ],
            'statistiques' => [
                'view' => 'statistiques_voir',
            ],
            'rapports' => [
                'view' => 'rapports_voir',
                'download' => 'rapports_telecharger',
            ],
            'utilisateurs' => [
                'add' => 'utilisateurs_ajouter',
                'edit' => 'utilisateurs_modifier',
                'delete' => 'utilisateurs_supprimer',
            ],
            'admins' => [
                'add' => 'admins_ajouter',
                'edit' => 'admins_modifier',
                'delete' => 'admins_supprimer',
            ],
            'categories' => [
                'add' => 'categories_ajouter',
                'edit' => 'categories_modifier',
            ],
            'paiements' => [
                'view' => 'paiements_voir',
                'download' => 'paiements_telecharger',
            ],
        ];
    }
}
