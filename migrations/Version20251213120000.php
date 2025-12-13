<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add moderation fields to comment table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment ADD is_valid TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE comment ADD contains_bad_words TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE comment ADD is_spam TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE comment ADD toxicity_score DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP toxicity_score');
        $this->addSql('ALTER TABLE comment DROP is_spam');
        $this->addSql('ALTER TABLE comment DROP contains_bad_words');
        $this->addSql('ALTER TABLE comment DROP is_valid');
    }
}
