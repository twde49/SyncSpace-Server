<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250208093702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE password_item ADD password_encrypted TEXT NOT NULL');
        $this->addSql('ALTER TABLE password_item ADD notes_encrypted TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE password_item ADD iv TEXT NOT NULL');
        $this->addSql('ALTER TABLE password_item ADD must_be_updated BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE password_item DROP password');
        $this->addSql('ALTER TABLE password_item DROP notes');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE password_item ADD password TEXT NOT NULL');
        $this->addSql('ALTER TABLE password_item ADD notes TEXT NOT NULL');
        $this->addSql('ALTER TABLE password_item DROP password_encrypted');
        $this->addSql('ALTER TABLE password_item DROP notes_encrypted');
        $this->addSql('ALTER TABLE password_item DROP iv');
        $this->addSql('ALTER TABLE password_item DROP must_be_updated');
    }
}
