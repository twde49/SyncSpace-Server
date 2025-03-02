<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214195244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file (id SERIAL NOT NULL, owner_id INT NOT NULL, filename VARCHAR(255) NOT NULL, filepath VARCHAR(255) NOT NULL, size INT NOT NULL, mime_type VARCHAR(50) NOT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8C9F36107E3C61F9 ON file (owner_id)');
        $this->addSql('COMMENT ON COLUMN file.uploaded_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE file_user (file_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(file_id, user_id))');
        $this->addSql('CREATE INDEX IDX_46FBE2793CB796C ON file_user (file_id)');
        $this->addSql('CREATE INDEX IDX_46FBE27A76ED395 ON file_user (user_id)');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36107E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file_user ADD CONSTRAINT FK_46FBE2793CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file_user ADD CONSTRAINT FK_46FBE27A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE file DROP CONSTRAINT FK_8C9F36107E3C61F9');
        $this->addSql('ALTER TABLE file_user DROP CONSTRAINT FK_46FBE2793CB796C');
        $this->addSql('ALTER TABLE file_user DROP CONSTRAINT FK_46FBE27A76ED395');
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE file_user');
    }
}
