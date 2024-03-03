<?php

declare(strict_types=1);

namespace App\Tests\application\Controller;

use App\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use TomoPongrac\WebshopApiBundle\Factory\CategoryFactory;
use TomoPongrac\WebshopApiBundle\Factory\ProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\TaxCategoryFactory;
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
}
