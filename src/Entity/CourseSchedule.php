<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class CourseSchedule
{
    public const DAYS_OF_WEEK = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
    ];

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(
        notInRangeMessage: 'Day of week must be between {{ min }} and {{ max }}',
        min: 0,
        max: 6
    )]
    private int $dayOfWeek;

    #[ORM\Column(type: 'time')]
    #[Assert\NotNull(message: 'Start time cannot be null')]
    private \DateTimeInterface $startTime;

    #[ORM\Column(type: 'time')]
    #[Assert\NotNull(message: 'End time cannot be null')]
    #[Assert\Expression(
        "this.getEndTime() > this.getStartTime()",
        message: "End time must be after start time"
    )]
    private \DateTimeInterface $endTime;

    /**
     * CourseSchedule constructor
     *
     * @param int $dayOfWeek 0 (Monday) to 6 (Sunday)
     * @param \DateTimeInterface $startTime
     * @param \DateTimeInterface $endTime
     * @throws InvalidArgumentException
     */
    public function __construct(int $dayOfWeek, \DateTimeInterface $startTime, \DateTimeInterface $endTime)
    {
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new InvalidArgumentException('Day of week must be between 0 and 6');
        }

        if ($startTime >= $endTime) {
            throw new InvalidArgumentException('End time must be after start time');
        }

        $this->dayOfWeek = $dayOfWeek;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    /**
     * Check if the course is currently active
     *
     * @param \DateTimeInterface $now
     * @return bool
     */
    public function isNow(\DateTimeInterface $now): bool
    {
        $nowDayOfWeek = (int)$now->format('N') - 1; // 0 (Monday) to 6 (Sunday)
        $nowTime = \DateTime::createFromFormat('H:i:s', $now->format('H:i:s'));

        return $this->dayOfWeek === $nowDayOfWeek
            && $nowTime >= $this->startTime
            && $nowTime <= $this->endTime;
    }

    /**
     * Get the next occurrence of this schedule
     *
     * @param \DateTimeInterface $from
     * @return \DateTime
     */
    public function getNextOccurrence(\DateTimeInterface $from): \DateTime
    {
        $fromDayOfWeek = (int)$from->format('N') - 1; // 0 (Monday) to 6 (Sunday)
        $fromTime = \DateTime::createFromFormat('H:i:s', $from->format('H:i:s'));

        // Create a copy of the from date
        $next = new \DateTime($from->format('Y-m-d'));

        // If same day and time has not passed yet
        if ($fromDayOfWeek === $this->dayOfWeek && $fromTime < $this->startTime) {
            // Set the time to the start time
            $next->setTime(
                (int)$this->startTime->format('H'),
                (int)$this->startTime->format('i'),
                (int)$this->startTime->format('s')
            );
            return $next;
        }

        // Calculate days to add
        $daysToAdd = ($this->dayOfWeek - $fromDayOfWeek + 7) % 7;
        if ($daysToAdd === 0) {
            $daysToAdd = 7; // If same day but time has passed, go to next week
        }

        // Add days
        $next->modify("+{$daysToAdd} days");

        // Set time
        $next->setTime(
            (int)$this->startTime->format('H'),
            (int)$this->startTime->format('i'),
            (int)$this->startTime->format('s')
        );

        return $next;
    }

    /**
     * Get formatted start time (8:30, 14:00, etc.)
     *
     * @return string
     */
    public function getFormattedStartTime(): string
    {
        return $this->startTime->format('H:i');
    }

    /**
     * Get formatted end time (10:00, 15:30, etc.)
     *
     * @return string
     */
    public function getFormattedEndTime(): string
    {
        return $this->endTime->format('H:i');
    }

    /**
     * Get day name (Monday, Tuesday, etc.)
     *
     * @return string
     */
    public function getDayName(): string
    {
        return self::DAYS_OF_WEEK[$this->dayOfWeek];
    }
}