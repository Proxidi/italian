<?php

namespace App\Http\Controllers;

use App\Actions\Users\SaveUser;
use App\Http\Requests\Users\SaveUserRequest;
use App\Models\User;
use App\Support\AuthenticatedNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UsersWriteController extends Controller
{
    public function edit(Request $request, User $user, AuthenticatedNavigation $navigation): View
    {
        Gate::authorize('update', $user);

        return view('usuarios.edit', [
            'layout' => $navigation->forUser($request->user('web')),
            'subject' => $user,
            'roles' => DB::table('tbl_roles')->whereIn('id', array_keys(SaveUser::ROLES))->get(['id', 'Nombre']),
            'plazas' => DB::table('tbl_plazas')->get(['id', 'Nombre']),
        ]);
    }

    public function store(SaveUserRequest $request, SaveUser $save): RedirectResponse
    {
        $this->persist($request, $save);

        return to_route('usuarios.index')
            ->with('message', 'Usuario Ingresado!')
            ->with('user-created', true);
    }

    public function update(SaveUserRequest $request, User $user, SaveUser $save): RedirectResponse
    {
        $this->persist($request, $save, $user);

        return to_route('usuarios.index')->with('message', 'Perfil Actualizado');
    }

    private function persist(SaveUserRequest $request, SaveUser $save, ?User $user = null): void
    {
        try {
            $save->handle($request->validated(), $user);
        } catch (UniqueConstraintViolationException $exception) {
            // A concurrent request can win after validation; no partial writes remain.
            throw ValidationException::withMessages([
                'usuario' => 'Los datos de email y usuario deben de ser únicos, trate con alguno diferente.',
            ]);
        }
    }
}
