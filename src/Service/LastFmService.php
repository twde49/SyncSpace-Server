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
     * Retrieves a list of artists similar to a given artist.
     *
     * @param string $artist The name of the artist
     * @param int    $limit  The maximum number of similar artists to retrieve
     *
     * @return array List of similar artists ['name' => string, 'match' => float, 'url' => string, ...]
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
     * Retrieves tracks similar to a given track.
     *
     * @param string $artist The name of the artist
     * @param string $track  The name of the track
     * @param int    $limit  The maximum number of similar tracks to retrieve
     *
     * @return array List of similar tracks
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
