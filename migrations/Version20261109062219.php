<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261109062219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cuisines (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, slug VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, image_filename VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_C17EA2F75E237E06 (name), UNIQUE INDEX UNIQ_C17EA2F7989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE favorites (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, recipe_id INT NOT NULL, INDEX IDX_E46960F5A76ED395 (user_id), INDEX IDX_E46960F559D8A214 (recipe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE posts (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(200) NOT NULL, category VARCHAR(50) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_885DBAFAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recipes (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, category VARCHAR(50) NOT NULL, image_filename VARCHAR(255) NOT NULL, ingredients LONGTEXT DEFAULT NULL, data_file VARCHAR(255) DEFAULT NULL, total_likes INT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, cuisine_id INT DEFAULT NULL, INDEX IDX_A369E2B5A76ED395 (user_id), INDEX IDX_A369E2B5ED4BAC14 (cuisine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE replies (id INT AUTO_INCREMENT NOT NULL, comment LONGTEXT NOT NULL, created_at DATETIME NOT NULL, post_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_A000672A4B89032C (post_id), INDEX IDX_A000672AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stories (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, location VARCHAR(120) NOT NULL, author_name VARCHAR(100) NOT NULL, quote LONGTEXT DEFAULT NULL, image_filename VARCHAR(255) NOT NULL, data_file VARCHAR(255) DEFAULT NULL, saves_count INT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, recipe_id INT DEFAULT NULL, cuisine_id INT DEFAULT NULL, INDEX IDX_9C8B9D5FA76ED395 (user_id), INDEX IDX_9C8B9D5F59D8A214 (recipe_id), INDEX IDX_9C8B9D5FED4BAC14 (cuisine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(50) NOT NULL, email VARCHAR(100) NOT NULL, password VARCHAR(255) NOT NULL, age INT DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE favorites ADD CONSTRAINT FK_E46960F5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favorites ADD CONSTRAINT FK_E46960F559D8A214 FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipes ADD CONSTRAINT FK_A369E2B5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipes ADD CONSTRAINT FK_A369E2B5ED4BAC14 FOREIGN KEY (cuisine_id) REFERENCES cuisines (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE replies ADD CONSTRAINT FK_A000672A4B89032C FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE replies ADD CONSTRAINT FK_A000672AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stories ADD CONSTRAINT FK_9C8B9D5FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stories ADD CONSTRAINT FK_9C8B9D5F59D8A214 FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stories ADD CONSTRAINT FK_9C8B9D5FED4BAC14 FOREIGN KEY (cuisine_id) REFERENCES cuisines (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE favorites DROP FOREIGN KEY FK_E46960F5A76ED395');
        $this->addSql('ALTER TABLE favorites DROP FOREIGN KEY FK_E46960F559D8A214');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFAA76ED395');
        $this->addSql('ALTER TABLE recipes DROP FOREIGN KEY FK_A369E2B5A76ED395');
        $this->addSql('ALTER TABLE recipes DROP FOREIGN KEY FK_A369E2B5ED4BAC14');
        $this->addSql('ALTER TABLE replies DROP FOREIGN KEY FK_A000672A4B89032C');
        $this->addSql('ALTER TABLE replies DROP FOREIGN KEY FK_A000672AA76ED395');
        $this->addSql('ALTER TABLE stories DROP FOREIGN KEY FK_9C8B9D5FA76ED395');
        $this->addSql('ALTER TABLE stories DROP FOREIGN KEY FK_9C8B9D5F59D8A214');
        $this->addSql('ALTER TABLE stories DROP FOREIGN KEY FK_9C8B9D5FED4BAC14');
        $this->addSql('DROP TABLE cuisines');
        $this->addSql('DROP TABLE favorites');
        $this->addSql('DROP TABLE posts');
        $this->addSql('DROP TABLE recipes');
        $this->addSql('DROP TABLE replies');
        $this->addSql('DROP TABLE stories');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
