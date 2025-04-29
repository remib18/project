<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Value object to represent course schedule
 */
#[ORM\Embeddable]
class CourseSchedule
{
    public const DAYS_OF_WEEK = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 7)]
    private int $dayOfWeek;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private \DateTimeInterface $startTime;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private \DateTimeInterface $endTime;

    public function __construct(int $dayOfWeek, \DateTimeInterface $startTime, \DateTimeInterface $endTime)
    {
        if ($dayOfWeek < 1 || $dayOfWeek > 7) {
            throw new \InvalidArgumentException('Day of week must be between 1 and 7');
        }

        if ($startTime >= $endTime) {
            throw new \InvalidArgumentException('Start time must be before end time');
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

    public function getDayName(): string
    {
        return self::DAYS_OF_WEEK[$this->dayOfWeek];
    }

    /**
     * Format time in format HH:MM
     */
    public function getFormattedStartTime(): string
    {
        return $this->startTime->format('H\hi');
    }

    /**
     * Format time in format HH:MM
     */
    public function getFormattedEndTime(): string
    {
        return $this->endTime->format('H\hi');
    }

    /**
     * Check if a given datetime is within this schedule's time period for the current week
     */
    public function isNow(\DateTimeInterface $dateTime): bool
    {
        // Check if current day matches
        if ((int)$dateTime->format('N') !== $this->dayOfWeek) {
            return false;
        }

        // Extract time only for comparison
        $currentTime = \DateTime::createFromFormat('H:i', $dateTime->format('H:i'));
        $startTime = \DateTime::createFromFormat('H:i', $this->startTime->format('H:i'));
        $endTime = \DateTime::createFromFormat('H:i', $this->endTime->format('H:i'));

        return $currentTime >= $startTime && $currentTime < $endTime;
    }

    /**
     * Get the next occurrence of this schedule
     */
    public function getNextOccurrence(\DateTimeInterface $from): \DateTime
    {
        $now = new \DateTime($from->format('Y-m-d H:i:s'));
        $currentDayOfWeek = (int)$now->format('N');
        $targetDayOfWeek = $this->dayOfWeek;

        // Calculate days to add
        $daysToAdd = 0;
        if ($currentDayOfWeek < $targetDayOfWeek) {
            $daysToAdd = $targetDayOfWeek - $currentDayOfWeek;
        } elseif ($currentDayOfWeek > $targetDayOfWeek) {
            $daysToAdd = 7 - ($currentDayOfWeek - $targetDayOfWeek);
        } else {
            // Same day, check time
            $currentTime = \DateTime::createFromFormat('H:i', $now->format('H:i'));
            $startTime = \DateTime::createFromFormat('H:i', $this->startTime->format('H:i'));

            if ($currentTime < $startTime) {
                // Today's occurrence is still coming
                $daysToAdd = 0;
            } else {
                // Today's occurrence has passed, get next week
                $daysToAdd = 7;
            }
        }

        $nextDate = clone $now;
        $nextDate->modify("+{$daysToAdd} days");
        $nextDate->setTime(
            (int)$this->startTime->format('H'),
            (int)$this->startTime->format('i'),
            0
        );

        return $nextDate;
    }
}