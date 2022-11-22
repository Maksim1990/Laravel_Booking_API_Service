<?php

namespace App\Services;

use App\Enums\StatusEnum;
use App\Exceptions\InvalidUserStatusException;
use App\Models\User;

class UserActivationService implements UserActivationServiceInterface
{
    public function changeUserStatus(User $user, ?StatusEnum $status): User
    {
        if ($status === null) {
            throw new InvalidUserStatusException();
        }
        $user->status = $status->getStringValue();
        $user->save();
        return $user;
    }
}
