<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class RefreshPermissions
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('id')) {
            $user = User::query()
                    ->find(session('id'));

            if ($user) {
                if ($user->role === 'admin') {
                    session([
                        'role' => 'admin',
                        'permissions' => ['all' => 1],
                    ]);
                } else {
                    $roles = Role::query()
                            ->where('user_id', $user->id)->get();

                    $permissions = [];

                    foreach ($roles as $roleRow) {
                        $permissions[$roleRow->module] = [
                            'add' => (int) $roleRow->add,
                            'edit' => (int) $roleRow->edit,
                            'delete' => (int) $roleRow->delete,
                            'view' => (int) $roleRow->view,
                            'download' => (int) $roleRow->download,
                        ];
                    }

                    session([
                        'role' => $user->role,
                        'permissions' => $permissions,
                    ]);
                }
            }
        }

        return $next($request);
    }
}
