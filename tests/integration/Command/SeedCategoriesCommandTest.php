<?php

declare(strict_types=1);

namespace App\Tests\integration\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TomoPongrac\WebshopApiBundle\Entity\Category;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SeedCategoriesCommandTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    /** @test */
    public function seedCategoriesCommand(): void
    {
        $kernel = self::bootKernel();
        $application = new Application(self::$kernel);

        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $command = $application->find('webshop-api:seed-categories');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'numberOfCategories' => '10',
        ]);

        // check that the database was seeded with categories
        $categories = $entityManager
            ->getRepository(Category::class)
            ->findAll();

        $this->assertCount(10, $categories);
    }
}
