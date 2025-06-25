<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalesData;
use League\Csv\Reader;

class ImportSalesFromCsv extends Command
{
    protected $signature = 'import:sales';
    protected $description = 'Import sales data from CSV file';

    public function handle()
    {
        $path = storage_path('app/data/Datasementara.csv');
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv as $row) {
            SalesData::create([
                'store' => $row['Store'],
                'department' => $row['Dept'],
                'date' => $row['Date'],
                'sales' => $row['Weekly_Sales'],
            ]);
        }

        $this->info('CSV imported successfully!');
    }
}
