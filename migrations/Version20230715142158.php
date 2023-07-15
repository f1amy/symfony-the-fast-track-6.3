<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230715142158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            INSERT INTO admin (id, username, roles, password)
                VALUES (nextval('admin_id_seq'), 'admin', '["ROLE_ADMIN"]',
                        '$2y$13$XkrtWrFopkBSXKM7eqPxDudM0QOpcHtq5IovuLTNR/5fKA3r7T8VS')
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM admin where username = 'admin'");
    }
}
