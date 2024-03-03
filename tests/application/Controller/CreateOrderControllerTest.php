<?php

namespace App\Tests\application\Controller;

use App\Factory\UserFactory;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use TomoPongrac\WebshopApiBundle\Entity\Order;
use TomoPongrac\WebshopApiBundle\Entity\OrderProduct;
use TomoPongrac\WebshopApiBundle\Entity\Profile;
use TomoPongrac\WebshopApiBundle\Entity\ShippingAddress;
use TomoPongrac\WebshopApiBundle\Factory\CategoryFactory;
use TomoPongrac\WebshopApiBundle\Factory\ContractListProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\PriceListFactory;
use TomoPongrac\WebshopApiBundle\Factory\PriceListProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\ProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\TaxCategoryFactory;
use TomoPongrac\WebshopApiBundle\Factory\TotalDiscountFactory;
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
        $this->assertEquals($this->getValidRequestData()['email'], $profile->getEmail());

        $shippingAddressRepository = $entityManager->getRepository(ShippingAddress::class);
        $shippingAddress = $shippingAddressRepository->findOneBy(['address' => $this->getValidRequestData()['address']]);
        $this->assertNotNull($shippingAddress, 'The profile should exist in the database.');

        $profileRepository = $entityManager->getRepository(Order::class);
        $order = $profileRepository->findOneBy(['profile' => $profile]);
        $this->assertCount(1, $order->getProducts());
    }

    /** @test */
    public function guestCanCreateOrderWithCorrectTotalAmount(): void
    {
        $taxCategory1 = TaxCategoryFactory::createOne(
            [
                'name' => 'Tax category name',
                'rate' => 0.20,
            ]
        )->object();
        $taxCategory2 = TaxCategoryFactory::createOne(
            [
                'name' => 'Tax category name',
                'rate' => 0.10,
            ]
        )->object();
        $category = CategoryFactory::createOne(
            [
                'name' => 'Category name',
            ]
        )->object();

        $product1 = ProductFactory::new(
            [
                'taxCategory' => $taxCategory1,
                'categories' => [$category],
                'price' => 1000,
            ]
        )->published()->create()->object();

        $product2 = ProductFactory::new(
            [
                'taxCategory' => $taxCategory2,
                'categories' => [$category],
                'price' => 5000,
            ]
        )->published()->create()->object();

        $request = $this->getValidRequestData();
        $request['products'] = [
            [
                'product_id' => $product1->getId(),
                'quantity' => 2,
            ],
            [
                'product_id' => $product2->getId(),
                'quantity' => 1,
            ],
        ];

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $request,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_CREATED)
            ->json();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $profileRepository = $entityManager->getRepository(Profile::class);
        $profile = $profileRepository->findOneBy(['firstName' => $request['first_name']]);

        $orderRepository = $entityManager->getRepository(Order::class);
        $order = $orderRepository->findOneBy(['profile' => $profile]);
        $this->assertCount(2, $order->getProducts());
        $this->assertEquals(7900, $order->getTotalPrice());
    }

    /** @test */
    public function guestCanCreateOrderWithCorrectTotalAmountWithTotalDiscountApplied(): void
    {
        $taxCategory1 = TaxCategoryFactory::createOne(
            [
                'name' => 'Tax category name',
                'rate' => 0.20,
            ]
        )->object();
        $taxCategory2 = TaxCategoryFactory::createOne(
            [
                'name' => 'Tax category name',
                'rate' => 0.10,
            ]
        )->object();
        $category = CategoryFactory::createOne(
            [
                'name' => 'Category name',
            ]
        )->object();

        $product1 = ProductFactory::new(
            [
                'taxCategory' => $taxCategory1,
                'categories' => [$category],
                'price' => 1000,
            ]
        )->published()->create()->object();

        $product2 = ProductFactory::new(
            [
                'taxCategory' => $taxCategory2,
                'categories' => [$category],
                'price' => 5000,
            ]
        )->published()->create()->object();

        TotalDiscountFactory::createOne(
            [
                'totalPrice' => 1000,
                'discountRate' => 0.1,
            ]
        );

        TotalDiscountFactory::createOne(
            [
                'totalPrice' => 100000,
                'discountRate' => 0.5,
            ]
        );

        $request = $this->getValidRequestData();
        $request['products'] = [
            [
                'product_id' => $product1->getId(),
                'quantity' => 1,
            ],
            [
                'product_id' => $product2->getId(),
                'quantity' => 1,
            ],
        ];

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $request,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_CREATED)
            ->json();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $profileRepository = $entityManager->getRepository(Profile::class);
        $profile = $profileRepository->findOneBy(['firstName' => $request['first_name']]);

        $orderRepository = $entityManager->getRepository(Order::class);
        $order = $orderRepository->findOneBy(['profile' => $profile]);
        $this->assertCount(2, $order->getProducts());
        $this->assertEquals(6030, $order->getTotalPrice());
    }

    /** @test */
    public function guestCanCreateOrderWithPriceFromPriceList(): void
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

        $product = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category],
                'price' => 1000,
            ]
        )->published()->create()->object();

        $priceList = PriceListFactory::createOne()->object();

        PriceListProductFactory::createOne([
            'priceList' => $priceList,
            'product' => $product,
            'price' => 500,
        ]);

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $this->getValidRequestData(),
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_CREATED)
            ->json();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $orderProductRepository = $entityManager->getRepository(OrderProduct::class);
        $orderProduct = $orderProductRepository->findOneBy(['product' => $product]);
        $this->assertEquals(500, $orderProduct->getPrice());
    }

    /** @test */
    public function userCanCreateOrderWithPriceFromContractList(): void
    {
        $user = UserFactory::createOne()->object();

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

        $product = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category],
                'price' => 1000,
            ]
        )->published()->create()->object();

        ContractListProductFactory::createOne([
            'user' => $user,
            'product' => $product,
            'price' => 300,
        ]);

        $priceList = PriceListFactory::createOne()->object();

        PriceListProductFactory::createOne([
            'priceList' => $priceList,
            'product' => $product,
            'price' => 500,
        ]);

        $json = $this->authenticateUserInBrowser($user)
            ->post(self::ENDPOINT_URL, [
                'json' => $this->getValidRequestData(),
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_CREATED)
            ->json();

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $orderProductRepository = $entityManager->getRepository(OrderProduct::class);
        $orderProduct = $orderProductRepository->findOneBy(['product' => $product]);
        $this->assertEquals(300, $orderProduct->getPrice());
    }

    /** @test */
    public function emailIsRequired(): void
    {
        $requestData = $this->getValidRequestData();
        unset($requestData['email']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function emailNeedsToBeValid(): void
    {
        $requestData = $this->getValidRequestData();
        $requestData['email'] = 'invalid-email';

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function firstNameIsRequired(): void
    {
        $requestData = $this->getValidRequestData();
        unset($requestData['first_name']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function lastNameIsRequired(): void
    {
        $requestData = $this->getValidRequestData();
        unset($requestData['last_name']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function phoneIsRequired(): void
    {
        $requestData = $this->getValidRequestData();
        unset($requestData['phone']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function addressIsRequired(): void
    {
        $requestData = $this->getValidRequestData();
        unset($requestData['address']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function cityIsRequired(): void
    {
        $requestData = $this->getValidRequestData();
        unset($requestData['city']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function zipIsRequired(): void
    {
        $requestData = $this->getValidRequestData();
        unset($requestData['zip']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function countryIsRequired(): void
    {
        $requestData = $this->getValidRequestData();
        unset($requestData['country']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function productsIsRequired(): void
    {
        $requestData = $this->getValidRequestData();
        unset($requestData['products']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function productsCantBeEmptyArray(): void
    {
        $requestData = $this->getValidRequestData();
        $requestData['products'] = [];

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function productsMustExist(): void
    {
        $requestData = $this->getValidRequestData();
        $requestData['products'] = [
            [
                'product_id' => 1,
                'quantity' => 1,
            ],
        ];

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function productsMustBePublished(): void
    {
        // Be sure that product exists
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

        $product1 = ProductFactory::new(
            [
                'taxCategory' => $taxCategory,
                'categories' => [$category],
                'price' => 1000,
            ]
        )->unpublished()->create()->object();

        $requestData = $this->getValidRequestData();
        $requestData['products'] = [
            [
                'product_id' => 1,
                'quantity' => 1,
            ],
        ];

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function productsQuantityMustBeGreaterThanZero(): void
    {
        // Be sure that product exists
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

        $product1 = ProductFactory::new(
            [
                'taxCategory' => $taxCategory,
                'categories' => [$category],
                'price' => 1000,
            ]
        )->published()->create()->object();

        $requestData = $this->getValidRequestData();
        $requestData['products'] = [
            [
                'product_id' => 1,
                'quantity' => 0,
            ],
        ];

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
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
                    'quantity' => 1,
                ],
            ],
        ];
    }
}
