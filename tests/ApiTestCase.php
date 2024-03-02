<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

class ApiTestCase extends WebTestCase
{
    use HasBrowser {
        browser as baseKernelBrowser;
    }

    protected function authenticateUserInBrowser(User $user): KernelBrowser
    {
        $tokenManager = static::getContainer()->get(JWTTokenManagerInterface::class);
        $token = $tokenManager->create($user);

        return $this->baseKernelBrowser()
            ->setDefaultHttpOptions(['headers' => ['Authorization' => 'Bearer '.$tokenManager->create($user)]]);
    }
}
