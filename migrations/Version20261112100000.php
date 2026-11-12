<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create support_tickets and support_messages tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE support_tickets (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, subject VARCHAR(200) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_4D1F0D1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE support_messages (id INT AUTO_INCREMENT NOT NULL, ticket_id INT NOT NULL, sender_id INT NOT NULL, message LONGTEXT NOT NULL, is_admin TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_5A4C8A9D700047D2 (ticket_id), INDEX IDX_5A4C8A9DF624B39D (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE support_tickets ADD CONSTRAINT FK_4D1F0D1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_messages ADD CONSTRAINT FK_5A4C8A9D700047D2 FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_messages ADD CONSTRAINT FK_5A4C8A9DF624B39D FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_messages DROP FOREIGN KEY FK_5A4C8A9D700047D2');
        $this->addSql('ALTER TABLE support_messages DROP FOREIGN KEY FK_5A4C8A9DF624B39D');
        $this->addSql('ALTER TABLE support_tickets DROP FOREIGN KEY FK_4D1F0D1A76ED395');
        $this->addSql('DROP TABLE support_messages');
        $this->addSql('DROP TABLE support_tickets');
    }
}
