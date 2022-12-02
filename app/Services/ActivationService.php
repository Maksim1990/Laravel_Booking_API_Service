<?php

namespace App\Services;

use App\Enums\StatusEnum;
use App\Exceptions\InvalidUserStatusException;
use App\Models\Admin;
use App\Models\User;

class ActivationService implements ActivationServiceInterface
{
    public function changeUserStatus(User $user, ?StatusEnum $status): User
    {
        //
    }

    public function changeAdminStatus(Admin $admin, ?StatusEnum $status): Admin
    {
        if ($status === null) {
            throw new InvalidUserStatusException();
        }
        $admin->status = $status->getStringValue();
        $admin->save();
        return $admin;
    }
}
