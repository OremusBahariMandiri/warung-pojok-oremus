<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = $this->userService->getAllUsers();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $users,
            ]);
        }

        return view('pages.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $modules = UserService::MODULES;
        return view('pages.users.create', compact('modules'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil ditambahkan.',
                'data' => $user,
            ], 201);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $user)
    {
        $user->load('accesses');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $user,
            ]);
        }

        return view('pages.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $user->load('accesses');
        $modules = UserService::MODULES;

        return view('pages.users.edit', compact('user', 'modules'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $updatedUser = $this->userService->updateUser($user, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil diperbarui.',
                'data' => $updatedUser,
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $this->userService->deleteUser($user);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil dihapus.',
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
