<?php

declare(strict_types=1);

namespace App\Tests\application\Controller;

use App\Factory\UserFactory;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use TomoPongrac\WebshopApiBundle\Factory\CategoryFactory;
use TomoPongrac\WebshopApiBundle\Factory\ContractListProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\PriceListFactory;
use TomoPongrac\WebshopApiBundle\Factory\PriceListProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\ProductFactory;
use TomoPongrac\WebshopApiBundle\Factory\TaxCategoryFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ListProductControllerTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    private const ENDPOINT_URL = '/api/webshop/products';

    /** @test */
    public function userCanSeeListOfProductsWithPagination(): void
    {
        $taxCategory = TaxCategoryFactory::createOne()->object();
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
                'price' => 1005,
            ]
        )->published()->createMany(10);

        $productFromSecondPage = ProductFactory::new(
            [
                'name' => 'Product name',
                'taxCategory' => $taxCategory,
                'categories' => [$category],
                'price' => 1005,
            ]
        )->published()->create();

        $json = $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL).'?page=1&limit=10')
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('current_page')
            ->assertMatches('current_page', 1)
            ->assertMatches('limit', 10)
            ->assertMatches('total_pages', 2)
            ->assertMatches('total_results', 11)
            ->assertMatches('data[0].price.amount', '10.05')
        ;
    }

    /** @test */
    public function userCanSeePriceFromPriceLictProduct(): void
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

        $priceList = PriceListFactory::createOne()->object();

        PriceListProductFactory::createOne([
            'priceList' => $priceList,
            'product' => $product,
            'price' => 501,
        ]);

        $json = $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL).'?page=1&limit=10')
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('current_page')
            ->assertMatches('current_page', 1)
            ->assertMatches('limit', 10)
            ->assertMatches('total_pages', 1)
            ->assertMatches('total_results', 1)
            ->assertMatches('data[0].price.amount', '5.01')
        ;
    }

    /** @test */
    public function userCanSeePriceFromContractListProduct(): void
    {
        $user = UserFactory::createOne()->object();

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

        ContractListProductFactory::createOne([
            'user' => $user,
            'product' => $product,
            'price' => 301,
        ]);

        $priceList = PriceListFactory::createOne()->object();

        PriceListProductFactory::createOne([
            'priceList' => $priceList,
            'product' => $product,
            'price' => 501,
        ]);

        $json = $this->authenticateUserInBrowser($user)
            ->get(sprintf(self::ENDPOINT_URL).'?page=1&limit=10')
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('current_page')
            ->assertMatches('current_page', 1)
            ->assertMatches('limit', 10)
            ->assertMatches('total_pages', 1)
            ->assertMatches('total_results', 1)
            ->assertMatches('data[0].price.amount', '3.01')
        ;
    }

    /** @test */
    public function pageIsRequiredPropertyInQuery(): void
    {
        $json = $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL).'?limit=10')
            ->assertJson()
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function limitIsRequiredPropertyInQuery(): void
    {
        $json = $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL).'?page=1')
            ->assertJson()
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
