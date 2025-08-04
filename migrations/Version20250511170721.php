<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250511170721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE favorite_track (id SERIAL NOT NULL, related_to_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_606BD38240B4AC4E ON favorite_track (related_to_id)');
        $this->addSql('CREATE TABLE favorite_track_track (favorite_track_id INT NOT NULL, track_id INT NOT NULL, PRIMARY KEY(favorite_track_id, track_id))');
        $this->addSql('CREATE INDEX IDX_65061F42F48524B2 ON favorite_track_track (favorite_track_id)');
        $this->addSql('CREATE INDEX IDX_65061F425ED23C43 ON favorite_track_track (track_id)');
        $this->addSql('CREATE TABLE playlist (id SERIAL NOT NULL, related_to_id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D782112D40B4AC4E ON playlist (related_to_id)');
        $this->addSql('CREATE TABLE playlist_track (playlist_id INT NOT NULL, track_id INT NOT NULL, PRIMARY KEY(playlist_id, track_id))');
        $this->addSql('CREATE INDEX IDX_75FFE1E56BBD148 ON playlist_track (playlist_id)');
        $this->addSql('CREATE INDEX IDX_75FFE1E55ED23C43 ON playlist_track (track_id)');
        $this->addSql('CREATE TABLE track (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, artist VARCHAR(255) NOT NULL, youtube_id VARCHAR(255) NOT NULL, coverUrl VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE track_history (id SERIAL NOT NULL, of_user_id INT NOT NULL, played_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FE24E8565A1B2224 ON track_history (of_user_id)');
        $this->addSql('COMMENT ON COLUMN track_history.played_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE track_history_track (track_history_id INT NOT NULL, track_id INT NOT NULL, PRIMARY KEY(track_history_id, track_id))');
        $this->addSql('CREATE INDEX IDX_EA2157DA4D40AF9 ON track_history_track (track_history_id)');
        $this->addSql('CREATE INDEX IDX_EA2157D5ED23C43 ON track_history_track (track_id)');
        $this->addSql('ALTER TABLE favorite_track ADD CONSTRAINT FK_606BD38240B4AC4E FOREIGN KEY (related_to_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE favorite_track_track ADD CONSTRAINT FK_65061F42F48524B2 FOREIGN KEY (favorite_track_id) REFERENCES favorite_track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE favorite_track_track ADD CONSTRAINT FK_65061F425ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE playlist ADD CONSTRAINT FK_D782112D40B4AC4E FOREIGN KEY (related_to_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE playlist_track ADD CONSTRAINT FK_75FFE1E56BBD148 FOREIGN KEY (playlist_id) REFERENCES playlist (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE playlist_track ADD CONSTRAINT FK_75FFE1E55ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE track_history ADD CONSTRAINT FK_FE24E8565A1B2224 FOREIGN KEY (of_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE track_history_track ADD CONSTRAINT FK_EA2157DA4D40AF9 FOREIGN KEY (track_history_id) REFERENCES track_history (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE track_history_track ADD CONSTRAINT FK_EA2157D5ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE favorite_track DROP CONSTRAINT FK_606BD38240B4AC4E');
        $this->addSql('ALTER TABLE favorite_track_track DROP CONSTRAINT FK_65061F42F48524B2');
        $this->addSql('ALTER TABLE favorite_track_track DROP CONSTRAINT FK_65061F425ED23C43');
        $this->addSql('ALTER TABLE playlist DROP CONSTRAINT FK_D782112D40B4AC4E');
        $this->addSql('ALTER TABLE playlist_track DROP CONSTRAINT FK_75FFE1E56BBD148');
        $this->addSql('ALTER TABLE playlist_track DROP CONSTRAINT FK_75FFE1E55ED23C43');
        $this->addSql('ALTER TABLE track_history DROP CONSTRAINT FK_FE24E8565A1B2224');
        $this->addSql('ALTER TABLE track_history_track DROP CONSTRAINT FK_EA2157DA4D40AF9');
        $this->addSql('ALTER TABLE track_history_track DROP CONSTRAINT FK_EA2157D5ED23C43');
        $this->addSql('DROP TABLE favorite_track');
        $this->addSql('DROP TABLE favorite_track_track');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE playlist_track');
        $this->addSql('DROP TABLE track');
        $this->addSql('DROP TABLE track_history');
        $this->addSql('DROP TABLE track_history_track');
    }
}
