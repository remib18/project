<?php

namespace App\Entity;

use App\Repository\CourseActivityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourseActivityRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CourseActivity
{
    public const TYPES = [
        'message',
        'document',
        'document-submission',
    ];

    public const CATEGORIES = [
        'general'=> [ 'name' => 'Général', 'desc' => 'Informations générales sur l\'UE' ],
        'lecture' => [ 'name' => 'Cours', 'desc' => 'Cours magistraux' ],
        'directed-study' => [ 'name' => 'TD', 'desc' => 'Travaux dirigés' ],
        'practical-work' => [ 'name' => 'TP', 'desc' => 'Travaux pratiques' ],
        'project' => [ 'name' => 'Projet', 'desc' => 'Projets à réaliser' ],
        'exam' => [ 'name' => 'Examen', 'desc' => 'Examens à rendre' ],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 63)]
    #[Assert\Choice(choices: self::TYPES, message: 'Choose a valid activity type.')]
    private ?string $type = null;

    #[ORM\Column(type: 'json')]
    private array $data = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $isPinned = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pinnedMessage = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\ManyToOne(inversedBy: 'activities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CourseUnit $courseUnit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid type "%s".', $type));
        }

        $this->type = $type;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the created_by field in the data array
     *
     * @param User $user The user who created this activity
     * @return $this
     */
    public function setCreatedBy(User $user): static
    {
        $this->data['created_by'] = $user->getId();
        return $this;
    }

    /**
     * Get the created_by user ID from the data array
     *
     * @return int|null The ID of the user who created this activity, or null if not set
     */
    public function getCreatedById(): ?int
    {
        return $this->data['created_by'] ?? null;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $this->updatedAt ?? $now;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isPinned(): ?bool
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned): static
    {
        $this->isPinned = $isPinned;

        return $this;
    }

    public function getPinnedMessage(): ?string
    {
        return $this->pinnedMessage;
    }

    public function setPinnedMessage(?string $pinnedMessage): static
    {
        $this->pinnedMessage = $pinnedMessage;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        if (!array_key_exists($category, self::CATEGORIES)) {
            throw new \InvalidArgumentException(sprintf('Invalid category "%s".', $category));
        }

        $this->category = $category;

        return $this;
    }

    public function getCourseUnit(): ?CourseUnit
    {
        return $this->courseUnit;
    }

    public function setCourseUnit(?CourseUnit $courseUnit): static
    {
        $this->courseUnit = $courseUnit;

        return $this;
    }
}