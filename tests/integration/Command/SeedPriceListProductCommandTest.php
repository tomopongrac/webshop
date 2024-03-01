<?php

declare(strict_types=1);

namespace App\Tests\integration\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TomoPongrac\WebshopApiBundle\Entity\PriceList;
use TomoPongrac\WebshopApiBundle\Factory\CategoryFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SeedPriceListProductCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    /** @test */
    public function seedPriceListProductCommand(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);

        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $product = CategoryFactory::createOne()->object();

        $command = $application->find('webshop-api:seed-price-list-product');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'numberOfPriceLists' => '2',
        ]);

        // check that the database was seeded with products
        $priceLists = $entityManager
            ->getRepository(PriceList::class)
            ->findAll();

        $this->assertCount(2, $priceLists);
    }
}
