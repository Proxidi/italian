<?php

namespace App\Actions\Auth;

use App\Models\Acceso;
use App\Models\Usuario;
use App\Support\CorePermissions;
use App\Support\PasswordCompatibility;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateUsuario
{
    public function __construct(private PasswordCompatibility $passwords) {}

    public function handle(string $username, #[\SensitiveParameter] string $password, Closure $establishSession): Usuario
    {
        $candidate = Usuario::query()->where('usuario', $username)->first();
        if (! $candidate || ! $this->passwords->verify($password, $candidate->password)) {
            $this->invalidCredentials();
        }
        $observedHash = $candidate->password;
        $newHash = Hash::needsRehash($observedHash) ? Hash::make($password) : null;

        return DB::transaction(function () use ($candidate, $observedHash, $newHash, $establishSession): Usuario {
            $user = Usuario::query()->lockForUpdate()->with(['plaza', 'roles'])->find($candidate->id);
            // A password changed after verification must never be replaced by
            // a rehash of the previous credential, even if both are modern.
            if (! $user || ! hash_equals($observedHash, $user->password)) {
                $this->invalidCredentials();
            }
            $role = $user->roles->count() === 1 ? $user->roles->first() : null;
            if (! $user->plaza || ! $role || $role->guard_name !== 'web'
                || ! array_key_exists($role->name, CorePermissions::labels())) {
                throw ValidationException::withMessages(['username' => 'El usuario no tiene una plaza o un rol de acceso válido.']);
            }
            if ($newHash !== null) {
                $user->password = $newHash;
                $user->save();
            }
            // Session creation and the mandatory audit belong to this success
            // path. The controller clears the guard/session if either fails.
            $establishSession($user);
            $now = now();
            Acceso::create([
                'fecha' => $now->toDateString(), 'hora' => $now->format('H:i:s'),
                'usuario_id' => $user->id, 'plaza_id' => $user->plaza_id,
            ]);

            return $user;
        });
    }

    private function invalidCredentials(): never
    {
        throw ValidationException::withMessages(['username' => 'El usuario y/o contraseña son incorrectos.']);
    }
}
