<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'recipes')]
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Cuisine::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Cuisine $cuisine = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private string $title;

    #[ORM\Column(length: 50)]
    private string $category = 'General';

    #[ORM\Column(length: 255)]
    private string $imageFilename = 'default.jpg';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $ingredients = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dataFile = null;

    #[ORM\Column]
    private int $totalLikes = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getCuisine(): ?Cuisine { return $this->cuisine; }
    public function setCuisine(?Cuisine $cuisine): self { $this->cuisine = $cuisine; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): self { $this->category = $category; return $this; }
    public function getImageFilename(): string { return $this->imageFilename; }
    public function setImageFilename(string $imageFilename): self { $this->imageFilename = $imageFilename; return $this; }
    public function getIngredients(): ?string { return $this->ingredients; }
    public function setIngredients(?string $ingredients): self { $this->ingredients = $ingredients; return $this; }
    public function getDataFile(): ?string { return $this->dataFile; }
    public function setDataFile(?string $dataFile): self { $this->dataFile = $dataFile; return $this; }
    public function getTotalLikes(): int { return $this->totalLikes; }
    public function setTotalLikes(int $totalLikes): self { $this->totalLikes = $totalLikes; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}