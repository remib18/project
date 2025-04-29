<?php

namespace App\Repository;

use App\Entity\CourseGroup;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

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
     * Find current ongoing course groups for a user
     *
     * @param User $user
     * @param \DateTimeInterface|null $dateTime
     * @return array<CourseGroup>
     */
    public function findCurrentGroupsForUser(User $user, \DateTimeInterface $dateTime = null): array
    {
        $dateTime = $dateTime ?? new \DateTime();
        $dayOfWeek = (int)$dateTime->format('N'); // 1 (Monday) to 7 (Sunday)
        $currentTime = $dateTime->format('H:i:s');

        return $this->createQueryBuilder('g')
            ->innerJoin('g.members', 'm', Join::WITH, 'm = :user')
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
     * Find upcoming course groups for a user
     *
     * @param User $user
     * @param \DateTimeInterface|null $dateTime
     * @param int $limit
     * @return array<CourseGroup>
     */
    public function findUpcomingGroupsForUser(User $user, \DateTimeInterface $dateTime = null, int $limit = 3): array
    {
        $dateTime = $dateTime ?? new \DateTime();
        $dayOfWeek = (int)$dateTime->format('N'); // 1 (Monday) to 7 (Sunday)
        $currentTime = $dateTime->format('H:i:s');

        $qb = $this->createQueryBuilder('g')
            ->innerJoin('g.members', 'm', Join::WITH, 'm = :user')
            ->innerJoin('g.unit', 'u')
            ->setParameter('user', $user)
            ->setMaxResults($limit);

        // First, get groups that are later today
        $todayGroups = (clone $qb)
            ->where('g.schedule.dayOfWeek = :dayOfWeek')
            ->andWhere('g.schedule.startTime > :currentTime')
            ->setParameter('dayOfWeek', $dayOfWeek)
            ->setParameter('currentTime', $currentTime)
            ->orderBy('g.schedule.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $remainingLimit = $limit - count($todayGroups);

        // If we need more, get groups from upcoming days
        if ($remainingLimit > 0) {
            $futureDays = [];
            for ($i = 1; $i <= 7; $i++) {
                $futureDay = ($dayOfWeek + $i) % 7;
                if ($futureDay === 0) $futureDay = 7; // Sunday is 7, not 0
                $futureDays[] = $futureDay;
            }

            $futureGroups = $this->createQueryBuilder('g')
                ->innerJoin('g.members', 'm', Join::WITH, 'm = :user')
                ->innerJoin('g.unit', 'u')
                ->where('g.schedule.dayOfWeek IN (:futureDays)')
                ->setParameter('user', $user)
                ->setParameter('futureDays', $futureDays)
                ->orderBy('g.schedule.dayOfWeek', 'ASC')
                ->addOrderBy('g.schedule.startTime', 'ASC')
                ->setMaxResults($remainingLimit)
                ->getQuery()
                ->getResult();

            return array_merge($todayGroups, $futureGroups);
        }

        return $todayGroups;
    }

    /**
     * Find course groups by room
     *
     * @param string $room
     * @return array<CourseGroup>
     */
    public function findByRoom(string $room): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.room = :room')
            ->setParameter('room', $room)
            ->getQuery()
            ->getResult();
    }
}