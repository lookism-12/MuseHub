<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for all MuseHub modules
 */
final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all tables for MuseHub modules (User, Artwork, Event, Marketplace, Community)';
    }

    public function up(Schema $schema): void
    {
        // User table
        if (!$schema->hasTable('user')) {
            $this->addSql('CREATE TABLE user (
                id INT AUTO_INCREMENT NOT NULL,
                uuid VARCHAR(36) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                username VARCHAR(255),
                roles JSON NOT NULL,
                bio TEXT,
                avatar_url VARCHAR(255),
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Category table
        if (!$schema->hasTable('category')) {
            $this->addSql('CREATE TABLE category (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Artwork table
        if (!$schema->hasTable('artwork')) {
            $this->addSql('CREATE TABLE artwork (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                image_url VARCHAR(255),
                price NUMERIC(10, 2),
                artist_uuid VARCHAR(255) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT "visible",
                category_id INT,
                PRIMARY KEY(id),
                INDEX IDX_CATEGORY (category_id),
                FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Event table
        if (!$schema->hasTable('event')) {
            $this->addSql('CREATE TABLE event (
                id INT AUTO_INCREMENT NOT NULL,
                uuid VARCHAR(36) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                date_time DATETIME NOT NULL,
                location VARCHAR(50) NOT NULL,
                organiser_uuid VARCHAR(36) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Participant table
        if (!$schema->hasTable('participant')) {
            $this->addSql('CREATE TABLE participant (
                id INT AUTO_INCREMENT NOT NULL,
                event_uuid VARCHAR(36) NOT NULL,
                participant_uuid VARCHAR(36) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT "confirmed",
                created_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                INDEX IDX_EVENT_UUID (event_uuid),
                INDEX IDX_PARTICIPANT_UUID (participant_uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Listing table
        if (!$schema->hasTable('listing')) {
            $this->addSql('CREATE TABLE listing (
                id INT AUTO_INCREMENT NOT NULL,
                uuid VARCHAR(36) NOT NULL UNIQUE,
                artwork_uuid VARCHAR(36) NOT NULL,
                price NUMERIC(10, 2) NOT NULL,
                stock INT NOT NULL DEFAULT 1,
                status VARCHAR(20) NOT NULL DEFAULT "available",
                created_at DATETIME NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Transaction table
        if (!$schema->hasTable('transaction')) {
            $this->addSql('CREATE TABLE transaction (
                id INT AUTO_INCREMENT NOT NULL,
                uuid VARCHAR(36) NOT NULL UNIQUE,
                buyer_uuid VARCHAR(36) NOT NULL,
                listing_uuid VARCHAR(36) NOT NULL,
                amount NUMERIC(10, 2) NOT NULL,
                date DATETIME NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT "paid",
                created_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                INDEX IDX_BUYER_UUID (buyer_uuid),
                INDEX IDX_LISTING_UUID (listing_uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Post table
        if (!$schema->hasTable('post')) {
            $this->addSql('CREATE TABLE post (
                id INT AUTO_INCREMENT NOT NULL,
                author_uuid VARCHAR(36) NOT NULL,
                content TEXT NOT NULL,
                image_url VARCHAR(255),
                likes_count INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                INDEX IDX_AUTHOR_UUID (author_uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Comment table
        if (!$schema->hasTable('comment')) {
            $this->addSql('CREATE TABLE comment (
                id INT AUTO_INCREMENT NOT NULL,
                post_id INT NOT NULL,
                commenter_uuid VARCHAR(36) NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY(id),
                INDEX IDX_POST_ID (post_id),
                INDEX IDX_COMMENTER_UUID (commenter_uuid),
                FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('comment')) {
            $this->addSql('DROP TABLE comment');
        }
        if ($schema->hasTable('post')) {
            $this->addSql('DROP TABLE post');
        }
        if ($schema->hasTable('transaction')) {
            $this->addSql('DROP TABLE transaction');
        }
        if ($schema->hasTable('listing')) {
            $this->addSql('DROP TABLE listing');
        }
        if ($schema->hasTable('participant')) {
            $this->addSql('DROP TABLE participant');
        }
        if ($schema->hasTable('event')) {
            $this->addSql('DROP TABLE event');
        }
        if ($schema->hasTable('artwork')) {
            $this->addSql('DROP TABLE artwork');
        }
        if ($schema->hasTable('category')) {
            $this->addSql('DROP TABLE category');
        }
        if ($schema->hasTable('user')) {
            $this->addSql('DROP TABLE user');
        }
    }
}