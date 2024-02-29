<?php

namespace App\Tests\application\Authentication;

use App\Entity\User;
use App\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RegistrationControllerTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    private const ENDPOINT_URL = '/api/register';

    /** @test */
    public function userCanRegister(): void
    {
        $json = $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $this->getValidRequestData(),
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_CREATED)
            ->json();

        $userRepository = static::getContainer()->get('doctrine.orm.entity_manager')->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $this->getValidRequestData()['email']]);
        $this->assertNotNull($user, 'The user should exist in the database.');
        $this->assertNotEquals($this->getValidRequestData()['password'], $user->getPassword(), 'The password should be hashed.');
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
    public function emailMustBeInValidFormat(): void
    {
        $requestData = [
            'email' => 'invalid-email',
            'password' => 'password',
            'password_confirmation' => 'password',
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
        $requestData = $this->getValidRequestData();
        unset($requestData['password']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function passwordConfirmationIsRequired(): void
    {
        $requestData = $this->getValidRequestData();
        unset($requestData['password_confirmation']);

        $this->baseKernelBrowser()
            ->post(self::ENDPOINT_URL, [
                'json' => $requestData,
            ])
            ->assertJson()
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function passwordConfirmationMustBeSameAsPassword(): void
    {
        $requestData = $this->getValidRequestData();
        $requestData['password_confirmation'] = 'different-password';

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
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    }
}
