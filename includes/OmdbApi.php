<?php

class OmdbApi {
    private $apiKey;
    private $baseUrl = 'http://www.omdbapi.com/';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * IMDb ID'sine göre içerik arar
     */
    public function getByImdbId($imdbId) {
        $url = $this->baseUrl . "?apikey=" . $this->apiKey . "&i=" . $imdbId;
        return $this->makeRequest($url);
    }

    /**
     * İsme göre içerik arar
     */
    public function searchByTitle($title) {
        $url = $this->baseUrl . "?apikey=" . $this->apiKey . "&t=" . urlencode($title);
        return $this->makeRequest($url);
    }

    /**
     * API isteği yapar
     */
    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl Error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return json_decode($response, true);
    }
} 