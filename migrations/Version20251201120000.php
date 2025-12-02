<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add createdAt field to Artwork entity for date filtering and sorting
 */
final class Version20251201120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add createdAt field to artwork table for date filtering and sorting';
    }

    public function up(Schema $schema): void
    {
        // Add created_at column to artwork table
        $this->addSql('ALTER TABLE artwork ADD created_at DATETIME DEFAULT NULL');
        
        // Set default value for existing records to current timestamp
        $this->addSql('UPDATE artwork SET created_at = NOW() WHERE created_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove created_at column from artwork table
        $this->addSql('ALTER TABLE artwork DROP COLUMN created_at');
    }
}
