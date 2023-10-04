<?php

namespace App\DataFixtures;

use App\Tests\FixturesProvider\EnrichmentFixturesProvider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $objectManager): void
    {
        EnrichmentFixturesProvider::getEnrichments($objectManager);
    }
}
