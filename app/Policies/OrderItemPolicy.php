<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderItemPolicy
{
    use HandlesAuthorization;

    public function owner(User $user, $item)
    {
        return Gate::check('owner', $item->order);
    }

    public function edit(User $user, $item)
    {
        if ($item->isOrganizationItem()) {
            return $user->is_super_admin;
        }

        if ($item->is_canceled) {
            return false;
        }

        return true;
    }
}
