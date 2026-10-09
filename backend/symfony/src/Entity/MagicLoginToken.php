<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MagicLoginTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MagicLoginTokenRepository::class)]
#[ORM\Table(name: 'magic_login_tokens')]
#[ORM\UniqueConstraint(name: 'uniq_magic_login_tokens_token_hash', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_magic_login_tokens_user_created', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'idx_magic_login_tokens_expires', columns: ['expires_at'])]
#[ORM\Index(name: 'idx_magic_login_tokens_consumed_expires', columns: ['consumed_at', 'expires_at'])]
class MagicLoginToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'magicLoginTokens')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 128)]
    private string $tokenHash = '';

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private int $expiresAt = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['unsigned' => true])]
    private ?int $consumedAt = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $requestIpHash = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $userAgentHash = null;

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private int $createdAt = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): self
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(int $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getConsumedAt(): ?int
    {
        return $this->consumedAt;
    }

    public function setConsumedAt(?int $consumedAt): self
    {
        $this->consumedAt = $consumedAt;

        return $this;
    }

    public function getRequestIpHash(): ?string
    {
        return $this->requestIpHash;
    }

    public function setRequestIpHash(?string $requestIpHash): self
    {
        $this->requestIpHash = $requestIpHash;

        return $this;
    }

    public function getUserAgentHash(): ?string
    {
        return $this->userAgentHash;
    }

    public function setUserAgentHash(?string $userAgentHash): self
    {
        $this->userAgentHash = $userAgentHash;

        return $this;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
