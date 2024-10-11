<?php

namespace App\Exports;

use App\Book;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\WithTitle;
class QuestionExport implements WithMultipleSheets
{
    protected $results;

    public function sheets(): array
    {
        $sheets    = [];
        $sheets[0] =  new TempExport();
        $sheets[1] =  new BaseData();

        return $sheets;
    }


}
