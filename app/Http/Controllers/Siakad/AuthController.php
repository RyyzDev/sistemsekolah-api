<?php

namespace App\Http\Controllers\Siakad;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your Account Is In-Active.'],
        ]);
    }

    $token = $user->createToken('auth-token')->plainTextToken;

    $userData = [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'roles' => $user->getRoleNames(),
    ];

    if ($user->isStudent()) {
        $userData['student'] = $user->student;
    } elseif ($user->isTeacher()) {
        $userData['teacher'] = $user->teacher;
    } elseif ($user->isParent()) {
        $userData['parent'] = $user->parent;
    }

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'token' => $token,
            'user' => $userData,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['student', 'teacher', 'parent']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }
}