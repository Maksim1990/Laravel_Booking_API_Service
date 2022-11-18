<?php

namespace App\Http\Controllers\API;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $credentials = $validator->safe()->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();
        if ($user == null) {
            return response()->json([
                'status' => 'error',
                'message' => sprintf('User with email %s was not found', $credentials['email']),
            ], 404);
        }

        $token = Auth::login($user);
        if (!$token) {
            return response()->error(
                code: 403,
                message: 'Not authenticated request.'
            );
        }

        return response()->success(
            data: [
                'user' => $user,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]
        );

    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->error(
                message: 'Validation error',
                errors: $validator->errors(),
                code: 422
            );
        }

        $validated = $validator->safe()->only(['name', 'email', 'password']);
        $validated['password'] = Hash::make('password', ['rounds' => 12]);
        $validated['status'] = StatusEnum::PENDING->getStringValue();

        $user = User::create($validated);
        $token = Auth::login($user);

        return response()->success(
            data: [
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => $user,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]);
    }

    public function logout()
    {
        Auth::logout();
        return response()->success(message: 'Successfully logged out');
    }

    public function refresh()
    {
        return response()->success(
            data: [
                'user' => Auth::user(),
                'authorisation' => [
                    'token' => Auth::refresh(),
                    'type' => 'bearer',
                ]
            ]);
    }
}
