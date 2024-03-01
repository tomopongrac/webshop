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
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class GetProductControllerTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    private const ENDPOINT_URL = '/api/webshop/products/%d';

    /** @test */
    public function userCanSeeProduct(): void
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

        $json = $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL, $product->getId()))
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('data')
            ->assertMatches('data.id', $product->getId())
            ->assertMatches('data.name', 'Product name')
            ->assertMatches('data.price.amount', '10.05')
            ->assertHas('data.categories')
            ->assertMatches('data.categories[0].name', 'Category name');
    }

    /** @test */
    public function userCanSeePriceFromPriceList(): void
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
        )->published()->create()->disableAutoRefresh();

        $priceList = PriceListFactory::createOne()->object();

        PriceListProductFactory::createOne([
            'priceList' => $priceList,
            'product' => $product,
            'price' => 501,
        ]);

        $json = $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL, $product->getId()))
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('data')
            ->assertMatches('data.id', $product->getId())
            ->assertMatches('data.name', 'Product name')
            ->assertMatches('data.price.amount', '5.01')
            ->assertHas('data.categories')
            ->assertMatches('data.categories[0].name', 'Category name');
    }

    /** @test */
    public function throwNotFoundIfCategoryDontExist(): void
    {
        $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL, 999))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /** @test */
    public function throwNotFoundIfProductDontExist(): void
    {
        $this->baseKernelBrowser()
            ->get(sprintf(self::ENDPOINT_URL, 999))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }
}
