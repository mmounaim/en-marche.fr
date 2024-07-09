<?php

namespace App\Entity\VotingPlatform;

use App\Entity\Committee;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'voting_platform_election_entity')]
#[ORM\Entity]
class ElectionEntity
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
     * @var Committee|null
     */
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: Committee::class, cascade: ['persist'])]
    private $committee;

    /**
     * @var Election
     */
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[ORM\OneToOne(inversedBy: 'electionEntity', targetEntity: Election::class)]
    private $election;

    public function __construct(?Committee $committee = null)
    {
        $this->committee = $committee;
    }

    public function setElection(Election $election): void
    {
        $this->election = $election;
    }

    public function getCommittee(): ?Committee
    {
        return $this->committee;
    }

    public function setCommittee(Committee $committee): void
    {
        $this->committee = $committee;
    }

    public function getName(): string
    {
        return $this->committee ? $this->committee->getName() : '';
    }
}
