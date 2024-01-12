<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240131123135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE answer_pointer ALTER start_answer_pointer TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE answer_pointer ALTER stop_answer_pointer TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE choice ALTER option_text TYPE TEXT');
        $this->addSql('ALTER TABLE enrichment_version ADD last_evaluation_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD thumb_up_title BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD thumb_up_description BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD thumb_up_topics BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD thumb_up_discipline BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD thumb_up_media_type BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD user_feedback TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment_version_metadata ALTER title TYPE TEXT');
        $this->addSql('ALTER TABLE enrichment_version_metadata ALTER description TYPE TEXT');
        $this->addSql('ALTER TABLE multiple_choice_question ALTER question TYPE TEXT');
        $this->addSql('ALTER TABLE multiple_choice_question ALTER explanation TYPE TEXT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE multiple_choice_question ALTER question TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE multiple_choice_question ALTER explanation TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE answer_pointer ALTER start_answer_pointer TYPE NUMERIC(10, 2)');
        $this->addSql('ALTER TABLE answer_pointer ALTER stop_answer_pointer TYPE NUMERIC(10, 2)');
        $this->addSql('ALTER TABLE enrichment_version DROP last_evaluation_date');
        $this->addSql('ALTER TABLE choice ALTER option_text TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP thumb_up_title');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP thumb_up_description');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP thumb_up_topics');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP thumb_up_discipline');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP thumb_up_media_type');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP user_feedback');
        $this->addSql('ALTER TABLE enrichment_version_metadata ALTER title TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE enrichment_version_metadata ALTER description TYPE VARCHAR(255)');
    }
}
