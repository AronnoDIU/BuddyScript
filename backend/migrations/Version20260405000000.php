<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema for users, posts, comments, and likes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `user` (id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", first_name VARCHAR(120) NOT NULL, last_name VARCHAR(120) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", roles JSON NOT NULL, UNIQUE INDEX uniq_user_email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE post (id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", author_id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", content LONGTEXT NOT NULL, visibility VARCHAR(20) NOT NULL, image_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", INDEX IDX_5A8A6C8DF675F31B (author_id), INDEX idx_post_created_at (created_at), INDEX idx_post_visibility (visibility), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE comment (id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", post_id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", author_id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", parent_id BINARY(16) DEFAULT NULL COMMENT "(DC2Type:uuid)", content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", INDEX IDX_9474526C4B89032C (post_id), INDEX IDX_9474526CF675F31B (author_id), INDEX IDX_9474526C727ACA70 (parent_id), INDEX idx_comment_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C727ACA70 FOREIGN KEY (parent_id) REFERENCES comment (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE post_like (id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", post_id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", user_id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", UNIQUE INDEX uniq_post_like_user (post_id, user_id), INDEX IDX_6D6489844B89032C (post_id), INDEX IDX_6D648984A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE post_like ADD CONSTRAINT FK_6D6489844B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_like ADD CONSTRAINT FK_6D648984A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE comment_like (id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", comment_id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", user_id BINARY(16) NOT NULL COMMENT "(DC2Type:uuid)", created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)", UNIQUE INDEX uniq_comment_like_user (comment_id, user_id), INDEX IDX_8A16D74DF8697D13 (comment_id), INDEX IDX_8A16D74DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT FK_8A16D74DF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_like ADD CONSTRAINT FK_8A16D74DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DF675F31B');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4B89032C');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CF675F31B');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C727ACA70');
        $this->addSql('ALTER TABLE post_like DROP FOREIGN KEY FK_6D6489844B89032C');
        $this->addSql('ALTER TABLE post_like DROP FOREIGN KEY FK_6D648984A76ED395');
        $this->addSql('ALTER TABLE comment_like DROP FOREIGN KEY FK_8A16D74DF8697D13');
        $this->addSql('ALTER TABLE comment_like DROP FOREIGN KEY FK_8A16D74DA76ED395');

        $this->addSql('DROP TABLE comment_like');
        $this->addSql('DROP TABLE post_like');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE `user`');
    }
}

