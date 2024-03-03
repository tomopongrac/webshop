<?php

declare(strict_types=1);

namespace App\Tests\application\Controller;

use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use TomoPongrac\WebshopApiBundle\Factory\CategoryFactory;
use TomoPongrac\WebshopApiBundle\Factory\ProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\TaxCategoryFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ListProductsInCategoryControllerTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    private const ENDPOINT_URL = '/api/webshop/categories/%s/products';

    /** @test */
    public function userCanSeeListOfProductsInCategoryWithPagination(): void
    {
        $taxCategory = TaxCategoryFactory::createOne()->object();
        $category = CategoryFactory::createOne(
            [
                'name' => 'Good Category name',
            ]
        )->object();
        $otherCategory = CategoryFactory::createOne(
            [
                'name' => 'Other category name',
            ]
        )->object();

        $productFromOtherCategory = ProductFactory::new(
            [
                'name' => 'Wrong product',
                'taxCategory' => $taxCategory,
                'categories' => [$otherCategory],
                'price' => 1005,
            ]
        )->published()->create();

        ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category],
                'price' => 1005,
            ]
        )->published()->createMany(10);

        $json = $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL, $category->getId()).'?page=1&limit=10')
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('current_page')
            ->assertMatches('current_page', 1)
            ->assertMatches('limit', 10)
            ->assertMatches('total_pages', 1)
            ->assertMatches('total_results', 10)
            ->assertMatches('data[0].name', 'Product name');
    }

    /** @test */
    public function pageIsRequiredPropertyInQuery(): void
    {
        $category = CategoryFactory::createOne(
            [
                'name' => 'Good Category name',
            ]
        )->object();

        $json = $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL, $category->getId()).'?limit=10')
            ->assertJson()
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function limitIsRequiredPropertyInQuery(): void
    {
        $category = CategoryFactory::createOne(
            [
                'name' => 'Good Category name',
            ]
        )->object();

        $json = $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL, $category->getId()).'?page=1')
            ->assertJson()
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
