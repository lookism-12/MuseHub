<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201211000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification table for tracking post reactions, comments, and replies';
    }

    public function up(Schema $schema): void
    {
        // Create notification table
        $this->addSql('CREATE TABLE notification (
            id INT AUTO_INCREMENT NOT NULL,
            recipient_uuid VARCHAR(64) NOT NULL,
            actor_uuid VARCHAR(64) NOT NULL,
            type VARCHAR(32) NOT NULL,
            post_id INT DEFAULT NULL,
            comment_id INT DEFAULT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_recipient_uuid (recipient_uuid),
            INDEX idx_is_read (is_read),
            INDEX idx_recipient_read (recipient_uuid, is_read),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification');
    }
}
