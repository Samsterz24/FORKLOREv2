<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261111120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add role column to users and seed admin Samsterz24';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD role VARCHAR(50) NOT NULL DEFAULT 'ROLE_USER'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN role');
    }
}