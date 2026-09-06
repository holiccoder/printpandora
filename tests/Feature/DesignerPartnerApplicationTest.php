<?php

namespace Tests\Feature;

use App\Models\DesignerPartnerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignerPartnerApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_designer_partner_application_is_stored(): void
    {
        $payload = [
            'name_or_company' => 'Studio Example',
            'country_or_region' => 'United Kingdom',
            'email' => 'hello@example.com',
            'website' => 'https://example.com',
            'portfolio_links' => "https://behance.net/example\nhttps://instagram.com/example",
            'design_field' => 'Brand identity and packaging',
            'expected_products_finishes' => 'Cotton business cards with foil and embossing',
        ];

        $this->post(route('designer-partner-program.applications.store'), $payload)
            ->assertRedirect(route('designer-partner-program').'#application-requirements-form')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('designer_partner_applications', [
            ...$payload,
            'status' => 'new',
        ]);
    }

    public function test_designer_partner_application_requires_all_fields(): void
    {
        $this->post(route('designer-partner-program.applications.store'), [])
            ->assertSessionHasErrors([
                'name_or_company',
                'country_or_region',
                'email',
                'website',
                'portfolio_links',
                'design_field',
                'expected_products_finishes',
            ]);

        $this->assertDatabaseCount(DesignerPartnerApplication::class, 0);
    }
}
