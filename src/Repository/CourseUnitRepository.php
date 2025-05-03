<?php

namespace App\Repository;

use App\Entity\CourseUnit;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @extends ServiceEntityRepository<CourseUnit>
 */
class CourseUnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseUnit::class);
    }

    /**
     * Find course units accessible to a specific user with eager loading of related entities
     *
     * @param User $user The current user
     * @return array<CourseUnit>
     */
    public function findCourseUnitsForUser(User $user): array
    {
        return $this->createQueryBuilder('cu')
            ->distinct()
            ->select('cu, g')
            ->innerJoin('cu.groups', 'g')
            ->innerJoin('g.members', 'm', Join::WITH, 'm = :user')
            ->setParameter('user', $user)
            ->orderBy('cu.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a course unit by slug with validation and eager loading of related entities
     *
     * @param string $slug
     * @return CourseUnit
     * @throws Exception If course unit with the given slug is not found
     */
    public function findBySlugOrFail(string $slug): CourseUnit
    {
        $courseUnit = $this->createQueryBuilder('cu')
            ->select('cu, g')
            ->leftJoin('cu.groups', 'g')
            ->where('cu.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$courseUnit) {
            throw new Exception(sprintf('Course with slug "%s" not found', $slug));
        }

        return $courseUnit;
    }

    /**
     * Find a course unit accessible to a specific user by slug with eager loading
     *
     * @param string $slug The course unit slug
     * @param User $user The current user
     * @return CourseUnit|null
     */
    public function findAccessibleCourseUnitBySlug(string $slug, User $user): ?CourseUnit
    {
        return $this->createQueryBuilder('cu')
            ->distinct()
            ->select('cu, g, a')
            ->innerJoin('cu.groups', 'g')
            ->innerJoin('g.members', 'm', Join::WITH, 'm = :user')
            ->leftJoin('cu.activities', 'a')
            ->where('cu.slug = :slug')
            ->setParameter('user', $user)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find course units by search term with pagination and eager loading
     *
     * @param string $searchTerm
     * @param int $limit
     * @param int $offset
     * @return array<CourseUnit>
     */
    public function findBySearchTerm(string $searchTerm, int $limit, int $offset): array
    {
        return $this->createQueryBuilder('cu')
            ->select('cu, g')
            ->leftJoin('cu.groups', 'g')
            ->where('cu.name LIKE :searchTerm OR cu.description LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('cu.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}