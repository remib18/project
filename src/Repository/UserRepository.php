<?php

namespace App\Repository;

use App\Entity\CourseGroup;
use App\Entity\CourseUnit;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find users by search term with pagination.
     * @param string $searchTerm The search term to filter users.
     * @param int $limit The maximum number of users to return.
     * @param int $offset The offset for pagination.
     * @return array|object[] An array of User entities matching the search term.
     */
    public function findBySearchTerm(string $searchTerm, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.firstname LIKE :searchTerm OR u.email LIKE :searchTerm OR u.lastname LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('u.lastname', 'DESC')
            ->addOrderBy('u.firstname', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get course members separated by role
     *
     * @param CourseUnit $courseUnit
     * @return array{professors: array<User>, students: array<User>}
     */
    public function getMembersByRole(CourseUnit $courseUnit): array
    {
        $members = [
            'professors' => [],
            'students' => []
        ];

        // Get all groups in the course unit
        $groups = $courseUnit->getGroups();

        foreach ($groups as $group) {
            $groupMembers = $group->getMembers();

            foreach ($groupMembers as $member) {
                $roles = $member->getRoles();
                $roleCategoryKey = in_array('ROLE_TEACHER', $roles) ? 'professors' : 'students';

                $members[$roleCategoryKey][] = $member;
            }
        }

        // Remove duplicates
        $members['professors'] = array_unique($members['professors'], SORT_REGULAR);
        $members['students'] = array_unique($members['students'], SORT_REGULAR);

        return $members;
    }

    /**
     * Find users who are not members of a specific group
     *
     * @param CourseGroup $group
     * @return array
     */
    public function findUsersNotInGroup(CourseGroup $group): array
    {
        $qb = $this->createQueryBuilder('u');

        // Get all users who are not members of the specified group
        $qb->leftJoin('u.memberInGroups', 'g', 'WITH', 'g.id = :groupId')
            ->where('g.id IS NULL')
            ->setParameter('groupId', $group->getId())
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find members of a course group filtered by role with pagination
     *
     * @param CourseGroup $group The group to find members for
     * @param Role $role The role to filter by
     * @param int $limit Maximum number of results
     * @param int $offset Starting index for pagination
     * @param string $searchTerm Optional search term to filter by name or email
     * @return array
     */
    public function findGroupMembersByRole(CourseGroup $group, Role $role, int $limit = 20, int $offset = 0, string $searchTerm = ''): array
    {
        $qb = $this->createQueryBuilder('u');

        // Join with the group's members
        $qb->innerJoin('u.memberInGroups', 'g')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $group->getId());

        // Add role filter condition using the MEMBER OF syntax for arrays
        $qb->andWhere(':role MEMBER OF u.roles')
            ->setParameter('role', $role->value);

        // Add search term condition if provided
        if (!empty($searchTerm)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.firstname', ':searchTerm'),
                    $qb->expr()->like('u.lastname', ':searchTerm'),
                    $qb->expr()->like('u.email', ':searchTerm')
                )
            );
            $qb->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        // Add ordering
        $qb->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC');

        // Add pagination
        $qb->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Count members of a course group filtered by role
     *
     * @param CourseGroup $group The group to count members for
     * @param Role $role The role to filter by
     * @param string $searchTerm Optional search term to filter by name or email
     * @return int
     */
    public function countGroupMembersByRole(CourseGroup $group, Role $role, string $searchTerm = ''): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        // Join with the group's members
        $qb->innerJoin('u.memberInGroups', 'g')
            ->where('g.id = :groupId')
            ->setParameter('groupId', $group->getId());

        // Add role filter condition
        $qb->andWhere(':role MEMBER OF u.roles')
            ->setParameter('role', $role->value);

        // Add search term condition if provided
        if (!empty($searchTerm)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.firstname', ':searchTerm'),
                    $qb->expr()->like('u.lastname', ':searchTerm'),
                    $qb->expr()->like('u.email', ':searchTerm')
                )
            );
            $qb->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find users not in a specific group filtered by role with pagination
     *
     * @param CourseGroup $group The group to exclude members from
     * @param Role $role The role to filter by
     * @param int $limit Maximum number of results
     * @param int $offset Starting index for pagination
     * @param string $searchTerm Optional search term to filter by name or email
     * @return array
     */
    public function findUsersNotInGroupByRole(CourseGroup $group, Role $role, int $limit = 20, int $offset = 0, string $searchTerm = ''): array
    {
        $qb = $this->createQueryBuilder('u');

        // Left join with the group's members to find users who are not in the group
        $qb->leftJoin('u.memberInGroups', 'g', 'WITH', 'g.id = :groupId')
            ->where('g.id IS NULL')
            ->setParameter('groupId', $group->getId());

        // Add role filter condition
        $qb->andWhere(':role MEMBER OF u.roles')
            ->setParameter('role', $role->value);

        // Add search term condition if provided
        if (!empty($searchTerm)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.firstname', ':searchTerm'),
                    $qb->expr()->like('u.lastname', ':searchTerm'),
                    $qb->expr()->like('u.email', ':searchTerm')
                )
            );
            $qb->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        // Add ordering
        $qb->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC');

        // Add pagination
        $qb->setMaxResults($limit)
            ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Count users not in a specific group filtered by role
     *
     * @param CourseGroup $group The group to exclude members from
     * @param Role $role The role to filter by
     * @param string $searchTerm Optional search term to filter by name or email
     * @return int
     */
    public function countUsersNotInGroupByRole(CourseGroup $group, Role $role, string $searchTerm = ''): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)');

        // Left join with the group's members to find users who are not in the group
        $qb->leftJoin('u.memberInGroups', 'g', 'WITH', 'g.id = :groupId')
            ->where('g.id IS NULL')
            ->setParameter('groupId', $group->getId());

        // Add role filter condition
        $qb->andWhere(':role MEMBER OF u.roles')
            ->setParameter('role', $role->value);

        // Add search term condition if provided
        if (!empty($searchTerm)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.firstname', ':searchTerm'),
                    $qb->expr()->like('u.lastname', ':searchTerm'),
                    $qb->expr()->like('u.email', ':searchTerm')
                )
            );
            $qb->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
