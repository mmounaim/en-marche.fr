<?php

namespace App\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\Persistence\ObjectManager;

class LoadGeoPolygonsData extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /** @var Connection $conn */
        $conn = $manager->getConnection();
        $conn->exec(file_get_contents(__DIR__.'/../../../dump/polygons.sql'));
    }

    public function getOrder()
    {
        return 2;
    }
}
