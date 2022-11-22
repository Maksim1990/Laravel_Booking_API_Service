<?php

namespace App\Http\Controllers\API;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserActivationServiceInterface;
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

        return response()->success(message: sprintf("User with ID %s was successfully created", $user->id));


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
        return response()->success(
            data: new UserResource($user)
        );
    }


    public function destroy(User $user)
    {
        $user->delete();
        return response()->success(message: sprintf("User with ID %s was successfully deleted", $user->id));
    }

    public function changeStatus(
        Request $request,
        User $user,
        UserActivationServiceInterface $activationService
    ) {
        $validator = Validator::make(
            $request->all(),
            ['status' => 'required|in:active,pending,disabled'],
            ['status' => 'Provided status is invalid.']
        );

        if ($validator->fails()) {
            return response()->error(
                errors: $validator->errors(),
                code: 422
            );
        }

        $status = $validator->getData()['status'];
        $activationService->changeUserStatus($user, StatusEnum::getEnumValue($status));
        return response()->success(
            message: sprintf(
                'Status for the user with ID %s set to %s',
                $user->id,
                $status
            )
        );
    }
}
