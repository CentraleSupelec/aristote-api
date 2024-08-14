<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240807211644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrichment ADD generate_metadata BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE enrichment ADD generate_quiz BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE enrichment ADD generate_notes BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE enrichment_version ADD notes TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrichment_version DROP notes');
        $this->addSql('ALTER TABLE enrichment DROP generate_metadata');
        $this->addSql('ALTER TABLE enrichment DROP generate_quiz');
        $this->addSql('ALTER TABLE enrichment DROP generate_notes');
    }
}
