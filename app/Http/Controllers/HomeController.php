<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SalesData;
use App\Models\SentimenData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $selectedDept = $request->input('department', 1);
        $selectedStore = $request->input('store', 1);
        $period = $request->input('period', 'monthly');

        Carbon::setLocale('id');

        $labelsForView = [];
        $alignedActualSales = [];
        $alignedForecastSales = [];
        $sumOfActualSales = 0;
        $sumOfPastForecastSales = 0;
        $lastUpdatedSales = 'T/A';
        $lastUpdatedDateOnlySales = 'T/A';
        $totalSentimenComments = SentimenData::count();


        $overallLastDailyDateString = SalesData::where('dept', $selectedDept)
            ->where('store', $selectedStore)
            ->max('date');

        if ($overallLastDailyDateString) {
            $overallLastDailyDate = Carbon::parse($overallLastDailyDateString)->startOfDay();
            $forecastBoundaryStartDateDefault = $overallLastDailyDate->copy()->subDays(89)->startOfDay();

            if ($period === 'weekly') {
                $actualHistoricStartWeekly = Carbon::parse('2010-02-05')->startOfDay();
                $actualHistoricEndWeekly = Carbon::parse('2012-10-25')->startOfDay();
                $forecastFromPastStartWeekly = Carbon::parse('2012-10-26')->startOfDay();

                $rawDailyActualHistoricalSales = SalesData::where('dept', $selectedDept)
                    ->where('store', $selectedStore)
                    ->where('date', '>=', $actualHistoricStartWeekly->toDateString())
                    ->where('date', '<=', $actualHistoricEndWeekly->toDateString())
                    ->orderBy('date', 'asc')
                    ->get(['date', 'daily_sales'])
                    ->map(fn($item) => ['date' => Carbon::parse($item->date)->startOfDay(), 'sales' => (float)$item->daily_sales]);

                $rawDailyForecastSalesFromPast = SalesData::where('dept', $selectedDept)
                    ->where('store', $selectedStore)
                    ->where('date', '>=', $forecastFromPastStartWeekly->toDateString())
                    ->where('date', '<=', $overallLastDailyDate->toDateString())
                    ->orderBy('date', 'asc')
                    ->get(['date', 'daily_sales'])
                    ->map(fn($item) => ['date' => Carbon::parse($item->date)->startOfDay(), 'sales' => (float)$item->daily_sales]);
            } else {
                $rawDailyActualHistoricalSales = SalesData::where('dept', $selectedDept)
                    ->where('store', $selectedStore)
                    ->where('date', '<', $forecastBoundaryStartDateDefault->toDateString())
                    ->orderBy('date', 'asc')
                    ->get(['date', 'daily_sales'])
                    ->map(fn($item) => ['date' => Carbon::parse($item->date)->startOfDay(), 'sales' => (float)$item->daily_sales]);

                $rawDailyForecastSalesFromPast = SalesData::where('dept', $selectedDept)
                    ->where('store', $selectedStore)
                    ->whereBetween('date', [$forecastBoundaryStartDateDefault->toDateString(), $overallLastDailyDate->toDateString()])
                    ->orderBy('date', 'asc')
                    ->get(['date', 'daily_sales'])
                    ->map(fn($item) => ['date' => Carbon::parse($item->date)->startOfDay(), 'sales' => (float)$item->daily_sales]);
            }

            $sumOfActualSales = $rawDailyActualHistoricalSales->sum('sales');
            $sumOfPastForecastSales = $rawDailyForecastSalesFromPast->sum('sales');

            $groupedActualHistoricalSales = $this->groupSalesData($rawDailyActualHistoricalSales, $period);
            $actualLabels = !empty($groupedActualHistoricalSales) ? array_keys($groupedActualHistoricalSales) : [];

            $groupedForecastSalesFromPast = $this->groupSalesData($rawDailyForecastSalesFromPast, $period);
            $forecastLabelsFromPast = !empty($groupedForecastSalesFromPast) ? array_keys($groupedForecastSalesFromPast) : [];

            $allCombinedLabels = $this->mergeAndSortLabels($actualLabels, $forecastLabelsFromPast, $period);
            $processedForecastDataForChart = $groupedForecastSalesFromPast;

            if (!empty($actualLabels) && !empty($groupedActualHistoricalSales)) {
                $lastActualLabelWithValue = end($actualLabels);
                if (isset($groupedActualHistoricalSales[$lastActualLabelWithValue])) {
                    $lastActualValue = $groupedActualHistoricalSales[$lastActualLabelWithValue];
                    if (!isset($processedForecastDataForChart[$lastActualLabelWithValue])) {
                        $processedForecastDataForChart = [$lastActualLabelWithValue => $lastActualValue] + $processedForecastDataForChart;
                    } else {
                        $processedForecastDataForChart[$lastActualLabelWithValue] = $lastActualValue;
                    }
                }
            }

            foreach ($allCombinedLabels as $label) {
                $alignedActualSales[] = $groupedActualHistoricalSales[$label] ?? null;
                if (count($actualLabels) > 0 && $label === $actualLabels[count($actualLabels) - 1] && isset($groupedActualHistoricalSales[$label])) {
                    $alignedForecastSales[] = $groupedActualHistoricalSales[$label];
                } else {
                    $alignedForecastSales[] = $processedForecastDataForChart[$label] ?? null;
                }
            }

            $labelsForView = array_map(fn($label) => $this->formatLabelForView($label, $period), $allCombinedLabels);
            $lastUpdateForFilterSales = SalesData::where('dept', $selectedDept)->where('store', $selectedStore)->max('updated_at');
            if ($lastUpdateForFilterSales) {
                $lastUpdatedDateTimeSales = Carbon::parse($lastUpdateForFilterSales);
                $lastUpdatedSales = $lastUpdatedDateTimeSales->translatedFormat('d F Y H:i');
                $lastUpdatedDateOnlySales = $lastUpdatedDateTimeSales->translatedFormat('d F Y');
            }
        } else {
            $lastUpdateOverallSales = SalesData::max('updated_at');
            if ($lastUpdateOverallSales) {
                $lastUpdatedDateTimeSales = Carbon::parse($lastUpdateOverallSales);
                $lastUpdatedSales = $lastUpdatedDateTimeSales->translatedFormat('d F Y H:i');
                $lastUpdatedDateOnlySales = $lastUpdatedDateTimeSales->translatedFormat('d F Y');
            }
        }

        $jumlahPositif = SentimenData::where('label_sentimen', 'positif')->count();
        $jumlahNegatif = SentimenData::where('label_sentimen', 'negatif')->count();
        $jumlahNetral = SentimenData::where('label_sentimen', 'netral')->count();

        // [MODIFIED] Calculate sentiment percentages
        $persentasePositif = $totalSentimenComments > 0 ? round(($jumlahPositif / $totalSentimenComments) * 100, 1) : 0;
        $persentaseNegatif = $totalSentimenComments > 0 ? round(($jumlahNegatif / $totalSentimenComments) * 100, 1) : 0;
        $persentaseNetral = $totalSentimenComments > 0 ? round(($jumlahNetral / $totalSentimenComments) * 100, 1) : 0;

        $lastSentimenUpdateTimestamp = SentimenData::max('updated_at');
        $lastUpdatedSentimenDisplay = 'T/A';
        if ($lastSentimenUpdateTimestamp) {
            $lastUpdatedSentimenDisplay = Carbon::parse($lastSentimenUpdateTimestamp)->translatedFormat('d F Y H:i');
        }

        $sentimentDonutLabels = [];
        $sentimentDonutDataValues = [];
        $minWordLength = 3;
        $topNWords = 5;

        $stopWords = [
            // Bahasa Indonesia
            'yang', 'untuk', 'pada', 'ke', 'para', 'namun', 'menurut', 'antara', 'dia', 'dua', 'ia', 'seperti', 'jika', 'maka', 'dan', 'atau', 'tetapi', 'dengan', 'dari', 'oleh', 'lagi', 'juga', 'saat', 'hal', 'akan', 'adalah', 'ialah', 'saya', 'kamu', 'kami', 'anda', 'mereka', 'ini', 'itu', 'tersebut', 'sangat', 'sekali', 'tidak', 'sudah', 'belum', 'bisa', 'harus', 'agar', 'supaya', 'karena', 'sebab', 'mungkin', 'hanya', 'saja', 'pula', 'pun', 'agak', 'ada', 'adanya', 'adapun', 'agaknya', 'bagaimana', 'bagaimanapun', 'bagi', 'bahkan', 'bahwa', 'bahwasanya', 'beberapa', 'begitu', 'begitupun', 'demi', 'demikian', 'demikianlah', 'di', 'dong', 'enggak', 'enggaknya', 'entah', 'entahlah', 'gak', 'guna', 'hai', 'halo', 'hingga', 'iya', 'jadi', 'jangan', 'jangankan', 'justru', 'kalau', 'kok', 'kecuali', 'kemudian', 'kenapa', 'kepada', 'kepadanya', 'kita', 'lah', 'lain', 'lainnya', 'lalu', 'lebih', 'macam', 'mana', 'manalagi', 'masih', 'melainkan', 'memang', 'meski', 'meskipun', 'nah', 'nanti', 'oh', 'ok', 'pasti', 'per', 'pernah', 'rupa', 'rupanya', 'sebagaimana', 'sebelum', 'sebelumnya', 'sebenarnya', 'sedang', 'sedangkan', 'segala', 'sehingga', 'sejak', 'sekitar', 'selain', 'selalu', 'selama', 'seluruh', 'seluruhnya', 'sementara', 'semua', 'sendiri', 'sering', 'serta', 'siapa', 'sini', 'situ', 'suatu', 'tanpa', 'tapi', 'telah', 'tentang', 'tentu', 'terhadap', 'toh', 'turut', 'untukmu', 'wah', 'wahai', 'walau', 'walaupun', 'ya', 'yaitu', 'yakni', 'nya', 'shopee', 'yg', 'ga', 'gaada', 'gada', 'gk', 'gakada', 'gitu', 'aja', 'sih', 'kak', 'ka', 'min', 'admin', 'seller', 'kurir', 'produk', 'barang', 'toko', 'pengiriman', 'pengemasan', 'harga', 'kualitas', 'respon', 'pelayanan', 'cepat', 'lambat', 'bagus', 'jelek', 'baik', 'buruk', 'sesuai', 'pesanan', 'gambar', 'deskripsi', 'banget', 'bgt', 'mantap', 'oke', 'okey', 'thanks', 'thank', 'you', 'terima', 'kasih', 'recommended', 'rekomended', 'pokoknya', 'deh', 'mantul', 'jos', 'gandos', 'membantu', 'aplikasi', 'banyak',

            // Bahasa Inggris
            'a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'being', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'can', 'could', 'may', 'might', 'must', 'and', 'but', 'or', 'nor', 'for', 'so', 'yet', 'if', 'then', 'else', 'when', 'where', 'why', 'how', 'what', 'which', 'who', 'whom', 'whose', 'this', 'that', 'these', 'those', 'am', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them', 'my', 'your', 'his', 'its', 'our', 'their', 'mine', 'yours', 'hers', 'ours', 'theirs', 'myself', 'yourself', 'himself', 'herself', 'itself', 'ourselves', 'yourselves', 'themselves', 'in', 'on', 'at', 'by', 'from', 'to', 'up', 'down', 'out', 'over', 'under', 'again', 'further', 'once', 'here', 'there', 'all', 'any', 'both', 'each', 'few', 'more', 'then', 'most', 'other', 'some', 'such', 'no', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 's', 't', 'just', 'don', 'shouldv', 'now', 'd', 'll', 'm', 'o', 're', 've', 'y', 'ain', 'aren', 'couldn', 'didn', 'doesn', 'hadn', 'hasn', 'haven', 'isn', 'ma', 'mightn', 'mustn', 'needn', 'shan', 'shouldn', 'wasn', 'weren', 'won', 'wouldn', 'about', 'above', 'after', 'against', 'because', 'before', 'below', 'between', 'into', 'through', 'during', 'of', 'off', 'throughout', 'until', 'while', 'with', 'product', 'item', 'seller', 'shop', 'store', 'price', 'quality', 'shipping', 'delivery', 'response', 'service', 'good', 'bad', 'great', 'nice', 'fast', 'slow', 'recommended', 'really', 'very',
        ];

        $allReviewTexts = SentimenData::pluck('review_text');
        $wordCounts = [];

        if ($allReviewTexts->isNotEmpty()) {
            foreach ($allReviewTexts as $text) {
                if (empty(trim($text ?? ''))) continue;
                $cleanedText = Str::lower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $text ?? ''));
                $words = preg_split('/\s+/', $cleanedText, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($words as $word) {
                    if (!in_array($word, $stopWords) && Str::length($word) >= $minWordLength) {
                        if (!isset($wordCounts[$word])) {
                            $wordCounts[$word] = 0;
                        }
                        $wordCounts[$word]++;
                    }
                }
            }
            arsort($wordCounts);
            $topWords = array_slice($wordCounts, 0, $topNWords, true);
            foreach ($topWords as $word => $count) {
                $sentimentDonutLabels[] = $word;
                $sentimentDonutDataValues[] = $count;
            }
        }

        $distinctStores = SalesData::select('store')->distinct()->orderBy('store', 'asc')->pluck('store');
        $distinctDepartments = SalesData::select('dept')->distinct()->orderBy('dept', 'asc')->pluck('dept');
        $totalStores = $distinctStores->count();
        $totalDepartments = $distinctDepartments->count();
        $sentimenTerbaru = SentimenData::latest('updated_at')->first();

        return view('home', [
            'widget' => [
                'users' => User::count(),
                'total_sales' => $sumOfActualSales + $sumOfPastForecastSales,
            ],
            'months' => $labelsForView,
            'actualSales' => $alignedActualSales,
            'forecastSales' => $alignedForecastSales,
            'selectedDept' => $selectedDept,
            'selectedStore' => $selectedStore,
            'distinctStores' => $distinctStores,
            'distinctDepartments' => $distinctDepartments,
            'totalStores' => $totalStores,
            'totalDepartments' => $totalDepartments,
            'lastUpdatedDateOnly' => $lastUpdatedDateOnlySales,
            'lastUpdated' => $lastUpdatedSales,
            'period' => $period,
            'jumlahPositif' => $jumlahPositif,
            'jumlahNegatif' => $jumlahNegatif,
            'jumlahNetral' => $jumlahNetral,

            // [MODIFIED] Data for sentiment percentages and icons
            'persentasePositif' => $persentasePositif,
            'persentaseNegatif' => $persentaseNegatif,
            'persentaseNetral' => $persentaseNetral,
            'sentimentIcons' => [
                'positif' => 'fas fa-smile-beam',
                'negatif' => 'fas fa-frown',
                'netral'  => 'fas fa-meh',
            ],

            'sentimenTerbaru' => $sentimenTerbaru,
            'sentimentLastUpdateDisplay' => $lastUpdatedSentimenDisplay,
            'sentimentDonutLabels' => $sentimentDonutLabels,
            'sentimentDonutDataValues' => $sentimentDonutDataValues,
            'totalSentimenComments' => $totalSentimenComments,
        ]);
    }

    private function groupSalesData(Collection $salesData, string $period): array
    {
        if ($salesData->isEmpty()) {
            return [];
        }
        $grouped = $salesData->groupBy(function ($item) use ($period) {
            /** @var Carbon $date */
            $date = $item['date'];
            switch ($period) {
                case 'daily':
                    return $date->format('Y-m-d');
                case 'weekly':
                    $dateForKey = $date->copy();
                    // Set the week to start on Friday as per the original logic context
                    $daysToSubtract = ($dateForKey->dayOfWeekIso - Carbon::FRIDAY + 7) % 7;
                    return $dateForKey->subDays($daysToSubtract)->format('Y-m-d');
                case 'monthly':
                    return $date->format('Y-m');
                default:
                    return $date->format('Y-m-d');
            }
        });
        return $grouped->map(function ($group) {
            return $group->sum('sales');
        })->sortKeys()->toArray();
    }

    private function parseLabelToDate(string $label, string $period): ?Carbon
    {
        try {
            if ($period === 'monthly') {
                return Carbon::createFromFormat('Y-m', $label)->startOfMonth();
            }
            return Carbon::createFromFormat('Y-m-d', $label)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function mergeAndSortLabels(array $actualLabels, array $forecastLabels, string $period): array
    {
        $mergedLabels = array_unique(array_merge($actualLabels, $forecastLabels));
        usort($mergedLabels, function ($a, $b) use ($period) {
            $dateA = $this->parseLabelToDate($a, $period);
            $dateB = $this->parseLabelToDate($b, $period);
            if (!$dateA || !$dateB) return 0;
            return $dateA->timestamp <=> $dateB->timestamp;
        });
        return $mergedLabels;
    }

    private function formatLabelForView(string $label, string $period): string
    {
        $date = $this->parseLabelToDate($label, $period);
        if (!$date) return $label;

        if ($period === 'monthly') {
            return $date->translatedFormat('F Y');
        } elseif ($period === 'weekly') {
            return "W" . $date->isoFormat('WW') . " (" . $date->translatedFormat('d M') . ")";
        }
        return $date->translatedFormat('d M Y');
    }
}
