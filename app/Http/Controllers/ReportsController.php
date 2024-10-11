<?php

namespace App\Http\Controllers;

use App\Question;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{


    public function index()
    {
        $userData           = Auth::user();
        $data['page_title'] = 'Reports';
        $filter             = array(
            'type_id'       => '',
            'subject'       => '',
            'topic'         => '',
            'sub_topic'     => '',
            'prof_exam'     => '',
            'question_type' => '',
            'diff_level'    => '',
            'groupBy'       => '',

        );
        $where              = array();
        if ($userData->role_id == 3) {
            $where['questions.user_id'] = $userData->id;
        }
        $where['questions.is_deleted'] = 0;
        $data['result']                = Question::getReports($where, 'professional.id');
        $data['filter']                = $filter;
        return view('reports.index', $data);
    }

    public function getFilteredData(Request $request)
    {
        $userData = Auth::user();
        $where    = array();
        $type_id  = $subject = $topic = $sub_topic = $prof = $qtype = $diff_level = $groupBy = '';
        $filter   = array(
            'type_id'       => '',
            'subject'       => '',
            'topic'         => '',
            'sub_topic'     => '',
            'prof_exam'     => '',
            'question_type' => '',
            'diff_level'    => '',
            'groupBy'       => '',

        );

        if (!empty($request->input('type_id')) && $request->input('type_id') > 0) {
            $where['questions.type_id'] = (int)$request->post('type_id');
            $filter['type_id']          = $request->post('type_id');
        }
        if (!empty($request->input('subject')) && $request->input('subject') > 0) {
            $where['questions.subject_id'] = (int)$request->get('subject');
            $filter['subject']             = $request->get('subject');
        }
        if (!empty($request->input('topic')) && $request->input('topic') > 0) {
            $where['questions.section_id'] = (int)$request->get('topic');
            $filter['topic']               = $request->get('topic');
        }
        if (!empty($request->input('sub_topic')) && $request->input('sub_topic') > 0) {
            $where['questions.topic_id'] = (int)$request->get('sub_topic');
            $filter['sub_topic']         = $request->get('sub_topic');
        }
        if (!empty($request->input('prof_exam')) && $request->input('prof_exam') > 0) {
            $where['professional_exam.id'] = (int)$request->get('prof_exam');
            $filter['prof_exam']           = $request->get('prof_exam');
        }
        if (!empty($request->input('question_type')) && $request->input('question_type') > 0) {
            $where['questions.qtype_id'] = $request->get('question_type');
            $filter['question_type']     = $request->get('question_type');
        }
        if (!empty($request->input('diff_level')) && $request->input('diff_level') > 0) {
            $where['questions.diff_id'] = (int)$request->get('diff_level');
            $filter['diff_level']       = $request->get('diff_level');
        }
        if (!empty($request->input('groupBy'))) {
            $groupBy           = $request->get('groupBy');
            $filter['groupBy'] = $request->get('groupBy');
        }

        if ($userData->role_id == 3) {
            $where['questions.user_id'] = $userData->id;
        }
        $where['questions.is_deleted'] = 0;
        //$where['questions.current_state']   = 4;
        //$where['questions.question_status'] = 3;
        //$where['questions.status']          = 'Yes';
        //dd($where);
        $data['result'] = Question::getReports($where, $groupBy);
        //dd($data['result']);
        $data['filter'] = $filter;
        return view('reports.index', $data);

    }

    /*__________________ALL Teacher Stats_____________________*/
    public function teacherReport()
    {
        $userData           = Auth::user();
        $data['page_title'] = 'Teacher Reports';
        $filter             = array(
            'type_id' => '',
            'subject' => '',
        );
        $where              = array();
        if ($userData->role_id == 3) {
            $where['questions.user_id'] = $userData->id;
        }
        $data['created'] = Question::getTeacherCreatedQuestions();
        $data['assign']  = Question::getTeacherReviewedQuestions();
       //dd($data['assign']);
        $data['filter']  = $filter;
        return view('reports.teacher.index', $data);
    }

    /*________________ALL Teacher Stats BY Fillter______________*/
    public function getTeacherReportFilteredData(Request $request)
    {
        $userData = Auth::user();
        $where    = array();
        $type_id  = $subject_id = NULL;
        $filter   = array(
            'type_id' => '',
            'subject' => '',
        );
        if (!empty($request->input('type_id')) && $request->input('type_id') > 0) {
            $type_id           = (int)$request->post('type_id');
            $filter['type_id'] = $request->post('type_id');
        }
        if (!empty($request->input('subject')) && $request->input('subject') > 0) {
            $subject_id        = (int)$request->get('subject');
            $filter['subject'] = $request->get('subject');
        }

        $data['created'] = Question::getTeacherCreatedQuestions($type_id, $subject_id);
        //dd($data['created']);
        $data['assign'] = Question::getTeacherReviewedQuestions($type_id, $subject_id);
        $data['filter'] = $filter;
        return view('reports.teacher.index', $data);

    }

    /*__________________Single Teacher Stats_____________________*/
    public function teacherSubjectReport($id)
    {

        if (!empty($id)) {
            $id = (int)base64url_decode($id);
        } else {
            return back()->with('msg_fail', 'Invalid ID');
        }

        $data['page_title'] = 'Subject Report';
        $CurrentUser        = Auth::user();
        if ($CurrentUser->role_id == 3) {
            $id = $CurrentUser->id;
        }
        $data['user']   = User::find($id);
        $data['result'] = Question::getTeacherSubjectReport($id);
        return view('reports.teacher.subject', $data);
    }
}
