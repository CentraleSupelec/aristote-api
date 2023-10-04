<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231004131540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE choice (id UUID NOT NULL, multiple_choice_question_id UUID NOT NULL, option_text VARCHAR(255) NOT NULL, correct_answer BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C1AB5A92EB3EBF2 ON choice (multiple_choice_question_id)');
        $this->addSql('COMMENT ON COLUMN choice.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN choice.multiple_choice_question_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE enrichment (id UUID NOT NULL, created_by_id VARCHAR(80) NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BCDAF6CEB03A8386 ON enrichment (created_by_id)');
        $this->addSql('COMMENT ON COLUMN enrichment.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE enrichment_version (id UUID NOT NULL, enrichment_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, text VARCHAR(255) NOT NULL, initial_version BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C544B204D9900337 ON enrichment_version (enrichment_id)');
        $this->addSql('COMMENT ON COLUMN enrichment_version.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN enrichment_version.enrichment_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE enrichment_version_metadata (id UUID NOT NULL, enrichment_version_id UUID DEFAULT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_598A2FCFC60A249A ON enrichment_version_metadata (enrichment_version_id)');
        $this->addSql('COMMENT ON COLUMN enrichment_version_metadata.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN enrichment_version_metadata.enrichment_version_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE multiple_choice_question (id UUID NOT NULL, enrichment_version_id UUID DEFAULT NULL, question VARCHAR(255) NOT NULL, explanation VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_24557253C60A249A ON multiple_choice_question (enrichment_version_id)');
        $this->addSql('COMMENT ON COLUMN multiple_choice_question.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN multiple_choice_question.enrichment_version_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE tag (id UUID NOT NULL, enrichment_version_metadata_id UUID NOT NULL, text VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_389B783E3C34647 ON tag (enrichment_version_metadata_id)');
        $this->addSql('COMMENT ON COLUMN tag.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN tag.enrichment_version_metadata_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE topic (id UUID NOT NULL, enrichment_version_metadata_id UUID NOT NULL, text VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9D40DE1BE3C34647 ON topic (enrichment_version_metadata_id)');
        $this->addSql('COMMENT ON COLUMN topic.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN topic.enrichment_version_metadata_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE transcript (id UUID NOT NULL, enrichment_version_id UUID DEFAULT NULL, original_filename VARCHAR(255) NOT NULL, language VARCHAR(255) NOT NULL, sentences JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A8F617C3C60A249A ON transcript (enrichment_version_id)');
        $this->addSql('COMMENT ON COLUMN transcript.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN transcript.enrichment_version_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE choice ADD CONSTRAINT FK_C1AB5A92EB3EBF2 FOREIGN KEY (multiple_choice_question_id) REFERENCES multiple_choice_question (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment ADD CONSTRAINT FK_BCDAF6CEB03A8386 FOREIGN KEY (created_by_id) REFERENCES api_client (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment_version ADD CONSTRAINT FK_C544B204D9900337 FOREIGN KEY (enrichment_id) REFERENCES enrichment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrichment_version_metadata ADD CONSTRAINT FK_598A2FCFC60A249A FOREIGN KEY (enrichment_version_id) REFERENCES enrichment_version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE multiple_choice_question ADD CONSTRAINT FK_24557253C60A249A FOREIGN KEY (enrichment_version_id) REFERENCES enrichment_version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B783E3C34647 FOREIGN KEY (enrichment_version_metadata_id) REFERENCES enrichment_version_metadata (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE topic ADD CONSTRAINT FK_9D40DE1BE3C34647 FOREIGN KEY (enrichment_version_metadata_id) REFERENCES enrichment_version_metadata (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transcript ADD CONSTRAINT FK_A8F617C3C60A249A FOREIGN KEY (enrichment_version_id) REFERENCES enrichment_version (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE choice DROP CONSTRAINT FK_C1AB5A92EB3EBF2');
        $this->addSql('ALTER TABLE enrichment DROP CONSTRAINT FK_BCDAF6CEB03A8386');
        $this->addSql('ALTER TABLE enrichment_version DROP CONSTRAINT FK_C544B204D9900337');
        $this->addSql('ALTER TABLE enrichment_version_metadata DROP CONSTRAINT FK_598A2FCFC60A249A');
        $this->addSql('ALTER TABLE multiple_choice_question DROP CONSTRAINT FK_24557253C60A249A');
        $this->addSql('ALTER TABLE tag DROP CONSTRAINT FK_389B783E3C34647');
        $this->addSql('ALTER TABLE topic DROP CONSTRAINT FK_9D40DE1BE3C34647');
        $this->addSql('ALTER TABLE transcript DROP CONSTRAINT FK_A8F617C3C60A249A');
        $this->addSql('DROP TABLE choice');
        $this->addSql('DROP TABLE enrichment');
        $this->addSql('DROP TABLE enrichment_version');
        $this->addSql('DROP TABLE enrichment_version_metadata');
        $this->addSql('DROP TABLE multiple_choice_question');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE topic');
        $this->addSql('DROP TABLE transcript');
    }
}
