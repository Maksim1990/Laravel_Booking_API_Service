<?php

namespace App\Services\AWS\Cognito;

use App\Exceptions\ServiceException;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\Result;
use Illuminate\Support\Facades\Password;

class CognitoClient
{
    const NEW_PASSWORD_CHALLENGE = 'NEW_PASSWORD_REQUIRED';
    const FORCE_PASSWORD_STATUS = 'FORCE_CHANGE_PASSWORD';
    const RESET_REQUIRED = 'PasswordResetRequiredException';
    const USER_NOT_FOUND = 'UserNotFoundException';
    const USERNAME_EXISTS = 'UsernameExistsException';
    const INVALID_PASSWORD = 'InvalidPasswordException';
    const CODE_MISMATCH = 'CodeMismatchException';
    const EXPIRED_CODE = 'ExpiredCodeException';

    /**
     * CognitoClient constructor.
     * @param CognitoIdentityProviderClient $client
     * @param string $clientId
     * @param string $clientSecret
     * @param string $poolId
     */
    public function __construct(
        protected CognitoIdentityProviderClient $client,
        protected string                        $clientId,
        protected string                        $clientSecret,
        protected string                        $poolId
    )
    {
    }

    /**
     * Checks if credentials of a user are valid
     *
     * @see http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminInitiateAuth.html
     * @param string $email
     * @param string $password
     * @return \Aws\Result|bool
     */
    public function authenticate($email, $password)
    {
        try {
            $response = $this->client->adminInitiateAuth([
                'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
                'AuthParameters' => [
                    'USERNAME' => $email,
                    'PASSWORD' => $password,
                    'SECRET_HASH' => $this->cognitoSecretHash($email)
                ],
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            throw new ServiceException(
                message: 'user_authentication_failure:' . $exception->getAwsErrorMessage()
            );
        }

        return $response;
    }

    public function verifyUser(string $accessToken, string $code, array $attributes)
    {
        try {
            $response = $this->client->verifyUserAttribute([
                'AccessToken' => $accessToken,
                'AttributeName' => $attributes,
                'Code' => $code,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            throw $exception;
        }

        return $response;
    }

    public function confirmSignUp(string $clientId, string $confirmationCode, string $username)
    {
        try {
            return $this->client->confirmSignUp([
                'ClientId' => $clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'ConfirmationCode' => $confirmationCode,
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            throw new ServiceException(
                message: 'user_not_confirmed:' . $exception->getAwsErrorMessage()
            );
        }
    }

    public function resendConfirmationCode(string $clientId, string $username)
    {
        try {
            return $this->client->resendConfirmationCode([
                'ClientId' => $clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            throw new ServiceException(
                message: 'confirmation_code_resending_failed:' . $exception->getAwsErrorMessage()
            );
        }
    }

    public function forgotPassword(string $clientId, string $username)
    {
        try {
            return $this->client->forgotPassword([
                'ClientId' => $clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            throw new ServiceException(
                message: 'forgot_password_request_failed:' . $exception->getAwsErrorMessage()
            );
        }
    }

    public function confirmForgotPassword(
        string $clientId,
        string $confirmationCode,
        string $username,
        string $password,
    )
    {
        try {
            return $this->client->confirmForgotPassword([
                'ClientId' => $clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'ConfirmationCode' => $confirmationCode,
                'Password' => $password,
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            throw new ServiceException(
                message: 'confirmation_forgot_password_failed:' . $exception->getAwsErrorMessage()
            );
        }
    }

    public function changePassword(string $accessToken, string $previousPassword, string $proposedPassword): Result
    {
        try {
            $response = $this->client->changePassword([
                'AccessToken' => $accessToken,
                'PreviousPassword' => $previousPassword,
                'ProposedPassword' => $proposedPassword,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            throw $exception;
        }

        return $response;
    }

    public function revokeToken(
        string $clientId,
        string $refreshToken
    )
    {
        try {
            return $this->client->revokeToken([
                'ClientId' => $clientId,
                'ClientSecret' => $this->clientSecret,
                'Token' => $refreshToken,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            throw new ServiceException(
                message: 'revoke_token_failed:' . $exception->getAwsErrorMessage()
            );
        }
    }

    public function getAuthUser($accessToken)
    {
        try {
            $user = $this->client->getUser(['AccessToken' => $accessToken]);
        } catch (CognitoIdentityProviderException $exception) {
            throw new ServiceException(
                message: 'get_auth_user:' . $exception->getAwsErrorMessage()
            );
        }

        return $user;
    }


    /**
     * Registers a user in the given user pool
     *
     * @param $email
     * @param $password
     * @param array $attributes
     * @return bool
     */
    public function register($email, $password, array $attributes = []): array
    {
        $attributes['email'] = $email;

        try {
            $response = $this->client->signUp([
                'ClientId' => $this->clientId,
                'Password' => $password,
                'SecretHash' => $this->cognitoSecretHash($email),
                'UserAttributes' => $this->formatAttributes($attributes),
                'Username' => $email,

            ]);
        } catch (CognitoIdentityProviderException $exception) {
            throw new ServiceException(
                message: 'register_user_failed:' . $exception->getAwsErrorMessage()
            );
        }

        $this->setUserAttributes($email, ['email_verified' => 'true']);

        return [
            'user_id' => $response['UserSub'],
            'cognito_client_id' => $this->clientId
        ];
    }

    /**
     * Send a password reset code to a user.
     * @see http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ForgotPassword.html
     *
     * @param string $username
     * @return string
     */
    public function sendResetLink($username)
    {
        try {
            $result = $this->client->forgotPassword([
                'ClientId' => $this->clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return Password::INVALID_USER;
            }

            throw $e;
        }

        return Password::RESET_LINK_SENT;
    }

    # HELPER FUNCTIONS

    /**
     * Set a users attributes.
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminUpdateUserAttributes.html
     *
     * @param string $username
     * @param array $attributes
     * @return bool
     */
    public function setUserAttributes($username, array $attributes)
    {
        $this->client->AdminUpdateUserAttributes([
            'Username' => $username,
            'UserPoolId' => $this->poolId,
            'UserAttributes' => $this->formatAttributes($attributes),
        ]);

        return true;
    }


    /**
     * Creates the Cognito secret hash
     * @param string $username
     * @return string
     */
    protected function cognitoSecretHash($username)
    {
        return $this->hash($username . $this->clientId);
    }

    /**
     * Creates a HMAC from a string
     *
     * @param string $message
     * @return string
     */
    protected function hash($message)
    {
        $hash = hash_hmac(
            'sha256',
            $message,
            $this->clientSecret,
            true
        );

        return base64_encode($hash);
    }

    /**
     * Get user details.
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_GetUser.html
     *
     * @param string $username
     * @return mixed
     */
    public function getUser($username)
    {
        try {
            $user = $this->client->AdminGetUser([
                'Username' => $username,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            return false;
        }

        return $user;
    }

    /**
     * Format attributes in Name/Value array
     *
     * @param array $attributes
     * @return array
     */
    protected function formatAttributes(array $attributes)
    {
        $userAttributes = [];

        foreach ($attributes as $key => $value) {
            $userAttributes[] = [
                'Name' => $key,
                'Value' => $value,
            ];
        }

        return $userAttributes;
    }
}
