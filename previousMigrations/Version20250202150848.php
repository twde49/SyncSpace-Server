<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250202150848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE password_item (id SERIAL NOT NULL, url TEXT NOT NULL, name TEXT NOT NULL, email TEXT NOT NULL, password TEXT NOT NULL, notes TEXT NOT NULL, is_favorite BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE conversation ALTER last_active_user_id SET NOT NULL');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14F675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE password_item');
        $this->addSql('ALTER TABLE conversation ALTER last_active_user_id DROP NOT NULL');
        $this->addSql('ALTER TABLE note DROP CONSTRAINT FK_CFBDFA14F675F31B');
    }
}
