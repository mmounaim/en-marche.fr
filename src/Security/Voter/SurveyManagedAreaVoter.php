<?php

namespace App\Security\Voter;

use App\Entity\Adherent;
use App\Entity\Jecoute\LocalSurvey;
use App\Entity\MyTeam\DelegatedAccess;
use App\Repository\Geo\ZoneRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SurveyManagedAreaVoter extends AbstractAdherentVoter
{
    public const PERMISSION = 'IS_SURVEY_MANAGER_OF';

    /** @var SessionInterface */
    private $session;
    /** @var ZoneRepository */
    private $zoneRepository;

    public function __construct(SessionInterface $session, ZoneRepository $zoneRepository)
    {
        $this->session = $session;
        $this->zoneRepository = $zoneRepository;
    }

    protected function doVoteOnAttribute(string $attribute, Adherent $adherent, $subject): bool
    {
        if ($delegatedAccess = $adherent->getReceivedDelegatedAccessByUuid($this->session->get(DelegatedAccess::ATTRIBUTE_KEY))) {
            $adherent = $delegatedAccess->getDelegator();
        }

        /** @var LocalSurvey $subject */
        $surveyZone = $subject->getZone();

        if ($adherent->isReferent()) {
            return $this->zoneRepository->isInJecouteZonesWithParents($adherent->getManagedArea()->getTags()->toArray(), $surveyZone);
        }

        if ($adherent->isJecouteManager()) {
            $managedZone = $adherent->getJecouteManagedArea()->getZone();
        }

        if ($adherent->isCandidate()) {
            $managedZone = $adherent->getCandidateManagedArea()->getZone();
        }

        if (isset($managedZone)) {
            return $surveyZone === $managedZone
                || ($managedZone->hasChild($surveyZone) || $managedZone->hasParent($surveyZone));
        }

        return false;
    }

    protected function supports($attribute, $subject): bool
    {
        return self::PERMISSION === $attribute && $subject instanceof LocalSurvey;
    }
}
