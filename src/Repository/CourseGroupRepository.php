<?php

namespace App\Repository;

use App\Entity\CourseGroup;
use App\Entity\CourseUnit;
use App\Entity\User;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<CourseGroup>
 */
class CourseGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseGroup::class);
    }

    /**
     * Find course group by ID with eager loading of related entities
     *
     * @param int $id The course group ID
     * @return CourseGroup|null
     */
    public function findWithRelations(int $id): ?CourseGroup
    {
        return $this->createQueryBuilder('g')
            ->addSelect('u', 'm')
            ->innerJoin('g.unit', 'u')
            ->leftJoin('g.members', 'm')
            ->where('g.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find current ongoing course groups for a user with eager loading
     *
     * @param User $user
     * @param DateTimeInterface|null $dateTime
     * @return array<CourseGroup>
     */
    public function findCurrentGroupsForUser(User $user, ?DateTimeInterface $dateTime = null): array
    {
        $dateTime = $dateTime ?? new \DateTime();
        $dayOfWeek = (int)$dateTime->format('N') - 1; // 0 (Monday) to 6 (Sunday)
        $currentTime = $dateTime->format('H:i:s');

        return $this->createQueryBuilder('g')
            ->select('g, u')  // Removed incorrect 's' alias
            ->innerJoin('g.members', 'm', 'WITH', 'm = :user')
            ->innerJoin('g.unit', 'u')
            ->where('g.schedule.dayOfWeek = :dayOfWeek')
            ->andWhere('g.schedule.startTime <= :currentTime')
            ->andWhere('g.schedule.endTime > :currentTime')
            ->setParameter('user', $user)
            ->setParameter('dayOfWeek', $dayOfWeek)
            ->setParameter('currentTime', $currentTime)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming course groups for a user with eager loading
     *
     * @param User $user
     * @param DateTimeInterface|null $dateTime
     * @param int $limit
     * @return array<CourseGroup>
     */
    public function findUpcomingGroupsForUser(User $user, ?DateTimeInterface $dateTime = null, int $limit = 3): array
    {
        $dateTime = $dateTime ?? new \DateTime();
        $dayOfWeek = (int)$dateTime->format('N') - 1; // 0 (Monday) to 6 (Sunday)
        $currentTime = $dateTime->format('H:i:s');

        // Create a list of days in order from today to 6 days from now
        $orderedDays = [];
        for ($i = 0; $i < 7; $i++) {
            $orderedDays[] = ($dayOfWeek + $i) % 7;
        }

        // Get all potential groups in a single query
        $allGroups = $this->createQueryBuilder('g')
            ->select('g, u')
            ->innerJoin('g.members', 'm', 'WITH', 'm = :user')
            ->innerJoin('g.unit', 'u')
            ->where('g.schedule.dayOfWeek IN (:allDays)')
            ->setParameter('user', $user)
            ->setParameter('allDays', $orderedDays)
            ->getQuery()
            ->getResult();

        // Filter and sort in PHP
        $today = $orderedDays[0];
        $futureGroups = [];
        $todayGroups = [];

        foreach ($allGroups as $group) {
            $schedule = $group->getSchedule();
            if ($schedule->getDayOfWeek() === $today) {
                // Only include today's groups if they're in the future
                if ($schedule->getStartTime()->format('H:i:s') > $currentTime) {
                    $todayGroups[] = $group;
                }
            } else {
                $futureGroups[] = $group;
            }
        }

        // Sort today's groups by start time
        usort($todayGroups, function($a, $b) {
            return $a->getSchedule()->getStartTime()->format('H:i:s') <=> $b->getSchedule()->getStartTime()->format('H:i:s');
        });

        // Sort future groups by day first, then time
        usort($futureGroups, function($a, $b) use ($orderedDays) {
            $dayA = $a->getSchedule()->getDayOfWeek();
            $dayB = $b->getSchedule()->getDayOfWeek();

            // Get the index of the day in our ordered array
            $dayIndexA = array_search($dayA, $orderedDays);
            $dayIndexB = array_search($dayB, $orderedDays);

            // Compare days first
            $dayComparison = $dayIndexA <=> $dayIndexB;
            if ($dayComparison !== 0) {
                return $dayComparison;
            }

            // If same day, compare times
            return $a->getSchedule()->getStartTime()->format('H:i:s') <=> $b->getSchedule()->getStartTime()->format('H:i:s');
        });

        // Combine and limit
        $result = array_merge($todayGroups, $futureGroups);
        return array_slice($result, 0, $limit);
    }

    /**
     * Find course groups by room with eager loading
     *
     * @param string $room
     * @return array<CourseGroup>
     */
    public function findByRoom(string $room): array
    {
        return $this->createQueryBuilder('g')
            ->select('g, u')
            ->leftJoin('g.unit', 'u')
            ->where('g.room = :room')
            ->setParameter('room', $room)
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a user is a member of any group in a course unit
     *
     * @param UserInterface $user
     * @param CourseUnit $courseUnit
     * @return bool
     */
    public function isUserInCourseUnit(UserInterface $user, CourseUnit $courseUnit): bool
    {
        $qb = $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->innerJoin('g.members', 'm')
            ->innerJoin('g.unit', 'u')
            ->where('m = :user')
            ->andWhere('u = :courseUnit')
            ->setParameter('user', $user)
            ->setParameter('courseUnit', $courseUnit);

        $count = $qb->getQuery()->getSingleScalarResult();

        return $count > 0;
    }
}