<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251119120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset token columns to user table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user');

        if (!$table->hasColumn('password_reset_token')) {
            $this->addSql('ALTER TABLE user ADD password_reset_token VARCHAR(64) DEFAULT NULL');
        }

        if (!$table->hasColumn('password_reset_requested_at')) {
            $this->addSql('ALTER TABLE user ADD password_reset_requested_at DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user');

        if ($table->hasColumn('password_reset_token')) {
            $this->addSql('ALTER TABLE user DROP password_reset_token');
        }

        if ($table->hasColumn('password_reset_requested_at')) {
            $this->addSql('ALTER TABLE user DROP password_reset_requested_at');
        }
    }
}


