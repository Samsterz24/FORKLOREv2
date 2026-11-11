<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261111130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE subscriptions (id INT AUTO_INCREMENT NOT NULL, plan VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_4778A01A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_4778A01A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users ADD bio LONGTEXT DEFAULT NULL, ADD avatar VARCHAR(255) DEFAULT NULL, ADD has_foodie_stamp TINYINT NOT NULL, ADD stamp_expires_at DATETIME DEFAULT NULL, ADD is_verified_chef TINYINT NOT NULL, CHANGE role role VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscriptions DROP FOREIGN KEY FK_4778A01A76ED395');
        $this->addSql('DROP TABLE subscriptions');
        $this->addSql('ALTER TABLE users DROP bio, DROP avatar, DROP has_foodie_stamp, DROP stamp_expires_at, DROP is_verified_chef, CHANGE role role VARCHAR(50) DEFAULT \'ROLE_USER\' NOT NULL');
    }
}
