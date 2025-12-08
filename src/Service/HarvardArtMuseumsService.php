<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HarvardArtMuseumsService
{
    private $client;
    private $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function getArtworks(int $page = 1, int $size = 10): array
    {
        if ($this->apiKey === 'your_api_key_here' || empty($this->apiKey)) {
            return ['records' => []];
        }

        $response = $this->client->request('GET', 'https://api.harvardartmuseums.org/object', [
            'query' => [
                'apikey' => $this->apiKey,
                'page' => $page,
                'size' => $size,
                'hasimage' => 1,
                'sort' => 'rank',
                'sortorder' => 'desc',
            ],
        ]);

        return $response->toArray();
    }
}
