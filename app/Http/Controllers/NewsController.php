<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NewsController extends Controller
{
    private $newsdataApiKey;
    private $newsdataApiBaseUrl = 'https://newsdata.io/api/1/latest';

    private $newsapiAiKey;
    private $newsapiAiBaseUrl = 'http://eventregistry.org/api/v1/article/getArticles';

    public function __construct()
    {
        $this->newsdataApiKey = env('NEWSDATA_API_KEY');
        $this->newsapiAiKey = env('NEWSAPI_AI_KEY');

        if (!$this->newsdataApiKey) {
            Log::critical('NEWSDATA_API_KEY tidak diset di file .env.');
        }
        if (!$this->newsapiAiKey) {
            Log::critical('NEWSAPI_AI_KEY tidak diset di file .env.');
        }
    }

    private function fetchFromNewsdata()
    {
        if (!$this->newsdataApiKey) {
            return ['items' => [], 'error' => 'Konfigurasi API Key Newsdata.io tidak ditemukan.'];
        }
        // Parameters for Newsdata.io
        $apiParams = [
            'apikey' => $this->newsdataApiKey,
            'q' => 'bisnis',
            'language' => 'id',
            'category' => 'business,politics,technology,top,world',
            'size' => 10
        ];
        $response = Http::timeout(10)->get($this->newsdataApiBaseUrl, $apiParams);
        $beritaItems = [];
        $error = null;

        if ($response->successful()) {
            $results = $response->json()['results'] ?? [];
            foreach ($results as $article) {
                // IMPORTANT: Ensure 'link' (source URL) and 'title' are present to be valid
                if (empty($article['link']) || empty($article['title']) || empty($article['article_id'])) {
                    Log::warning('Skipping Newsdata article due to missing link, title, or article_id.', ['article_data' => $article]);
                    continue;
                }
                $beritaItems[] = [
                    'id' => 'newsdata_' . $article['article_id'],
                    'judul' => $article['title'],
                    'slug' => Str::slug($article['title'] . '-' . $article['article_id']),
                    'gambar' => $article['image_url'] ?? null,
                    'kutipan' => Str::limit(strip_tags($article['description'] ?? ($article['snippet'] ?? 'Kutipan tidak tersedia.')), 150),
                    'tanggal_raw' => $article['pubDate'] ?? null,
                    'tanggal' => isset($article['pubDate']) ? Carbon::parse($article['pubDate'])->translatedFormat('d F Y') : 'Tanggal Tidak Diketahui',
                    'penulis' => isset($article['creator']) && is_array($article['creator']) && !empty($article['creator']) ? implode(', ', $article['creator']) : ($article['source_id'] ?? 'Sumber Tidak Diketahui'),
                    'sumber_link' => $article['link'] // Direct link to the original source, to be used in the 'show' view or a specific "View Source" button
                ];
            }
        } else {
            $error = "Newsdata API Error: " . $response->status();
            Log::error('Newsdata Fetch Error', ['status' => $response->status(), 'body' => $response->body(), 'params' => $apiParams]);
        }
        return ['items' => $beritaItems, 'error' => $error];
    }

    private function fetchFromNewsApiAi()
    {
        if (!$this->newsapiAiKey) {
            return ['items' => [], 'error' => 'Konfigurasi API Key NewsAPI.ai tidak ditemukan.'];
        }

        // Parameters for NewsAPI.ai (Event Registry)
        $apiParams = [
            'action' => 'getArticles',
            'keyword' => 'ekonomi',
            'lang' => 'ind',
            'articlesSortBy' => 'date',
            'articlesCount' => 10,
            'articleBodyLen' => 500,
            'resultType' => 'articles',
            'dataType' => ['news', 'blog'],
            'apiKey' => $this->newsapiAiKey,
            'forceMaxDataTimeWindow' => 31
        ];

        $response = Http::timeout(10)->get($this->newsapiAiBaseUrl, $apiParams);
        $beritaItems = [];
        $error = null;

        if ($response->successful() && isset($response->json()['articles']['results'])) {
            $results = $response->json()['articles']['results'];
            foreach ($results as $article) {
                if (isset($article['isDuplicate']) && $article['isDuplicate'] === true) {
                    continue;
                }
                // IMPORTANT: Ensure 'url' (source URL), 'title', and 'uri' (for ID) are present
                if (empty($article['url']) || empty($article['title']) || empty($article['uri'])) {
                    Log::warning('Skipping NewsAPI.ai article due to missing url, title, or uri.', ['article_data' => $article]);
                    continue;
                }

                $uniqueId = 'newsapiai_' . $article['uri']; // Unique ID for internal routing
                $penulisArray = [];
                if (!empty($article['authors'])) {
                    foreach ($article['authors'] as $author) {
                        if (isset($author['name'])) {
                            $penulisArray[] = $author['name'];
                        }
                    }
                }
                $penulis = !empty($penulisArray) ? implode(', ', $penulisArray) : ($article['source']['title'] ?? 'Sumber Tidak Diketahui');

                $beritaItems[] = [
                    'id' => $uniqueId,
                    'judul' => $article['title'],
                    'slug' => Str::slug($article['title'] . '-' . $article['uri']),
                    'gambar' => $article['image'] ?? null,
                    'kutipan' => Str::limit(strip_tags($article['body'] ?? 'Kutipan tidak tersedia.'), 150),
                    'tanggal_raw' => $article['dateTimePub'] ?? ($article['dateTime'] ?? null),
                    'tanggal' => isset($article['dateTimePub']) ? Carbon::parse($article['dateTimePub'])->translatedFormat('d F Y') : (isset($article['dateTime']) ? Carbon::parse($article['dateTime'])->translatedFormat('d F Y') : 'Tanggal Tidak Diketahui'),
                    'penulis' => $penulis,
                    'sumber_link' => $article['url'] // Direct link to the original source, to be used in the 'show' view or a specific "View Source" button
                ];
            }
        } else {
            $error = "NewsAPI.ai Error: Status " . ($response->status() ?: 'Unknown');
            $loggedParams = $apiParams;
            unset($loggedParams['apiKey']);
            Log::error('NewsAPI.ai Fetch Error:', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'parameters_sent' => $loggedParams
            ]);
        }
        return ['items' => $beritaItems, 'error' => $error];
    }

    public function index()
    {
        $newsdataResult = $this->fetchFromNewsdata();
        $newsapiAiResult = $this->fetchFromNewsApiAi();

        $allBeritaItems = array_merge($newsdataResult['items'], $newsapiAiResult['items']);
        $apiErrors = array_filter([$newsdataResult['error'], $newsapiAiResult['error']]);
        $apiError = !empty($apiErrors) ? implode(' | ', $apiErrors) : null;

        if ($apiError) {
            Log::warning('One or more news APIs failed to return data.', ['errors' => $apiErrors]);
        }

        $uniqueBeritaItems = [];
        $seenLinks = [];

        foreach ($allBeritaItems as $item) {
            if (!empty($item['sumber_link']) && !in_array($item['sumber_link'], $seenLinks)) {
                $uniqueBeritaItems[] = $item;
                $seenLinks[] = $item['sumber_link'];
            }
        }

        usort($uniqueBeritaItems, function ($a, $b) {
            $timeA = isset($a['tanggal_raw']) ? Carbon::parse($a['tanggal_raw'])->timestamp : PHP_INT_MIN;
            $timeB = isset($b['tanggal_raw']) ? Carbon::parse($b['tanggal_raw'])->timestamp : PHP_INT_MIN;
            return $timeB <=> $timeA;
        });

        $beritaItems = $uniqueBeritaItems;


        return view('news', compact('beritaItems', 'apiError'));
    }

    /**
     * Display the specified resource (article details page).
     * This 'show' method is intended to display article details internally.
     * It should provide a clear link to the original 'sumber_link' for the user to visit the external source.
     */
    public function show($articleId)
    {
        $berita = null;
        $apiParamsUsed = [];

        $createBeritaObject = function ($article, $idPrefix, $idField, $linkField, $contentFields, $dateField, $authorLogic) {
            if (empty($article[$linkField])) {
                Log::warning('Attempted to create berita object for show page with missing link field.', ['idPrefix' => $idPrefix, 'idField' => $idField, 'article_title' => $article['title'] ?? 'N/A']);
                return null;
            }

            $title = $article['title'] ?? 'Judul Tidak Tersedia';
            $actualIdValue = $article[$idField] ?? Str::random(10);
            $slugId = is_array($actualIdValue) ? http_build_query($actualIdValue) : (string)$actualIdValue;

            $content = 'Konten lengkap tidak tersedia.';
            foreach ($contentFields as $field) {
                if (!empty($article[$field])) {
                    // Sanitize content, allowing basic HTML tags for formatting.
                    $content = strip_tags($article[$field], '<p><br><a><img><h1><h2><h3><h4><h5><h6><strong><em><ul><ol><li><blockquote>');
                    break;
                }
            }

            $penulis = call_user_func($authorLogic, $article);
            $parsedDate = null;
            if (isset($article[$dateField])) {
                try {
                    $parsedDate = Carbon::parse($article[$dateField])->isoFormat('dddd, D MMMM YYYY HH:mm [WIB]');
                } catch (\Exception $e) {
                    Log::warning('Failed to parse date for article show page.', ['date_value' => $article[$dateField], 'error' => $e->getMessage()]);
                    $parsedDate = 'Tanggal Tidak Valid';
                }
            } else {
                $parsedDate = 'Tanggal Tidak Diketahui';
            }


            return (object) [
                'id' => $idPrefix . $slugId,
                'judul' => $title,
                'slug' => Str::slug($title . '-' . $slugId),
                'gambar' => $article['image_url'] ?? $article['image'] ?? null,
                'konten_lengkap' => $content,
                'tanggal' => $parsedDate,
                'penulis' => $penulis,
                'sumber_link' => $article[$linkField] // Link to the original source
            ];
        };

        if (Str::startsWith($articleId, 'newsdata_')) {
            $actualId = Str::after($articleId, 'newsdata_');
            if (!$this->newsdataApiKey) abort(500, 'Konfigurasi API Newsdata bermasalah.');

            $apiParamsUsed = ['apikey' => $this->newsdataApiKey, 'language' => 'id,en', 'size' => 5];

            if (!is_numeric($actualId) && !Str::isUuid($actualId) && strlen($actualId) > 32) {

                $apiParamsUsed['q'] = preg_replace('/-[^-]*$/', '', $actualId);
                $apiParamsUsed['size'] = 20;
            }


            $response = Http::timeout(15)->get($this->newsdataApiBaseUrl, $apiParamsUsed);

            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];
                foreach ($results as $article) {
                    // Match by article_id if the query was broad, or if 'q' by ID worked.
                    if ((string)($article['article_id'] ?? null) === (string)$actualId) {
                        $berita = $createBeritaObject(
                            $article,
                            'newsdata_',
                            'article_id',
                            'link',
                            ['content', 'description', 'snippet'],
                            'pubDate',
                            function ($art) {
                                return isset($art['creator']) && is_array($art['creator']) && !empty($art['creator']) ? implode(', ', $art['creator']) : ($art['source_id'] ?? 'Sumber Tidak Diketahui');
                            }
                        );
                        if ($berita) break;
                    }
                }
            } else {
                Log::error('Gagal mengambil data Newsdata API untuk show.', ['status' => $response->status(), 'body' => $response->body(), 'params_used' => $apiParamsUsed]);
            }
        } elseif (Str::startsWith($articleId, 'newsapiai_')) {
            $actualUri = Str::after($articleId, 'newsapiai_');
            if (!$this->newsapiAiKey) abort(500, 'Konfigurasi API NewsAPI.ai bermasalah.');

            // EventRegistry can fetch by article URI directly.
            $apiParamsUsed = [
                'action' => 'getArticle',
                'articleUri' => $actualUri,
                'resultType' => 'info',
                'articleBodyLen' => -1,
                'includeArticleImage' => true,
                'apiKey' => $this->newsapiAiKey
            ];
            $response = Http::timeout(15)->get('http://eventregistry.org/api/v1/article/getArticle', $apiParamsUsed);

            if ($response->successful() && isset($response->json()[$actualUri]['info'])) {
                $article = $response->json()[$actualUri]['info'];
                if (!empty($article['uri']) && !empty($article['url'])) {
                    $berita = $createBeritaObject(
                        $article,
                        'newsapiai_',
                        'uri',
                        'url',
                        ['body'],
                        'dateTimePub',
                        function ($art_detail) {
                            $penulisArray = [];
                            if (!empty($art_detail['authors'])) {
                                foreach ($art_detail['authors'] as $author) {
                                    if (isset($author['name'])) {
                                        $penulisArray[] = $author['name'];
                                    }
                                }
                            }
                            return !empty($penulisArray) ? implode(', ', $penulisArray) : ($art_detail['source']['title'] ?? 'Sumber Tidak Diketahui');
                        }
                    );
                } else {
                    Log::warning('NewsAPI.ai article fetched for show page is missing URI or URL.', ['article_uri' => $actualUri, 'response_data' => $article]);
                }
            } else {
                $loggedParamsShow = $apiParamsUsed;
                unset($loggedParamsShow['apiKey']);
                Log::error('Gagal mengambil data NewsAPI.ai untuk show.', ['status' => $response->status(), 'body' => $response->body(), 'params_used' => $loggedParamsShow, 'actual_uri_sent' => $actualUri]);
            }
        } else {
            Log::warning('Format article_id tidak dikenal untuk method show.', ['article_id' => $articleId]);
            abort(404, 'Format ID Artikel Tidak Dikenal.');
        }

        if (!$berita) {
            $loggedParams = $apiParamsUsed;
            if (isset($loggedParams['apiKey'])) unset($loggedParams['apiKey']);
            Log::warning('Berita dengan article_id tidak ditemukan atau tidak memiliki link sumber yang valid setelah mencoba API yang relevan.', [
                'article_id' => $articleId,
                'api_params_used' => $loggedParams
            ]);
            abort(404, 'Berita tidak ditemukan atau halaman sumber tidak tersedia.');
        }

        return view('news_show', compact('berita'));
    }
}
