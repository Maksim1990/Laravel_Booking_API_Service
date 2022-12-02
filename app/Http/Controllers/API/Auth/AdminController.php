<?php

namespace App\Http\Controllers\API\Auth;

use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminCollection;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Services\ActivationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api')->except(['show','login','register']);
    }

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

        $admin = Admin::where('email', $credentials['email'])->first();
        if ($admin == null) {
            return response()->json([
                'status' => 'error',
                'message' => sprintf('Admin with email %s was not found', $credentials['email']),
            ], 404);
        }

        $token = Auth::login($admin);
        if (!$token) {
            return response()->error(
                code: 403,
                message: 'Not authenticated request.'
            );
        }

        return response()->success(
            data: [
                'admin' => $admin,
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
            'email' => 'required|string|email|max:255|unique:admins',
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

        $admin = Admin::create($validated);
        $token = Auth::login($admin);

        return response()->success(
            data: [
                'status' => 'success',
                'message' => 'Admin created successfully',
                'admin' => $admin,
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
                'admin' => Auth::user(),
                'authorisation' => [
                    'token' => Auth::refresh(),
                    'type' => 'bearer',
                ]
            ]);
    }

    public function index()
    {
        return new AdminCollection(Admin::all());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:admins|max:15',
            'email' => 'required|email|unique:admins',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $validator->safe()->only(['name', 'email', 'password']);
        $validated['password'] = Hash::make('password', ['rounds' => 12,]);
        $admin = Admin::create($validated);

        return response()->success(message: sprintf("Admin with ID %s was successfully created", $admin->id));
    }

    public function show(Admin $admin)
    {
        return response()->success(
            data: new AdminResource($admin)
        );
    }


    public function update(Request $request, Admin $admin)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'max:15',
                Rule::unique('users')->ignore($admin->id),
            ],
            'email' => [
                Rule::unique('users')->ignore($admin->id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $admin->update(
            $validator->safe()->only(['name', 'email'])
        );
        return response()->success(
            data: new AdminResource($admin)
        );
    }


    public function destroy(Admin $admin)
    {
        $admin->delete();
        return response()->success(message: sprintf("Admin with ID %s was successfully deleted", $admin->id));
    }

    public function changeStatus(
        Request $request,
        Admin $admin,
        ActivationServiceInterface $activationService
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
        $activationService->changeAdminStatus($admin, StatusEnum::getEnumValue($status));
        return response()->success(
            message: sprintf(
                'Status for the admin with ID %s set to %s',
                $admin->id,
                $status
            )
        );
    }
}
