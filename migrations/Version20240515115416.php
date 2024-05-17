<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240515115416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrichment_version ADD ai_generated BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD disciplines JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD media_types JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD end_user_identifier VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD ai_evaluation VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD ai_model VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD infrastructure VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD notification_webhook_url TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE enrichment_version DROP ai_generated');
        $this->addSql('ALTER TABLE enrichment_version DROP disciplines');
        $this->addSql('ALTER TABLE enrichment_version DROP media_types');
        $this->addSql('ALTER TABLE enrichment_version DROP end_user_identifier');
        $this->addSql('ALTER TABLE enrichment_version DROP ai_evaluation');
        $this->addSql('ALTER TABLE enrichment_version DROP ai_model');
        $this->addSql('ALTER TABLE enrichment_version DROP infrastructure');
        $this->addSql('ALTER TABLE enrichment_version DROP notification_webhook_url');
    }
}
