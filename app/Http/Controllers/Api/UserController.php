<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json(compact('token'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'username'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed', // expects password_confirmation
        ]);

        try {
            $user = User::create([
                'name'     => $validated['name'],
                'username'     => $validated['username'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Generate token right after registration
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => 'User successfully registered',
                'user'    => $user,
                'token'   => $token,
            ], 201);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not register user'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken());
            return response()->json(['message' => 'Logout successful']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not logout'], 500);
        }
    }
}
