<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250318140421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_settings (id SERIAL NOT NULL, related_to_id INT NOT NULL, modules_layout JSON NOT NULL, theme VARCHAR(30) NOT NULL, notifications_enabled BOOLEAN NOT NULL, geolocation_enabled BOOLEAN NOT NULL DEFAULT false, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5C844C540B4AC4E ON user_settings (related_to_id)');
        $this->addSql('ALTER TABLE user_settings ADD CONSTRAINT FK_5C844C540B4AC4E FOREIGN KEY (related_to_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE user_settings DROP CONSTRAINT FK_5C844C540B4AC4E');
        $this->addSql('DROP TABLE user_settings');
    }
}
