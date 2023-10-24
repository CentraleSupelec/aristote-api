<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231023150523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE topic DROP CONSTRAINT fk_9d40de1be3c34647');
        $this->addSql('ALTER TABLE tag DROP CONSTRAINT fk_389b783e3c34647');
        $this->addSql('DROP TABLE topic');
        $this->addSql('DROP TABLE tag');
        $this->addSql('ALTER TABLE enrichment ADD ai_processed_by_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD transcribed_by_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD disciplines JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD media_types JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD ai_enrichment_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD ai_enrichment_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD transribing_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD transribing_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD CONSTRAINT FK_BCDAF6CEC34C8145 FOREIGN KEY (ai_processed_by_id) REFERENCES api_client (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment ADD CONSTRAINT FK_BCDAF6CEED22A2D3 FOREIGN KEY (transcribed_by_id) REFERENCES api_client (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_BCDAF6CEC34C8145 ON enrichment (ai_processed_by_id)');
        $this->addSql('CREATE INDEX IDX_BCDAF6CEED22A2D3 ON enrichment (transcribed_by_id)');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD topics JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD discipline VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD media_type VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE topic (id UUID NOT NULL, enrichment_version_metadata_id UUID NOT NULL, text VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_9d40de1be3c34647 ON topic (enrichment_version_metadata_id)');
        $this->addSql('COMMENT ON COLUMN topic.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN topic.enrichment_version_metadata_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE tag (id UUID NOT NULL, enrichment_version_metadata_id UUID NOT NULL, text VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_389b783e3c34647 ON tag (enrichment_version_metadata_id)');
        $this->addSql('COMMENT ON COLUMN tag.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN tag.enrichment_version_metadata_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE topic ADD CONSTRAINT fk_9d40de1be3c34647 FOREIGN KEY (enrichment_version_metadata_id) REFERENCES enrichment_version_metadata (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT fk_389b783e3c34647 FOREIGN KEY (enrichment_version_metadata_id) REFERENCES enrichment_version_metadata (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment DROP CONSTRAINT FK_BCDAF6CEC34C8145');
        $this->addSql('ALTER TABLE enrichment DROP CONSTRAINT FK_BCDAF6CEED22A2D3');
        $this->addSql('DROP INDEX IDX_BCDAF6CEC34C8145');
        $this->addSql('DROP INDEX IDX_BCDAF6CEED22A2D3');
        $this->addSql('ALTER TABLE enrichment DROP ai_processed_by_id');
        $this->addSql('ALTER TABLE enrichment DROP transcribed_by_id');
        $this->addSql('ALTER TABLE enrichment DROP disciplines');
        $this->addSql('ALTER TABLE enrichment DROP media_types');
        $this->addSql('ALTER TABLE enrichment DROP ai_enrichment_started_at');
        $this->addSql('ALTER TABLE enrichment DROP ai_enrichment_ended_at');
        $this->addSql('ALTER TABLE enrichment DROP transribing_started_at');
        $this->addSql('ALTER TABLE enrichment DROP transribing_ended_at');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP topics');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP discipline');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP media_type');
    }
}
