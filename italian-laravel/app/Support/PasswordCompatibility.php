<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

/** Temporary SHA-1 reader, removable after the imported hashes are retired. */
class PasswordCompatibility
{
    public function verify(#[\SensitiveParameter] string $plain, #[\SensitiveParameter] string $stored): bool
    {
        if (preg_match('/^[a-f0-9]{40}$/iD', $stored)) {
            return hash_equals(strtolower($stored), sha1($plain));
        }

        // Each native hasher retains algorithm verification. Accepting an
        // existing bcrypt/Argon hash never disables verification globally.
        $driver = match (password_get_info($stored)['algoName']) {
            'bcrypt' => 'bcrypt', 'argon2i' => 'argon', 'argon2id' => 'argon2id', default => null,
        };

        return $driver !== null && Hash::driver($driver)->check($plain, $stored);
    }
}
