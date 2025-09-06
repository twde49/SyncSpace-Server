<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class LastFmService
{
    private string $apiUrl = 'http://ws.audioscrobbler.com/2.0/';

    public function __construct(private readonly HttpClientInterface $http, private readonly string $apiKey)
    {
    }

    /**
     * Récupère une liste d'artistes similaires à un artiste donné.
     *
     * @return array ['name' => string, 'match' => float, 'url' => string, ...]
     */
    public function getSimilarArtists(string $artist, int $limit = 10): array
    {
        $response = $this->http->request('GET', $this->apiUrl, [
            'query' => [
                'method' => 'artist.getsimilar',
                'artist' => $artist,
                'api_key' => $this->apiKey,
                'format' => 'json',
                'limit' => $limit,
            ],
        ]);

        $data = $response->toArray();

        return $data['similarartists']['artist'] ?? [];
    }

    /**
     * Récupère des pistes similaires à une piste donnée.
     */
    public function getSimilarTracks(string $artist, string $track, int $limit = 10): array
    {
        $response = $this->http->request('GET', $this->apiUrl, [
            'query' => [
                'method' => 'track.getsimilar',
                'artist' => $artist,
                'track' => $track,
                'api_key' => $this->apiKey,
                'format' => 'json',
                'limit' => $limit,
            ],
        ]);

        $data = $response->toArray();

        return $data['similartracks']['track'] ?? [];
    }
}
