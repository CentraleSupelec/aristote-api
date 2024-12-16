<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241213112951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_client ADD transcription_model_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE api_client ADD transcription_infrastructure_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE api_client ADD translation_model_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE api_client ADD translation_infrastructure_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN api_client.transcription_model_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN api_client.transcription_infrastructure_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN api_client.translation_model_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN api_client.translation_infrastructure_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D591492760 FOREIGN KEY (transcription_model_id) REFERENCES ai_model (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D59C5EF53A FOREIGN KEY (transcription_infrastructure_id) REFERENCES infrastructure (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D52A67282E FOREIGN KEY (translation_model_id) REFERENCES ai_model (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D5BCA19E74 FOREIGN KEY (translation_infrastructure_id) REFERENCES infrastructure (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_41B343D591492760 ON api_client (transcription_model_id)');
        $this->addSql('CREATE INDEX IDX_41B343D59C5EF53A ON api_client (transcription_infrastructure_id)');
        $this->addSql('CREATE INDEX IDX_41B343D52A67282E ON api_client (translation_model_id)');
        $this->addSql('CREATE INDEX IDX_41B343D5BCA19E74 ON api_client (translation_infrastructure_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_client DROP CONSTRAINT FK_41B343D591492760');
        $this->addSql('ALTER TABLE api_client DROP CONSTRAINT FK_41B343D59C5EF53A');
        $this->addSql('ALTER TABLE api_client DROP CONSTRAINT FK_41B343D52A67282E');
        $this->addSql('ALTER TABLE api_client DROP CONSTRAINT FK_41B343D5BCA19E74');
        $this->addSql('DROP INDEX IDX_41B343D591492760');
        $this->addSql('DROP INDEX IDX_41B343D59C5EF53A');
        $this->addSql('DROP INDEX IDX_41B343D52A67282E');
        $this->addSql('DROP INDEX IDX_41B343D5BCA19E74');
        $this->addSql('ALTER TABLE api_client DROP transcription_model_id');
        $this->addSql('ALTER TABLE api_client DROP transcription_infrastructure_id');
        $this->addSql('ALTER TABLE api_client DROP translation_model_id');
        $this->addSql('ALTER TABLE api_client DROP translation_infrastructure_id');
    }
}
