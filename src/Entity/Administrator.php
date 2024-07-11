<?php

namespace App\Entity;

use App\Repository\AdministratorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'administrators')]
#[ORM\Entity(repositoryClass: AdministratorRepository::class)]
#[UniqueEntity(fields: ['emailAddress'])]
class Administrator implements UserInterface, TwoFactorInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string|null
     */
    #[ORM\Column(unique: true)]
    #[Assert\Email]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255, maxMessage: 'common.email.max_length')]
    private $emailAddress;

    /**
     * @var string|null
     */
    #[ORM\Column]
    private $password;

    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    private $googleAuthenticatorSecret;

    /**
     * @var AdministratorRole[]|Collection
     */
    #[ORM\JoinTable(name: 'administrators_roles')]
    #[ORM\JoinColumn(name: 'administrator_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'administrator_role_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: AdministratorRole::class)]
    private Collection $administratorRoles;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private $activated = true;

    public function __construct()
    {
        $this->administratorRoles = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->emailAddress ?: '';
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_ADMIN_DASHBOARD'];

        foreach ($this->administratorRoles as $administratorRole) {
            if (!$administratorRole->enabled) {
                continue;
            }

            $roles[] = $administratorRole->code;
        }

        return $roles;
    }

    public function getSalt()
    {
        return;
    }

    public function getUsername()
    {
        return $this->emailAddress;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function eraseCredentials()
    {
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param string|null $emailAddress
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @param string|null $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function isActivated(): bool
    {
        return $this->activated;
    }

    public function setActivated(bool $activated): void
    {
        $this->activated = $activated;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    /**
     * @param string|null $googleAuthenticatorSecret
     */
    public function setGoogleAuthenticatorSecret($googleAuthenticatorSecret): void
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return null !== $this->googleAuthenticatorSecret;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->emailAddress;
    }

    public function addAdministratorRole(AdministratorRole $administratorRole): void
    {
        if (!$this->administratorRoles->contains($administratorRole)) {
            $this->administratorRoles->add($administratorRole);
        }
    }

    public function removeAdministratorRole(AdministratorRole $administratorRole): void
    {
        $this->administratorRoles->removeElement($administratorRole);
    }

    public function getAdministratorRoles(): Collection
    {
        return $this->administratorRoles;
    }

    public function getAdministratorRoleCodes(): array
    {
        return array_map(function (AdministratorRole $administratorRole): string {
            return $administratorRole->code;
        }, $this->administratorRoles->toArray());
    }
}
