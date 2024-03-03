<?php

declare(strict_types=1);

namespace App\Tests\application\Controller;

use App\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use TomoPongrac\WebshopApiBundle\Factory\CategoryFactory;
use TomoPongrac\WebshopApiBundle\Factory\PriceListFactory;
use TomoPongrac\WebshopApiBundle\Factory\PriceListProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\ProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\TaxCategoryFactory;
use Zenstruck\Browser\Json;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class FilterProductsControllerTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    private const ENDPOINT_URL = '/api/webshop/products/filter';

    /** @test */
    public function guestCanFilterProductsByName(): void
    {
        $taxCategory = TaxCategoryFactory::createOne()->object();
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
                'price' => 1005,
            ]
        )->published()->create();

        ProductFactory::new(
            [
                'name' => 'name',
                'taxCategory' => $taxCategory,
                'categories' => [$category],
                'price' => 1005,
            ]
        )->published()->create();

        $request = [
            'filters' => [
                'name' => 'Product name',
                'categories' => [],
            ],
            'order' => [
                'by' => 'name',
                'direction' => 'asc',
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
            ],
        ];

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $request,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('current_page')
            ->assertMatches('current_page', 1)
            ->assertMatches('limit', 10)
            ->assertMatches('total_pages', 1)
            ->assertMatches('total_results', 1)
            ->assertMatches('data[0].id', $product->getId());
    }

    /** @test */
    public function guestCanFilterProductsByCategories(): void
    {
        $taxCategory = TaxCategoryFactory::createOne()->object();
        $category1 = CategoryFactory::createOne()->object();
        $category2 = CategoryFactory::createOne()->object();
        $categoryUnfiltered = CategoryFactory::createOne()->object();

        $product1 = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category1],
                'price' => 1005,
            ]
        )->published()->create();

        $product2 = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category2],
                'price' => 1005,
            ]
        )->published()->create();

        $wrongProduct = ProductFactory::new(
            [
                'name' => 'name',
                'taxCategory' => $taxCategory,
                'categories' => [$categoryUnfiltered],
                'price' => 1005,
            ]
        )->published()->create();

        $request = [
            'filters' => [
                'name' => '',
                'categories' => [
                    $category1->getId(),
                    $category2->getId(),
                ],
            ],
            'order' => [
                'by' => 'name',
                'direction' => 'asc',
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
            ],
        ];

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $request,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('current_page')
            ->assertMatches('current_page', 1)
            ->assertMatches('limit', 10)
            ->assertMatches('total_pages', 1)
            ->assertMatches('total_results', 2)
            ->assertThatEach('data', fn (Json $json) => $json->assertThat('id', fn (Json $json) => $json->isLessThan($wrongProduct->getId())));
    }

    /** @test */
    public function guestCanFilterProductsByPrices(): void
    {
        $taxCategory = TaxCategoryFactory::createOne()->object();
        $category1 = CategoryFactory::createOne()->object();
        $category2 = CategoryFactory::createOne()->object();
        $categoryUnfiltered = CategoryFactory::createOne()->object();

        $product1 = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category1],
                'price' => 1000,
            ]
        )->published()->create();

        $product2 = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category2],
                'price' => 2000,
            ]
        )->published()->create();

        $wrongProduct = ProductFactory::new(
            [
                'name' => 'name',
                'taxCategory' => $taxCategory,
                'categories' => [$categoryUnfiltered],
                'price' => 3000,
            ]
        )->published()->create();

        $request = [
            'filters' => [
                'name' => '',
                'categories' => [],
                'price' => [
                    'min' => 1500,
                    'max' => 2500,
                ],
            ],
            'order' => [
                'by' => 'name',
                'direction' => 'asc',
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
            ],
        ];

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $request,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('current_page')
            ->assertMatches('current_page', 1)
            ->assertMatches('limit', 10)
            ->assertMatches('total_pages', 1)
            ->assertMatches('total_results', 1)
            ->assertMatches('data[0].id', $product2->getId());
    }

    /** @test */
    public function guestCanFilterProductsByPricesFromPriceList(): void
    {
        $taxCategory = TaxCategoryFactory::createOne()->object();
        $category1 = CategoryFactory::createOne()->object();
        $category2 = CategoryFactory::createOne()->object();
        $categoryUnfiltered = CategoryFactory::createOne()->object();

        $product1 = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category1],
                'price' => 1000,
            ]
        )->published()->create();

        $product2 = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category2],
                'price' => 5000,
            ]
        )->published()->create();

        $wrongProduct = ProductFactory::new(
            [
                'name' => 'name',
                'taxCategory' => $taxCategory,
                'categories' => [$categoryUnfiltered],
                'price' => 3000,
            ]
        )->published()->create();

        $priceList = PriceListFactory::createOne()->object();

        PriceListProductFactory::createOne([
            'priceList' => $priceList,
            'product' => $product2,
            'price' => 2000,
        ]);

        $request = [
            'filters' => [
                'name' => '',
                'categories' => [],
                'price' => [
                    'min' => 1500,
                    'max' => 2500,
                ],
            ],
            'order' => [
                'by' => 'name',
                'direction' => 'asc',
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
            ],
        ];

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $request,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('current_page')
            ->assertMatches('current_page', 1)
            ->assertMatches('limit', 10)
            ->assertMatches('total_pages', 1)
            ->assertMatches('total_results', 1)
            ->assertMatches('data[0].id', $product2->getId())
            ->assertMatches('data[0].price.amount', '20.00');
    }

    /** @test */
    public function guestCanOrderProductsByNameAsc(): void
    {
        $taxCategory = TaxCategoryFactory::createOne()->object();
        $category1 = CategoryFactory::createOne()->object();
        $category2 = CategoryFactory::createOne()->object();
        $categoryUnfiltered = CategoryFactory::createOne()->object();

        $product1 = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category1],
                'price' => 1000,
            ]
        )->published()->create();

        $product2 = ProductFactory::new(
            [
                'name' => 'First Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category2],
                'price' => 5000,
            ]
        )->published()->create();

        $request = [
            'filters' => [
                'name' => '',
                'categories' => [],
                'price' => [],
            ],
            'order' => [
                'by' => 'name',
                'direction' => 'asc',
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
            ],
        ];

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $request,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('current_page')
            ->assertMatches('current_page', 1)
            ->assertMatches('limit', 10)
            ->assertMatches('total_pages', 1)
            ->assertMatches('total_results', 2)
            ->assertMatches('data[0].id', $product2->getId());
    }

    /** @test */
    public function guestCanOrderPricesByNameDesc(): void
    {
        $taxCategory = TaxCategoryFactory::createOne()->object();
        $category1 = CategoryFactory::createOne()->object();
        $category2 = CategoryFactory::createOne()->object();
        $categoryUnfiltered = CategoryFactory::createOne()->object();

        $product1 = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category1],
                'price' => 1000,
            ]
        )->published()->create();

        $product2 = ProductFactory::new(
            [
                'name' => 'First Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category2],
                'price' => 5000,
            ]
        )->published()->create();

        $request = [
            'filters' => [
                'name' => '',
                'categories' => [],
                'price' => [],
            ],
            'order' => [
                'by' => 'price',
                'direction' => 'desc',
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
            ],
        ];

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $request,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('current_page')
            ->assertMatches('current_page', 1)
            ->assertMatches('limit', 10)
            ->assertMatches('total_pages', 1)
            ->assertMatches('total_results', 2)
            ->assertMatches('data[0].id', $product2->getId());
    }
}
