<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $username;

    #[ORM\Column(length: 100, unique: true)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\Column(nullable: true)]
    private ?int $age = null;

    #[ORM\Column(length: 50)]
    private string $role = 'ROLE_USER';

    #[ORM\Column(length: 20)]
    private string $status = 'active';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column]
    private bool $hasFoodieStamp = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $stampExpiresAt = null;

    #[ORM\Column]
    private bool $isVerifiedChef = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function getAge(): ?int { return $this->age; }
    public function setAge(?int $age): self { $this->age = $age; return $this; }
    public function getRole(): string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function isBanned(): bool { return $this->status === 'banned'; }
    public function isStaff(): bool { return $this->role === 'ROLE_STAFF'; }
    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $bio): self { $this->bio = $bio; return $this; }
    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): self { $this->avatar = $avatar; return $this; }
    public function hasFoodieStamp(): bool { return $this->hasFoodieStamp; }
    public function setHasFoodieStamp(bool $v): self { $this->hasFoodieStamp = $v; return $this; }
    public function getStampExpiresAt(): ?\DateTimeImmutable { return $this->stampExpiresAt; }
    public function setStampExpiresAt(?\DateTimeImmutable $v): self { $this->stampExpiresAt = $v; return $this; }
    public function isVerifiedChef(): bool { return $this->isVerifiedChef; }
    public function setIsVerifiedChef(bool $v): self { $this->isVerifiedChef = $v; return $this; }
    public function getInitial(): string { return strtoupper(substr($this->username, 0, 1)); }
    public function getJoined(): \DateTimeImmutable { return $this->createdAt; }
    public function getStampExpires(): ?string { return $this->stampExpiresAt?->format('M j, Y'); }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getRoles(): array { return [$this->role]; }
    public function getUserIdentifier(): string { return $this->username; }
    public function eraseCredentials(): void {}
}