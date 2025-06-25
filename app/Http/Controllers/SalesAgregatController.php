<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesAgregatData;
use Carbon\Carbon;

class SalesAgregatController extends Controller
{
    public function lastUpdatedAgregat()
    {
        // Set locale Carbon ke bahasa Indonesia
        Carbon::setLocale('id');

        // Ambil waktu terakhir update dari tabel sales_agregat
        $lastUpdateAgregat = SalesAgregatData::orderBy('updated_at', 'desc')->value('updated_at');

        return view('sales.agregat', compact('lastUpdate'));
    }
}
