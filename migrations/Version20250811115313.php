<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811115313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // First, add the track_id column as nullable
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_track ADD track_id INT
        SQL);

        // Migrate data from the many-to-many table to the direct relationship
        $this->addSql(<<<'SQL'
            UPDATE favorite_track 
            SET track_id = (
                SELECT ftt.track_id 
                FROM favorite_track_track ftt 
                WHERE ftt.favorite_track_id = favorite_track.id
                LIMIT 1
            )
        SQL);

        // Now make the column NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_track ALTER track_id SET NOT NULL
        SQL);

        // Drop the old many-to-many constraints and table
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_track_track DROP CONSTRAINT fk_65061f42f48524b2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_track_track DROP CONSTRAINT fk_65061f425ed23c43
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE favorite_track_track
        SQL);

        // Add the foreign key constraint and index
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_track ADD CONSTRAINT FK_606BD3825ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_606BD3825ED23C43 ON favorite_track (track_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE favorite_track_track (favorite_track_id INT NOT NULL, track_id INT NOT NULL, PRIMARY KEY(favorite_track_id, track_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_65061f425ed23c43 ON favorite_track_track (track_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_65061f42f48524b2 ON favorite_track_track (favorite_track_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_track_track ADD CONSTRAINT fk_65061f42f48524b2 FOREIGN KEY (favorite_track_id) REFERENCES favorite_track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_track_track ADD CONSTRAINT fk_65061f425ed23c43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_track DROP CONSTRAINT FK_606BD3825ED23C43
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_606BD3825ED23C43
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_track DROP track_id
        SQL);
    }
}
