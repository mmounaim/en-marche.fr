<?php

namespace App\VotePlace;

use App\Address\Address;
use App\Entity\VotePlace;

class VotePlaceFactory
{
    public static function createFromArray(array $data): VotePlace
    {
        $votePlace = VotePlace::create(
            $data['name'],
            $data['code'],
            $data['postalCode'] ?? null,
            $data['city'] ?? null,
            $data['address'],
            $data['country'] ?? Address::FRANCE
        );

        return $votePlace;
    }
}
