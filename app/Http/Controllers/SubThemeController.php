<?php

namespace App\Http\Controllers;

use App\ProfessionalByType;
use App\ProfessionlExam;
use App\Rules\CheckSubTheme;
use App\Section;
use App\Subject;
use App\SubTheme;
use App\Theme;
use App\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\CommonImport;
use Maatwebsite\Excel\Facades\Excel;

class SubThemeController extends Controller
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
        $data['page_title'] = 'Sub Theme List';
        $data['result']     = SubTheme::where('is_deleted', 0)->orderBy('id', 'DESC')->get();
        return view('sub_theme.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['page_title'] = 'Add Sub Theme';
        return view('sub_theme.add', $data);
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
            'subject_id'     => 'required',
            'type_id'        => 'required',
            'section_id'     => 'required',
            'theme_id'       => 'required',
            'sub_theme_name' => ['required', 'string', 'min:3', 'max:250', new CheckSubTheme($request->all())]
        ]);
        $subtheme                 = new SubTheme();
        $subtheme->subject_id     = $request->input('subject_id');
        $subtheme->type_id        = $request->input('type_id');
        $subtheme->section_id     = $request->input('section_id');
        $subtheme->topic_id       = $request->input('topic_id') ? $request->input('topic_id') : 0;
        $subtheme->theme_id       = $request->input('theme_id');
        $subtheme->sub_theme_name = strtolower(trim($request->input('sub_theme_name')));
        $subtheme->created_by     = session('user_info')['admin_id'];
        $subtheme->updated_by     = 0;
        $subtheme->save();
        return redirect('admin/subtheme')->with('msg_success', 'Sub Theme created successfully.');
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
        $data['page_title'] = 'Edit Sub Theme';
        $row                = SubTheme::findOrFail($id);
        if ($row) {
            $data['row'] = $row;
            return view('sub_theme.edit', $data);
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
        $id       = (int)base64url_decode($id);
        $subtheme = SubTheme::findOrFail($id);
        $this->validate($request, [
            'subject_id'     => 'required',
            'type_id'        => 'required',
            'section_id'     => 'required',
            'theme_id'       => 'required',
            'sub_theme_name' => ['required', 'string', 'min:3', 'max:250', new CheckSubTheme($request->all())]
        ]);
        $subtheme->subject_id     = $request->input('subject_id');
        $subtheme->type_id        = $request->input('type_id');
        $subtheme->section_id     = $request->input('section_id');
        $subtheme->topic_id       = $request->input('topic_id') ? $request->input('topic_id') : 0;
        $subtheme->theme_id       = $request->input('theme_id');
        $subtheme->sub_theme_name = strtolower(trim($request->input('sub_theme_name')));
        $subtheme->updated_by     = session('user_info')['admin_id'];
        $subtheme->save();
        return redirect('admin/subtheme')->with('msg_success', 'Sub Theme update successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $id                   = base64url_decode($id);
        $subtheme             = SubTheme::findOrFail($id);
        $subtheme->is_deleted = 1;
        $subtheme->save();
        return back()->with('msg_success', 'Sub Theme deleted successfully');
    }

    /*GET Sub TOPIC BY TOPIC(OLD SECTION)*/
    public function get_topics_by_section_id(Request $request)
    {
        $section_id        = $request->input('section_id');
        $result            = getTopicsBySectionId($section_id);
        $reponse['result'] = $result;
        echo json_encode($reponse);
        exit;
    }

    /*GET THEME BY TOPIC(OLD SECTION)*/
    public function get_themes_by_section_id(Request $request)
    {
        $section_id        = $request->input('section_id');
        $result            = themeDropDownBySectionID($section_id);
        $reponse['result'] = $result;
        echo json_encode($reponse);
        exit;
    }

     public function get_themes_and_topics_by_section_id(Request $request)
    {
        $section_id = $request->input('section_id');
        $reponse['themes_result'] = themeDropDownBySectionID($section_id);
        $reponse['topics_result'] = getTopicsBySectionId($section_id);
        echo json_encode($reponse);
        exit;
    }

    /*GET THEME BY SUB TOPIC(OLD TOPIC)*/
    public function get_theme_by_id(Request $request)
    {
        $topic_id          = $request->input('topic_id');
        $sub_topic_id      = $request->input('sub_topic_id');
        $result            = themeDropDown($topic_id,$sub_topic_id);
        $reponse['result'] = $result['str'];
        $reponse['found']  = $result['found'];
        echo json_encode($reponse);
        exit;
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
                $keys   = array('type_id', 'subject_id', 'topic_name', 'prof_id', 'sub_topic_name', 'theme_name', 'sub_theme_name');
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
                        if (empty($csv_ct['theme_name'])) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ',  Theme Name cannot be empty.');
                            break;
                        }
                        if (empty($csv_ct['sub_theme_name'])) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', Sub Theme Name cannot be empty.');
                            break;
                        }

                        /*---Check Max 250 character --- */
                        foreach ($csv_ct as $key => $val) {
                            $validString = trim($val);
                            if ($key == 'type_id') {
                                $key = 'program_type';
                            } else if ($key == 'prof_id') {
                                $key = 'Professional';
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

                        /*-- 5: CHECK TOPICS --*/
                        $topic_id = Section::where(['section_name' => strtolower(trim($csv_ct['topic_name'])), 'prof_id' => $prof_id->id, 'subject_id' => $subject_id->id, 'is_deleted' => 0, 'type_id' => $type])->first();
                        if (empty($topic_id)) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', Topic ' . $csv_ct['topic_name'] . ' not exist in the system.');
                            break;
                        }

                        /*-- 6: CHECK SUB TOPICS --*/
                        if (!empty(trim($csv_ct['sub_topic_name']))) {
                            $cond         = array(
                                'type_id'    => $type,
                                'subject_id' => $subject_id->id,
                                'section_id' => $topic_id->id,
                                'topic_name' => strtolower(trim($csv_ct['sub_topic_name'])),
                                'is_deleted' => 1
                            );
                            $sub_topic_id = Topic::where($cond)->first();
                            if (empty($sub_topic_id)) {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Sub Topic ' . $csv_ct['sub_topic_name'] . ' not exist in the system.');
                                break;
                            } else {
                                $sub_topic_id = $sub_topic_id->id;
                            }
                        } else {
                            $sub_topic_id = 0;
                        }
                        /*-- 7: CHECK  THEME --*/
                        $cond_theme = array(
                            'type_id'    => $type,
                            'subject_id' => $subject_id->id,
                            'section_id' => $topic_id->id,
                            'theme_name' => strtolower(trim($csv_ct['theme_name'])),
                            'is_deleted' => 0
                        );
                        $theme_id   = Theme::where($cond_theme)->first();
                        if (empty($theme_id)) {
                            return back()->with('msg_fail', 'Row # ' . $row_num . ', Theme ' . $csv_ct['theme_name'] . ' not exist in the system.');
                            break;
                        }


                        $verify_array = array(
                            'subject_id'     => $subject_id->id,
                            'type_id'        => $type,
                            'section_id'     => $topic_id->id,
                            'topic_id'       => $sub_topic_id,
                            'theme_id'       => $theme_id->id,
                            'is_deleted'     => 0,
                            'sub_theme_name' => strtolower(trim($csv_ct['sub_theme_name']))
                        );
                        /*-- 8: CHECK IF SUB Theme EXIST IN THE SYSTEM. -- */
                        if (!SubTheme::where($verify_array)->first()) {
                            $csv_data[] = $verify_array;
                        }
                    }
                } else {
                    return back()->with('msg_fail', 'File is empty.');
                }
                if (!empty($csv_data) && count($csv_data) > 0) {
                    foreach ($csv_data as $row) {
                        if (!SubTheme::where($row)->first()) {
                            $subtheme                 = new SubTheme();
                            $subtheme->subject_id     = $row['subject_id'];
                            $subtheme->type_id        = $row['type_id'];
                            $subtheme->section_id     = $row['section_id'];
                            $subtheme->topic_id       = $row['topic_id'];
                            $subtheme->theme_id       = $row['theme_id'];
                            $subtheme->sub_theme_name = strtolower(trim($row['sub_theme_name']));
                            $subtheme->created_by     = session('user_info')['admin_id'];
                            $subtheme->updated_by     = 0;
                            $subtheme->save();
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
