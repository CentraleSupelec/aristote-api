<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240528131921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE choice ADD translated_option_text TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD translated_by_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD translation_task_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD translation_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD translation_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD language VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD translate_to VARCHAR(255) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN enrichment.translation_task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE enrichment ADD CONSTRAINT FK_BCDAF6CE406E6639 FOREIGN KEY (translated_by_id) REFERENCES api_client (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_BCDAF6CE406E6639 ON enrichment (translated_by_id)');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD translated_title TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD translated_description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD translated_topics JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE multiple_choice_question ADD translated_question TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE multiple_choice_question ADD translated_explanation TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE transcript ADD translated_text TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE transcript ADD translated_sentences JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE choice DROP translated_option_text');
        $this->addSql('ALTER TABLE enrichment DROP CONSTRAINT FK_BCDAF6CE406E6639');
        $this->addSql('DROP INDEX IDX_BCDAF6CE406E6639');
        $this->addSql('ALTER TABLE enrichment DROP translated_by_id');
        $this->addSql('ALTER TABLE enrichment DROP translation_task_id');
        $this->addSql('ALTER TABLE enrichment DROP translation_started_at');
        $this->addSql('ALTER TABLE enrichment DROP translation_ended_at');
        $this->addSql('ALTER TABLE enrichment DROP language');
        $this->addSql('ALTER TABLE enrichment DROP translate_to');
        $this->addSql('ALTER TABLE transcript DROP translated_text');
        $this->addSql('ALTER TABLE transcript DROP translated_sentences');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP translated_title');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP translated_description');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP translated_topics');
        $this->addSql('ALTER TABLE multiple_choice_question DROP translated_question');
        $this->addSql('ALTER TABLE multiple_choice_question DROP translated_explanation');
    }
}
