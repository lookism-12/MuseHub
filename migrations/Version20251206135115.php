<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206135115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset fields to user table';
    }

    public function up(Schema $schema): void
    {
        // Add password reset token and timestamp fields to user table
        $this->addSql('ALTER TABLE user ADD password_reset_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD password_reset_requested_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove password reset fields from user table
        $this->addSql('ALTER TABLE user DROP password_reset_token');
        $this->addSql('ALTER TABLE user DROP password_reset_requested_at');
    }
}
