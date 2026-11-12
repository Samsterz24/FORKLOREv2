<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add instructions column to recipes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipes ADD instructions LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recipes DROP instructions');
    }
}
