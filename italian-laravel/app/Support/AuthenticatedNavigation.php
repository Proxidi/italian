<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class AuthenticatedNavigation
{
    public function forUser(User $user): array
    {
        $user->loadMissing('roles');

        $role = null;
        $panel = null;

        foreach (config('authenticated-navigation.role_panels') as $candidate => $candidatePanel) {
            if ($user->hasRole($candidate, 'web')) {
                $role = $candidate;
                $panel = $candidatePanel;

                break;
            }
        }

        abort_if($panel === null, 403, 'No tiene acceso a un panel.');

        $definition = config("authenticated-navigation.panels.{$panel}");

        $name = trim(implode(' ', array_filter([
            $user->Nombre,
            $user->Apepat,
            $user->Apemat,
        ], static fn ($value) => $value !== null && $value !== '')));

        $plaza = $user->plaza_id === null
            ? null
            : DB::table('tbl_plazas')
                ->where('id', $user->plaza_id)
                ->value('Nombre');

        return [
            'role' => $role,
            'panel' => $panel,
            'title' => $definition['title'],
            'subtitle' => $definition['subtitle'],
            'profile' => [
                'short_name' => filled($user->Nombre)
                    ? (string) $user->Nombre
                    : (string) $user->Usuario,
                'name' => $name !== '' ? $name : (string) $user->Usuario,
                'plaza' => $plaza,
                'avatar' => $user->Sexo === 'H' ? 'man.png' : 'woman.png',
            ],
            'sidebar' => $this->resolveItems([
                ...config('authenticated-navigation.common'),
                ...$definition['sidebar'],
            ], $user),
            'cards' => $this->resolveItems($definition['cards'], $user),
        ];
    }

    private function resolveItems(array $items, User $user): array
    {
        $resolved = [];

        foreach ($items as $item) {
            $result = $this->resolve($item, $user);

            if ($result !== null) {
                $resolved[] = $result;
            }
        }

        return $resolved;
    }

    private function resolve(array $item, User $user): ?array
    {
        if (isset($item['children'])) {
            $item['children'] = $this->resolveItems($item['children'], $user);

            if ($item['children'] === []) {
                return null;
            }

            $item['active'] = collect($item['children'])->contains(
                fn (array $child) => $child['active'] ?? false,
            );

            return $item;
        }

        if (($item['type'] ?? null) === 'logout') {
            return $item;
        }

        $destinations = config('authenticated-navigation.destinations', []);

        $destination = isset($item['destination'])
            ? ($destinations[$item['destination']] ?? null)
            : null;

        if (isset($destination['permission'])
            && ! $user->can($destination['permission'])) {
            return null;
        }

        $routeName = $destination['route'] ?? null;
        $available = $routeName !== null && Route::has($routeName);

        $item['url'] = $available ? route($routeName) : null;
        $item['active'] = $available && request()->routeIs($routeName);
        $item['legacy'] = $destination['legacy'] ?? null;
        $item['unavailable_message'] ??= 'Módulo pendiente de migración';

        return $item;
    }
}
