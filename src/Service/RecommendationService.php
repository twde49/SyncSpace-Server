<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Track;
use App\Repository\TrackRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecommendationService
{
    /**
     * Constructor for RecommendationService.
     *
     * @param HttpClientInterface $httpClient      The HTTP client for API requests
     * @param TrackRepository     $trackRepository The track repository
     * @param YoutubeService      $youtubeService  The YouTube service
     * @param string              $apiKey          The API key for Last.fm
     */
    public function __construct(private readonly HttpClientInterface $httpClient, private readonly TrackRepository $trackRepository, private readonly YoutubeService $youtubeService, private readonly string $apiKey)
    {
    }

    /**
     * Gets the next music recommendation based on artist, track, and offset.
     *
     * @param string      $artist The artist name
     * @param string|null $track  The track name (optional)
     * @param int         $offset The offset for recommendations
     *
     * @return array|null The recommended track data or null if not found
     */
    public function getNextRecommendation(string $artist, ?string $track = null, int $offset = 0): ?array
    {
        if ($track) {
            $recommendations = $this->getRecommendations($artist, $track, 10);
            $recommendations = array_slice($recommendations, $offset);
            foreach ($recommendations as $rec) {
                $youtubeId = $this->youtubeService->searchBestVideoId($rec->getArtist(), $rec->getTitle());
                if ($youtubeId) {
                    return [
                        'title' => $rec->getTitle(),
                        'artist' => $rec->getArtist(),
                        'youtubeId' => $youtubeId,
                        'coverUrl' => $rec->getCoverUrl(),
                    ];
                }
            }
        }

        // 2. Sinon, cherche les top tracks des artistes similaires
        $recommendations = $this->getRecommendations($artist, null, 10);
        $recommendations = array_slice($recommendations, $offset);
        foreach ($recommendations as $rec) {
            $youtubeId = $this->youtubeService->searchBestVideoId($rec->getArtist(), $rec->getTitle());
            if ($youtubeId) {
                return [
                    'title' => $rec->getTitle(),
                    'artist' => $rec->getArtist(),
                    'youtubeId' => $youtubeId,
                    'coverUrl' => $rec->getCoverUrl(),
                ];
            }
        }

        return null;
    }

    /**
     * Hydrates the first recommendation with a YouTube ID and returns it as an array.
     *
     * @param array $recommendations Array of recommended tracks
     *
     * @return array|null The hydrated recommendation or null if not found
     */
    private function hydrateYoutubeIdForFirst(array $recommendations): ?array
    {
        $firstTrack = $recommendations[0];

        // Recherche Youtube par artiste + titre
        $youtubeId = $this->youtubeService->searchBestVideoId($firstTrack->getArtist(), $firstTrack->getTitle());

        if (!$youtubeId) {
            return null;
        }

        return [
            'title' => $firstTrack->getTitle(),
            'artist' => $firstTrack->getArtist(),
            'youtubeId' => $youtubeId,
            'coverUrl' => $firstTrack->getCoverUrl(),
        ];
    }

    /**
     * Gets a list of recommendations (Track[]).
     *
     * @param string      $artist The artist name
     * @param string|null $track  The track name (optional)
     * @param int         $limit  The number of recommendations to fetch
     *
     * @return array Array of recommended tracks
     */
    public function getRecommendations(string $artist, ?string $track = null, int $limit = 10): array
    {
        if ($track) {
            $recommendations = $this->fetchSimilarTracks($artist, $track, $limit);
            if (!empty($recommendations)) {
                return $recommendations;
            }
        }

        return $this->fetchSimilarArtistsTopTracks($artist, $limit);
    }

    /**
     * Fetches similar tracks from Last.fm.
     *
     * @param string $artist The artist name
     * @param string $track  The track name
     * @param int    $limit  The number of tracks to fetch
     *
     * @return array Array of similar tracks
     */
    private function fetchSimilarTracks(string $artist, string $track, int $limit): array
    {
        $url = 'http://ws.audioscrobbler.com/2.0/';
        $params = [
            'method' => 'track.getsimilar',
            'artist' => $artist,
            'track' => $track,
            'api_key' => $this->lastFmApiKey,
            'format' => 'json',
            'limit' => $limit,
        ];

        $response = $this->httpClient->request('GET', $url, ['query' => $params]);
        $data = $response->toArray();

        $similarTracks = $data['similartracks']['track'] ?? [];

        return $this->hydrateTracksFromLastFm($similarTracks);
    }

    /**
     * Fetches top tracks for similar artists from Last.fm.
     *
     * @param string $artist The artist name
     * @param int    $limit  The number of artists to fetch
     *
     * @return array Array of top tracks for similar artists
     */
    private function fetchSimilarArtistsTopTracks(string $artist, int $limit): array
    {
        $url = 'http://ws.audioscrobbler.com/2.0/';
        $params = [
            'method' => 'artist.getsimilar',
            'artist' => $artist,
            'api_key' => $this->lastFmApiKey,
            'format' => 'json',
            'limit' => $limit,
        ];

        $response = $this->httpClient->request('GET', $url, ['query' => $params]);
        $data = $response->toArray();

        $similarArtists = $data['similarartists']['artist'] ?? [];

        $tracks = [];
        foreach ($similarArtists as $similarArtist) {
            $topTrack = $this->getTopTrackByArtist($similarArtist['name']);
            if ($topTrack) {
                $tracks[] = $topTrack;
            }
        }

        return $tracks;
    }

    /**
     * Gets the top track for a given artist from Last.fm.
     *
     * @param string $artistName The artist name
     *
     * @return Track|null The top track entity or null if not found
     */
    private function getTopTrackByArtist(string $artistName): ?Track
    {
        $url = 'http://ws.audioscrobbler.com/2.0/';
        $params = [
            'method' => 'artist.gettoptracks',
            'artist' => $artistName,
            'api_key' => $this->lastFmApiKey,
            'format' => 'json',
            'limit' => 1,
        ];

        $response = $this->httpClient->request('GET', $url, ['query' => $params]);
        $data = $response->toArray();

        $topTracks = $data['toptracks']['track'] ?? [];
        if (empty($topTracks)) {
            return null;
        }

        $topTrackData = is_array($topTracks) && isset($topTracks[0]) ? $topTracks[0] : $topTracks;

        // Créer une entité Track (sans youtubeId, tu pourras chercher YouTube plus tard)
        $track = new Track();
        $track->setTitle($topTrackData['name']);
        $track->setArtist($artistName);
        $track->setYoutubeId('');

        return $track;
    }

    /**
     * Hydrates Track entities from Last.fm track data.
     *
     * @param array $tracksData Array of track data from Last.fm
     *
     * @return array Array of hydrated Track entities
     */
    private function hydrateTracksFromLastFm(array $tracksData): array
    {
        $tracks = [];
        foreach ($tracksData as $item) {
            $track = new Track();
            $track->setTitle($item['name']);
            $track->setArtist($item['artist']['name']);
            $track->setYoutubeId('');

            $tracks[] = $track;
        }

        return $tracks;
    }

    /**
     * Gets top tracks for a random tag from Last.fm.
     *
     * @param array $tags Array of tags to choose from (optional)
     *
     * @return array Array of top tracks for the random tag
     */
    public function getRandomTagTopTracks(array $tags = []): array
    {
        if (empty($tags)) {
            $tags = ['rock', 'pop', 'electronic', 'jazz', 'hip-hop', 'indie', 'ambient', 'funk', 'k-pop', 'classical'];
        }

        $randomTag = $tags[array_rand($tags)];

        $url = 'http://ws.audioscrobbler.com/2.0/';
        $params = [
            'method' => 'tag.gettoptracks',
            'tag' => $randomTag,
            'api_key' => $this->lastFmApiKey,
            'format' => 'json',
            'limit' => 20,
        ];

        $response = $this->httpClient->request('GET', $url, ['query' => $params]);
        $data = $response->toArray();

        $tracksData = $data['tracks']['track'] ?? [];

        return $this->hydrateTracksFromLastFm($tracksData);
    }

    /**
     * Gets a discovery track recommendation.
     *
     * @return array|null The recommended discovery track or null if not found
     */
    public function getDiscoverRecommendation(): ?array
    {
        $tracks = $this->getRandomTagTopTracks();

        shuffle($tracks); // Mélange les pistes pour éviter toujours les mêmes

        foreach ($tracks as $track) {
            $youtubeId = $this->youtubeService->searchBestVideoId($track->getArtist(), $track->getTitle());

            if ($youtubeId) {
                return [
                    'title' => $track->getTitle(),
                    'artist' => $track->getArtist(),
                    'youtubeId' => $youtubeId,
                    'coverUrl' => $track->getCoverUrl(),
                ];
            }
        }

        return null;
    }

    /**
     * Hydrates a recommendation at a specific offset with a YouTube ID.
     *
     * @param array $recommendations Array of recommended tracks
     * @param int   $offset          The offset to hydrate
     *
     * @return array|null The hydrated recommendation or null if not found
     */
    public function hydrateYoutubeIdAtOffset(array $recommendations, int $offset): ?array
    {
        if (!isset($recommendations[$offset])) {
            return null;
        }

        $track = $recommendations[$offset];
        $youtubeId = $this->youtubeService->searchBestVideoId($track->getArtist(), $track->getTitle());

        if (!$youtubeId) {
            return null;
        }

        return [
            'title' => $track->getTitle(),
            'artist' => $track->getArtist(),
            'youtubeId' => $youtubeId,
            'coverUrl' => $track->getCoverUrl(),
        ];
    }
}
