<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250429220857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE course_activity (id SERIAL NOT NULL, course_unit_id INT NOT NULL, type VARCHAR(63) NOT NULL, data JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, name VARCHAR(255) NOT NULL, is_pinned BOOLEAN NOT NULL, pinned_message VARCHAR(255) DEFAULT NULL, category VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5C0640CF07E75E1 ON course_activity (course_unit_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN course_activity.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN course_activity.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE course_group (id SERIAL NOT NULL, unit_id INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_846B432DF8BD700D ON course_group (unit_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE course_group_user (course_group_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(course_group_id, user_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7B2BC3BA57E0B411 ON course_group_user (course_group_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_7B2BC3BAA76ED395 ON course_group_user (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE course_unit (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_activity ADD CONSTRAINT FK_5C0640CF07E75E1 FOREIGN KEY (course_unit_id) REFERENCES course_unit (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group ADD CONSTRAINT FK_846B432DF8BD700D FOREIGN KEY (unit_id) REFERENCES course_unit (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group_user ADD CONSTRAINT FK_7B2BC3BA57E0B411 FOREIGN KEY (course_group_id) REFERENCES course_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group_user ADD CONSTRAINT FK_7B2BC3BAA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_activity DROP CONSTRAINT FK_5C0640CF07E75E1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group DROP CONSTRAINT FK_846B432DF8BD700D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group_user DROP CONSTRAINT FK_7B2BC3BA57E0B411
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE course_group_user DROP CONSTRAINT FK_7B2BC3BAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE course_activity
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE course_group
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE course_group_user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE course_unit
        SQL);
    }
}
