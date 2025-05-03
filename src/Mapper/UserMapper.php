<?php

namespace App\Mapper;

use App\DTO\UserDTO;
use App\Entity\User;

/**
 * Maps User entities to DTOs
 */
class UserMapper
{
    /**
     * Convert a User entity to a UserDTO
     *
     * @param User $user
     * @return UserDTO
     */
    public function mapUserToDTO(User $user): UserDTO
    {
        // Extract only group IDs to avoid circular references
        $groupIds = [];
        foreach ($user->getMemberInGroups() as $group) {
            $groupIds[] = $group->getId();
        }

        return new UserDTO(
            $user->getId(),
            $user->getEmail(),
            $user->getFirstname(),
            $user->getLastname(),
            $user->getRoles(),
            $groupIds
        );
    }

    /**
     * Convert multiple User entities to DTOs
     *
     * @param array $users
     * @return array
     */
    public function mapUsersToDTO(array $users): array
    {
        $dtos = [];
        foreach ($users as $user) {
            $dtos[] = $this->mapUserToDTO($user);
        }
        return $dtos;
    }
}