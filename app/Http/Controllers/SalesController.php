<?php

namespace App\Http\Controllers;

use App\Models\SalesData;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function index()
    {
        $sales = SalesData::select('date', 'store', 'dept', 'daily_sales')->get();
        return response()->json($sales);
    }

    public function filter(Request $request)
    {
        $query = SalesData::select('date', 'store', 'dept', 'daily_sales');

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        if ($request->has('store')) {
            $query->where('store', $request->store);
        }

        return response()->json($query->get());
    }
}
