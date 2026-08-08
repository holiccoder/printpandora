<?php

namespace Tests\Unit;

use App\Filament\Resources\ProductResource;
use Filament\Schemas\Schema;
use Tests\TestCase;

class ProductConfigurationFormTest extends TestCase
{
    public function test_configuration_schema_can_be_built(): void
    {
        $schema = ProductResource::form(Schema::make());

        $this->assertNotEmpty($schema->getComponents());
    }
}
