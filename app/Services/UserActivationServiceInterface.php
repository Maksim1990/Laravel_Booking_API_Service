<?php

namespace App\Services;

use App\Enums\StatusEnum;
use App\Models\User;

interface UserActivationServiceInterface
{
    public function changeUserStatus(User $user, ?StatusEnum $status): User;
}
