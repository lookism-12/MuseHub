<?php

namespace App\Service;

use App\Entity\User;

class RoleAssigner
{
    public function assignRole(User $user, string $requestedRole = null): void
    {
        // If role is explicitly requested during registration
        if ($requestedRole) {
            $validRoles = ['ROLE_USER', 'ROLE_ARTIST'];
            if (in_array($requestedRole, $validRoles, true)) {
                $user->setRoles([$requestedRole]);
                return;
            }
        }

        // Auto-assign based on profile information
        // If user has bio and avatar, likely an artist
        if ($user->getBio() && $user->getAvatarUrl()) {
            $user->setRoles(['ROLE_ARTIST']);
        } else {
            $user->setRoles(['ROLE_USER']);
        }
    }
}

