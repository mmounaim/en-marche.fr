<?php

namespace App\Normalizer\Indexer;

use App\Entity\Pap\Campaign;

class PapCampaignNormalizer extends AbstractJeMengageTimelineFeedNormalizer
{
    protected function getClassName(): string
    {
        return Campaign::class;
    }

    /** @param Campaign $object */
    protected function getTitle(object $object): string
    {
        return $object->getTitle();
    }

    /** @param Campaign $object */
    protected function getDescription(object $object): ?string
    {
        return 'Nouvelle campagne de porte-à-porte autour de vous.';
    }

    /** @param Campaign $object */
    protected function isLocal(object $object): bool
    {
        return false;
    }

    /** @param Campaign $object */
    protected function getDate(object $object): ?\DateTime
    {
        return $object->getCreatedAt();
    }

    /** @param Campaign $object */
    protected function getAuthor(object $object): ?string
    {
        return null;
    }

    /** @param Campaign $object */
    protected function isNational(object $object): bool
    {
        return true;
    }
}