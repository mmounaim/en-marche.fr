<?php

namespace App\Controller\EnMarche\Election\VotePlaceResults;

use App\AdherentSpace\AdherentSpaceEnum;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/espace-responsable-communal/assesseurs/bureaux-de-vote", name="app_vote_results_municipal_manager")
 *
 * @IsGranted("ROLE_MUNICIPAL_MANAGER")
 */
class MunicipalManagerVoteResultController extends DefaultVoteResultController
{
    protected function getSpaceType(): string
    {
        return AdherentSpaceEnum::MUNICIPAL_MANAGER;
    }
}
