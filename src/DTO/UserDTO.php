<?php

namespace App\DTO;

/**
 * Data Transfer Object for User information
 */
readonly class UserDTO
{
    /**
     * @param int $id The user ID
     * @param string $email The user email
     * @param string $firstname The user's first name
     * @param string $lastname The user's last name
     * @param array $roles The user's roles
     * @param array $groupIds IDs of groups the user belongs to (not full entities)
     */
    public function __construct(
        public int     $id,
        public string  $email,
        public string  $firstname,
        public string  $lastname,
        public array   $roles,
        public array   $groupIds = []
    ) {
    }
}