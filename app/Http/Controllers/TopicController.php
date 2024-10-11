<?php

namespace App\Http\Controllers;

use App\ProfessionalByType;
use App\ProfessionlExam;
use App\Question;
use App\Rules\CheckTopic;
use App\Section;
use App\Subject;
use App\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\CommonImport;
use Maatwebsite\Excel\Facades\Excel;

class TopicController extends Controller
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
        //
        $data['page_title'] = 'Sub Topic List';
        $data['result']     = Topic::where('is_deleted', 1)->orderBy('id', 'DESC')->get();
        return view('topic.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['page_title'] = 'Add Sub Topic';
        return view('topic.add', $data);
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
            'subject_id' => 'required',
            'type_id'    => 'required',
            'topic_name' => ['required', 'string', 'min:3', 'max:250', new CheckTopic($request->all())],
            'section_id' => 'required',
            'total_mcqs' => 'required',
            'total_seqs' => 'required',
        ]);
        $topic             = new Topic();
        $topic->subject_id = $request->input('subject_id');
        $topic->type_id    = $request->input('type_id');
        $topic->topic_name = strtolower(trim($request->input('topic_name')));
        $topic->section_id = $request->input('section_id');
        $topic->total_mcqs = $request->input('total_mcqs');
        $topic->total_seqs = $request->input('total_seqs');
        $topic->created_by = session('user_info')['admin_id'];
        $topic->updated_by = 0;
        $topic->save();
        return redirect('admin/topic')->with('msg_success', 'Sub Topic created successfully.');
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
        $data['page_title'] = 'Edit Sub Topic';
        $row                = Topic::findOrFail($id);
        if ($row) {
            $data['row'] = $row;
            return view('topic.edit', $data);
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
        $id    = base64url_decode($id);
        $topic = Topic::findOrFail($id);
        $this->validate($request, [
            'subject_id' => 'required',
            'type_id'    => 'required',
            'topic_name' => ['required', 'string', 'min:3', 'max:250', new CheckTopic($request->all())],
            'section_id' => 'required',
            'total_mcqs' => 'required',
            'total_seqs' => 'required',
        ]);

        $topic->subject_id = $request->input('subject_id');
        $topic->type_id    = $request->input('type_id');
        $topic->topic_name = strtolower(trim($request->input('topic_name')));
        $topic->section_id = $request->input('section_id');
        $topic->total_mcqs = $request->input('total_mcqs');
        $topic->total_seqs = $request->input('total_seqs');
        $topic->updated_by = session('user_info')['admin_id'];
        $topic->save();
        return redirect('admin/topic')->with('msg_success', 'Sub Topic update successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $id    = base64url_decode($id);
        $topic = Topic::findOrFail($id);

        if (Question::where(['topic_id' => $id, 'is_deleted' => 0])->first()) {
            return back()->with('msg_warning', 'This Sub Topic has Question, cannot delete it.');
        }
        $topic->is_deleted = 0;
        $topic->save();
        return back()->with('msg_success', 'Sub Topic deleted successfully');
    }

    /*------ Get section by subject_id ---------*/
    public function getSectionBySubjectId(Request $request)
    {
        $subject_id        = $request->input('subject_id');
        $result            = getSectionBySubjectId($subject_id);
        $reponse['result'] = $result;
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
                    $keys   = array('type_id', 'subject_id', 'topic_name', 'prof_id', 'sub_topic_name', 'total_seq', 'total_mcq');
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
                            foreach($csv_ct as $key=>$val){
                                $validString = trim($val);
                                if($key == 'type_id'){
                                    $key = 'program_type';
                                }else if($key == 'prof_id'){
                                    $key = 'Professional';
                                }else if($key == 'subject_id'){
                                    $key = 'subject_name';
                                }
                                if(strlen($validString) > 250){

                                    if($key == 'total_mcq' ||$key == 'total_seq'){
                                        return back()->with('msg_fail', 'Row # ' . $row_num . ', Max 10 digits allowed in ' .str_replace('_',' ',$key)  );
                                        break;
                                    }else{
                                        return back()->with('msg_fail', 'Row # ' . $row_num . ', Max 250 character allowed in ' .str_replace('_',' ',$key)  );
                                        break;
                                    }
                                }
                                if(str_contains($validString,'<')){
                                    return back()->with('msg_fail', 'Row # ' . $row_num . ', Symbol < Not allowed in  ' .str_replace('_',' ',$key)  );
                                    break;
                                }
                            }

                            /*-----Validate input Rules Start-----*/

                            if (empty($csv_ct['subject_id'])) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Subject Name cannot be empty.');
                                break;
                            }
                            if (empty($csv_ct['topic_name'])) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Topic cannot be empty.');
                                break;
                            }
                            if (empty($csv_ct['prof_id'])) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Professional cannot be empty.');
                                break;
                            }
                            if (empty($csv_ct['sub_topic_name'])) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Sub Topic cannot be empty.');
                                break;
                            }

                            if ($csv_ct['total_mcq'] === null) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ',  Total MCQ cannot be empty.');
                                break;
                            }

                            if ($csv_ct['total_seq'] === null) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Total SEQ cannot be empty.');
                                break;
                            }


                            if (!validate_numeric($csv_ct['total_mcq'])) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Only Digits Allowed in Total MCQ.');
                                break;
                            }
                            if (!validate_numeric($csv_ct['total_seq'])) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Only Digits Allowed in Total SEQ.');
                                break;
                            }
                            /*--1: CHECK TYPE EXIST -- */
                            if (strtolower(trim($csv_ct['type_id'])) == 'mbbs') {
                                $type = 2;
                            } elseif (strtolower(trim($csv_ct['type_id'])) == 'bsc nursing') {
                                $type = 1;
                            }elseif (strtolower(trim($csv_ct['type_id'])) == '') {
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

                            /*-- 3: Professional Exist Check--*/
                            $professional = ucfirst(strtolower(trim($csv_ct['prof_id'])));
                            $prof_id      = ProfessionlExam::where(['p_exam' => $professional])->first();
                            if (empty($prof_id)) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Professional ' . $csv_ct['prof_id'] . ' not exist in the system.');
                                break;
                            }

                            /*-- 4: Professional Exist  For Particular Type Check--*/
                            $is_prof_of_type = ProfessionalByType::where(['type_id' => $type, 'prof_id' => $prof_id->id])->first();
                            if (empty($is_prof_of_type)) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', ' . $csv_ct['prof_id'] . ' Professional is not for ' . $csv_ct['type_id'] . ' .');
                                break;
                            }

                            /*-- 5: CHECK PROFESSIONAL --*/
                            $section_id = Section::where(['section_name' => strtolower(trim($csv_ct['topic_name'])), 'prof_id' => $prof_id->id, 'subject_id' => $subject_id->id, 'is_deleted' => 0, 'type_id' => $type])->first();
                            if (is_null($section_id)) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Topic ' . $csv_ct['topic_name'] . ' not exist in the system.');
                                break;
                            }

                            /*-- 6: CHECK IF Sub TOPIC(Topic) EXIST IN THE SYSTEM. -- */
                            $verify_array = array(
                                'subject_id' => $subject_id->id,
                                'type_id'    => $type,
                                'section_id' => $section_id->id,
                                'topic_name' => strtolower(trim($csv_ct['sub_topic_name']))
                            );

                            if (!Topic::SubTopicExist($verify_array)) {
                                $verify_array['total_seqs'] = $csv_ct['total_seq'];
                                $verify_array['total_mcqs'] = $csv_ct['total_mcq'];
                                $csv_data[]                 = $verify_array;

                            }
                        }
                    } else {
                        return back()->with('msg_fail', 'File is empty.');
                    }
                    if (!empty($csv_data) && count($csv_data) > 0) {
                        foreach ($csv_data as $row) {
                            if (!Topic::SubTopicExist($row)) {
                                $topic             = new Topic();
                                $topic->subject_id = $row['subject_id'];
                                $topic->type_id    = $row['type_id'];
                                $topic->topic_name = strtolower(trim($row['topic_name']));
                                $topic->section_id = $row['section_id'];
                                $topic->total_mcqs = $row['total_mcqs'];
                                $topic->total_seqs = $row['total_seqs'];
                                $topic->created_by = session('user_info')['admin_id'];
                                $topic->updated_by = 0;
                                $topic->save();
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
