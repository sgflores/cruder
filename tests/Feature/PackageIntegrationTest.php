<?php

namespace SgFlores\Cruder\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use SgFlores\Cruder\CruderServiceProvider;
use SgFlores\Cruder\Tests\Models\User;
use SgFlores\Cruder\Tests\Services\TestUserService;
use SgFlores\Cruder\Tests\TestCase;

class PackageIntegrationTest extends TestCase
{
    #[Test]
    public function it_can_load_the_service_provider()
    {
        $this->assertInstanceOf(
            CruderServiceProvider::class,
            $this->app->getProvider(CruderServiceProvider::class)
        );
    }

    #[Test]
    public function it_can_instantiate_base_crud_service()
    {
        $service = new TestUserService(new User);

        $this->assertInstanceOf(TestUserService::class, $service);
    }
}
