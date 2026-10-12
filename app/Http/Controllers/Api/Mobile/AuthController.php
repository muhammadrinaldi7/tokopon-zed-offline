<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

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

    /**
     * Registrasi Akun Pengguna Baru Mobile App.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'device_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data pendaftaran tidak valid: ' . $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => trim($request->input('name')),
                    'email' => strtolower(trim($request->input('email'))),
                    'password' => Hash::make($request->input('password')),
                ]);

                $cleanPhone = preg_replace('/[^0-9]/', '', (string) $request->input('phone', ''));

                UserProfile::create([
                    'user_id' => $user->id,
                    'full_name' => $user->name,
                    'phone_number' => $cleanPhone ?: null,
                    'address' => $request->input('address'),
                ]);

                if (Role::where('name', 'customer')->exists()) {
                    $user->assignRole('customer');
                }

                $deviceName = $request->input('device_name') ?: 'TokoponMobileApp';
                $token = $user->createToken($deviceName, ['mobile:customer'])->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Pendaftaran akun berhasil.',
                    'data' => [
                        'token' => $token,
                        'token_type' => 'Bearer',
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $cleanPhone,
                            'address' => $request->input('address', ''),
                            'roles' => ['customer'],
                        ],
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftarkan akun: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Memperbarui profil akun pengguna.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi tidak valid atau telah berakhir.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Format data tidak valid: ' . $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name') && !empty($request->name)) {
            $user->name = trim($request->name);
            $user->save();
        }

        $profile = $user->profile ?: new UserProfile(['user_id' => $user->id]);
        if ($request->has('name')) $profile->full_name = $user->name;
        if ($request->has('phone')) $profile->phone_number = preg_replace('/[^0-9]/', '', (string) $request->phone);
        if ($request->has('address')) $profile->address = $request->address;
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $profile->phone_number ?? '',
                'address' => $profile->address ?? '',
                'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [],
            ],
        ]);
    }

    /**
     * Memperbarui Expo Push Token milik pengguna yang sedang login.
     */
    public function updatePushToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'push_token' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Format push token tidak valid: ' . $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi tidak valid atau telah berakhir.',
            ], 401);
        }

        $user->update([
            'push_token' => trim($request->input('push_token')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Push token updated',
        ]);
    }
}
