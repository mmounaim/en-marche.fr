<?php

namespace AppBundle\Entity;

use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ReferentTagRepository")
 * @ORM\Table(
 *     name="referent_tags",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="referent_tag_name_unique", columns="name"),
 *         @ORM\UniqueConstraint(name="referent_tag_code_unique", columns="code")
 *     }
 * )
 *
 * @UniqueEntity("name")
 * @UniqueEntity("code")
 *
 * @Algolia\Index(autoIndex=false)
 */
class ReferentTag
{
    public const TYPE_DEPARTMENT = 'department';
    public const TYPE_COUNTRY = 'country';
    public const TYPE_DISTRICT = 'district';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned": true})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(length=100, unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Length(max="100")
     *
     * @Serializer\Groups("public")
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(length=100, unique=true)
     *
     * @Assert\NotBlank
     * @Assert\Length(max=100)
     * @Assert\Regex(pattern="/^[a-z0-9-]+$/", message="referent_tag.code.invalid")
     *
     * @Serializer\Groups({"adherent_change_diff", "public"})
     */
    private $code;

    /**
     * Mailchimp Id of the tag
     *
     * @var string|null
     *
     * @ORM\Column(nullable=true)
     */
    private $externalId;

    /**
     * @var string
     *
     * @ORM\Column(nullable=true)
     */
    private $type;

    public function __construct(string $name = null, string $code = null)
    {
        $this->name = $name;
        $this->code = $code;
    }

    public function __toString(): string
    {
        return $this->name ?: '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId;
    }
}
