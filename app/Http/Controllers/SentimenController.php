<?php

namespace App\Http\Controllers;

use App\Models\SentimenData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class SentimenController extends Controller
{
    /**
     * Menampilkan halaman utama analisis sentimen dengan data statistik.
     */
    public function index()
    {
        // Ambil data statistik untuk ditampilkan di kartu-kartu sentimen
        $jumlahPositif = SentimenData::where('label_sentimen', 'positif')->count();
        $jumlahNegatif = SentimenData::where('label_sentimen', 'negatif')->count();
        $jumlahNetral  = SentimenData::where('label_sentimen', 'netral')->count();

        // Ambil timestamp pembaruan terakhir dan format
        $lastSentimenUpdateTimestamp = SentimenData::max('updated_at');
        $sentimentLastUpdateDisplay = 'N/A'; // Default value
        if ($lastSentimenUpdateTimestamp) {
            // Menggunakan Carbon untuk memformat tanggal ke format Indonesia
            $sentimentLastUpdateDisplay = Carbon::parse($lastSentimenUpdateTimestamp)->translatedFormat('d F Y H:i');
        }

        // Kirim semua data ke view 'sentimen'
        return view('sentimen', [
            'jumlahPositif' => $jumlahPositif,
            'jumlahNegatif' => $jumlahNegatif,
            'jumlahNetral' => $jumlahNetral,
            'sentimentLastUpdateDisplay' => $sentimentLastUpdateDisplay,
        ]);
    }

    /**
     * Memprediksi sentimen dari teks yang diberikan melalui API eksternal (FastAPI).
     */
    public function predict(Request $request)
    {
        // Validasi input dari frontend Laravel
        $request->validate([
            'review_text' => 'required|string|min:3',
        ]);

        $commentText = $request->input('review_text');
        // Pastikan endpoint ini mengarah ke API FastAPI Anda
        // Default: http://127.0.0.1:5000/predict
        $apiEndpoint = env('SENTIMENT_API_ENDPOINT', 'http://127.0.0.1:5000/predict');

        try {
            // Panggil API FastAPI dengan timeout.
            // FastAPI mengharapkan body JSON dengan key 'text'.
            $response = Http::timeout(30)->post($apiEndpoint, [
                'text' => $commentText,
            ]);

            // Jika respons dari API berhasil (status 2xx)
            if ($response->successful()) {
                $dataFromApi = $response->json();
                // FastAPI mengembalikan 'sentiment' dan 'text'
                $sentimentResult = strtolower($dataFromApi['sentiment'] ?? 'tidak diketahui');
                $originalCommentReceived = $dataFromApi['text'] ?? $commentText; // Sesuaikan dengan key 'text' dari FastAPI

                return response()->json([
                    'message' => 'Analisis sentimen berhasil',
                    'label_sentimen' => $sentimentResult,
                    'original_comment' => $originalCommentReceived
                ]);
            }

            // Jika respons dari API gagal (status 4xx atau 5xx)
            $errorBody = $response->json();
            $errorMessage = 'Gagal menganalisis sentimen dari layanan eksternal.';
            $details = null;

            // Coba ekstrak detail error dari respons FastAPI (biasanya ada di 'detail')
            if ($errorBody && isset($errorBody['detail'])) {
                if (is_array($errorBody['detail']) && !empty($errorBody['detail'][0]['msg'])) {
                    // Jika error detail adalah list (misal dari validasi Pydantic)
                    $details = $errorBody['detail'][0]['msg'];
                } else {
                    // Jika error detail adalah string
                    $details = $errorBody['detail'];
                }
                $errorMessage .= " Detail: " . $details;
            } else if ($errorBody) {
                // Fallback jika tidak ada 'detail' tapi ada body JSON
                $details = $errorBody;
                $errorMessage .= " Respons API: " . json_encode($errorBody);
            } else {
                // Jika tidak ada body JSON
                $errorMessage .= " Status: " . $response->status() . ", Body: " . $response->body();
            }

            Log::error('Sentiment API request failed:', ['status' => $response->status(), 'response' => $response->body()]);

            return response()->json([
                'error' => $errorMessage,
                'details' => $details
            ], $response->status());

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Tangani error jika tidak bisa terhubung ke API (misal, API tidak berjalan)
            Log::error('Sentiment API connection error:', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'Tidak dapat terhubung ke layanan analisis sentimen. Pastikan API FastAPI berjalan.'
            ], 503); // Service Unavailable
        } catch (\Exception $e) {
            // Tangani error tak terduga lainnya
            Log::error('Unexpected error during sentiment analysis:', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'Terjadi kesalahan tak terduga saat analisis sentimen.'
            ], 500); // Internal Server Error
        }
    }

    /**
     * Menyimpan hasil analisis sentimen ke database.
     */
    public function save(Request $request)
    {
        // Tentukan nilai sentimen yang diizinkan
        $allowedSentiments = ['positif', 'negatif', 'netral', 'tidak diketahui'];

        // Validasi data yang akan disimpan
        $validatedData = $request->validate([
            'review_text' => 'required|string|max:10000',
            'label_sentimen' => ['required', 'string', Rule::in($allowedSentiments)],
        ]);

        try {
            // Pastikan label dalam format lowercase sebelum disimpan
            $validatedData['label_sentimen'] = strtolower($validatedData['label_sentimen']);

            SentimenData::create($validatedData);

            return response()->json(['success' => true, 'message' => 'Komentar berhasil disimpan!']);
        } catch (\Exception $e) {
            Log::error('Failed to save sentiment data:', ['message' => $e->getMessage(), 'data' => $validatedData]);
            return response()->json(['success' => false, 'error' => 'Gagal menyimpan data sentimen.'], 500);
        }
    }
}
