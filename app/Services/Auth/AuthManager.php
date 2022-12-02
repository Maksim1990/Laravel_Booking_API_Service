<?php

namespace App\Services\Auth;

use App\Exceptions\ServiceException;
use Symfony\Component\HttpFoundation\Response;

class AuthManager
{
    public const TESTING_ENV = 'testing';
    public const ENVS_TO_DISABLE_TOKEN_EXPIRATION = [
        self::TESTING_ENV
    ];

    private const DATE_FORMAT = 'd-m-Y H:i:s';
    private string $token = '';

    public function __construct(private array $params)
    {

    }

    public function processAuthToken(string $authHeader): string
    {
        $jwtPayload = $this->parseJwtToken($authHeader);

        //-- Preform token expiration checking if ENV is not 'testing'
        if (!in_array($this->params['env'], self::ENVS_TO_DISABLE_TOKEN_EXPIRATION)) {
            //-- Check if provided JWT token is not expired
            try {
                if (!$this->checkTokenExpirationDate($jwtPayload)) {
                    throw new ServiceException('JWT Token is expired', Response::HTTP_UNAUTHORIZED);
                }
            } catch (\Throwable $e) {
                throw new ServiceException('Error while processing auth token', $e->getCode());
            }
        }

        return $jwtPayload->username;
    }

    public function getAuthToken(string $authHeader): string
    {
        $this->parseJwtToken($authHeader);
        return $this->token;
    }


    /**
     * @param string $bearerToken
     * @return mixed|null
     * @throws ServiceException
     */
    public function parseJwtToken(string $bearerToken)
    {
        $this->token = $bearerToken;
        $tokenParts = explode(".", $this->token);
        if (count($tokenParts) > 2) {
            $tokenPayload = base64_decode($tokenParts[1]);
            return json_decode($tokenPayload);
        }
        throw new ServiceException('JWT Token has invalid format', Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @param object $jwtPayload
     * @return bool
     */
    private function checkTokenExpirationDate(object $jwtPayload): bool
    {
        date_default_timezone_set(config('app.timezone'));
        $strDateNow = strtotime(date(self::DATE_FORMAT));
        $strDateIAT = isset($jwtPayload->iat) ? strtotime(date(self::DATE_FORMAT, $jwtPayload->iat)) : null;
        $strDateEXP = isset($jwtPayload->exp) ? strtotime(date(self::DATE_FORMAT, $jwtPayload->exp)) : null;

        return ((!is_null($strDateIAT) && !is_null($strDateEXP))
            && $strDateNow >= $strDateIAT && $strDateNow < $strDateEXP);
    }
}
