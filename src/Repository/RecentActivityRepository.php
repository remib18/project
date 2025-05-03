<?php

namespace App\Repository;

use App\Entity\User;
use App\DTO\RecentActivityDTO;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Repository for querying recent activities from the database view
 */
class RecentActivityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get recent activities for a specific user
     *
     * @param User $user The user to get activities for
     * @param int $limit Maximum number of activities to return
     * @return array<RecentActivityDTO>
     */
    public function getRecentActivitiesForUser(User $user, int $limit = 5): array
    {
        $conn = $this->entityManager->getConnection();
        $isTeacher = in_array('ROLE_TEACHER', $user->getRoles());
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        // Basic query structure
        $sql = "
            SELECT 
                id,
                activity_type,
                user_id,
                user_name,
                action_text,
                '/course/' || course_slug AS target,
                alert_message
            FROM 
                recent_course_activities
            WHERE 
                1=1
        ";

        $params = [];
        $types = [];

        // Different filtering based on user role
        if (!$isAdmin) {
            // For teachers, we want to see all activities in their courses
            if ($isTeacher) {
                $sql .= "
                    AND course_unit_id IN (
                        SELECT DISTINCT cu.id
                        FROM course_unit cu
                        JOIN course_group cg ON cg.unit_id = cu.id
                        JOIN course_group_user cgu ON cgu.course_group_id = cg.id
                        WHERE cgu.user_id = :userId
                    )
                ";
                $params['userId'] = $user->getId();
                $types['userId'] = Types::INTEGER;
            } else {
                // For students, only show their own activities or alerts
                $sql .= "
                    AND (
                        (user_id = :userId) OR
                        (alert_message IS NOT NULL AND course_unit_id IN (
                            SELECT DISTINCT cu.id
                            FROM course_unit cu
                            JOIN course_group cg ON cg.unit_id = cu.id
                            JOIN course_group_user cgu ON cgu.course_group_id = cg.id
                            WHERE cgu.user_id = :userId
                        ))
                    )
                ";
                $params['userId'] = $user->getId();
                $types['userId'] = Types::INTEGER;
            }
        }

        // Finalize query with order and limit
        $sql .= "
            ORDER BY 
                updated_at DESC
            LIMIT :limit
        ";
        $params['limit'] = $limit;
        $types['limit'] = Types::INTEGER;

        $stmt = $conn->executeQuery($sql, $params, $types);
        $results = $stmt->fetchAllAssociative();

        return array_map(function ($row) {
            $type = $row['alert_message'] ? 'alert' : 'user';

            return new RecentActivityDTO(
                $type,
                $type === 'alert' ? null : $row['user_name'],
                $type === 'alert' ? null : $row['action_text'],
                $row['target'],
                $type === 'alert' ? $row['alert_message'] : null
            );
        }, $results);
    }
}