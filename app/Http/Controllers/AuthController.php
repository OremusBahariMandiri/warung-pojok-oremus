<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('products.index');
        }

        return view('pages.auth.login');
    }

    /**
     * Handle login request with NRK & Password.
     */
    public function login(LoginRequest $request)
    {
        $credentials = [
            'nrk' => $request->nrk,
            'password' => $request->password,
        ];

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            /** @var \App\Models\User $user */
            $user = Auth::user();

            ActivityLogService::log(
                action: 'LOGIN',
                module: 'AUTH',
                entityType: User::class,
                entityId: $user?->id,
                description: "User {$user->employee_name} ({$user->nrk}) logged in successfully.",
                oldValues: null,
                newValues: [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
                userId: $user->id
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Login berhasil.',
                    'data' => [
                        'user' => $user->load('accesses'),
                    ]
                ]);
            }

            return redirect()->intended(route('products.index'))->with('success', "Selamat datang kembali, {$user->employee_name}!");
        }

        ActivityLogService::log(
            action: 'FAILED_LOGIN',
            module: 'AUTH',
            entityType: User::class,
            entityId: 0,
            description: "Failed login attempt for NRK: {$request->nrk}",
            oldValues: null,
            newValues: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            userId: null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'NRK atau password yang Anda masukkan salah.',
                'errors' => [
                    'nrk' => ['NRK atau password yang Anda masukkan salah.'],
                ]
            ], 422);
        }

        return back()
            ->withInput($request->only('nrk', 'remember'))
            ->withErrors([
                'nrk' => 'NRK atau password yang Anda masukkan salah.',
            ]);
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            ActivityLogService::log(
                action: 'LOGOUT',
                module: 'AUTH',
                entityType: User::class,
                entityId: $user->id,
                description: "User {$user->employee_name} ({$user->nrk}) logged out.",
                oldValues: null,
                newValues: null,
                userId: $user->id
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Logout berhasil.',
            ]);
        }

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

    /**
     * Get current authenticated user details and permissions.
     */
    public function me(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user->load('accesses'),
            ]
        ]);
    }
}