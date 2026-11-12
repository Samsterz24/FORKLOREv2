<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert moderator users to staff role';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE users SET role = 'ROLE_STAFF' WHERE role = 'ROLE_MODERATOR'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE users SET role = 'ROLE_MODERATOR' WHERE role = 'ROLE_STAFF'");
    }
}
