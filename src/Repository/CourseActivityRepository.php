<?php

namespace App\Repository;

use App\Entity\CourseActivity;
use App\Entity\CourseUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseActivity>
 */
class CourseActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseActivity::class);
    }

    /**
     * Get recent activities for a course unit
     *
     * @param CourseUnit $courseUnit
     * @param int $limit
     * @return array<CourseActivity>
     */
    public function getRecentActivities(CourseUnit $courseUnit, int $limit = 3): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.courseUnit = :courseUnit')
            ->setParameter('courseUnit', $courseUnit)
            ->orderBy('a.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get pinned resources for a course unit
     *
     * @param CourseUnit $courseUnit
     * @return array<CourseActivity>
     */
    public function getPinnedResources(CourseUnit $courseUnit): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.courseUnit = :courseUnit')
            ->andWhere('a.isPinned = :pinned')
            ->setParameter('courseUnit', $courseUnit)
            ->setParameter('pinned', true)
            ->orderBy('a.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get activities by category
     *
     * @param CourseUnit $courseUnit
     * @return array<CourseActivity>
     */
    public function getActivitiesByCategory(CourseUnit $courseUnit): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.courseUnit = :courseUnit')
            ->setParameter('courseUnit', $courseUnit)
            ->orderBy('a.category', 'ASC')
            ->addOrderBy('a.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}