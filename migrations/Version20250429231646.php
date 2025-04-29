<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250429231646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group ADD name VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group ADD room VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group ADD schedule_day_of_week SMALLINT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group ADD schedule_start_time TIME(0) WITHOUT TIME ZONE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group ADD schedule_end_time TIME(0) WITHOUT TIME ZONE NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group DROP name
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group DROP room
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group DROP schedule_day_of_week
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group DROP schedule_start_time
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group DROP schedule_end_time
        SQL);
    }
}
