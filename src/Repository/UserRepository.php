<?php

namespace App\Repository;

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
}
