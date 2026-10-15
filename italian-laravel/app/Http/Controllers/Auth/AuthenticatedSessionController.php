<?php
// app/Http/Controllers/Auth/AuthenticatedSessionController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\AuthenticatedNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        // Reproduce Mdl_seguridad::autenticar():
        // Usuario + Password SHA-1 y joins internos con plazas y roles.
        $user = User::query()
            ->select([
                'tbl_usuarios.*',
                'p.Nombre as Plaza',
                'r.Nombre as Rol',
            ])
            ->join('tbl_plazas as p', 'tbl_usuarios.plaza_id', '=', 'p.id')
            ->join('tbl_roles as r', 'tbl_usuarios.rol_id', '=', 'r.id')
            ->where('tbl_usuarios.Usuario', $credentials['username'])
            ->where('tbl_usuarios.Password', sha1($credentials['password']))
            ->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'username' => 'El usuario y/o contraseña son incorrectos.',
            ]);
        }

        abort_unless(
            $user->hasAnyRole([
                'superadmin',
                'admin',
                'root',
                'submanager',
                'socio',
            ]),
            403,
            'El usuario no tiene un rol de acceso asignado.',
        );

        $now = now();
        $hoy = $now->copy()->subDays(2)->toDateString();

        // Reproduce el resultado relevante de consultarFechaVenta():
        // existe una fila con Fecha y plaza_id, sin otros filtros.
        $hasReport = DB::table('tbl_reporte_ventas')
            ->where('Fecha', $hoy)
            ->where('plaza_id', $user->plaza_id)
            ->exists();

        $mensaje = $hasReport
            ? ''
            : 'Archivo de Corte Solicitado. Fecha:  '.$hoy;

        DB::table('tbl_bitacora_accesos')->insert([
            'Fecha' => $now->toDateString(),
            'Hora' => $now->format('H:i:s'),
            'usuario_id' => $user->getKey(),
            'plaza_id' => $user->plaza_id,
        ]);

        // Las credenciales ya fueron verificadas mediante la consulta.
        // login() establece la sesión sin verificar ni rehashear Password.
        Auth::guard('web')->login($user, remember: false);

        $request->session()->regenerate();

        $request->session()->put([
            'modal' => 1,
            'msj' => $mensaje,
            'hoy' => $hoy,
        ]);

        return redirect()->route('inicio');
    }

    public function inicio(
        Request $request,
        AuthenticatedNavigation $navigation,
    ): View {
        $user = $request->user('web');

        abort_if($user === null, 401);

        return view('inicio.index', [
            'layout' => $navigation->forUser($user),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
