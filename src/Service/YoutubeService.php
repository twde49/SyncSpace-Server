<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class YoutubeService
{
    public function __construct(private readonly HttpClientInterface $http, private readonly string $apiKey)
    {
    }

    /**
     * Searches for YouTube videos via the API.
     *
     * @param string $query      The search query
     * @param int    $maxResults The maximum number of results to retrieve
     *
     * @return array List of videos (videoId, title, channelTitle, thumbnail)
     */
    public function search(
        string $query,
        int $maxResults = 10,
    ): array {
        $url = 'https://www.googleapis.com/youtube/v3/search';
        $params = [
            'q' => $query,
            'key' => $this->apiKey,
            'part' => 'snippet',
            'type' => 'video',
            'maxResults' => $maxResults,
        ];
        $response = $this->http->request('GET', $url, ['query' => $params]);

        $data = $response->toArray();

        if (empty($data['items'])) {
            return [];
        }

        $filteredItems = array_filter($data['items'], fn ($item) => isset($item['id']['videoId']));

        return array_map(function ($item) {
            $videoId = $item['id']['videoId'];

            return [
                'videoId' => $videoId,
                'title' => $item['snippet']['title'],
                'channelTitle' => $item['snippet']['channelTitle'],
                'thumbnail' => $item['snippet']['thumbnails']['default']['url'],
            ];
        }, $filteredItems);
    }

    /**
     * Searches for the best video matching the artist and title,
     * by testing multiple queries and filtering by duration and popularity.
     *
     * @param string $artist The artist
     * @param string $title  The title
     *
     * @return string|null The YouTube video ID or null if none found
     */
    public function searchBestVideoId(string $artist, string $title): ?string
    {
        $query = "$artist - $title official audio";
        $results = $this->search($query, 5);

        foreach ($results as $result) {
            $titleLower = strtolower((string) $result['title']);

            // Filtrage basique
            if (
                str_contains($titleLower, 'cover')
                || str_contains($titleLower, 'live')
                || str_contains($titleLower, 'remix')
            ) {
                continue;
            }

            if (
                str_contains($titleLower, $artist)
                || str_contains($titleLower, $title)
            ) {
                return $result['videoId'];
            }
        }

        // Aucun résultat propre trouvé
        return $results[0]['videoId'] ?? null;
    }

    /**
     * Searches for videos for a query and filters by duration and views.
     *
     * @param string $query The search query
     *
     * @return string|null Valid video ID or null
     */
    private function searchAndFilterVideo(string $query): ?string
    {
        $searchResults = $this->search($query, 5);

        if (empty($searchResults)) {
            return null;
        }

        $videoIds = array_column($searchResults, 'videoId');

        $videosDetails = $this->getVideosDetails($videoIds);

        foreach ($videosDetails as $video) {
            $duration = $this->convertISO8601DurationToSeconds(
                $video['contentDetails']['duration']
            );
            $viewCount = (int) ($video['statistics']['viewCount'] ?? 0);

            // Durée entre 2 minutes et 10 minutes, minimum 10k vues
            if ($duration >= 120 && $duration <= 600 && $viewCount > 10000) {
                return $video['id'];
            }
        }

        return null;
    }

    /**
     * Retrieves video details via the YouTube API.
     *
     * @param array $videoIds List of video IDs
     *
     * @return array Video details
     */
    private function getVideosDetails(array $videoIds): array
    {
        $url = 'https://www.googleapis.com/youtube/v3/videos';
        $params = [
            'part' => 'contentDetails,statistics',
            'id' => implode(',', $videoIds),
            'key' => $this->apiKey,
        ];

        $response = $this->http->request('GET', $url, ['query' => $params]);
        $data = $response->toArray();

        return $data['items'] ?? [];
    }

    /**
     * Converts an ISO 8601 duration to seconds.
     *
     * @param string $isoDuration The duration in ISO 8601 format
     *
     * @return int The duration in seconds
     */
    private function convertISO8601DurationToSeconds(string $isoDuration): int
    {
        $interval = new \DateInterval($isoDuration);

        return $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }
}
