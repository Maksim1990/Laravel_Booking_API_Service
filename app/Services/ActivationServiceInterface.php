<?php

namespace App\Services;

use App\Enums\StatusEnum;
use App\Models\Admin;
use App\Models\User;

interface ActivationServiceInterface
{
    public function changeUserStatus(User $user, ?StatusEnum $status): User;

    public function changeAdminStatus(Admin $admin, ?StatusEnum $status): Admin;
}
