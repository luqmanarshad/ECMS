<?php

namespace App\Http\Controllers;

use App\Question;
use App\Rules\CheckSection;
use App\Section;
use App\Topic;
use App\Subject;
use App\ProfessionlExam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\CommonImport;
use Maatwebsite\Excel\Facades\Excel;


class SectionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:Admin']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['page_title'] = 'Topic List';
        $data['result']     = Section::where('is_deleted', 0)->orderBy('id', 'DESC')->get();
        return view('section.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['page_title'] = 'Add Topic';
        return view('section.add', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request, [
            'subject_id'   => 'required',
            'type_id'      => 'required',
            'section_name' => ['required', 'string', 'min:3', 'max:250', new CheckSection($request->all())],
            'prof_id'      => 'required'
        ]);
        $section               = new Section();
        $section->subject_id   = $request->input('subject_id');
        $section->type_id      = $request->input('type_id');
        $section->section_name = strtolower(trim($request->input('section_name')));
        $section->prof_id      = $request->input('prof_id');
        $section->created_by   = session('user_info')['admin_id'];
        $section->updated_by   = 0;
        $section->save();
        return redirect('admin/section')->with('msg_success', 'Topic created successfully.');
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $id                 = (int)base64url_decode($id);
        $data['page_title'] = 'Edit Topic';
        $row                = Section::findOrFail($id);
        if ($row) {
            $data['row'] = $row;
            return view('section.edit', $data);
        } else {
            return back()->with('msg_fail', 'No Record found against Id.');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $id      = base64url_decode($id);
        $section = Section::findOrFail($id);
        $this->validate($request, [
            'subject_id'   => 'required',
            'type_id'      => 'required',
            'section_name' => ['required', 'string', 'min:3', 'max:250', new CheckSection($request->all())],
            'prof_id'      => 'required'
        ]);

        $section->subject_id   = $request->input('subject_id');
        $section->type_id      = $request->input('type_id');
        $section->section_name = strtolower(trim($request->input('section_name')));
        $section->prof_id      = $request->input('prof_id');
        $section->updated_by   = session('user_info')['admin_id'];
        $section->save();
        return redirect('admin/section')->with('msg_success', 'Topic update successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $id      = base64url_decode($id);
        $section = Section::findOrFail($id);

        if (Topic::where(['section_id' => $id, 'is_deleted' => 1])->first()) {
            return back()->with('msg_warning', 'This Topic has Sub Topics, cannot delete it.');
        }
        if (Question::where(['section_id' => $id, 'is_deleted' => 0])->first()) {
            return back()->with('msg_warning', 'This Topic has Question, cannot delete it.');
        }
        $section->is_deleted = 1;
        $section->save();
        return back()->with('msg_success', 'Section deleted successfully');
    }

    public function getAllSubjectByTypeIdForBook(Request $request)
    {

        $type_id                = $request->input('type_id');
        $result                 = return_Subjects_by_type_id_forBook($type_id);
        $result_exam            = professional_exam_drop_down($type_id);
        $reponse['result']      = $result;
        $reponse['result_exam'] = $result_exam;
        echo json_encode($reponse);

    }

    public function import(Request $request)
    {
        $this->validate($request, ['userfile', 'file|mimes:xlsx']);
        $file            = $request->file('userfile');
        $filename        = $file->getClientOriginalName();
        $extension       = $file->getClientOriginalExtension();
        $fileSize        = $file->getSize();
        $name            = explode(".", $filename);
        $realName        = $name[0] . time() . '.' . $extension;
        $valid_extension = array("xlsx");

        // 5MB in Bytes
        $maxFileSize = 6097152;
        if (in_array(strtolower($extension), $valid_extension)) {
            // Check file size
            if ($fileSize <= $maxFileSize) {
                $record = Excel::toArray(new CommonImport(), $request->file('userfile'));
                foreach ($record[0] as $row) {
                    $arrResult[] = $row;
                }

                $titles = array_shift($arrResult);
                $keys   = array('subject_id', 'section_name', 'prof_id', 'type_id');
                $final  = array();

                foreach ($arrResult as $key => $value) {
                    if (sizeof($keys) == sizeof($value)) {
                        $final[] = array_combine($keys, $value);
                    } else {
                        return back()->with('msg_fail', 'You must have to follow file format.');
                        break;
                    }
                }

                $row_num = 1;
                if (!empty($final)) {
                    foreach ($final as $csv_ct) {
                        $row_num++;
                        /*---Check Max 250 character --- */
                        foreach ($csv_ct as $key => $val) {
                            $validString = trim($val);
                            if ($key == 'type_id') {
                                $key = 'program_type';
                            } else if ($key == 'prof_id') {
                                $key = 'Professional';
                            } else if ($key == 'section_name') {
                                $key = 'topic_name';
                            } else if ($key == 'subject_id') {
                                $key = 'subject_name';
                            }
                            if (strlen($validString) > 250) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Max 250 character allowed in ' . str_replace('_', ' ', $key));
                                break;
                            }
                            if (str_contains($validString, '<')) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Symbol < Not allowed in  ' . str_replace('_', ' ', $key));
                                break;
                            }
                        }

                        /*--1: CHECK TYPE EXIST -- */

                        if (empty($csv_ct['subject_id'])) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', Subject Name cannot be empty.');
                            break;
                        }

                        if (empty($csv_ct['prof_id'])) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', Professional cannot be empty.');
                            break;
                        }
                        if (empty($csv_ct['section_name'])) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', Topic Name cannot be empty.');
                            break;
                        }
                        if (strtolower(trim($csv_ct['type_id'])) == 'mbbs') {
                            $type = 2;
                        } elseif (strtolower(trim($csv_ct['type_id'])) == 'bsc nursing') {
                            $type = 1;
                        } elseif (strtolower(trim($csv_ct['type_id'])) == '') {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', Type cannot be empty.');
                            break;
                        } else {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', Type ' . $csv_ct['type_id'] . ' not exist in the system.');
                            break;
                        }


                        /*-- 2: Subject Exist Check--*/
                        $subject_id = Subject::where(['subject_name' => strtolower(trim($csv_ct['subject_id'])), 'status' => 1, 'type_id' => $type])->first();
                        if (empty($subject_id)) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', Subject ' . $csv_ct['subject_id'] . ' not exist in the system.');
                            break;
                        }

                        /*-- 3: CHECK PROFESSIONAL --*/
                        $prof_id = ProfessionlExam::where(['p_exam' => strtolower(trim($csv_ct['prof_id']))])->first();
                        if (empty($prof_id)) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', Professional ' . $csv_ct['prof_id'] . ' not exist in the system.');
                            break;
                        }

                        /*-- 4: CHECK TYPE WITH PROFESSIONAL --  */
                        $bsc  = array(1, 2, 3, 4);
                        $mbbs = array(1, 2, 3, 4, 5);
                        if ($type == 1 && !in_array($prof_id->id, $bsc)) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', ' . $csv_ct['prof_id'] . ' professional is not for BSc Nusring.');
                            break;
                        } elseif ($type == 2 && !in_array($prof_id->id, $mbbs)) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', ' . $csv_ct['prof_id'] . ' professional is not for MBBS.');
                            break;

                        }

                        if (!Section::where(['section_name' => strtolower(trim($csv_ct['section_name'])), 'subject_id' => $subject_id->id, 'prof_id' => $prof_id->id, 'type_id' => $type, 'is_deleted' => 0])->first()) {
                            $subject_name = trim($csv_ct['subject_id']);
                            $type_id      = $type;
                            if (!empty($subject_name) && !empty($type_id)) {
                                $csv_data[] = array(
                                    'subject_id'   => $subject_id->id,
                                    'type_id'      => $type,
                                    'prof_id'      => $prof_id->id,
                                    'section_name' => strtolower(trim($csv_ct['section_name']))
                                );
                            }
                        }
                    }
                } else {
                    return back()->with('msg_fail', 'File is Empty.');
                }
                if (!empty($csv_data) && count($csv_data) > 0) {
                    foreach ($csv_data as $row) {
                        if (!Section::where(['section_name' => strtolower(trim($row['section_name'])), 'subject_id' => $row['subject_id'], 'prof_id' => $row['prof_id'], 'type_id' => $row['type_id'], 'is_deleted' => 0])->first()) {
                            $section               = new Section();
                            $section->subject_id   = $row['subject_id'];
                            $section->type_id      = $row['type_id'];
                            $section->prof_id      = $row['prof_id'];
                            $section->section_name = strtolower(trim($row['section_name']));
                            $section->created_by   = session('user_info')['admin_id'];
                            $section->updated_by   = 0;
                            $section->save();
                        }
                    }
                    return back()->with('msg_success', 'File successfully Imported.');
                } else {
                    return back()->with('msg_warning', 'Record already exist in the system.');
                }

            } else {
                return back()->with('msg_fail', 'File Size is greater then allowed size.');
            }
        } else {
            return back()->with('msg_fail', 'File Extension Should be xlsx.');
        }
    }//end of import function
}
