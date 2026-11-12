<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'staff_warnings')]
class StaffWarning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $staff;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $targetUser;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getStaff(): User { return $this->staff; }
    public function setStaff(User $staff): self { $this->staff = $staff; return $this; }
    public function getTargetUser(): User { return $this->targetUser; }
    public function setTargetUser(User $targetUser): self { $this->targetUser = $targetUser; return $this; }
    public function getReason(): string { return $this->reason; }
    public function setReason(string $reason): self { $this->reason = $reason; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
