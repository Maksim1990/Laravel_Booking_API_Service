<?php

namespace App\Exceptions;

class InvalidUserStatusException extends \Exception
{
    public function render($request)
    {
        return response()->error(
            message: "Provided user's status is invalid or missed",
            code: 400
        );
    }
}
