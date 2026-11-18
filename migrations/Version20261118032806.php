<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118032806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add seen_at column to staff_warnings table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE staff_warnings ADD seen_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE staff_warnings DROP seen_at');
    }
}
