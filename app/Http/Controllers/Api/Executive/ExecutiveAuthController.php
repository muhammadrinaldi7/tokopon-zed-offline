<?php

namespace App\Http\Controllers\Api\Executive;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ExecutiveAuthController extends Controller
{
    /**
     * Allowed roles for Executive Dashboard access.
     */
    protected array $allowedRoles = ['superadmin', 'admin', 'director'];

    /**
     * Handle executive login and issue Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi yang Anda masukkan salah.',
            ], 401);
        }

        // Verify role
        if (!$user->hasAnyRole($this->allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Akun ini tidak memiliki otorisasi Executive / Direksi.',
            ], 403);
        }

        $deviceName = $request->device_name ?: 'ExecutiveDashboard';
        $token = $user->createToken($deviceName, ['executive:read'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Autentikasi Executive berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'business_unit' => $user->businessUnit ? [
                        'id' => $user->businessUnit->id,
                        'name' => $user->businessUnit->name,
                        'code' => $user->businessUnit->code,
                    ] : null,
                ],
            ],
        ]);
    }

    /**
     * Return authenticated executive profile and available scopes.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasAnyRole($this->allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Akun ini tidak memiliki otorisasi Executive / Direksi.',
            ], 403);
        }

        $businessUnits = BusinessUnit::where('is_active', true)->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'business_unit' => $user->businessUnit ? [
                    'id' => $user->businessUnit->id,
                    'name' => $user->businessUnit->name,
                    'code' => $user->businessUnit->code,
                ] : null,
                'available_business_units' => $businessUnits,
            ],
        ]);
    }

    /**
     * Log out current user and revoke active token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil. Token telah dicabut.',
        ]);
    }
}
