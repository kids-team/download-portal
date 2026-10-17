<?php

namespace Tests\Feature;

use App\Models\Audience;
use App\Models\Category;
use App\Models\Document;
use App\Models\MediaAsset;
use App\Models\Tag;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use LogicException;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_orders_children_by_position(): void
    {
        $parent = Document::factory()->create();
        $laterChild = Document::factory()->for($parent, 'parent')->create(['position' => 20]);
        $earlierChild = Document::factory()->for($parent, 'parent')->create(['position' => 10]);

        $children = $parent->children()->get();

        $this->assertSame([$earlierChild->id, $laterChild->id], $children->pluck('id')->all());
    }

    public function test_casts_lexical_editor_state_to_an_array(): void
    {
        $editorState = [
            'root' => [
                'children' => [],
                'direction' => null,
                'format' => '',
                'indent' => 0,
                'type' => 'root',
                'version' => 1,
            ],
        ];
        $document = Document::factory()->create(['content' => $editorState]);

        $document->refresh();

        $this->assertSame($editorState, $document->content);
    }

    public function test_rejects_duplicate_slugs_at_the_root_level(): void
    {
        Document::factory()->create(['slug' => 'material']);

        $this->expectException(QueryException::class);

        Document::factory()->create(['slug' => 'material']);
    }

    public function test_rejects_duplicate_sibling_slugs_but_allows_them_below_another_parent(): void
    {
        $firstParent = Document::factory()->create();
        $secondParent = Document::factory()->create();
        Document::factory()->for($firstParent, 'parent')->create(['slug' => 'arbeitsblatt']);
        $allowedDocument = Document::factory()->for($secondParent, 'parent')->create(['slug' => 'arbeitsblatt']);

        $this->assertModelExists($allowedDocument);

        $this->expectException(QueryException::class);

        Document::factory()->for($firstParent, 'parent')->create(['slug' => 'arbeitsblatt']);
    }

    public function test_moves_a_document_to_a_new_parent(): void
    {
        $firstParent = Document::factory()->create();
        $secondParent = Document::factory()->create();
        $document = Document::factory()->for($firstParent, 'parent')->create();

        $document->moveTo($secondParent);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'parent_id' => $secondParent->id,
        ]);
    }

    public function test_rejects_moving_a_document_below_its_descendant(): void
    {
        $root = Document::factory()->create();
        $child = Document::factory()->for($root, 'parent')->create();
        $grandchild = Document::factory()->for($child, 'parent')->create();

        try {
            $root->moveTo($grandchild);
            $this->fail('Expected moving a document below its descendant to fail.');
        } catch (LogicException) {
            $this->assertDatabaseHas('documents', [
                'id' => $root->id,
                'parent_id' => null,
            ]);
        }
    }

    public function test_rejects_direct_parent_updates_that_create_a_cycle(): void
    {
        $root = Document::factory()->create();
        $child = Document::factory()->for($root, 'parent')->create();
        $grandchild = Document::factory()->for($child, 'parent')->create();

        $this->expectException(QueryException::class);

        Document::query()->whereKey($root)->update(['parent_id' => $grandchild->id]);
    }

    public function test_relates_taxonomy_tags_and_cover_media(): void
    {
        $category = Category::factory()->create(['synonyms' => ['material']]);
        $audience = Audience::factory()->create();
        $coverMedia = MediaAsset::factory()->create();
        $tag = Tag::factory()->create();
        $document = Document::factory()->create([
            'category_id' => $category->id,
            'audience_id' => $audience->id,
            'cover_media_id' => $coverMedia->id,
        ]);
        $document->tags()->attach($tag);

        $document->load('category', 'audience', 'coverMedia', 'tags');

        $this->assertSame($category->id, $document->category->id);
        $this->assertSame(['material'], $document->category->synonyms);
        $this->assertSame($audience->id, $document->audience->id);
        $this->assertSame($coverMedia->id, $document->coverMedia->id);
        $this->assertSame([$tag->id], $document->tags->pluck('id')->all());
    }

    public function test_clears_nullable_references_when_taxonomy_or_cover_media_is_deleted(): void
    {
        $category = Category::factory()->create();
        $audience = Audience::factory()->create();
        $coverMedia = MediaAsset::factory()->create();
        $document = Document::factory()->create([
            'category_id' => $category->id,
            'audience_id' => $audience->id,
            'cover_media_id' => $coverMedia->id,
        ]);

        $category->delete();
        $audience->delete();
        $coverMedia->delete();

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'category_id' => null,
            'audience_id' => null,
            'cover_media_id' => null,
        ]);
    }
}
