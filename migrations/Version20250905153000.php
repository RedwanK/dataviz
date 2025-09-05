<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250905153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enable TimescaleDB and create tag_reading hypertable with indexes and optional policies';
    }

    public function up(Schema $schema): void
    {
        // Enable extension
        $this->addSql("CREATE EXTENSION IF NOT EXISTS timescaledb");

        // Base table for hypertable
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS tag_reading (
                time TIMESTAMPTZ NOT NULL,
                device_id INT NOT NULL,
                tag_id INT NOT NULL,
                gateway_id INT NULL,
                value_type TEXT NOT NULL,
                value_num DOUBLE PRECISION NULL,
                value_bool BOOLEAN NULL,
                value_text TEXT NULL,
                value_json JSONB NULL
            );
            SQL);

        // Make it a hypertable
        $this->addSql("SELECT create_hypertable('tag_reading', 'time', if_not_exists => TRUE)");

        // Helpful index for tag/time queries
        $this->addSql("CREATE INDEX IF NOT EXISTS tag_reading_tag_time_idx ON tag_reading (tag_id, time DESC)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tag_reading');
        // We keep the extension installed
    }
}

