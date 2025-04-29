<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250429231119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adding a view for recent course activities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE VIEW recent_course_activities AS
            SELECT
                ca.id,
                ca.type AS activity_type,
                ca.name AS activity_name,
                ca.updated_at,
                ca.course_unit_id,
                cu.slug AS course_slug,
                ca.is_pinned,
                u.id AS user_id,
                CONCAT(u.firstname, \' \', u.lastname) AS user_name,
                CASE
                    WHEN ca.type = \'document\' THEN \'a ajouté une nouvelle ressource dans l\'\'UE \'
                    WHEN ca.type = \'document-submission\' THEN \'a ajouté un devoir à rendre dans l\'\'UE \'
                    WHEN ca.type = \'message\' THEN \'a publié un message dans l\'\'UE \'
                    ELSE \'a modifié une ressource de l\'\'UE \'
                END || cu.name AS action_text,
                CASE
                    WHEN ca.is_pinned = TRUE THEN ca.pinned_message
                    ELSE NULL
                END AS alert_message
            FROM
                course_activity ca
            JOIN
                course_unit cu ON ca.course_unit_id = cu.id
            JOIN
                "user" u ON u.id = (ca.data->>\'created_by\')::int
            ORDER BY
                ca.updated_at DESC;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS recent_course_activities');
    }
}
