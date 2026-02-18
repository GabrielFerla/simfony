<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Etapa 1 — Tabelas user e daily_entry (Só Uma Coisa).
 */
final class Version20260218140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria tabelas user e daily_entry com UNIQUE(user_id, date).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE "user" (
            id UUID NOT NULL,
            email VARCHAR(180) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            display_name VARCHAR(255) NOT NULL,
            timezone VARCHAR(64) NOT NULL DEFAULT \'America/Sao_Paulo\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');

        $this->addSql('CREATE TABLE daily_entry (
            id UUID NOT NULL,
            user_id UUID NOT NULL,
            date DATE NOT NULL,
            intention TEXT NOT NULL,
            completed BOOLEAN DEFAULT NULL,
            skipped BOOLEAN NOT NULL DEFAULT false,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_DAILY_ENTRY_USER_DATE ON daily_entry (user_id, date)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_date ON daily_entry (user_id, date)');
        $this->addSql('ALTER TABLE daily_entry ADD CONSTRAINT FK_DAILY_ENTRY_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE daily_entry DROP CONSTRAINT FK_DAILY_ENTRY_USER');
        $this->addSql('DROP TABLE daily_entry');
        $this->addSql('DROP TABLE "user"');
    }
}
