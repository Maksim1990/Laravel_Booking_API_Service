<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api')->except(['show']);
    }

    public function index()
    {
        return new UserCollection(User::all());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:users|max:15',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $validator->safe()->only(['name', 'email', 'password']);
        $validated['password'] = Hash::make('password', ['rounds' => 12,]);
        $user = User::create($validated);

        return response()->json([
            'data' => sprintf("User with ID %s was successfully created", $user->id)
        ]);


    }

    public function show(User $user)
    {
        return response()->success(
            data: new UserResource($user)
        );
    }


    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'max:15',
                Rule::unique('users')->ignore($user->id),
            ],
            'email' => [
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->update(
            $validator->safe()->only(['name', 'email'])
        );
        return new UserResource($user);
    }


    public function destroy(User $user)
    {
        $user->delete();
        return response()->json([
            'data' => sprintf("User with ID %s was successfully deleted", $user->id)
        ]);
    }
}
