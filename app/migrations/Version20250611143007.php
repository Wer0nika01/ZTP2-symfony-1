<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611143007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE events ADD start_time DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD end_time DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD location VARCHAR(255) DEFAULT NULL, ADD is_all_day TINYINT(1) DEFAULT 0 NOT NULL, DROP created_at, DROP updated_at, CHANGE comment description LONGTEXT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE events ADD created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', ADD updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', DROP start_time, DROP end_time, DROP location, DROP is_all_day, CHANGE description comment LONGTEXT DEFAULT NULL
        SQL);
    }
}
