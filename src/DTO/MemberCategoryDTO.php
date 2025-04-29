<?php

namespace App\DTO;

use App\Entity\User;

/**
 * Data Transfer Object for Member Category information
 */
readonly class MemberCategoryDTO
{
    /**
     * @param string $title The category title
     * @param string $desc The category description
     * @param array<User> $members Users in this category
     */
    public function __construct(
        public string $title,
        public string $desc,
        public array  $members
    ) {
    }
}