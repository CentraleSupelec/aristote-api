<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231012151825 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrichment ADD failure_cause VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD media_url TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD notification_webhook_url TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE enrichment DROP failure_cause');
        $this->addSql('ALTER TABLE enrichment DROP media_url');
        $this->addSql('ALTER TABLE enrichment DROP notification_webhook_url');
    }
}
