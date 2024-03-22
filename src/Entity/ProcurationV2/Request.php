<?php

namespace App\Entity\ProcurationV2;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Api\Filter\InZoneOfScopeFilter;
use App\Procuration\V2\RequestStatusEnum;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="procuration_v2_requests")
 * @ORM\Entity(repositoryClass="App\Repository\Procuration\RequestRepository")
 *
 * @ApiResource(
 *     attributes={
 *         "routePrefix": "/v3/procuration",
 *         "security": "is_granted('ROLE_OAUTH_SCOPE_JEMENGAGE_ADMIN') and is_granted('IS_FEATURE_GRANTED', 'procuration')",
 *         "normalization_context": {
 *             "groups": {"procuration_request_list"}
 *         },
 *     },
 *     itemOperations={},
 *     collectionOperations={
 *         "get": {
 *             "path": "/requests",
 *             "normalization_context": {
 *                 "groups": {"procuration_request_list"},
 *                 "enable_tag_translator": true,
 *                 "datetime_format": "Y-m-d",
 *             },
 *         },
 *     },
 * )
 *
 * @ApiFilter(InZoneOfScopeFilter::class)
 * @ApiFilter(OrderFilter::class, properties={"createdAt"})
 */
class Request extends AbstractProcuration
{
    /**
     * @ORM\Column(enumType=RequestStatusEnum::class)
     */
    public RequestStatusEnum $status = RequestStatusEnum::PENDING;

    /**
     * @Assert\Valid
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ProcurationV2\Proxy", inversedBy="requests")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     *
     * @Groups({"procuration_request_list"})
     */
    public ?Proxy $proxy = null;

    public function setProxy(?Proxy $proxy): void
    {
        $proxy->addRequest($this);
    }

    /**
     * @Groups({"procuration_request_list"})
     * @SerializedName("tags")
     */
    public function getAdherentTags(): ?array
    {
        return $this->adherent?->tags;
    }
}