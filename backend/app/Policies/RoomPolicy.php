<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('rooms.view');
    }

    public function view(User $user, Room $room): bool
    {
        return $user->hasPermissionTo('rooms.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('rooms.create');
    }

    public function update(User $user, Room $room): bool
    {
        return $user->hasPermissionTo('rooms.update');
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->hasPermissionTo('rooms.delete');
    }

    public function restore(User $user, Room $room): bool
    {
        return $user->hasPermissionTo('rooms.update');
    }

    public function forceDelete(User $user, Room $room): bool
    {
        return $user->hasPermissionTo('rooms.delete');
    }
}
