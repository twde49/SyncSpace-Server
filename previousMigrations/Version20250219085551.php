<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250219085551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE file ADD is_folder BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F3610727ACA70 FOREIGN KEY (parent_id) REFERENCES file (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8C9F3610727ACA70 ON file (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE file DROP CONSTRAINT FK_8C9F3610727ACA70');
        $this->addSql('DROP INDEX IDX_8C9F3610727ACA70');
        $this->addSql('ALTER TABLE file DROP parent_id');
        $this->addSql('ALTER TABLE file DROP is_folder');
    }
}
