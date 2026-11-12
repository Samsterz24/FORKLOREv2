<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user status, recipe visibility, story saves, and staff warnings tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD status VARCHAR(20) NOT NULL DEFAULT 'active'");
        $this->addSql('ALTER TABLE recipes ADD is_public TINYINT(1) NOT NULL DEFAULT 1');

        $this->addSql('CREATE TABLE story_saves (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, story_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_STORY_SAVES_USER (user_id), INDEX IDX_STORY_SAVES_STORY (story_id), UNIQUE INDEX unique_user_story (user_id, story_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE story_saves ADD CONSTRAINT FK_STORY_SAVES_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE story_saves ADD CONSTRAINT FK_STORY_SAVES_STORY FOREIGN KEY (story_id) REFERENCES stories (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE staff_warnings (id INT AUTO_INCREMENT NOT NULL, staff_id INT NOT NULL, target_user_id INT NOT NULL, reason LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_STAFF_WARN_STAFF (staff_id), INDEX IDX_STAFF_WARN_TARGET (target_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE staff_warnings ADD CONSTRAINT FK_STAFF_WARN_STAFF FOREIGN KEY (staff_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE staff_warnings ADD CONSTRAINT FK_STAFF_WARN_TARGET FOREIGN KEY (target_user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE staff_warnings DROP FOREIGN KEY FK_STAFF_WARN_STAFF');
        $this->addSql('ALTER TABLE staff_warnings DROP FOREIGN KEY FK_STAFF_WARN_TARGET');
        $this->addSql('DROP TABLE staff_warnings');
        $this->addSql('ALTER TABLE story_saves DROP FOREIGN KEY FK_STORY_SAVES_USER');
        $this->addSql('ALTER TABLE story_saves DROP FOREIGN KEY FK_STORY_SAVES_STORY');
        $this->addSql('DROP TABLE story_saves');
        $this->addSql('ALTER TABLE recipes DROP is_public');
        $this->addSql('ALTER TABLE users DROP status');
    }
}
