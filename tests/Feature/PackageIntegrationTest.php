<?php

namespace SgFlores\Cruder\Tests\Feature;

use SgFlores\Cruder\Tests\TestCase;
use SgFlores\Cruder\Tests\Services\TestUserService;
use SgFlores\Cruder\CruderServiceProvider;

class PackageIntegrationTest extends TestCase
{
    /** @test */
    public function it_can_load_the_service_provider()
    {
        $this->assertInstanceOf(
            CruderServiceProvider::class,
            $this->app->getProvider(CruderServiceProvider::class)
        );
    }


    /** @test */
    public function it_can_instantiate_base_crud_service()
    {
        $service = new TestUserService();
        
        $this->assertInstanceOf(TestUserService::class, $service);
    }
}
