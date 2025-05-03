<?php

namespace App\DTO;

/**
 * Data Transfer Object for Recent Activities information
 */
readonly class RecentActivityDTO
{
    /**
     * @param string $type Activity type (user, alert, deadline, etc.)
     * @param string|null $user User who performed the action (if applicable)
     * @param string|null $action Action performed (if applicable)
     * @param string $target Target URL
     * @param string|null $message Alert message (if applicable)
     */
    public function __construct(
        public string  $type,
        public ?string $user,
        public ?string $action,
        public string  $target,
        public ?string $message = null
    ) {
    }
}