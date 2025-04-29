<?php

namespace App\Entity;

use App\Repository\CourseUnitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseUnitRepository::class)]
class CourseUnit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    /**
     * @var Collection<int, CourseGroup>
     */
    #[ORM\OneToMany(targetEntity: CourseGroup::class, mappedBy: 'unit', orphanRemoval: true)]
    private Collection $groups;

    /**
     * @var Collection<int, CourseActivity>
     */
    #[ORM\OneToMany(targetEntity: CourseActivity::class, mappedBy: 'courseUnit', orphanRemoval: true)]
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

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
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

    public function addGroup(CourseGroup $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
            $group->setUnit($this);
        }

        return $this;
    }

    public function removeGroup(CourseGroup $group): static
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

    public function addActivity(CourseActivity $activity): static
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
            $activity->setCourseUnit($this);
        }

        return $this;
    }

    public function removeActivity(CourseActivity $activity): static
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
