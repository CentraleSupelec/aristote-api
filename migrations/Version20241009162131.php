<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241009162131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrichment ADD latest_enrichment_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('
            UPDATE enrichment e
            SET latest_enrichment_requested_at = COALESCE((
                SELECT ev.created_at 
                FROM enrichment_version ev 
                WHERE ev.enrichment_id = e.id 
                ORDER BY ev.created_at DESC 
                LIMIT 1
            ), e.created_at)
        ');
        $this->addSql('ALTER TABLE enrichment ADD priority INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrichment DROP latest_enrichment_requested_at');
        $this->addSql('ALTER TABLE enrichment DROP priority');
    }
}
