<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\BuybackDeviceTemplateSheet;
use App\Exports\Sheets\BuybackTierReferenceSheet;

class BuybackDeviceTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new BuybackDeviceTemplateSheet(),
            new BuybackTierReferenceSheet(),
        ];
    }
}
