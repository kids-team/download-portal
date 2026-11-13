<?php

namespace Tests\Feature;

use App\Services\BibleApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BibleApiClientTest extends TestCase
{
    public function test_fetches_verses_from_the_configured_bible_api(): void
    {
        config()->set('services.bible_api.url', 'http://bible-api:8080');
        Http::fake([
            'http://bible-api:8080/verses/john/3:16?translation=slt' => Http::response([
                'translation' => 'slt',
                'verses' => [['text' => 'Denn so sehr hat Gott die Welt geliebt.']],
            ]),
        ]);

        $response = app(BibleApiClient::class)->verses('john/3:16', 'slt');

        $this->assertSame('slt', $response['translation']);
        $this->assertSame('Denn so sehr hat Gott die Welt geliebt.', $response['verses'][0]['text']);
    }
}
