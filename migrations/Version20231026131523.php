<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231026131523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrichment ADD ai_processing_task_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD transcription_task_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD notification_status VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD notified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN enrichment.ai_processing_task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN enrichment.transcription_task_id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE enrichment DROP ai_processing_task_id');
        $this->addSql('ALTER TABLE enrichment DROP transcription_task_id');
        $this->addSql('ALTER TABLE enrichment DROP notification_status');
        $this->addSql('ALTER TABLE enrichment DROP notified_at');
    }
}
