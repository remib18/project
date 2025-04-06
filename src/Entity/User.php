<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "L'email ne peut pas être vide")]
    #[Assert\Email(
        message: "L'email '{{ value }}' n'est pas un email valide.",
        mode: "html5"
    )]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le prénom ne peut pas être vide")]
    private ?string $firstname = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le nom ne peut pas être vide")]
    private ?string $lastname = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return string[] an array of roles
     * @see Role
     */
    public function getRoles(): array
    {
        $enumRoles = array_map(fn(string $role) => Role::tryFrom($role), $this->roles);

        if (in_array(null, $enumRoles, true)) {
            throw new \InvalidArgumentException('Invalid role found');
        }

        // guarantee every user at least has ROLE_USER
        $enumRoles[] = Role::ROLE_USER;

        $stringRoles = array_map(fn(Role $role) => $role->value, $enumRoles);

        return array_unique($stringRoles);
    }

    /**
     * @param Role[]|string[] $roles
     */
    public function setRoles(array $roles): static
    {
        $stringRoles = array_map(function (Role|string $role): string {
            if ($role instanceof Role) {
                return $role->value;
            }

            return Role::tryFrom($role)->value ?? throw new \InvalidArgumentException('Invalid role found');
        }, $roles);

        $this->roles = $stringRoles;

        return $this;
    }

    /**
     * Provides the full name of the user if both firstname and lastname are set.
     * If only one of them is set, it will return that one.
     * @return string|null
     */
    public function getFullName(): ?string
    {
        if (null !== $this->firstname && null === $this->lastname) {
            return $this->firstname;
        }

        if (null === $this->firstname && null !== $this->lastname) {
            return $this->lastname;
        }

        return $this->firstname . ' ' . $this->lastname;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * Check if a user has a specific role.
     * @param Role $role
     * @return bool
     */
    public function isGranted(Role $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * Return the user identifier.
     * This is the same as getUserIdentifier() but is used for compatibility with some Symfony components.
     * @legacy
     * @return string
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * Magic method to get properties dynamically.
     * @param $name string the name of the property to get
     * @return string the value of the property
     * @throws Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException if the property does not exist
     */
    public function __get(string $name)
    {
        if ('username' === $name) {
            return $this->getUserIdentifier();
        }
        throw new Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException("Undefined property: " . $name);
    }
}
