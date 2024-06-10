<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240603114013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audio ADD duration INT DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD deleted BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE enrichment ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD ai_generation_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE enrichment ADD evaluation_mark NUMERIC(6, 3) DEFAULT NULL');
        $this->addSql('ALTER TABLE video ADD duration INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE video DROP duration');
        $this->addSql('ALTER TABLE enrichment DROP deleted');
        $this->addSql('ALTER TABLE enrichment DROP deleted_at');
        $this->addSql('ALTER TABLE enrichment DROP ai_generation_count');
        $this->addSql('ALTER TABLE enrichment DROP evaluation_mark');
        $this->addSql('ALTER TABLE audio DROP duration');
    }
}
