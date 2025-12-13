<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251208122600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add latitude and longitude columns to event table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD latitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP COLUMN latitude');
        $this->addSql('ALTER TABLE event DROP COLUMN longitude');
    }
}
