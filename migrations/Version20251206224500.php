<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add deleted_at column to user table for soft delete functionality
 */
final class Version20251206224500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at column to user table for soft delete (trash) functionality';
    }

    public function up(Schema $schema): void
    {
        // Add deleted_at column to user table
        $this->addSql('ALTER TABLE user ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove deleted_at column from user table
        $this->addSql('ALTER TABLE user DROP deleted_at');
    }
}
