<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class BibleApiClient
{
    /**
     * @return array<string, mixed>
     */
    public function books(string $translation): array
    {
        return $this->request()->get('books', ['translation' => $translation])->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(string $translation): array
    {
        return $this->request()->get('schema', ['translation' => $translation])->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function verses(string $reference, string $translation): array
    {
        return $this->request()->get('verses/'.ltrim($reference, '/'), ['translation' => $translation])->throw()->json();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) config('services.bible_api.url'))
            ->acceptJson()
            ->connectTimeout(2)
            ->timeout(5);
    }
}
