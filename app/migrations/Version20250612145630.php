<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250612145630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE contacts_tags (contact_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_6FDD317FE7A1254A (contact_id), INDEX IDX_6FDD317FBAD26311 (tag_id), PRIMARY KEY(contact_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contacts_tags ADD CONSTRAINT FK_6FDD317FE7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contacts_tags ADD CONSTRAINT FK_6FDD317FBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE contacts_tags DROP FOREIGN KEY FK_6FDD317FE7A1254A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contacts_tags DROP FOREIGN KEY FK_6FDD317FBAD26311
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE contacts_tags
        SQL);
    }
}
