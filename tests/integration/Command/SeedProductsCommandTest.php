<?php

declare(strict_types=1);

namespace App\Tests\integration\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TomoPongrac\WebshopApiBundle\Entity\Product;
use TomoPongrac\WebshopApiBundle\Factory\CategoryFactory;
use TomoPongrac\WebshopApiBundle\Factory\TaxCategoryFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SeedProductsCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    /** @test */
    public function seedProductsCommand(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);

        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        CategoryFactory::createMany(10);
        TaxCategoryFactory::createMany(5);

        $command = $application->find('webshop-api:seed-products');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'numberOfProducts' => '20',
        ]);

        // check that the database was seeded with products
        $products = $entityManager
            ->getRepository(Product::class)
            ->findAll();

        $this->assertCount(20, $products);
    }
}
