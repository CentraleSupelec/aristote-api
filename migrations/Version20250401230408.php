<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250401230408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_client ADD enrichment_model_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE api_client ADD enrichment_infrastructure_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE api_client ALTER secret DROP NOT NULL');
        $this->addSql('ALTER TABLE api_client ALTER active DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN api_client.enrichment_model_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN api_client.enrichment_infrastructure_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D57C0FB895 FOREIGN KEY (enrichment_model_id) REFERENCES ai_model (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D53B9E9DAD FOREIGN KEY (enrichment_infrastructure_id) REFERENCES infrastructure (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_41B343D57C0FB895 ON api_client (enrichment_model_id)');
        $this->addSql('CREATE INDEX IDX_41B343D53B9E9DAD ON api_client (enrichment_infrastructure_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_client DROP CONSTRAINT FK_41B343D57C0FB895');
        $this->addSql('ALTER TABLE api_client DROP CONSTRAINT FK_41B343D53B9E9DAD');
        $this->addSql('DROP INDEX IDX_41B343D57C0FB895');
        $this->addSql('DROP INDEX IDX_41B343D53B9E9DAD');
        $this->addSql('ALTER TABLE api_client DROP enrichment_model_id');
        $this->addSql('ALTER TABLE api_client DROP enrichment_infrastructure_id');
        $this->addSql('ALTER TABLE api_client ALTER secret SET NOT NULL');
        $this->addSql('ALTER TABLE api_client ALTER active SET DEFAULT false');
    }
}
