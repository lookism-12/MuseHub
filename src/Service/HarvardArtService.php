<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HarvardArtService
{
    private const BASE_URL = 'https://api.harvardartmuseums.org';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey
    ) {
    }

    public function getArtworks(int $page = 1, int $limit = 12): array
    {
        $response = $this->httpClient->request('GET', self::BASE_URL . '/object', [
            'query' => [
                'apikey' => $this->apiKey,
                'page' => $page,
                'size' => $limit,
                'hasimage' => 1,
                'sort' => 'rank',
                'sortorder' => 'desc',
            ]
        ]);

        return $response->toArray();
    }
}
