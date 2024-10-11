<?php

namespace App\Exports;

use App\Subject;
use Maatwebsite\Excel\Concerns\FromCollection;

class BaseData implements FromCollection
{
    public function collection()
    {
        return Subject::all();
    }
}
