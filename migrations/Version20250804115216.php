<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250804115216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE track_history_track DROP CONSTRAINT fk_ea2157da4d40af9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE track_history_track DROP CONSTRAINT fk_ea2157d5ed23c43
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE track_history_track
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE password_item ALTER created_at DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE track DROP coverurl
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX uniq_fe24e8565a1b2224
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE track_history ADD track_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE track_history ADD CONSTRAINT FK_FE24E8565ED23C43 FOREIGN KEY (track_id) REFERENCES track (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FE24E8565A1B2224 ON track_history (of_user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_FE24E8565ED23C43 ON track_history (track_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER is_online DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER verification_code_valid_until TYPE TIMESTAMP(0) WITHOUT TIME ZONE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER is_validated DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".verification_code_valid_until IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_settings ALTER geolocation_enabled DROP DEFAULT
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE track_history_track (track_history_id INT NOT NULL, track_id INT NOT NULL, PRIMARY KEY(track_history_id, track_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ea2157d5ed23c43 ON track_history_track (track_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_ea2157da4d40af9 ON track_history_track (track_history_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE track_history_track ADD CONSTRAINT fk_ea2157da4d40af9 FOREIGN KEY (track_history_id) REFERENCES track_history (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE track_history_track ADD CONSTRAINT fk_ea2157d5ed23c43 FOREIGN KEY (track_id) REFERENCES track (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_settings ALTER geolocation_enabled SET DEFAULT false
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE track ADD coverurl VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE track_history DROP CONSTRAINT FK_FE24E8565ED23C43
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FE24E8565A1B2224
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_FE24E8565ED23C43
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE track_history DROP track_id
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_fe24e8565a1b2224 ON track_history (of_user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE password_item ALTER created_at SET DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER is_online SET DEFAULT false
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER verification_code_valid_until TYPE DATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ALTER is_validated SET DEFAULT false
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "user".verification_code_valid_until IS '(DC2Type:date_immutable)'
        SQL);
    }
}
