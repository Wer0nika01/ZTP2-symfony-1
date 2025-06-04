<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250604093139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tasks ADD author_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tasks ADD CONSTRAINT FK_50586597F675F31B FOREIGN KEY (author_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_50586597F675F31B ON tasks (author_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE tasks DROP FOREIGN KEY FK_50586597F675F31B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_50586597F675F31B ON tasks
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tasks DROP author_id
        SQL);
    }
}
