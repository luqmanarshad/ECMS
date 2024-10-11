<?php

namespace App\Exports;


use App\Book;
use App\Cognitive_level;
use App\Professional;
use App\Question_diff;
use App\Relevance;
use App\Section;
use App\Subject;
use App\Topic;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TempExport implements WithEvents, WithHeadings, ShouldAutoSize
{

    public function headings(): array
    {
        return [
            "Program", "Subject", "Topic", "Sub Topic",
            "Question", "Option A", "Option B", "Option C", "Option D", "Option E",
            "Answers",
            "Name of Book/Source/Reference", "Difficulty Level",
            "Cognitive Level", "Relevance",
            "Chapter No", "Page No", "Year"
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                //Set Heading With Bold
                $event->sheet->getDelegate()->getStyle('A1:R1')->getFont()->setBold(true);
                //Custom Style to Questions Column
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(100);
                $event->sheet->getDelegate()->getColumnDimension('E')->setAutoSize(false);
                $event->sheet->getDelegate()->getStyle('E:J')->getAlignment()->setWrapText(true);

                $type_id    = request()->input('type_id');
                $subject_id = request()->input('subject_id');
                $type       = Professional::where(['id' => (int)$type_id])->first();
                $subject    = Subject::where(['type_id' => $type_id, 'id' => $subject_id, 'status' => 1])->first();
                $topics     = Section::where(['type_id' => (int)$type_id, 'subject_id' => (int)$subject_id, 'is_deleted' => 0])->get();
                $books      = Book::where(['level' => $type_id, 'subject_id' => $subject_id, 'status' => 1])->get();
                $diffLevel  = Question_diff::all();
                $cognitive  = Cognitive_level::all();
                $relevance  = Relevance::all();
                $years = range(2016, date('Y'));

                /*_______________SET DATA FOR MAKING DROP DOWN______________*/
                $event->sheet->getDelegate()->SetCellValue("AA1", $type->p_name);
                $event->sheet->getDelegate()->SetCellValue("AB1", $subject->subject_name);
                $event->sheet->getDelegate()->getParent()->addNamedRange(
                    new \PhpOffice\PhpSpreadsheet\NamedRange(
                        'Type',
                        $event->sheet->getDelegate(),
                        'AA1:AA1'
                    )
                );
                $event->sheet->getDelegate()->getParent()->addNamedRange(
                    new \PhpOffice\PhpSpreadsheet\NamedRange(
                        'SUBJECT',
                        $event->sheet->getDelegate(),
                        'AB1:AB1'
                    )
                );

                /*----TOPIC------*/
                if (!empty($topics) && count($topics) > 0) {
                    $i = 1;
                    foreach ($topics as $topic) {
                        $professional_exam = $topic->ProfessionlExam;
                        $event->sheet->getDelegate()->SetCellValue("AD" . $i, ucfirst($topic->section_name.' ['.$professional_exam->p_exam.']'));
                        $i++;
                    }

                    $event->sheet->getDelegate()->getParent()->addNamedRange(
                        new \PhpOffice\PhpSpreadsheet\NamedRange(
                            'topic',
                            $event->sheet->getDelegate(),
                            'AD1:AD' . ($i - 1)
                        )
                    );

                    /*---- SUB TOPIC ----*/
                    $subTopics = Topic::where(['type_id' => $type_id, 'subject_id' => $subject_id, 'is_deleted' => 1])->orderBy('section_id', 'ASC')->get();
                    if (!empty($subTopics) && count($subTopics) > 0) {
                        $t = 1;
                        foreach ($subTopics as $subTopic) {
                            $event->sheet->getDelegate()->SetCellValue("AE" . $t, ucfirst($subTopic->topic_name));
                            $t++;
                        }
                        $event->sheet->getDelegate()->getParent()->addNamedRange(
                            new \PhpOffice\PhpSpreadsheet\NamedRange(
                                'Subtopic',
                                $event->sheet->getDelegate(),
                                'AE1:AE' . ($t - 1)
                            )
                        );
                    }


                }

                if (!empty($books) && count($books) > 0) {
                    $b = 1;
                    foreach ($books as $book) {
                        $event->sheet->getDelegate()->SetCellValue("AG" . $b, $book->book_name . '/' . $book->volumn . '/' . $book->author);
                        $b++;
                    }
                    $event->sheet->getDelegate()->getParent()->addNamedRange(
                        new \PhpOffice\PhpSpreadsheet\NamedRange(
                            'Book',
                            $event->sheet->getDelegate(),
                            'AG1:AG' . ($b - 1)
                        )
                    );
                }

                if (!empty($diffLevel) && count($diffLevel) > 0) {
                    $d = 1;
                    foreach ($diffLevel as $diff) {
                        $event->sheet->getDelegate()->SetCellValue("AH" . $d, $diff->diff_level);
                        $d++;
                    }
                    $event->sheet->getDelegate()->getParent()->addNamedRange(
                        new \PhpOffice\PhpSpreadsheet\NamedRange(
                            'Diff',
                            $event->sheet->getDelegate(),
                            'AH1:AH' . ($d - 1)
                        )
                    );
                }

                if (!empty($cognitive) && count($cognitive) > 0) {
                    $d = 1;
                    foreach ($cognitive as $cog) {
                        $event->sheet->getDelegate()->SetCellValue("AI" . $d, $cog->cognitive_name);
                        $d++;
                    }
                    $event->sheet->getDelegate()->getParent()->addNamedRange(
                        new \PhpOffice\PhpSpreadsheet\NamedRange(
                            'COG',
                            $event->sheet->getDelegate(),
                            'AI1:AI' . ($d - 1)
                        )
                    );
                }

                if (!empty($relevance) && count($relevance) > 0) {
                    $d = 1;
                    foreach ($relevance as $relv) {
                        $event->sheet->getDelegate()->SetCellValue("AJ" . $d, $relv->relevance_name);
                        $d++;
                    }
                    $event->sheet->getDelegate()->getParent()->addNamedRange(
                        new \PhpOffice\PhpSpreadsheet\NamedRange(
                            'RELEVANCE',
                            $event->sheet->getDelegate(),
                            'AJ1:AJ' . ($d - 1)
                        )
                    );
                }

                $event->sheet->getDelegate()->SetCellValue("AC1", 'A');
                $event->sheet->getDelegate()->SetCellValue("AC2", 'B');
                $event->sheet->getDelegate()->SetCellValue("AC3", 'C');
                $event->sheet->getDelegate()->SetCellValue("AC4", 'D');
                $event->sheet->getDelegate()->SetCellValue("AC5", 'E');
                $event->sheet->getDelegate()->getParent()->addNamedRange(
                    new \PhpOffice\PhpSpreadsheet\NamedRange(
                        'ANSWER',
                        $event->sheet->getDelegate(),
                        'AC1:AC5'
                    )
                );

                if (!empty($years) && count($years) > 0) {
                    $d = 1;
                    foreach ($years as $year) {
                        $event->sheet->getDelegate()->SetCellValue("AK" . $d, $year);
                        $d++;
                    }
                    $event->sheet->getDelegate()->getParent()->addNamedRange(
                        new \PhpOffice\PhpSpreadsheet\NamedRange(
                            'YEAR',
                            $event->sheet->getDelegate(),
                            'AK1:AK' . ($d - 1)
                        )
                    );
                }

                /*------------------------------Print 1000 Rows--------------------------------------*/
                for ($r = 2; $r <= 1000; $r++) {
                    /*------ Type Drop Down ------*/
                    if (!empty($type->p_name)) {
                        $objValidation = $event->sheet->getDelegate()->getCell('A' . $r)->getDataValidation();
                        $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                        $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                        $objValidation->setAllowBlank(false);
                        $objValidation->setShowInputMessage(true);
                        $objValidation->setShowErrorMessage(true);
                        $objValidation->setShowDropDown(true);
                        $objValidation->setErrorTitle('Input error');
                        $objValidation->setError('Program is not in list.');
                        $objValidation->setPromptTitle('select from list');
                        $objValidation->setPrompt('Please select Program from list.');
                        $objValidation->setFormula1('=Type'); //note this
                        $objValidation->setShowDropDown(true);

                    }

                    /*------ Subject Drop Down ------*/
                    if (!empty($subject->subject_name)) {
                        $objValidation = $event->sheet->getDelegate()->getCell('B' . $r)->getDataValidation();
                        $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                        $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                        $objValidation->setAllowBlank(false);
                        $objValidation->setShowInputMessage(true);
                        $objValidation->setShowErrorMessage(true);
                        $objValidation->setShowDropDown(true);
                        $objValidation->setErrorTitle('Input error');
                        $objValidation->setError('Subject is not in list.');
                        $objValidation->setPromptTitle('select from list');
                        $objValidation->setPrompt('Please select Subject from list.');
                        $objValidation->setFormula1('=SUBJECT'); //note this
                        $objValidation->setShowDropDown(true);

                    }

                    /*-----------Topic Drop Down-------------*/
                    if (!empty($topics) && count($topics) > 0) {
                        $objValidation = $event->sheet->getDelegate()->getCell('C' . $r)->getDataValidation();
                        $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                        $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                        $objValidation->setAllowBlank(false);
                        $objValidation->setShowInputMessage(true);
                        $objValidation->setShowErrorMessage(true);
                        $objValidation->setShowDropDown(true);
                        $objValidation->setErrorTitle('Input error');
                        $objValidation->setError('Topic is not in list.');
                        $objValidation->setPromptTitle('Pick from list');
                        $objValidation->setPrompt('Please select topic from list.');
                        $objValidation->setFormula1('=topic'); //note this
                        $objValidation->setShowDropDown(true);

                        /*------------Sub Topic---------------*/
                        $objValidation1 = $event->sheet->getDelegate()->getCell('D' . $r)->getDataValidation();
                        $objValidation1->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                        $objValidation1->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                        $objValidation1->setAllowBlank(false);
                        $objValidation1->setShowInputMessage(true);
                        $objValidation1->setShowErrorMessage(true);
                        $objValidation1->setShowDropDown(true);
                        $objValidation1->setErrorTitle('Input error');
                        $objValidation1->setError('Sub Topic is not in list.');
                        $objValidation1->setPromptTitle('Pick from list');
                        $objValidation1->setPrompt('Please select sub topic from list.');
                        $objValidation1->setFormula1('Subtopic'); //note this
                        //$objValidation1->setFormula1('=INDIRECT($C$2)');

                    }

                    /*------ Book Drop Down ------*/
                    if (!empty($books) && count($books) > 0) {
                        $objValidation = $event->sheet->getDelegate()->getCell('L' . $r)->getDataValidation();
                        $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                        $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                        $objValidation->setAllowBlank(false);
                        $objValidation->setShowInputMessage(true);
                        $objValidation->setShowErrorMessage(true);
                        $objValidation->setShowDropDown(true);
                        $objValidation->setErrorTitle('Input error');
                        $objValidation->setError('Book is not in list.');
                        $objValidation->setPromptTitle('select from list');
                        $objValidation->setPrompt('Please select book from list.');
                        $objValidation->setFormula1('=Book'); //note this
                        $objValidation->setShowDropDown(true);

                    }

                    /*------ Difficulty Drop Down ------*/
                    if (!empty($diffLevel) && count($diffLevel) > 0) {
                        $objValidation = $event->sheet->getDelegate()->getCell('M' . $r)->getDataValidation();
                        $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                        $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                        $objValidation->setAllowBlank(false);
                        $objValidation->setShowInputMessage(true);
                        $objValidation->setShowErrorMessage(true);
                        $objValidation->setShowDropDown(true);
                        $objValidation->setErrorTitle('Input error');
                        $objValidation->setError('Difficulty is not in list.');
                        $objValidation->setPromptTitle('select from list');
                        $objValidation->setPrompt('Please select Difficulty from list.');
                        $objValidation->setFormula1('=Diff'); //note this
                        $objValidation->setShowDropDown(true);

                    }

                    /*------ Cognitive Drop Down ------*/
                    if (!empty($cognitive) && count($cognitive) > 0) {
                        $objValidation = $event->sheet->getDelegate()->getCell('N' . $r)->getDataValidation();
                        $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                        $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                        $objValidation->setAllowBlank(false);
                        $objValidation->setShowInputMessage(true);
                        $objValidation->setShowErrorMessage(true);
                        $objValidation->setShowDropDown(true);
                        $objValidation->setErrorTitle('Input error');
                        $objValidation->setError('Cognitive is not in list.');
                        $objValidation->setPromptTitle('select from list');
                        $objValidation->setPrompt('Please select Cognitive from list.');
                        $objValidation->setFormula1('=COG'); //note this
                        $objValidation->setShowDropDown(true);

                    }

                    /*------ Relevance Drop Down ------*/
                    if (!empty($relevance) && count($relevance) > 0) {

                        $objValidation = $event->sheet->getDelegate()->getCell('O' . $r)->getDataValidation();
                        $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                        $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                        $objValidation->setAllowBlank(false);
                        $objValidation->setShowInputMessage(true);
                        $objValidation->setShowErrorMessage(true);
                        $objValidation->setShowDropDown(true);
                        $objValidation->setErrorTitle('Input error');
                        $objValidation->setError('Relevance is not in list.');
                        $objValidation->setPromptTitle('select from list');
                        $objValidation->setPrompt('Please select Relevance from list.');
                        $objValidation->setFormula1('=RELEVANCE'); //note this
                        $objValidation->setShowDropDown(true);

                    }

                    /*------ Answer Drop Down ------*/

                    $objValidation = $event->sheet->getDelegate()->getCell('K' . $r)->getDataValidation();
                    $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $objValidation->setErrorTitle('Input error');
                    $objValidation->setError('Answer is not in list.');
                    $objValidation->setPromptTitle('select from list');
                    $objValidation->setPrompt('Please select Answer from list.');
                    $objValidation->setFormula1('=ANSWER'); //note this
                    $objValidation->setShowDropDown(true);

                    $objValidation = $event->sheet->getDelegate()->getCell('R' . $r)->getDataValidation();
                    $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $objValidation->setErrorTitle('Input error');
                    $objValidation->setError('Year is not in list.');
                    $objValidation->setPromptTitle('Select only from list');
                    $objValidation->setPrompt('Please select Year from list.');
                    $objValidation->setFormula1('=YEAR'); //note this
                    $objValidation->setShowDropDown(true);
                }

            }
        ];
    }
}
