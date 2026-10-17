<?php

namespace Tests\Feature;

use App\Models\Audience;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicTaxonomyApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_categories_as_schema_org_defined_terms(): void
    {
        $category = Category::factory()->create([
            'name' => 'Bibel',
            'slug' => 'bibel',
            'synonyms' => ['Schrift'],
        ]);

        $response = $this->getJson(route('v1.categories.index'));

        $response->assertOk()
            ->assertHeaderContains('Content-Type', 'application/ld+json')
            ->assertJsonPath('@type', 'ItemList')
            ->assertJsonPath('itemListElement.0.item.@type', 'DefinedTerm')
            ->assertJsonPath('itemListElement.0.item.termCode', 'bibel');

        $response = $this->getJson(route('v1.categories.show', $category));

        $response->assertOk()
            ->assertJsonPath('@id', route('v1.categories.show', $category))
            ->assertJsonPath('alternateName.0', 'Schrift');
    }

    public function test_returns_tags_as_schema_org_defined_terms(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Schöpfung',
            'slug' => 'schoepfung',
        ]);

        $response = $this->getJson(route('v1.tags.index'));

        $response->assertOk()
            ->assertHeaderContains('Content-Type', 'application/ld+json')
            ->assertJsonPath('@type', 'ItemList')
            ->assertJsonPath('itemListElement.0.item.identifier', 'tag:'.$tag->id);

        $response = $this->getJson(route('v1.tags.show', $tag));

        $response->assertOk()
            ->assertJsonPath('@type', 'DefinedTerm')
            ->assertJsonPath('termCode', 'schoepfung');
    }

    public function test_returns_audiences_as_schema_org_audiences(): void
    {
        $audience = Audience::factory()->create([
            'name' => 'Kinder',
            'slug' => 'kinder',
        ]);

        $response = $this->getJson(route('v1.audiences.index'));

        $response->assertOk()
            ->assertHeaderContains('Content-Type', 'application/ld+json')
            ->assertJsonPath('@type', 'ItemList')
            ->assertJsonPath('itemListElement.0.item.@type', 'Audience');

        $response = $this->getJson(route('v1.audiences.show', $audience));

        $response->assertOk()
            ->assertJsonPath('@id', route('v1.audiences.show', $audience))
            ->assertJsonPath('audienceType', 'Kinder');
    }
}
