<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012082738 extends AbstractMigration
{
    private const ADMIN_EMAIL = 'admin@nexcode.hu';

    public function getDescription(): string
    {
        return 'Kezdő séma: users és magic_login_tokens táblák, admin@nexcode.hu super admin user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE magic_login_tokens (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, token_hash VARCHAR(128) NOT NULL, expires_at INT UNSIGNED NOT NULL, consumed_at INT UNSIGNED DEFAULT NULL, request_ip_hash VARCHAR(128) DEFAULT NULL, user_agent_hash VARCHAR(128) DEFAULT NULL, created_at INT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, INDEX IDX_8F61CB08A76ED395 (user_id), INDEX idx_magic_login_tokens_user_created (user_id, created_at), INDEX idx_magic_login_tokens_expires (expires_at), INDEX idx_magic_login_tokens_consumed_expires (consumed_at, expires_at), UNIQUE INDEX uniq_magic_login_tokens_token_hash (token_hash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, display_name VARCHAR(180) DEFAULT NULL, roles JSON NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, last_login_at INT UNSIGNED DEFAULT NULL, created_at INT UNSIGNED NOT NULL, updated_at INT UNSIGNED NOT NULL, INDEX idx_users_created_at (created_at), UNIQUE INDEX uniq_users_email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE magic_login_tokens ADD CONSTRAINT FK_8F61CB08A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');

        $now = time();
        $this->addSql(
            'INSERT INTO users (email, roles, is_active, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [self::ADMIN_EMAIL, json_encode(['ROLE_SUPER_ADMIN'], JSON_THROW_ON_ERROR), $now, $now],
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE magic_login_tokens DROP FOREIGN KEY FK_8F61CB08A76ED395');
        $this->addSql('DROP TABLE magic_login_tokens');
        $this->addSql('DROP TABLE users');
    }
}
