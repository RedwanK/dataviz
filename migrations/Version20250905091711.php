<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250905091711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON app_user (email)');
        $this->addSql('CREATE TABLE app_user_user_group (user_id INT NOT NULL, user_group_id INT NOT NULL, PRIMARY KEY(user_id, user_group_id))');
        $this->addSql('CREATE INDEX IDX_72BF5CF8A76ED395 ON app_user_user_group (user_id)');
        $this->addSql('CREATE INDEX IDX_72BF5CF81ED93D47 ON app_user_user_group (user_group_id)');
        $this->addSql('CREATE TABLE device (id SERIAL NOT NULL, gateway_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_92FB68E577F8E00 ON device (gateway_id)');
        $this->addSql('CREATE TABLE gateway (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE tag (id SERIAL NOT NULL, device_id INT NOT NULL, code VARCHAR(255) NOT NULL, data_type VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_389B78394A4C7D4 ON tag (device_id)');
        $this->addSql('CREATE TABLE user_group (id SERIAL NOT NULL, name VARCHAR(120) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_group_name ON user_group (name)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE app_user_user_group ADD CONSTRAINT FK_72BF5CF8A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE app_user_user_group ADD CONSTRAINT FK_72BF5CF81ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE device ADD CONSTRAINT FK_92FB68E577F8E00 FOREIGN KEY (gateway_id) REFERENCES gateway (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B78394A4C7D4 FOREIGN KEY (device_id) REFERENCES device (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE app_user_user_group DROP CONSTRAINT FK_72BF5CF8A76ED395');
        $this->addSql('ALTER TABLE app_user_user_group DROP CONSTRAINT FK_72BF5CF81ED93D47');
        $this->addSql('ALTER TABLE device DROP CONSTRAINT FK_92FB68E577F8E00');
        $this->addSql('ALTER TABLE tag DROP CONSTRAINT FK_389B78394A4C7D4');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE app_user_user_group');
        $this->addSql('DROP TABLE device');
        $this->addSql('DROP TABLE gateway');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE user_group');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
