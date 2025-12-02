<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251120120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add post_reaction table and track dislikes count on posts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post ADD dislikes_count INT NOT NULL DEFAULT 0');
        $this->addSql('CREATE TABLE post_reaction (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, user_uuid VARCHAR(64) NOT NULL, type VARCHAR(8) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8F488B9F4B89032C (post_id), UNIQUE INDEX uniq_post_reaction (post_id, user_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE post_reaction ADD CONSTRAINT FK_8F488B9F4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('UPDATE post SET dislikes_count = 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP dislikes_count');
        $this->addSql('DROP TABLE post_reaction');
    }
}

