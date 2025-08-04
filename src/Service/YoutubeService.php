<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class YoutubeService
{
    private string $apiKey;
    private HttpClientInterface $http;

    public function __construct(
        HttpClientInterface $http,
        string $youtubeApiKey
    ) {
        $this->http = $http;
        $this->apiKey = $youtubeApiKey;
    }

    /**
     * Recherche des vidéos YouTube via l'API.
     *
     * @param string $query La requête de recherche.
     * @param int $maxResults Nombre max de résultats à récupérer.
     * @return array Liste des vidéos (videoId, title, channelTitle, thumbnail, isLiked).
     */
    public function search(
        string $query,
        int $maxResults = 10,
    ): array {
        $url = "https://www.googleapis.com/youtube/v3/search";
        $params = [
            "q" => $query,
            "key" => $this->apiKey,
            "part" => "snippet",
            "type" => "video",
            "maxResults" => $maxResults,
        ];
        $response = $this->http->request("GET", $url, ["query" => $params]);

        $data = $response->toArray();

        if (empty($data["items"])) {
            return [];
        }

        $filteredItems = array_filter($data["items"], function ($item) {
            return isset($item["id"]["videoId"]);
        });

        return array_map(function ($item) {
            $videoId = $item["id"]["videoId"];
            return [
                "videoId" => $videoId,
                "title" => $item["snippet"]["title"],
                "channelTitle" => $item["snippet"]["channelTitle"],
                "thumbnail" => $item["snippet"]["thumbnails"]["default"]["url"]
            ];
        }, $filteredItems);
    }

    /**
     * Recherche la meilleure vidéo correspondant à l'artiste + titre,
     * en testant plusieurs requêtes et filtrant par durée et popularité.
     *
     * @param string $artist
     * @param string $title
     * @return string|null L'ID de la vidéo YouTube ou null si aucune trouvée.
     */
    public function searchBestVideoId(string $artist, string $title): ?string
    {
        $query = "$artist - $title official audio";
        $results = $this->search($query,5);

        foreach ($results as $result) {
            $titleLower = strtolower($result["title"]);

            // Filtrage basique
            if (
                str_contains($titleLower, "cover") ||
                str_contains($titleLower, "live") ||
                str_contains($titleLower, "remix")
            ) {
                continue;
            }

            if (
                str_contains($titleLower, $artist) ||
                str_contains($titleLower, $title)
            ) {
                return $result["videoId"];
            }
        }

        // Aucun résultat propre trouvé
        return $results[0]["videoId"] ?? null;
    }

    /**
     * Recherche des vidéos pour une requête et filtre sur durée et vues.
     *
     * @param string $query
     * @return string|null ID vidéo valide ou null.
     */
    private function searchAndFilterVideo(string $query): ?string
    {
        $searchResults = $this->search($query, 5);

        if (empty($searchResults)) {
            return null;
        }

        $videoIds = array_column($searchResults, "videoId");

        $videosDetails = $this->getVideosDetails($videoIds);

        foreach ($videosDetails as $video) {
            $duration = $this->convertISO8601DurationToSeconds(
                $video["contentDetails"]["duration"]
            );
            $viewCount = (int) ($video["statistics"]["viewCount"] ?? 0);

            // Durée entre 2 minutes et 10 minutes, minimum 10k vues
            if ($duration >= 120 && $duration <= 600 && $viewCount > 10000) {
                return $video["id"];
            }
        }

        return null;
    }

    /**
     * Récupère les détails des vidéos via l'API YouTube.
     *
     * @param array $videoIds Liste d'IDs vidéo.
     * @return array Détails vidéos.
     */
    private function getVideosDetails(array $videoIds): array
    {
        $url = "https://www.googleapis.com/youtube/v3/videos";
        $params = [
            "part" => "contentDetails,statistics",
            "id" => implode(",", $videoIds),
            "key" => $this->apiKey,
        ];

        $response = $this->http->request("GET", $url, ["query" => $params]);
        $data = $response->toArray();

        return $data["items"] ?? [];
    }

    /**
     * Convertit une durée ISO 8601 en secondes.
     *
     * @param string $isoDuration
     * @return int
     */
    private function convertISO8601DurationToSeconds(string $isoDuration): int
    {
        $interval = new \DateInterval($isoDuration);
        return $interval->h * 3600 + $interval->i * 60 + $interval->s;
    }
}
