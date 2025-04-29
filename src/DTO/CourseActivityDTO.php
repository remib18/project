<?php

namespace App\DTO;

/**
 * Data Transfer Object for Course Activity information
 */
readonly class CourseActivityDTO
{
    /**
     * @param string $title The activity title
     * @param string $desc Description or formatted time
     * @param string $target URL target for the activity
     * @param string $action Action text (e.g., "View" or "Submit")
     * @param string|null $userDesc Optional pinned message
     * @param string $type Activity type
     * @param string $icon Icon name
     * @param bool $isPinned Whether the activity is pinned
     * @param string|null $severity Optional severity level
     * @param string|null $content Optional content
     */
    public function __construct(
        public string  $title,
        public string  $desc,
        public string  $target,
        public string  $action,
        public ?string $userDesc,
        public string  $type,
        public string  $icon,
        public bool    $isPinned,
        public ?string $severity,
        public ?string $content
    ) {
    }
}