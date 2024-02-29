<?php

declare(strict_types=1);

namespace App\Tests\application\Authentication;

use App\Factory\UserFactory;
use App\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SecurityControllerTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public const ENDPOINT_URL = '/api/login';

    /** @test */
    public function userCanLogin(): void
    {
        $user = UserFactory::createOne([
            'email' => 'john.doe@example.com',
        ]);

        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => [
                    'email' => $user->getEmail(),
                    'password' => 'password',
                ],
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_OK)
            ->json();

        $json->assertHas('token')
            ->assertHas('user')
            ->assertMatches('user.email', 'john.doe@example.com');
    }

    /** @test */
    public function userCantLoginWithWrongCredentials(): void
    {
        UserFactory::createOne([
            'email' => 'john.doe@example.com',
            'password' => 'password',
        ]);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => [
                    'email' => 'email@example.com',
                    'password' => 'fake-password',
                ],
            ])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function emailIsRequired(): void
    {
        $requestData = [
            'password' => 'password',
        ];

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function passwordIsRequired(): void
    {
        $requestData = [
            'email' => 'john.doe@example.com',
        ];

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
