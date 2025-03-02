<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250301174523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial database setup with all modifications';
    }

    public function up(Schema $schema): void
    {
        // User table
        $this->addSql('CREATE TABLE "user" (
            id SERIAL NOT NULL, 
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) DEFAULT NULL,
            last_name VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');

        // Conversation table
        $this->addSql('CREATE TABLE conversation (
            id SERIAL NOT NULL,
            created_by_id INT NOT NULL,
            last_active_user_id INT NOT NULL,
            last_message_id INT DEFAULT NULL,
            name VARCHAR(255) DEFAULT NULL,
            type VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            last_activity TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            avatar VARCHAR(500) DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN conversation.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN conversation.last_activity IS \'(DC2Type:datetime_immutable)\'');

        // Conversation_user table
        $this->addSql('CREATE TABLE conversation_user (
            conversation_id INT NOT NULL,
            user_id INT NOT NULL,
            PRIMARY KEY(conversation_id, user_id)
        )');

        // Message table
        $this->addSql('CREATE TABLE message (
            id SERIAL NOT NULL,
            sender_id INT NOT NULL,
            conversation_id INT NOT NULL,
            content TEXT NOT NULL,
            type VARCHAR(255) NOT NULL,
            sent_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            is_read BOOLEAN NOT NULL,
            attachment VARCHAR(255) DEFAULT NULL,
            is_deleted BOOLEAN NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN message.sent_at IS \'(DC2Type:datetime_immutable)\'');

        // Note table
        $this->addSql('CREATE TABLE note (
            id SERIAL NOT NULL,
            author_id INT NOT NULL,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN note.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN note.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Password_item table
        $this->addSql('CREATE TABLE password_item (
            id SERIAL NOT NULL,
            associated_to_id INT NOT NULL,
            url TEXT NOT NULL,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            password_encrypted TEXT NOT NULL,
            notes_encrypted TEXT DEFAULT NULL,
            iv TEXT NOT NULL,
            is_favorite BOOLEAN NOT NULL,
            must_be_updated BOOLEAN NOT NULL,
            PRIMARY KEY(id)
        )');

        // File table with folder structure
        // Incorporating changes from Version20250219091125, Version20250219091819, Version20250301174523
        $this->addSql('CREATE TABLE file (
            id SERIAL NOT NULL,
            owner_id INT NOT NULL,
            parent_id INT DEFAULT NULL,
            filename VARCHAR(255) DEFAULT NULL,
            filepath VARCHAR(255) DEFAULT NULL,
            original_name VARCHAR(255) DEFAULT NULL,
            size INT DEFAULT NULL,
            mime_type VARCHAR(255) NOT NULL,
            uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            is_folder BOOLEAN NOT NULL DEFAULT false,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN file.uploaded_at IS \'(DC2Type:datetime_immutable)\'');

        // File_user table
        $this->addSql('CREATE TABLE file_user (
            file_id INT NOT NULL,
            user_id INT NOT NULL,
            PRIMARY KEY(file_id, user_id)
        )');

        // Messenger_messages table (with updated comments from Version20250219091125)
        $this->addSql('CREATE TABLE messenger_messages (
            id BIGSERIAL NOT NULL,
            body TEXT NOT NULL,
            headers TEXT NOT NULL,
            queue_name VARCHAR(190) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');

        // Add all indexes
        $this->addSql('CREATE INDEX IDX_8A8E26E9B03A8386 ON conversation (created_by_id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E94A25660F ON conversation (last_active_user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8A8E26E9BA0E79C3 ON conversation (last_message_id)');
        $this->addSql('CREATE INDEX IDX_5AECB5559AC0396 ON conversation_user (conversation_id)');
        $this->addSql('CREATE INDEX IDX_5AECB555A76ED395 ON conversation_user (user_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307FF624B39D ON message (sender_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F9AC0396 ON message (conversation_id)');
        $this->addSql('CREATE INDEX IDX_CFBDFA14F675F31B ON note (author_id)');
        $this->addSql('CREATE INDEX IDX_8D4E2255D83C54C2 ON password_item (associated_to_id)');
        $this->addSql('CREATE INDEX IDX_8C9F36107E3C61F9 ON file (owner_id)');
        $this->addSql('CREATE INDEX IDX_8C9F3610727ACA70 ON file (parent_id)');
        $this->addSql('CREATE INDEX IDX_46FBE2793CB796C ON file_user (file_id)');
        $this->addSql('CREATE INDEX IDX_46FBE27A76ED395 ON file_user (user_id)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');

        // Add all foreign key constraints
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E94A25660F FOREIGN KEY (last_active_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9BA0E79C3 FOREIGN KEY (last_message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_user ADD CONSTRAINT FK_5AECB5559AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE conversation_user ADD CONSTRAINT FK_5AECB555A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14F675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE password_item ADD CONSTRAINT FK_8D4E2255D83C54C2 FOREIGN KEY (associated_to_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36107E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F3610727ACA70 FOREIGN KEY (parent_id) REFERENCES file (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file_user ADD CONSTRAINT FK_46FBE2793CB796C FOREIGN KEY (file_id) REFERENCES file (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE file_user ADD CONSTRAINT FK_46FBE27A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add messenger notification function and trigger
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS file_user');
        $this->addSql('DROP TABLE IF EXISTS file');
        $this->addSql('DROP TABLE IF EXISTS password_item');
        $this->addSql('DROP TABLE IF EXISTS note');
        $this->addSql('DROP TABLE IF EXISTS message');
        $this->addSql('DROP TABLE IF EXISTS conversation_user');
        $this->addSql('DROP TABLE IF EXISTS conversation');
        $this->addSql('DROP TABLE IF EXISTS "user"');
        $this->addSql('DROP TABLE IF EXISTS messenger_messages');
    }
}