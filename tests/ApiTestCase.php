<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

class ApiTestCase extends WebTestCase
{
    use HasBrowser {
        browser as baseKernelBrowser;
    }
}
