<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250905123314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE note_user (note_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(note_id, user_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2DE9C71126ED0855 ON note_user (note_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2DE9C711A76ED395 ON note_user (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note_user ADD CONSTRAINT FK_2DE9C71126ED0855 FOREIGN KEY (note_id) REFERENCES note (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note_user ADD CONSTRAINT FK_2DE9C711A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note_user DROP CONSTRAINT FK_2DE9C71126ED0855
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note_user DROP CONSTRAINT FK_2DE9C711A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE note_user
        SQL);
    }
}
