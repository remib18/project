<?php

namespace App\Repository;

use App\Entity\CourseUnit;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseUnit>
 *
 * @method CourseUnit|null find($id, $lockMode = null, $lockVersion = null)
 * @method CourseUnit|null findOneBy(array $criteria, array $orderBy = null)
 * @method CourseUnit[]    findAll()
 * @method CourseUnit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseUnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseUnit::class);
    }

    /**
     * Find course units accessible to a specific user
     *
     * @param User $user The current user
     * @return array<CourseUnit>
     */
    public function findCourseUnitsForUser(User $user): array
    {
        return $this->createQueryBuilder('cu')
            ->distinct()
            ->innerJoin('cu.groups', 'g')
            ->innerJoin('g.members', 'm', Join::WITH, 'm = :user')
            ->setParameter('user', $user)
            ->orderBy('cu.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a course unit by slug with validation
     *
     * @param string $slug
     * @return CourseUnit
     * @throws \Exception If course unit with the given slug is not found
     */
    public function findBySlugOrFail(string $slug): CourseUnit
    {
        $courseUnit = $this->findOneBy(['slug' => $slug]);

        if (!$courseUnit) {
            throw new \Exception(sprintf('Course with slug "%s" not found', $slug));
        }

        return $courseUnit;
    }

    /**
     * Find a course unit accessible to a specific user by slug
     *
     * @param string $slug The course unit slug
     * @param User $user The current user
     * @return CourseUnit|null
     */
    public function findAccessibleCourseUnitBySlug(string $slug, User $user): ?CourseUnit
    {
        return $this->createQueryBuilder('cu')
            ->distinct()
            ->innerJoin('cu.groups', 'g')
            ->innerJoin('g.members', 'm', Join::WITH, 'm = :user')
            ->where('cu.slug = :slug')
            ->setParameter('user', $user)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}