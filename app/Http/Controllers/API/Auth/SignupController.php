<?php

namespace App\Http\Controllers\API\Auth;

use App\Enums\StatusEnum;
use App\Exceptions\ServiceException;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use App\Services\ActivationServiceInterface;
use App\Services\Auth\AuthManager;
use App\Services\AWS\Cognito\CognitoClient;
use Aws\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SignupController extends Controller
{
    private ?User $user = null;
    protected const USER_CONFIRMED_STATUS = 'CONFIRMED';

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users|max:255',
            'name' => 'required|unique:users|string|max:50',
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
        $validated['status'] = StatusEnum::PENDING->getStringValue();

        try {
            //Add new user into Cognito pool
            $cognitoUserData = app()
                ->make(CognitoClient::class)
                ->register($validated['email'], $validated['password'], []);
        } catch (ServiceException $exception) {
            return response()->error(
                message: sprintf('Can\'t register user error: %s', $exception->getMessage()),
                code: 400
            );
        }

        if (!empty($cognitoUserData)) {
            $validated['id'] = $cognitoUserData['user_id'];
            $validated['cognito_client_id'] = $cognitoUserData['cognito_client_id'];
            $validated['password'] = Hash::make('password', ['rounds' => 12]);
            $user = User::create($validated);
            return response()->success(
                data: [
                    'status' => 'success',
                    'message' => 'Admin created successfully',
                    'admin' => $user,
                    '_id' => $cognitoUserData['user_id'],
                    'cognito_client_id' => $cognitoUserData['cognito_client_id']
                ]
            );
        } else {
            return response()->error(
                message: 'User can not be registered into Cognito pool',
                code: 500
            );
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->error(
                message: 'Validation error',
                errors: $validator->errors(),
                code: 422
            );
        }
        $credentials = $validator->safe()->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();
        if ($user == null) {
            return response()->json([
                'status' => 'error',
                'message' => sprintf('User with email %s was not found', $credentials['email']),
            ], 404);
        }

        Auth::login($user);
        try {
            $authData = app()->make(CognitoClient::class)->authenticate($credentials['email'], $credentials['password']);
        } catch (ServiceException $exception) {
            return response()->error(
                message: sprintf('Can\'t login user error: %s', $exception->getMessage()),
                code: 400
            );
        }

        return $this->respondWithToken($authData['AuthenticationResult']);
    }

    protected function respondWithToken(array $token)
    {
        return [
            'access_token' => $token['AccessToken'],
            'expires_in' => $token['ExpiresIn'],
            'refresh_token' => $token['RefreshToken'],
            'id_token' => $token['IdToken'],
            'token_type' => $token['TokenType'],
            'user' => auth()->user(),
        ];
    }

    public function confirmRegistration(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
                'code' => 'required|int'
            ],
        );

        if ($validator->fails()) {
            return response()->error(
                message: 'Validation error',
                errors: $validator->errors(),
                code: 422
            );
        }
        $validated = $validator->safe()->only(['code', 'email']);
        if (!$this->processAuthUser($validated['email'])) {
            return response()->error(
                message: sprintf('User with email %s was not found', $validated['email']),
                code: 422
            );
        }

        if ($this->user->status === StatusEnum::ACTIVE->getStringValue()) {
            return response()->success(
                data: [
                    'message' => 'User is already confirmed',
                ]
            );
        }

        try {
            app()->make(CognitoClient::class)->confirmSignUp(
                $this->user->cognito_client_id,
                $validated['code'],
                $validated['email']
            );
        } catch (ServiceException $exception) {
            return response()->error(
                message: sprintf('Can\'t confirm user error: %s', $exception->getMessage()),
                code: 500
            );
        }

        $this->user->status = StatusEnum::ACTIVE->getStringValue();
        $this->user->update();

        return response()->success(
            data: [
                'message' => sprintf('User %s was confirmed', $validated['email']),
            ]
        );
    }

    public function resendConfirmationCode(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            ['email' => 'required|email'],
        );

        if ($validator->fails()) {
            return response()->error(
                message: 'Validation error',
                errors: $validator->errors(),
                code: 422
            );
        }
        $validated = $validator->safe()->only(['name', 'email', 'password']);

        if (!$this->processAuthUser($validated['email'])) {
            return response()->error(
                message: sprintf('User with email %s was not found', $validated['email']),
                code: 422
            );
        }

        if (($this->getCognitoUserData()['UserStatus'] ?? null) === self::USER_CONFIRMED_STATUS) {
            return response()->success(
                data: [
                    'message' => 'User is already confirmed',
                ]
            );
        }

        app()->make(CognitoClient::class)->resendConfirmationCode(
            $this->user->cognito_client_id,
            $validated['email']
        );
        return response()->success(
            message: sprintf(
                'Confirmation code was resent to %s email.',
                $validated['email']
            )
        );
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
                'previous_password' => 'required',
                'password' => 'required|string|min:6',
                'confirm_password' => 'required|same:password',
            ],
        );

        if ($validator->fails()) {
            return response()->error(
                message: 'Validation error',
                errors: $validator->errors(),
                code: 422
            );
        }

        $validated = $validator->safe()->only(['email', 'previous_password', 'password']);
        if (!$this->processAuthUser($validated['email'])) {
            return response()->error(
                message: sprintf('User with email %s was not found', $validated['email']),
                code: 422
            );
        }
        $accessToken = $request->bearerToken() ?? null;

        try {
            app()->make(CognitoClient::class)->changePassword(
                $accessToken,
                $validated['previous_password'],
                $validated['password']
            );
        } catch (ServiceException $exception) {
            return response()->error(
                message: sprintf('Can\'t change user password error: %s', $exception->getMessage()),
                code: 500
            );
        }

        $this->user->password = Hash::make('password', ['rounds' => 12]);
        $this->user->update();

        return response()->success(
            message: sprintf(
                'Password was successfully reset for the user %s.',
                $validated['email']
            )
        );
    }

    protected function processAuthUser(string $email): bool
    {
        $this->user = User::where('email', $email)->first();
        $this->cognitoUserData = $this->getCognitoUserByName($email);
        return $this->cognitoUserData && $this->user !== null;
    }

    private function getCognitoUserByName(string $email): Result|bool
    {
        return app()->make(CognitoClient::class)->getUser($email);
    }

    protected function getCognitoUserData(): ?Result
    {
        return $this->cognitoUserData;
    }
}
