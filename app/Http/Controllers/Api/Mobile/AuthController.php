<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Autentikasi Pengguna Mobile App & Penerbitan Token Sanctum Resmi.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan isi email dan password dengan benar.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $loginInput = trim($request->input('email'));
        $password = $request->input('password');

        // Cari user berdasarkan email
        $user = User::where('email', $loginInput)->first();

        // Validasi ketat kecocokan akun dan kata sandi menggunakan Hash::check
        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi yang Anda masukkan salah. Silakan periksa kembali.',
            ], 401);
        }

        // Buat token Sanctum resmi untuk Mobile App
        $deviceName = $request->input('device_name') ?: 'TokoponMobileApp';
        $token = $user->createToken($deviceName, ['mobile:customer'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->identity ?? ($user->phone ?? '081298765432'),
                    'address' => $user->address ?? 'Indonesia',
                    'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [],
                ],
            ],
        ]);
    }

    /**
     * Mengambil profil pengguna yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi tidak valid atau telah berakhir.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->identity ?? ($user->phone ?? ''),
                'address' => $user->address ?? '',
                'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [],
            ],
        ]);
    }

    /**
     * Logout & pencabutan token Sanctum aktif.
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
