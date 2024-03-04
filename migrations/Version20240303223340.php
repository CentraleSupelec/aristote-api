<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240303223340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ai_model (id UUID NOT NULL, name TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN ai_model.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE infrastructure (id UUID NOT NULL, name TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN infrastructure.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE api_client ADD ai_model_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE api_client ADD infrastructure_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN api_client.ai_model_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN api_client.infrastructure_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D566933187 FOREIGN KEY (ai_model_id) REFERENCES ai_model (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D5243E7A84 FOREIGN KEY (infrastructure_id) REFERENCES infrastructure (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_41B343D566933187 ON api_client (ai_model_id)');
        $this->addSql('CREATE INDEX IDX_41B343D5243E7A84 ON api_client (infrastructure_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_client DROP CONSTRAINT FK_41B343D566933187');
        $this->addSql('ALTER TABLE api_client DROP CONSTRAINT FK_41B343D5243E7A84');
        $this->addSql('DROP TABLE ai_model');
        $this->addSql('DROP TABLE infrastructure');
        $this->addSql('DROP INDEX IDX_41B343D566933187');
        $this->addSql('DROP INDEX IDX_41B343D5243E7A84');
        $this->addSql('ALTER TABLE api_client DROP ai_model_id');
        $this->addSql('ALTER TABLE api_client DROP infrastructure_id');
    }
}
