<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\SalesData;
use Carbon\Carbon;
use Parsedown;

class ChatbotController extends Controller
{
    private Parsedown $markdownParser;

    public function __construct()
    {
        $this->markdownParser = new Parsedown();
    }

    public function index()
    {
        return view('chatbot');
    }

    /**
     * Titik masuk utama untuk menangani pesan pengguna.
     */
    public function handleQuery(Request $request): \Illuminate\Http\JsonResponse
    {
        $userMessage = $request->input('message', '');
        if (empty($userMessage)) {
            return response()->json(['response' => $this->formatResponseAsHtml('Silakan masukkan pertanyaan Anda.')]);
        }

        try {
            // Langsung ke alur utama untuk query penjualan berbasis Eloquent.
            return $this->handleEloquentQuery($userMessage);
        } catch (\Exception $e) {
            Log::error('ChatbotController General Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['response' => $this->formatResponseAsHtml('Maaf, terjadi kesalahan internal pada sistem.')], 500);
        }
    }

    /**
     * Menangani query dinamis dengan mengubah pertanyaan menjadi parameter Eloquent.
     */
    private function handleEloquentQuery(string $userMessage): \Illuminate\Http\JsonResponse
    {
        Log::info("Using Eloquent Builder for: '{$userMessage}'");

        // 1. Ekstrak parameter dari pertanyaan pengguna menjadi JSON.
        $params = $this->extractQueryParameters($userMessage);
        if (empty($params)) {
            Log::warning('AI could not extract parameters. Checking for relevance.', ['message' => $userMessage]);
            $finalAnswer = $this->generateRefusalOrClarification($userMessage);
            return response()->json(['response' => $this->formatResponseAsHtml($finalAnswer)]);
        }
        Log::info('AI Extracted Parameters:', $params);

        // 2. Bangun dan eksekusi query Eloquent dari parameter.
        $queryResult = $this->buildAndExecuteEloquentQuery($params);
        if ($queryResult === null) {
            return response()->json(['response' => $this->formatResponseAsHtml('Maaf, terjadi kesalahan saat mengambil data dari database.')]);
        }
        if (empty($queryResult)) {
            return response()->json(['response' => $this->formatResponseAsHtml('Saya tidak menemukan data yang cocok dengan permintaan Anda.')]);
        }

        // 3. Hasilkan jawaban akhir dalam bahasa natural dari hasil query.
        $finalAnswer = $this->generateFinalAnswer($userMessage, $queryResult, $params);
        return response()->json(['response' => $this->formatResponseAsHtml($finalAnswer)]);
    }

    /**
     * Meminta AI untuk mengubah pertanyaan bahasa natural menjadi objek JSON berisi parameter query.
     */
    private function extractQueryParameters(string $userQuestion): ?array
    {
        $schema = $this->getDatabaseSchema(['sales_data']);
        $prompt = <<<PROMPT
        Anda adalah AI parser yang sangat teliti, bertugas mengubah pertanyaan menjadi format JSON untuk query Eloquent. Anda hanya fokus pada data penjualan.

        PERATURAN:
        1.  **HANYA** keluarkan JSON yang valid. Jangan tambahkan penjelasan, komentar, atau markdown.
        2.  Jika pertanyaan sama sekali tidak relevan dengan data penjualan (misal: tentang cuaca, politik, dll.), keluarkan objek JSON kosong `{}`.
        3.  `filters` adalah array of object. Setiap object berisi `column`, `operator` (`=`, `>`, `<`, `<=`, `>=`, `in`), dan `value`.
        4.  Untuk filter waktu, gunakan operator 'year', 'month', atau 'date'.
        5.  Jika ada kata seperti 'antara', 'dan', atau daftar item (misal: 'toko 1, 2, dan 5'), gunakan operator `in` dengan value berupa array.
        6.  `aggregation` berisi `function` (`sum`, `avg`, `count`) dan `column`.
        7.  `order_by` berisi `column` dan `direction` ('asc' atau 'desc'). Untuk sorting berdasarkan agregasi, gunakan aliasnya (misal: 'sum_daily_sales').
        8.  Jika diminta data teratas atau terbanyak, gunakan `order_by` dengan `direction`: 'desc' dan `limit`.

        CONTOH PENTING (Mencari Pemenang Absolut):
        Pertanyaan: "Berapa penjualan terbanyak pada toko 1, 2, dan 3 di tahun 2010?" ATAU "di departemen berapa penjualan terbanyak pada toko 1, 2, dan 3 di tahun 2010?"
        Kedua pertanyaan ini memiliki makna yang sama: mencari satu kombinasi toko-departemen dengan penjualan tertinggi. Keduanya HARUS menghasilkan JSON di bawah ini.
        JSON:
        ```json
        {
            "select": ["store", "dept"],
            "aggregation": {"function": "sum", "column": "daily_sales"},
            "filters": [
                {"column": "store", "operator": "in", "value": [1, 2, 3]},
                {"column": "date", "operator": "year", "value": "2010"}
            ],
            "group_by": ["store", "dept"],
            "order_by": {"column": "sum_daily_sales", "direction": "desc"},
            "limit": 1
        }
        ```
        ---
        Skema Database:
        {$schema}
        ---
        Pertanyaan Pengguna: "{$userQuestion}"
        ---
        JSON:
        PROMPT;

        $response = $this->askGemini($prompt);
        $jsonResponse = trim(preg_replace('/^```json\s*|\s*```$/', '', $response));

        try {
            return json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error('Failed to decode JSON from AI', ['response' => $jsonResponse, 'error' => $e->getMessage()]);
            return null;
        }
    }


    /**
     * Membangun dan mengeksekusi query Eloquent secara dinamis berdasarkan parameter.
     */
    private function buildAndExecuteEloquentQuery(array $params): ?array
    {
        Log::debug('Entering buildAndExecuteEloquentQuery', ['params' => $params]);
        $query = SalesData::query();

        // 1. Terapkan klausa WHERE dengan logika yang diperbaiki
        if (!empty($params['filters'])) {
            foreach ($params['filters'] as $filter) {
                if (empty($filter['column']) || empty($filter['operator']) || !isset($filter['value'])) continue;

                $column = $filter['column'];
                $operator = strtolower($filter['operator']);
                $value = $filter['value'];

                switch ($operator) {
                    case 'year': $query->whereYear($column, $value); break;
                    case 'month': $query->whereMonth($column, $value); break;
                    case 'date': try { $query->whereDate($column, Carbon::parse($value)); } catch (\Exception $e) { Log::warning('Carbon could not parse date value', ['value' => $value]); } break;
                    case 'in': $query->whereIn($column, (array) $value); break;
                    default: $query->where($column, $operator, $value); break;
                }
            }
        }

        // 2. Terapkan GROUP BY
        if (!empty($params['group_by'])) {
            $query->groupBy((array) $params['group_by']);
        }

        // 3. Siapkan daftar SELECT
        $selectExpressions = [];
        if (!empty($params['select'])) {
            $selectExpressions = array_merge($selectExpressions, (array) $params['select']);
        }
        if (!empty($params['group_by'])) {
            $selectExpressions = array_merge($selectExpressions, (array) $params['group_by']);
        }
        $selectExpressions = array_unique($selectExpressions);

        $aggregationAlias = null;
        if (!empty($params['aggregation'])) {
            $func = strtolower($params['aggregation']['function']);
            $col = $params['aggregation']['column'];
            $aggregationAlias = "{$func}_{$col}";
            $selectExpressions[] = DB::raw("{$func}({$col}) as {$aggregationAlias}");
        }

        if (empty($selectExpressions)) {
            $query->select('*');
        } else {
            $query->select($selectExpressions);
        }

        // 4. Terapkan ORDER BY
        if (!empty($params['order_by']['column'])) {
            $orderByColumn = $params['order_by']['column'];
            $direction = strtolower($params['order_by']['direction'] ?? 'asc');
            // Penting: Gunakan orderByRaw untuk sorting pada alias kolom agregasi
            if ($aggregationAlias && $orderByColumn === $aggregationAlias) {
                 $query->orderByRaw("{$aggregationAlias} {$direction}");
            } else {
                 $query->orderBy($orderByColumn, $direction);
            }
        }


        // 5. Terapkan LIMIT
        if (!empty($params['limit']) && is_numeric($params['limit'])) {
            $query->limit((int) $params['limit']);
        }

        // 6. Eksekusi query dengan logging
        try {
            Log::info('FINAL EXECUTING ELOQUENT QUERY:', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
            return $query->get()->toArray();
        } catch (\Illuminate\Database\QueryException $e) {
            Log::critical('!!! ELOQUENT QUERY FAILED !!!', [
                'error_message' => $e->getMessage(),
                'sql_code' => $e->getCode(),
                'full_sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
            return null;
        }
    }

    /**
     * Mengambil skema dari tabel database yang ditentukan.
     */
    private function getDatabaseSchema(array $tableNames): string
    {
        $schemaDescription = "";
        foreach ($tableNames as $tableName) {
            if (Schema::hasTable($tableName)) {
                $schemaDescription .= "Tabel: `{$tableName}`\nKolom: ";
                $schemaDescription .= implode(', ', Schema::getColumnListing($tableName));
                $schemaDescription .= "\n";
            }
        }
        return $schemaDescription;
    }

    /**
     * Menghasilkan jawaban akhir dalam bahasa natural berdasarkan hasil query.
     */
    private function generateFinalAnswer(string $userQuestion, array $queryResult, array $params): string
    {
        $jsonResult = json_encode($queryResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $jsonParams = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        Log::debug('Generating final answer with result and params.', ['result_count' => count($queryResult), 'params' => $params]);

        $prompt = <<<PROMPT
        Anda adalah asisten analis data yang cerdas dan komunikatif. Tugas Anda adalah menjawab pertanyaan pengguna dengan bahasa yang natural, akurat, dan mudah dimengerti, berdasarkan data dan konteks filter yang diberikan.

        PERATURAN PENTING:
        1.  **Jangan sebutkan nama kolom mentah** seperti 'sum_daily_sales' atau 'daily_sales'. Ubah menjadi label yang bisa dimengerti manusia (misalnya: 'Total Penjualan', 'Penjualan Harian'). Format angka mata uang dengan simbol dolar ($) dan pemisah ribuan.
        2.  **Sajikan jawaban dalam kalimat lengkap** dan ringkas.
        3.  **Sertakan konteks dari filter** dalam jawaban Anda untuk memastikan jawabannya relevan (misal: sebutkan tahun atau toko yang ditanyakan).
        4.  Jika hasil query berisi **beberapa baris data** (bukan satu nilai agregat), **WAJIB** format jawaban sebagai tabel Markdown yang rapi. Beri judul yang sesuai untuk tabel.
        5.  Untuk pertanyaan "terbanyak/tertinggi", identifikasi pemenangnya dan sebutkan secara eksplisit dalam kalimat.
        6.  **ATURAN KRITIS**: Untuk jawaban "terbanyak" atau "tertinggi" yang dikelompokkan oleh lebih dari satu kolom (misal: 'store' dan 'dept'), Anda **WAJIB** menyebutkan nilai dari **SEMUA** kolom tersebut secara spesifik. Jawaban harus dalam format "departemen [nomor] di toko [nomor]".

        CONTOH JAWABAN "TERBANYAK" (Multi-Group - **SANGAT PENTING**):
        Pertanyaan: 'Berapa penjualan terbanyak pada toko 1, 2, dan 3 di tahun 2010?'
        Hasil Query: `[{"store": 2, "dept": 92, "sum_daily_sales": 543210.98}]`
        Jawaban yang Benar: "Pada tahun 2010, di antara toko 1, 2, dan 3, penjualan terbanyak diraih oleh **departemen 92 di toko 2** dengan total penjualan sebesar **$543,211**."

        ---
        Konteks Filter yang Digunakan (untuk referensi Anda):
        ```json
        {$jsonParams}
        ```

        Data dari Database (Hasil Query):
        ```json
        {$jsonResult}
        ```
        ---
        Pertanyaan Pengguna: "{$userQuestion}"
        Jawaban Analitis Anda (dalam Bahasa Indonesia, format Markdown, pastikan mengikuti ATURAN KRITIS dan contoh yang relevan):
        PROMPT;


        return $this->askGemini($prompt);
    }


    /**
     * Menganalisis pertanyaan yang tidak dapat diproses dan memberikan penolakan atau klarifikasi.
     */
    private function generateRefusalOrClarification(string $userQuestion): string
    {
        $prompt = "Anda adalah AI yang bertindak sebagai penjaga gerbang (gatekeeper) untuk chatbot analis penjualan. Tugas Anda adalah menilai pertanyaan pengguna yang tidak dapat saya proses menjadi query database.\n\n"
            . "PERATURAN:\n"
            . "1.  Analisis pertanyaan pengguna: `\"{$userQuestion}\"`\n"
            . "2.  **Jika pertanyaan tersebut SAMA SEKALI TIDAK BERHUBUNGAN dengan analisis data, penjualan, toko, departemen, atau bisnis** (contoh: 'siapa presiden indonesia?', 'ceritakan sebuah lelucon', 'apa itu fotosintesis?'), maka **TOLAK DENGAN SOPAN**. Jelaskan bahwa Anda adalah chatbot untuk analisis data penjualan dan tidak dapat menjawab pertanyaan di luar topik tersebut.\n"
            . "3.  **Jika pertanyaan tersebut TERLIHAT BERHUBUNGAN dengan penjualan tapi saya gagal memahaminya**, maka minta pengguna untuk **mengajukan pertanyaan kembali dengan lebih sederhana**. Sarankan untuk menggunakan kata kunci seperti 'total penjualan', 'rata-rata penjualan', 'departemen', 'toko', atau 'tahun'.\n\n"
            . "Jawaban Anda (dalam Bahasa Indonesia):";

        return $this->askGemini($prompt);
    }


    /**
     * Mengirimkan prompt ke Google Gemini API.
     */
    private function askGemini(string $prompt): string
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            Log::error('GEMINI_API_KEY is not set.');
            return 'Maaf, konfigurasi API untuk AI sedang bermasalah.';
        }

        $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(60) // 60-second timeout
                ->post($apiUrl, [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => [ 'temperature' => 0.1, 'maxOutputTokens' => 2048 ]
                ]);

            if ($response->successful()) {
                // Safely access the deeply nested text value
                return data_get($response->json(), 'candidates.0.content.parts.0.text', 'Maaf, saya tidak dapat memproses respons dari AI.');
            }

            Log::error('Gemini API Error', ['status' => $response->status(), 'body' => $response->body()]);
            return 'Maaf, terjadi kesalahan saat menghubungi layanan AI.';

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Connection to Gemini failed.', ['message' => $e->getMessage()]);
            return 'Maaf, koneksi ke layanan AI gagal.';
        }
    }

    /**
     * Mengonversi teks Markdown menjadi HTML.
     */
    private function formatResponseAsHtml(string $text): string
    {
        $this->markdownParser->setSafeMode(true);
        return $this->markdownParser->text($text);
    }
}
