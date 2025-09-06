<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Track;
use App\Repository\TrackRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecommendationService
{
    public function __construct(private readonly HttpClientInterface $httpClient, private readonly TrackRepository $trackRepository, private readonly YoutubeService $youtubeService, private readonly string $apiKey)
    {
    }

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
     * Hydrate la première recommandation d'un youtubeId et renvoie cette recommandation en tableau.
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
     * Récupère une liste de recommandations (Track[]) (fonction existante mise à jour).
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
