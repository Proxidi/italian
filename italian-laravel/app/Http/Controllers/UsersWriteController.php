<?php

namespace App\Http\Controllers;

use App\Actions\Users\SaveUser;
use App\Http\Requests\Users\SaveUserRequest;
use App\Models\Usuario;
use App\Support\AuthenticatedNavigation;
use App\Support\UserCatalogs;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UsersWriteController extends Controller
{
    public function edit(Request $request, Usuario $user, AuthenticatedNavigation $navigation): View
    {
        Gate::authorize('update', $user);

        return view('usuarios.edit', [
            'layout' => $navigation->forUser($request->user('web')),
            'subject' => $user,
            ...UserCatalogs::forForm(),
            'selectedRoleId' => $user->roles->count() === 1 ? $user->roles->first()->id : null,
        ]);
    }

    public function store(SaveUserRequest $request, SaveUser $save): RedirectResponse
    {
        $this->persist($request, $save);

        return to_route('usuarios.index')
            ->with('message', 'Usuario Ingresado!')
            ->with('user-created', true);
    }

    public function update(SaveUserRequest $request, Usuario $user, SaveUser $save): RedirectResponse
    {
        $this->persist($request, $save, $user);

        return to_route('usuarios.index')->with('message', 'Perfil Actualizado');
    }

    private function persist(SaveUserRequest $request, SaveUser $save, ?Usuario $user = null): void
    {
        try {
            $save->handle($request->validated(), $user);
        } catch (UniqueConstraintViolationException $exception) {
            // A concurrent request can win after validation; no partial writes remain.
            throw ValidationException::withMessages([
                'usuario' => 'Los datos de email y usuario deben de ser únicos, trate con alguno diferente.',
            ]);
        } catch (QueryException) {
            // INSERT/UPDATE bindings may contain a password hash. Preserve the
            // rollback and return a safe error without logging those bindings.
            abort(503, 'No fue posible guardar el usuario. Intenta nuevamente.');
        }
    }
}
