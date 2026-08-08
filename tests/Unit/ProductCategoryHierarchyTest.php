<?php

namespace Tests\Unit;

use App\Filament\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductCategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_support_multiple_levels_and_hierarchical_labels(): void
    {
        $root = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $child = ProductCategory::create([
            'name' => 'Cotton Cards',
            'slug' => 'cotton-cards',
            'parent_id' => $root->id,
        ]);
        $grandchild = ProductCategory::create([
            'name' => '400 GSM Cotton Cards',
            'slug' => '400-gsm-cotton-cards',
            'parent_id' => $child->id,
        ]);
        $sibling = ProductCategory::create([
            'name' => 'Flyers',
            'slug' => 'flyers',
        ]);

        $this->assertSame($root->id, $child->parent->id);
        $this->assertSame([$child->id, $grandchild->id], $root->descendantIds());
        $this->assertSame('Business Cards / Cotton Cards / 400 GSM Cotton Cards', $grandchild->hierarchyPath());
        $this->assertSame(2, $grandchild->depth());

        $options = ProductCategoryResource::categoryOptions();

        $this->assertSame('Business Cards / Cotton Cards / 400 GSM Cotton Cards', $options[$grandchild->id]);
        $this->assertSame('Flyers', $options[$sibling->id]);
    }

    public function test_editing_a_category_cannot_create_a_cycle(): void
    {
        $root = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $child = ProductCategory::create([
            'name' => 'Cotton Cards',
            'slug' => 'cotton-cards',
            'parent_id' => $root->id,
        ]);

        $this->expectException(ValidationException::class);

        $root->update(['parent_id' => $child->id]);
    }

    public function test_edit_parent_options_exclude_the_current_category_and_its_descendants(): void
    {
        $root = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $child = ProductCategory::create([
            'name' => 'Cotton Cards',
            'slug' => 'cotton-cards',
            'parent_id' => $root->id,
        ]);
        $other = ProductCategory::create([
            'name' => 'Flyers',
            'slug' => 'flyers',
        ]);

        $options = ProductCategoryResource::categoryOptions($root);

        $this->assertArrayNotHasKey($root->id, $options);
        $this->assertArrayNotHasKey($child->id, $options);
        $this->assertArrayHasKey($other->id, $options);
    }
}
