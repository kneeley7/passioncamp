<?php

namespace App\Policies;

use App\Room;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomPolicy
{
    use HandlesAuthorization;

    public function owner(User $user, Room $room)
    {
        return $user->organization_id === $room->organization_id;
    }
}
