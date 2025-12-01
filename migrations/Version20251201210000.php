<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add parent_comment_id to comment table for threaded/nested comments';
    }

    public function up(Schema $schema): void
    {
        // Add parent_comment_id column to comment table
        $this->addSql('ALTER TABLE comment ADD parent_comment_id INT DEFAULT NULL');

        // Add foreign key constraint
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CBF2AF943 FOREIGN KEY (parent_comment_id) REFERENCES comment (id) ON DELETE CASCADE');

        // Add index for performance
        $this->addSql('CREATE INDEX IDX_9474526CBF2AF943 ON comment (parent_comment_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove foreign key and index
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CBF2AF943');
        $this->addSql('DROP INDEX IDX_9474526CBF2AF943 ON comment');

        // Remove column
        $this->addSql('ALTER TABLE comment DROP parent_comment_id');
    }
}
