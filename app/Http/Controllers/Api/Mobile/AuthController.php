<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
                'user' => $this->formatUserData($user),
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
            'data' => $this->formatUserData($user),
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
                    'domisili' => $request->input('address'),
                ]);

                if (!empty($cleanPhone)) {
                    try {
                        $loyaltyService = app(\App\Services\LoyaltyService::class);
                        $loyaltyService->getOrCreateAccount($cleanPhone, $user->id, $user->name);
                    } catch (\Throwable $e) {
                        Log::warning('Gagal membuat loyalty account saat registrasi: ' . $e->getMessage());
                    }
                }

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
                        'user' => $this->formatUserData($user),
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
        if ($request->has('phone')) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string) $request->phone);
            $profile->phone_number = $cleanPhone ?: null;
        }
        if ($request->has('address')) {
            $profile->domisili = $request->address;
        }
        $profile->save();

        if (!empty($profile->phone_number)) {
            try {
                $loyaltyService = app(\App\Services\LoyaltyService::class);
                $loyaltyAccount = $loyaltyService->getOrCreateAccount($profile->phone_number, $user->id, $user->name);
                if ($loyaltyAccount && $loyaltyAccount->user_id !== $user->id) {
                    $loyaltyAccount->user_id = $user->id;
                    $loyaltyAccount->customer_name = $user->name;
                    $loyaltyAccount->save();
                }
            } catch (\Throwable $e) {
                Log::warning('Gagal sinkronisasi loyalty saat update profil: ' . $e->getMessage());
            }
        }

        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => $this->formatUserData($user),
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

    /**
     * Format payload seragam data pengguna untuk Mobile App.
     */
    protected function formatUserData(User $user): array
    {
        $user->loadMissing(['profile', 'loyaltyAccount', 'addresses']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->customer_phone,
            'address' => $user->customer_address,
            'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [],
        ];
    }
}
