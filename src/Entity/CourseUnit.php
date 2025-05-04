<?php

namespace App\Entity;

use App\Repository\CourseUnitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourseUnitRepository::class)]
class CourseUnit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Course name cannot be blank')]
    #[Assert\Length(max: 255, maxMessage: 'Course name cannot be longer than {{ limit }} characters')]
    private ?string $name = null;

    #[ORM\Column(length: 1000)]
    #[Assert\NotBlank(message: 'Course description cannot be blank')]
    #[Assert\Length(max: 1000, maxMessage: 'Course description cannot be longer than {{ limit }} characters')]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(
        pattern: '/^(https?:\/\/.*|\/.*\.(jpg|jpeg|png|webp))$/i',
        message: 'Image must be a valid URL or file path'
    )]
    #[Assert\Length(max: 255, maxMessage: 'Image URL cannot be longer than {{ limit }} characters')]
    private ?string $image = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Course slug cannot be blank')]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'Course slug can only contain lowercase letters, numbers, and hyphens')]
    #[Assert\Length(max: 255, maxMessage: 'Course slug cannot be longer than {{ limit }} characters')]
    private ?string $slug = null;

    /**
     * @var Collection<int, CourseGroup>
     */
    #[ORM\OneToMany(
        targetEntity: CourseGroup::class,
        mappedBy: 'unit',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $groups;

    /**
     * @var Collection<int, CourseActivity>
     */
    #[ORM\OneToMany(
        targetEntity: CourseActivity::class,
        mappedBy: 'courseUnit',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $activities;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->activities = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, CourseGroup>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(CourseGroup $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
            $group->setUnit($this);
        }

        return $this;
    }

    public function removeGroup(CourseGroup $group): self
    {
        if ($this->groups->removeElement($group)) {
            // set the owning side to null (unless already changed)
            if ($group->getUnit() === $this) {
                $group->setUnit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CourseActivity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(CourseActivity $activity): self
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
            $activity->setCourseUnit($this);
        }

        return $this;
    }

    public function removeActivity(CourseActivity $activity): self
    {
        if ($this->activities->removeElement($activity)) {
            // set the owning side to null (unless already changed)
            if ($activity->getCourseUnit() === $this) {
                $activity->setCourseUnit(null);
            }
        }

        return $this;
    }
}