<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426173627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrichment ADD initial_enrichment_version_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD last_enrichment_version_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD enrichment_retries INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE enrichment ADD translation_retries INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE enrichment ADD evaluation_retries INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE enrichment ADD media_text_length INT DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD media_duration_in_seconds INT DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment RENAME COLUMN retries TO transcription_retries');
        $this->addSql('ALTER TABLE enrichment RENAME COLUMN transribing_started_at TO transcribing_started_at');
        $this->addSql('ALTER TABLE enrichment RENAME COLUMN transribing_ended_at TO transcribing_ended_at');
        $this->addSql('COMMENT ON COLUMN enrichment.initial_enrichment_version_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN enrichment.last_enrichment_version_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE enrichment ADD CONSTRAINT FK_BCDAF6CEA8CB0384 FOREIGN KEY (initial_enrichment_version_id) REFERENCES enrichment_version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment ADD CONSTRAINT FK_BCDAF6CEF485E793 FOREIGN KEY (last_enrichment_version_id) REFERENCES enrichment_version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BCDAF6CEA8CB0384 ON enrichment (initial_enrichment_version_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BCDAF6CEF485E793 ON enrichment (last_enrichment_version_id)');
        $this->addSql('ALTER TABLE enrichment_version ADD initial_enrichment_version_of_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD last_enrichment_version_of_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD ai_processed_by_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD ai_evaluated_by_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD transcribed_by_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD translated_by_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD ai_enrichment_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD ai_enrichment_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD transcribing_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD transcribing_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD translation_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD translation_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD ai_evaluation_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD ai_evaluation_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD notified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD notification_status VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD evaluation_mark NUMERIC(6, 3) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD generate_metadata BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD generate_quiz BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD generate_notes BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD failure_cause VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD enrichment_retries INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD translation_retries INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD evaluation_retries INT DEFAULT 0 NOT NULL');
        $this->addSql('COMMENT ON COLUMN enrichment_version.initial_enrichment_version_of_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN enrichment_version.last_enrichment_version_of_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE enrichment_version ADD CONSTRAINT FK_C544B20482AF0314 FOREIGN KEY (initial_enrichment_version_of_id) REFERENCES enrichment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment_version ADD CONSTRAINT FK_C544B20430B0ECC2 FOREIGN KEY (last_enrichment_version_of_id) REFERENCES enrichment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment_version ADD CONSTRAINT FK_C544B204C34C8145 FOREIGN KEY (ai_processed_by_id) REFERENCES api_client (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment_version ADD CONSTRAINT FK_C544B204CCBB448C FOREIGN KEY (ai_evaluated_by_id) REFERENCES api_client (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment_version ADD CONSTRAINT FK_C544B204ED22A2D3 FOREIGN KEY (transcribed_by_id) REFERENCES api_client (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment_version ADD CONSTRAINT FK_C544B204406E6639 FOREIGN KEY (translated_by_id) REFERENCES api_client (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C544B20482AF0314 ON enrichment_version (initial_enrichment_version_of_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C544B20430B0ECC2 ON enrichment_version (last_enrichment_version_of_id)');
        $this->addSql('CREATE INDEX IDX_C544B204C34C8145 ON enrichment_version (ai_processed_by_id)');
        $this->addSql('CREATE INDEX IDX_C544B204CCBB448C ON enrichment_version (ai_evaluated_by_id)');
        $this->addSql('CREATE INDEX IDX_C544B204ED22A2D3 ON enrichment_version (transcribed_by_id)');
        $this->addSql('CREATE INDEX IDX_C544B204406E6639 ON enrichment_version (translated_by_id)');
        $this->addSql('CREATE TABLE parameter (id UUID NOT NULL, name TEXT NOT NULL, description TEXT NOT NULL, value TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2A9791105E237E06 ON parameter (name)');
        $this->addSql('COMMENT ON COLUMN parameter.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        // Insert default parameters
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->addSql(sprintf(
            "INSERT INTO parameter (id, name, description, value, created_at, updated_at) VALUES
                (uuid_generate_v4(), 'MAX_TRANSCRIPTION_RETRIES', 'Maximum number of retries for transcription', '2', '%s', '%s'),
                (uuid_generate_v4(), 'MAX_ENRICHMENT_RETRIES', 'Maximum number of retries for enrichment', '2', '%s', '%s'),
                (uuid_generate_v4(), 'MAX_TRANSLATION_RETRIES', 'Maximum number of retries for translation', '2', '%s', '%s'),
                (uuid_generate_v4(), 'MAX_EVALUATION_RETRIES', 'Maximum number of retries for evaluation', '2', '%s', '%s'),
                (uuid_generate_v4(), 'MAX_MEDIA_DURATION_IN_SECONDS', 'Maximum allowed media duration in seconds', '14400', '%s', '%s'),
                (uuid_generate_v4(), 'MAX_TEXT_LENGTH', 'Maximum allowed text length', '-1', '%s', '%s'),
                (uuid_generate_v4(), 'TRANSCRIPTION_WORKER_TIMEOUT_IN_MINUTES', 'Timeout before retrying transcription', '60', '%s', '%s'),
                (uuid_generate_v4(), 'AI_ENRICHMENT_WORKER_TIMEOUT_IN_MINUTES', 'Timeout before retrying enrichment', '60', '%s', '%s'),
                (uuid_generate_v4(), 'TRANSLATION_WORKER_TIMEOUT_IN_MINUTES', 'Timeout before retrying translation', '10', '%s', '%s'),
                (uuid_generate_v4(), 'AI_EVALUATION_WORKER_TIMEOUT_IN_MINUTES', 'Timeout before retrying evaluation', '120', '%s', '%s')
            ",
            $now, $now,
            $now, $now,
            $now, $now,
            $now, $now,
            $now, $now,
            $now, $now,
            $now, $now,
            $now, $now,
            $now, $now,
            $now, $now,
        ));
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE parameter');
        $this->addSql('ALTER TABLE enrichment_version DROP CONSTRAINT FK_C544B20482AF0314');
        $this->addSql('ALTER TABLE enrichment_version DROP CONSTRAINT FK_C544B20430B0ECC2');
        $this->addSql('ALTER TABLE enrichment_version DROP CONSTRAINT FK_C544B204C34C8145');
        $this->addSql('ALTER TABLE enrichment_version DROP CONSTRAINT FK_C544B204CCBB448C');
        $this->addSql('ALTER TABLE enrichment_version DROP CONSTRAINT FK_C544B204ED22A2D3');
        $this->addSql('ALTER TABLE enrichment_version DROP CONSTRAINT FK_C544B204406E6639');
        $this->addSql('DROP INDEX UNIQ_C544B20482AF0314');
        $this->addSql('DROP INDEX UNIQ_C544B20430B0ECC2');
        $this->addSql('DROP INDEX IDX_C544B204C34C8145');
        $this->addSql('DROP INDEX IDX_C544B204CCBB448C');
        $this->addSql('DROP INDEX IDX_C544B204ED22A2D3');
        $this->addSql('DROP INDEX IDX_C544B204406E6639');
        $this->addSql('ALTER TABLE enrichment_version DROP initial_enrichment_version_of_id');
        $this->addSql('ALTER TABLE enrichment_version DROP last_enrichment_version_of_id');
        $this->addSql('ALTER TABLE enrichment_version DROP ai_processed_by_id');
        $this->addSql('ALTER TABLE enrichment_version DROP ai_evaluated_by_id');
        $this->addSql('ALTER TABLE enrichment_version DROP transcribed_by_id');
        $this->addSql('ALTER TABLE enrichment_version DROP translated_by_id');
        $this->addSql('ALTER TABLE enrichment_version DROP ai_enrichment_started_at');
        $this->addSql('ALTER TABLE enrichment_version DROP ai_enrichment_ended_at');
        $this->addSql('ALTER TABLE enrichment_version DROP transcribing_started_at');
        $this->addSql('ALTER TABLE enrichment_version DROP transcribing_ended_at');
        $this->addSql('ALTER TABLE enrichment_version DROP translation_started_at');
        $this->addSql('ALTER TABLE enrichment_version DROP translation_ended_at');
        $this->addSql('ALTER TABLE enrichment_version DROP ai_evaluation_started_at');
        $this->addSql('ALTER TABLE enrichment_version DROP ai_evaluation_ended_at');
        $this->addSql('ALTER TABLE enrichment_version DROP notified_at');
        $this->addSql('ALTER TABLE enrichment_version DROP notification_status');
        $this->addSql('ALTER TABLE enrichment_version DROP evaluation_mark');
        $this->addSql('ALTER TABLE enrichment_version DROP generate_metadata');
        $this->addSql('ALTER TABLE enrichment_version DROP generate_quiz');
        $this->addSql('ALTER TABLE enrichment_version DROP generate_notes');
        $this->addSql('ALTER TABLE enrichment_version DROP failure_cause');
        $this->addSql('ALTER TABLE enrichment_version DROP enrichment_retries');
        $this->addSql('ALTER TABLE enrichment_version DROP translation_retries');
        $this->addSql('ALTER TABLE enrichment_version DROP evaluation_retries');
        $this->addSql('ALTER TABLE enrichment DROP CONSTRAINT FK_BCDAF6CEA8CB0384');
        $this->addSql('ALTER TABLE enrichment DROP CONSTRAINT FK_BCDAF6CEF485E793');
        $this->addSql('DROP INDEX UNIQ_BCDAF6CEA8CB0384');
        $this->addSql('DROP INDEX UNIQ_BCDAF6CEF485E793');
        $this->addSql('ALTER TABLE enrichment ADD retries INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE enrichment ADD transribing_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD transribing_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment DROP transcribing_started_at');
        $this->addSql('ALTER TABLE enrichment DROP transcribing_ended_at');
        $this->addSql('ALTER TABLE enrichment DROP initial_enrichment_version_id');
        $this->addSql('ALTER TABLE enrichment DROP last_enrichment_version_id');
        $this->addSql('ALTER TABLE enrichment DROP transcription_retries');
        $this->addSql('ALTER TABLE enrichment DROP enrichment_retries');
        $this->addSql('ALTER TABLE enrichment DROP translation_retries');
        $this->addSql('ALTER TABLE enrichment DROP evaluation_retries');
        $this->addSql('ALTER TABLE enrichment DROP media_text_length');
        $this->addSql('ALTER TABLE enrichment DROP media_duration_in_seconds');
    }
}
