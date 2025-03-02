<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250202160940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE password_item ADD associated_to_id INT NOT NULL');
        $this->addSql('ALTER TABLE password_item ADD CONSTRAINT FK_8D4E2255D83C54C2 FOREIGN KEY (associated_to_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8D4E2255D83C54C2 ON password_item (associated_to_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE password_item DROP CONSTRAINT FK_8D4E2255D83C54C2');
        $this->addSql('DROP INDEX IDX_8D4E2255D83C54C2');
        $this->addSql('ALTER TABLE password_item DROP associated_to_id');
    }
}
