<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240112123157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE answer_pointer (id UUID NOT NULL, multiple_choice_question_id UUID DEFAULT NULL, start_answer_pointer NUMERIC(10, 2) DEFAULT NULL, stop_answer_pointer NUMERIC(10, 2) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7A44A7D9EB3EBF2 ON answer_pointer (multiple_choice_question_id)');
        $this->addSql('COMMENT ON COLUMN answer_pointer.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN answer_pointer.multiple_choice_question_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE answer_pointer ADD CONSTRAINT FK_7A44A7D9EB3EBF2 FOREIGN KEY (multiple_choice_question_id) REFERENCES multiple_choice_question (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE choice ADD thumb_up BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD ai_evaluated_by_id VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD ai_evaluation_task_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD ai_evaluation VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD ai_evaluation_started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD ai_evaluation_ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrichment ADD end_user_identifier VARCHAR(255) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN enrichment.ai_evaluation_task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE enrichment ADD CONSTRAINT FK_BCDAF6CECCBB448C FOREIGN KEY (ai_evaluated_by_id) REFERENCES api_client (identifier) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_BCDAF6CECCBB448C ON enrichment (ai_evaluated_by_id)');
        $this->addSql('ALTER TABLE multiple_choice_question ADD evaluation JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE multiple_choice_question ADD thumb_up BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE multiple_choice_question ADD user_feedback TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE answer_pointer DROP CONSTRAINT FK_7A44A7D9EB3EBF2');
        $this->addSql('DROP TABLE answer_pointer');
        $this->addSql('ALTER TABLE multiple_choice_question DROP evaluation');
        $this->addSql('ALTER TABLE multiple_choice_question DROP thumb_up');
        $this->addSql('ALTER TABLE multiple_choice_question DROP user_feedback');
        $this->addSql('ALTER TABLE enrichment DROP CONSTRAINT FK_BCDAF6CECCBB448C');
        $this->addSql('DROP INDEX IDX_BCDAF6CECCBB448C');
        $this->addSql('ALTER TABLE enrichment DROP ai_evaluated_by_id');
        $this->addSql('ALTER TABLE enrichment DROP ai_evaluation_task_id');
        $this->addSql('ALTER TABLE enrichment DROP ai_evaluation');
        $this->addSql('ALTER TABLE enrichment DROP ai_evaluation_started_at');
        $this->addSql('ALTER TABLE enrichment DROP ai_evaluation_ended_at');
        $this->addSql('ALTER TABLE enrichment DROP end_user_identifier');
        $this->addSql('ALTER TABLE choice DROP thumb_up');
    }
}
