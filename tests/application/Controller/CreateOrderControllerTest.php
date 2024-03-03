<?php

namespace App\Tests\application\Controller;

use App\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use TomoPongrac\WebshopApiBundle\Entity\Order;
use TomoPongrac\WebshopApiBundle\Entity\Profile;
use TomoPongrac\WebshopApiBundle\Entity\ShippingAddress;
use TomoPongrac\WebshopApiBundle\Factory\CategoryFactory;
use TomoPongrac\WebshopApiBundle\Factory\ProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\TaxCategoryFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CreateOrderControllerTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    private const ENDPOINT_URL = '/api/webshop/orders';

    /** @test */
    public function guestCanCreateOrder(): void
    {
        $taxCategory = TaxCategoryFactory::createOne(
            [
                'name' => 'Tax category name',
                'rate' => 0.22,
            ]
        )->object();
        $category = CategoryFactory::createOne(
            [
                'name' => 'Category name',
            ]
        )->object();

        ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category],
                'price' => 1000,
            ]
        )->published()->create()->disableAutoRefresh();

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $this->getValidRequestData(),
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_CREATED)
            ->json();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $profileRepository = $entityManager->getRepository(Profile::class);
        $profile = $profileRepository->findOneBy(['firstName' => $this->getValidRequestData()['first_name']]);
        $this->assertNotNull($profile, 'The profile should exist in the database.');

        $shippingAddressRepository = $entityManager->getRepository(ShippingAddress::class);
        $shippingAddress = $shippingAddressRepository->findOneBy(['address' => $this->getValidRequestData()['address']]);
        $this->assertNotNull($shippingAddress, 'The profile should exist in the database.');

        $profileRepository = $entityManager->getRepository(Order::class);
        $order = $profileRepository->findOneBy(['profile' => $profile]);
        $this->assertCount(1, $order->getProducts());
    }

    private function getValidRequestData(): array
    {
        return [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '123456789',
            'address' => '123 Main St',
            'city' => 'New York',
            'zip' => '10001',
            'country' => 'US',
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => 2,
                ],
                [
                    'product_id' => 2,
                    'quantity' => 1,
                ],
            ],
        ];
    }
}
