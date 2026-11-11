<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'stories')]
class Story
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Recipe::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Recipe $recipe = null;

    #[ORM\ManyToOne(targetEntity: Cuisine::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Cuisine $cuisine = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    private string $title;

    #[ORM\Column(length: 120)]
    private string $location;

    #[ORM\Column(length: 100)]
    private string $authorName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $quote = null;

    #[ORM\Column(length: 255)]
    private string $imageFilename = 'default.jpg';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dataFile = null;

    #[ORM\Column]
    private int $savesCount = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getRecipe(): ?Recipe { return $this->recipe; }
    public function setRecipe(?Recipe $recipe): self { $this->recipe = $recipe; return $this; }
    public function getCuisine(): ?Cuisine { return $this->cuisine; }
    public function setCuisine(?Cuisine $cuisine): self { $this->cuisine = $cuisine; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getLocation(): string { return $this->location; }
    public function setLocation(string $location): self { $this->location = $location; return $this; }
    public function getAuthorName(): string { return $this->authorName; }
    public function setAuthorName(string $authorName): self { $this->authorName = $authorName; return $this; }
    public function getQuote(): ?string { return $this->quote; }
    public function setQuote(?string $quote): self { $this->quote = $quote; return $this; }
    public function getImageFilename(): string { return $this->imageFilename; }
    public function setImageFilename(string $imageFilename): self { $this->imageFilename = $imageFilename; return $this; }
    public function getDataFile(): ?string { return $this->dataFile; }
    public function setDataFile(?string $dataFile): self { $this->dataFile = $dataFile; return $this; }
    public function getSavesCount(): int { return $this->savesCount; }
    public function setSavesCount(int $savesCount): self { $this->savesCount = $savesCount; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}