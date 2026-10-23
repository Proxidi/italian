<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Usuario;
use App\Support\AuthenticatedNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthenticateUsuario $authenticate): RedirectResponse
    {
        try {
            $authenticate->handle($request->validated('username'), $request->validated('password'), function (Usuario $user) use ($request): void {
                Auth::guard('web')->login($user, remember: false);
                $request->session()->regenerate();
            });
        } catch (ValidationException $exception) {
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
            throw $exception;
        } catch (Throwable) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            // A database error may include password bindings. Do not report or
            // propagate the original exception to the response/application log.
            abort(503, 'No fue posible completar el inicio de sesión. Intenta nuevamente.');
        }

        // The Corte contract is documented in migration-context.md. Its real
        // integration awaits the migrated sales table; no fabricated msj/hoy.
        $request->session()->forget(['msj', 'hoy']);
        $request->session()->put('modal', 1);

        return redirect()->route('inicio');
    }

    public function inicio(Request $request, AuthenticatedNavigation $navigation): View
    {
        return view('inicio.index', ['layout' => $navigation->forUser($request->user('web'))]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
