<?php

namespace App\Entity;

use App\Repository\CourseGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourseGroupRepository::class)]
class CourseGroup
{
    public const GROUP_TYPES = [
        'CM' => 'Cours Magistral',
        'TD' => 'Travaux Dirigés',
        'TP' => 'Travaux Pratiques',
        'PR' => 'Projet',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Group name cannot be blank')]
    #[Assert\Length(max: 50, maxMessage: 'Group name cannot be longer than {{ limit }} characters')]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'groups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CourseUnit $unit = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(
        targetEntity: User::class,
        inversedBy: 'memberInGroups'
    )]
    private Collection $members;

    #[ORM\Embedded(class: CourseSchedule::class)]
    #[Assert\NotNull(message: 'Schedule cannot be null')]
    #[Assert\Valid]
    private ?CourseSchedule $schedule = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Room cannot be blank')]
    #[Assert\Length(max: 50, maxMessage: 'Room cannot be longer than {{ limit }} characters')]
    private ?string $room = null;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUnit(): ?CourseUnit
    {
        return $this->unit;
    }

    public function setUnit(?CourseUnit $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(User $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
        }

        return $this;
    }

    public function removeMember(User $member): self
    {
        $this->members->removeElement($member);

        return $this;
    }

    public function getSchedule(): ?CourseSchedule
    {
        return $this->schedule;
    }

    public function setSchedule(?CourseSchedule $schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }

    /**
     * Check if this group's course is currently active
     */
    public function isNow(\DateTimeInterface $dateTime = null): bool
    {
        if ($this->schedule === null) {
            return false;
        }

        return $this->schedule->isNow($dateTime ?? new \DateTime());
    }

    /**
     * Get the next occurrence of this group's schedule
     */
    public function getNextOccurrence(\DateTimeInterface $from = null): ?\DateTime
    {
        if ($this->schedule === null) {
            return null;
        }

        return $this->schedule->getNextOccurrence($from ?? new \DateTime());
    }

    public function getRoom(): ?string
    {
        return $this->room;
    }

    public function setRoom(string $room): self
    {
        $this->room = $room;

        return $this;
    }

    /**
     * Get course group type from name (CM, TD, TP, PR)
     */
    public function getType(): string
    {
        $pattern = '/^(CM|TD|TP|PR)/';
        if (preg_match($pattern, $this->name, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Get full type name (e.g., "Cours Magistral" for CM)
     */
    public function getTypeName(): string
    {
        $type = $this->getType();
        return self::GROUP_TYPES[$type] ?? $type;
    }
}