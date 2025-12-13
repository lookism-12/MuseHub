<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add moderation fields to posts table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post ADD moderation_status VARCHAR(255) DEFAULT \'approved\'');
        $this->addSql('ALTER TABLE post ADD moderation_details JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post DROP COLUMN moderation_status');
        $this->addSql('ALTER TABLE post DROP COLUMN moderation_details');
    }
}
