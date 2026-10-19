<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Emailes regisztráció és belépés, hét napos auth munkamenet.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD terms_accepted_at INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD email_verified_at INT UNSIGNED DEFAULT NULL');
        $this->addSql("ALTER TABLE magic_login_tokens ADD purpose VARCHAR(24) NOT NULL DEFAULT 'login'");
        $this->addSql(
            'CREATE TABLE auth_sessions ('
            . 'id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, '
            . 'user_id BIGINT UNSIGNED NOT NULL, '
            . 'token_hash VARCHAR(128) NOT NULL, '
            . 'expires_at INT UNSIGNED NOT NULL, '
            . 'created_at INT UNSIGNED NOT NULL, '
            . 'UNIQUE INDEX uniq_auth_sessions_token_hash (token_hash), '
            . 'INDEX idx_auth_sessions_expires (expires_at), '
            . 'INDEX IDX_BE9A1A95A76ED395 (user_id), '
            . 'PRIMARY KEY(id), '
            . 'CONSTRAINT FK_BE9A1A95A76ED395 FOREIGN KEY (user_id) '
            . 'REFERENCES users (id) ON DELETE CASCADE) '
            . 'DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE auth_sessions');
        $this->addSql('ALTER TABLE magic_login_tokens DROP purpose');
        $this->addSql('ALTER TABLE users DROP terms_accepted_at, DROP email_verified_at');
    }
}
