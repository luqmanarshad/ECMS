<?php

namespace App\Http\Controllers;

use App\Book;
use App\Rules\CheckSubject;
use App\Rules\CheckSubjectSingleQuote;
use App\Section;
use App\Subject;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\CommonImport;
use Maatwebsite\Excel\Facades\Excel;


class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:Admin']);
    }

    public function index()
    {
        $data['page_title'] = 'Subject List';
        $data['results']    = Subject::where('status', 1)->orderBy('id', 'DESC')->get();

        return view('subject.index', $data);
    }

    public function create()
    {
        $data['page_title'] = 'Add Subject';
        return view('subject.add', $data);
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'subject_name' => ['required','string','min:3','max:250', new CheckSubject($request->all())],
            'type_id'      => 'required'
        ]);
        $subject               = new Subject();
        $subject->subject_name = strtolower(trim($request->input('subject_name')));
        $subject->type_id      = $request->input('type_id');
        $subject->save();
        return redirect('admin/subject')->with('msg_success', 'Subject created successfully.');
    }

    public function edit($id)
    {
        $id                 = (int)base64url_decode($id);
        $data['page_title'] = 'Edit Subject';
        $row                = Subject::findOrFail($id);
        if ($row) {
            $data['row'] = $row;
            return view('subject.edit', $data);
        } else {
            return back()->with('msg_fail', 'No Record found against Id.');
        }
    }

    public function update(Request $request, $id)
    {
        $id      = base64url_decode($id);
        $subject = Subject::findOrFail($id);
        $this->validate($request, [
            'subject_name' => ['required','string','min:3','max:250', new CheckSubject($request->all())],
            'type_id'      => 'required'
        ]);
        $subject->subject_name = strtolower(trim($request->input('subject_name')));
        $subject->type_id      = $request->input('type_id');
        $subject->save();
        return redirect('admin/subject')->with('msg_success', 'Subject update successfully');
    }

    public function destroy($id)
    {
        $id      = base64url_decode($id);
        $subject = Subject::findOrFail($id);

        if (Section::checkSection($id)) {
            return back()->with('msg_warning', 'This Subject have Sections, cannot delete it.');
        }
        if (User::checkTeachers($id)) {
            return back()->with('msg_warning', 'This Subject have Teachers, cannot delete it.');
        }

        if (Book::where(['subject_id' => $id, 'status' => 1])->first()) {
            return back()->with('msg_warning', 'This Subject have Books, cannot delete it.');
        }

        $subject->status = 0;
        $subject->save();
        return back()->with('msg_success', 'Subject deleted successfully');
        //
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
                $keys  = array('subject_name', 'type_id');
                $final = array();
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
                                }
                                if(strlen($validString) > 250){
                                    return back()->with('msg_fail', 'Row # ' . $row_num . ', Max 250 character allowed in ' .str_replace('_',' ',$key)  );
                                    break;
                                }

                                if(str_contains($validString,'<')){
                                    return back()->with('msg_fail', 'Row # ' . $row_num . ', Symbol <  Not allowed in  ' .str_replace('_',' ',$key)  );
                                    break;
                                }

                                if(empty(trim($csv_ct['subject_name']))){
                                    return back()->with('msg_fail', 'Row # ' . $row_num . ', Subject Name cannot be empty.');
                                    break;
                                }

                            }

                            if (strtolower(trim($csv_ct['type_id'])) == 'mbbs') {
                                $type = 2;
                            } elseif (strtolower(trim($csv_ct['type_id'])) == 'bsc nursing') {
                                $type = 1;
                            } elseif (strtolower(trim($csv_ct['type_id'])) == '') {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Type cannot be empty.');
                                break;
                            }else {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Type ' . $csv_ct['type_id'] . ' not exist in the system.');
                                break;
                            }

                            if (!empty($csv_ct['subject_name'])) {
                                if (!Subject::where(['subject_name' => strtolower(trim($csv_ct['subject_name'])), 'type_id' => $type])->first()) {
                                    $subject_name = trim($csv_ct['subject_name']);
                                    $type_id      = $type;
                                    if (!empty($subject_name) && !empty($type_id)) {
                                        $csv_data[] = array(
                                            'subject_name' => strtolower(trim($csv_ct['subject_name'])),
                                            'type_id'      => $type
                                        );
                                    }
                                }
                            } else {
                                return back()->with('msg_fail', 'Row # ' . $row_num . ', Subject Name cannot be empty.');
                                break;
                            }
                        }
                    }else{
                        return back()->with('msg_fail', 'File is Empty.');
                    }
                    if (!empty($csv_data) && count($csv_data) > 0) {
                        foreach ($csv_data as $row) {
                            if (!Subject::where(['subject_name' => strtolower(trim($row['subject_name'])), 'type_id' => $row['type_id']])->first()) {
                                $subject               = new Subject();
                                $subject->subject_name = strtolower(trim($row['subject_name']));
                                $subject->type_id      = $row['type_id'];
                                $subject->save();
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
