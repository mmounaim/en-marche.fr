<?php

namespace App\Controller\EnMarche\Election\VotePlaceResults;

use App\AdherentSpace\AdherentSpaceEnum;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/espace-municipales-2020/assesseurs/bureaux-de-vote", name="app_vote_results_municipal_chief")
 *
 * @IsGranted("ROLE_MUNICIPAL_CHIEF")
 */
class MunicipalChiefVoteResultController extends DefaultVoteResultController
{
    protected function getSpaceType(): string
    {
        return AdherentSpaceEnum::MUNICIPAL_CHIEF;
    }
}
