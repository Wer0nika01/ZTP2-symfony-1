<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250618224952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE categories CHANGE slug slug VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_3AF34668989D9B62 ON categories (slug)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contacts CHANGE author_id author_id INT DEFAULT NULL, CHANGE address address TEXT DEFAULT NULL, CHANGE notes notes TEXT DEFAULT NULL, CHANGE phone phone_number VARCHAR(50) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contacts_tags DROP FOREIGN KEY FK_6FDD317FBAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contacts_tags ADD CONSTRAINT FK_6FDD317FBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE events DROP location, DROP is_all_day, CHANGE author_id author_id INT DEFAULT NULL, CHANGE status status VARCHAR(20) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE events_tags DROP FOREIGN KEY FK_3EC905CBAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE events_tags ADD CONSTRAINT FK_3EC905CBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag CHANGE slug slug VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_389B783989D9B62 ON tag (slug)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_389B783989D9B62 ON tag
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tag CHANGE slug slug VARCHAR(64) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE events_tags DROP FOREIGN KEY FK_3EC905CBAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE events_tags ADD CONSTRAINT FK_3EC905CBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE events ADD location VARCHAR(255) DEFAULT NULL, ADD is_all_day TINYINT(1) DEFAULT 0 NOT NULL, CHANGE author_id author_id INT NOT NULL, CHANGE status status INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contacts_tags DROP FOREIGN KEY FK_6FDD317FBAD26311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contacts_tags ADD CONSTRAINT FK_6FDD317FBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON UPDATE NO ACTION ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contacts CHANGE author_id author_id INT NOT NULL, CHANGE address address LONGTEXT DEFAULT NULL, CHANGE notes notes LONGTEXT DEFAULT NULL, CHANGE phone_number phone VARCHAR(50) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_3AF34668989D9B62 ON categories
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE categories CHANGE slug slug VARCHAR(64) NOT NULL
        SQL);
    }
}
