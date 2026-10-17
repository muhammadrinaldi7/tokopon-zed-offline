<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'business_unit_id' => $user->business_unit_id,
                    'active_business_unit_id' => method_exists($user, 'getActiveBusinessUnitId') ? $user->getActiveBusinessUnitId() : $user->business_unit_id,
                    'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [],
                ] : null,
            ],
            'businessUnits' => fn () => \App\Models\BusinessUnit::all(['id', 'name', 'code', 'category', 'is_second']),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'reverb' => [
                'key' => config('reverb.apps.apps.0.key', env('VITE_REVERB_APP_KEY')),
                'host' => env('VITE_REVERB_HOST', 'localhost'),
                'port' => env('VITE_REVERB_PORT', 8080),
                'scheme' => env('VITE_REVERB_SCHEME', 'http'),
            ],
        ];
    }
}
