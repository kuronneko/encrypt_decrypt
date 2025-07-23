<?php

namespace Kuronneko\LaravelDevExtremeEncrypted\Tests\Feature;

use Kuronneko\LaravelDevExtremeEncrypted\Tests\TestCase;
use Kuronneko\LaravelDevExtremeEncrypted\Components\DevExtremeConfig;
use Kuronneko\LaravelDevExtremeEncrypted\Traits\HandlesEncryptedFields;
use Illuminate\Database\Eloquent\Model;

class DevExtremeConfigTest extends TestCase
{
    /** @test */
    public function it_can_create_a_basic_configuration()
    {
        $config = DevExtremeConfig::make()
            ->searchableFields(['name', 'email'])
            ->sortBy('id', 'desc')
            ->build();

        $this->assertIsArray($config);
        $this->assertEquals(['name', 'email'], $config['searchableFields']);
        $this->assertEquals(['id' => 'desc'], $config['defaultSort']);
    }

    /** @test */
    public function it_filters_out_protected_fields()
    {
        config(['devextreme-encrypted.security.protected_fields' => ['password']]);

        $config = DevExtremeConfig::make()
            ->searchableFields(['name', 'email', 'password'])
            ->build();

        $this->assertEquals(['name', 'email'], $config['searchableFields']);
    }

    /** @test */
    public function it_can_add_multiple_related_models()
    {
        $mockModel1 = new class extends Model {};
        $mockModel2 = new class extends Model {};

        $config = DevExtremeConfig::make()
            ->relatedModel('locations', $mockModel1)
            ->relatedModel('orders', $mockModel2)
            ->build();

        $this->assertCount(2, $config['relatedModels']);
        $this->assertArrayHasKey('locations', $config['relatedModels']);
        $this->assertArrayHasKey('orders', $config['relatedModels']);
    }

    /** @test */
    public function it_can_set_custom_cache_duration()
    {
        $config = DevExtremeConfig::make()
            ->cacheDuration(15)
            ->build();

        $this->assertEquals(15, $config['cacheDuration']);
    }
}
