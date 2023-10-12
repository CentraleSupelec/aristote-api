<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231012121446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE media (id UUID NOT NULL, enrichment_id UUID DEFAULT NULL, file_name VARCHAR(255) NOT NULL, original_file_name VARCHAR(255) DEFAULT NULL, mime_type VARCHAR(255) DEFAULT NULL, size VARCHAR(255) NOT NULL, file_directory VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, dtype VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A2CA10CD9900337 ON media (enrichment_id)');
        $this->addSql('COMMENT ON COLUMN media.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN media.enrichment_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE video (id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN video.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CD9900337 FOREIGN KEY (enrichment_id) REFERENCES enrichment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2CBF396750 FOREIGN KEY (id) REFERENCES media (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment_version DROP text');
        $this->addSql('ALTER TABLE transcript ADD text TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE media DROP CONSTRAINT FK_6A2CA10CD9900337');
        $this->addSql('ALTER TABLE video DROP CONSTRAINT FK_7CC7DA2CBF396750');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE video');
        $this->addSql('ALTER TABLE enrichment_version ADD text VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE transcript DROP text');
    }
}
