<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'support_messages')]
class SupportMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SupportTicket::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SupportTicket $ticket;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $sender;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $message = '';

    #[ORM\Column]
    private bool $isAdmin = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getTicket(): SupportTicket { return $this->ticket; }
    public function setTicket(SupportTicket $ticket): self { $this->ticket = $ticket; return $this; }
    public function getSender(): User { return $this->sender; }
    public function setSender(User $sender): self { $this->sender = $sender; return $this; }
    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }
    public function isAdmin(): bool { return $this->isAdmin; }
    public function setIsAdmin(bool $isAdmin): self { $this->isAdmin = $isAdmin; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
