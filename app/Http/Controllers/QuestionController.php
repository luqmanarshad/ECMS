<?php

namespace App\Http\Controllers;

use App\Book;
use App\Cognitive_level;
use App\Exports\QuestionExport;
use App\Exports\TempExport;
use App\MCQImport;
use App\MCQImportLogs;
use App\Professional;
use App\ProfessionlExam;
use App\Question;
use App\Question_diff;
use App\QuestionSave;
use App\Relevance;
use App\Section;
use App\SeqQuestion;
use App\SeqQuestionSave;
use App\Subject;
use App\Topic;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CommonImport;

class QuestionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($tab = '')
    {

        $data['page_title']        = 'Setting';
        $rejected                  = array(
            'is_deleted'      => 0,
            'question_status' => 2,
            'current_state'   => 3,
            'status'          => 'No',
            'assign_to'       => session('user_info')['admin_id'],
            'user_id'         => session('user_info')['admin_id']
        );
        $data['question_rejected'] = Question::where($rejected)->get();
        $data['question_saved']    = QuestionSave::where(['is_deleted' => 0, 'status' => 'No', 'user_id' => session('user_info')['admin_id']])->get();
        if ($tab == 'index' || $tab == '') {
            $data['active_tab'] = 'saved';
        } else {
            $data['active_tab'] = $tab;
        }


        return view('question.index', $data);
    }

    public function create($type)
    {
        $data['page_title']   = 'Add Question';
        $data['qustion_diff'] = Question_diff::all();
        $data['years'] = range(2016, date('Y'));

        if ($type == 'mcq')
            return view('question.add_mcq', $data);
        elseif ($type == 'seq')
            return view('question.add_seq', $data);
    }

    public function store(Request $request)
    {
        $question_type = $request->input('question_type');
        $this->validate($request, [
            'cognitive_level'  => 'required',
            'relevance'        => 'required',
            'subject_id'       => 'required',
            'section_id'       => 'required',
            'diff_id'          => 'required',
            'book_reference[]' => 'array',
        ]);

        if ($question_type == 1) {

            $this->validate($request, [
                'topic_id'       => 'required',
                'question_marks' => 'required',
                'mcq_question'   => 'required',
                'answer[]'       => 'array',
                'option1'        => 'required',
                'option2'        => 'required',
                'option3'        => 'required',
                'option4'        => 'required',
                'option5'        => 'required',
            ]
        );

        } elseif ($question_type == 2) {
            $this->validate($request, [
                'topic_id[]' => 'array',
                'marks[]'    => 'array',
            ]
        );
        }

        $random_num                    = $request->input('random_num');
        $post_data                     = $request->all();
        $program_type                  = Subject::find($post_data['subject_id']);
        $question_data                 = new Question();
        $book_reference                = implode(',', $post_data['book_reference']);
        $question_data->unique_id      = mt_rand(100000, 999999);
        $question_data->qtype_id       = $question_type;
        $question_data->subject_id     = $post_data['subject_id'];
        $question_data->section_id     = $post_data['section_id'];
        $question_data->diff_id        = $post_data['diff_id'];
        $question_data->book_reference = $book_reference;
        $question_data->chapter_no     = $post_data['chapter_no'];
        $question_data->year           = $post_data['year'];
        $question_data->status         = 'No';
        $question_data->user_id        = session('user_info')['admin_id'];
        $type_id                       = $program_type->type_id;
        $question_data->type_id        = $program_type->type_id;
        $subject_id                    = $post_data['subject_id'];
        if ($request->input('is_scenario_question')) {
            $question_data->is_scenario_question = 1;
        } else {
            $question_data->is_scenario_question = 0;
        }

        /* -- New Fields -- */
        $question_data->cognitive_level = $request->input('cognitive_level');
        $question_data->relevance       = $request->input('relevance');
        $question_data->theme_id        = $request->input('theme_id');
        $question_data->sub_theme_id    = $request->input('sub_theme_id');
        $question_data->objective       = $request->input('objective');
        $question_data->keywords        = $request->input('keywords');

        /*------------------Type MCQ's---------------------*/
        if ($question_type == 1) {
            $qtype                   = 'mcq';
            $question_data->question = option_encrypt($post_data['mcq_question']);
            $question_data->option1  = option_encrypt($post_data['option1']);
            $question_data->option2  = option_encrypt($post_data['option2']);
            $question_data->option3  = option_encrypt($post_data['option3']);
            $question_data->option4  = option_encrypt($post_data['option4']);
            $question_data->option5  = option_encrypt($post_data['option5']);

            if (is_array($post_data['answer']))
                $question_data->answer = implode(",", $post_data['answer']);
            $question_data->topic_id           = $post_data['topic_id'];
            $question_data->page_no            = $post_data['page_no'];
            $question_data->chapter_no         = $post_data['chapter_no'];
            $question_data->is_marker_question = $post_data['is_marker_question'];
            $question_data->marks              = $post_data['question_marks'];


        } elseif ($question_type == 2) {
            $qtype = 'seq';
            /*------------------Type SEQ's---------------------*/
            $statement = array();
            if (!empty($post_data['seq_question']) && count($post_data['seq_question']) > 0) {
                $length = sizeof($post_data['seq_question']);
                for ($s = 0; $s < $length; $s++) {
                    /*----Statement Level Question ------*/

                    /*-----is Scenario question*/
                    if (($question_data->is_scenario_question == 1) && ($s == 0)) {
                        $ans = '';
                    } else {
                        $ans = $post_data['seq_answer'][$s];
                    }

                    $statement[$s] = array(
                        'question'   => option_encrypt($post_data['seq_question'][$s]),
                        'answer'     => option_encrypt($ans),
                        'topic_id'   => $post_data['topic_id'][$s],
                        'marks'      => $post_data['marks'][$s],
                        'page_no'    => $post_data['page_no'][$s],
                        'has_parent' => 0,
                        'child_no'   => 0,
                        'child_list' => array()

                    );

                    /*----Sub Statement Level Question ------*/
                    if (!empty($post_data['seq_child_question'][$s])) {
                        $lengthOfChild = sizeof($post_data['seq_child_question'][$s]);
                        for ($c = 0; $c < $lengthOfChild; $c++)
                            $statement[$s]['child_list'][$c] = array(
                                'question'   => option_encrypt($post_data['seq_child_question'][$s][$c]),
                                'answer'     => option_encrypt($post_data['seq_child_answer'][$s][$c]),
                                'topic_id'   => $post_data['child_topic'][$s][$c],
                                'marks'      => $post_data['child'][$s][$c],
                                'page_no'    => $post_data['child_page_no'][$s][$c],
                                'has_parent' => 1,
                                'child_no'   => ($c + 1),
                            );
                    }

                }
            }
            $question_data->topic_id = $post_data['topic_id'][0];
        }

        //Delete Save Question if any available in the question_save table
        $duplicate_id = $request->input('duplicate_id');

        if (!is_null($duplicate_id)) {
            DB::table('questions_save')->where(['id' => $duplicate_id])->update(['status' => 'Yes', 'is_deleted' => 1]);
        }

        $user_id  = session('user_info')['admin_id'];
        $teachers = User::getTeacherForAssignQuestion($subject_id, $type_id, $user_id, $user_id);
        if (empty($teachers)) {
            $question_data->save();
            return back()->with('msg_success', 'Successfully Submitted');

        } else {
            $random_teacher_id                   = $teachers->id;//$this->checkQuota($type_id, $subject_id);
            $question_data->assign_to            = $random_teacher_id;
            $question_data->teacher1_id          = $random_teacher_id;
            $question_data->teacher1_assign_date = date('Y-m-d H:i:s');
            $question_data->chapter_no           = $post_data['chapter_no'];
            $question_data->save();
            $question_id = $question_data->id;
            if (!empty($question_id)) {
                if ($question_type == 2) {
                    if (!empty($statement) && count($statement) > 0) {
                        foreach ($statement as $row) {
                            $seqQuestion              = new SeqQuestion();
                            $seqQuestion->question    = $row['question'];
                            $seqQuestion->answer      = $row['answer'];
                            $seqQuestion->topic_id    = $row['topic_id'];
                            $seqQuestion->marks       = $row['marks'];
                            $seqQuestion->page_no     = $row['page_no'];
                            $seqQuestion->has_parent  = $row['has_parent'];
                            $seqQuestion->child_no    = $row['child_no'];
                            $seqQuestion->question_id = $question_id;
                            $seqQuestion->group_id    = null;
                            $has_parent               = $row['has_parent'];
                            $child_list               = $row['child_list'];
                            unset($row['child_list']);
                            $seqQuestion->save();
                            $master_question = $seqQuestion->id;

                            if ((!is_null($child_list)) && (count($child_list) > 0) && ($has_parent == 0) && (!empty($master_question))) {
                                foreach ($child_list as $c_list) {
                                    $childSeqQuestion              = new SeqQuestion();
                                    $childSeqQuestion->question    = $c_list['question'];
                                    $childSeqQuestion->answer      = $c_list['answer'];
                                    $childSeqQuestion->topic_id    = $c_list['topic_id'];
                                    $childSeqQuestion->marks       = $c_list['marks'];
                                    $childSeqQuestion->page_no     = $c_list['page_no'];
                                    $childSeqQuestion->has_parent  = $c_list['has_parent'];
                                    $childSeqQuestion->child_no    = $c_list['child_no'];
                                    $childSeqQuestion->group_id    = $master_question;
                                    $childSeqQuestion->question_id = $question_id;
                                    $childSeqQuestion->save();
                                }
                            }
                        }

                    }
                }

                $mobileResult = User::find($random_teacher_id);
                /*------------ Send Sms start -----------*/
                $msgAssign   = 'Question has been assigned to you for review.';
                $sms_setting = session('user_info')['setting'];
                if ($sms_setting->assign_question == 1) {
                    if (!empty($mobileResult->contact_no)) {
                        sendMessage($mobileResult->contact_no, $msgAssign);
                    }
                }


                /*-----------Send Email ---------------*/
                if ($sms_setting->email_assign_question == 1) {
                    if (!empty($mobileResult->email)) {
                        // sendEmail($mobileResult->email, 'Question Assigned For Review', $msgAssign);
                    }
                }
                return redirect('user/question/add/' . $qtype)->with('msg_success', 'Successfully Submitted.');
            }

        }//end else part

    }//End of function

    /*EDIT "INCORRECT" QUESTION & SUBMIT IT, TO FIRST REVIEWER. */
    public function edit($qtype, $id)
    {
        $data['page_title']   = "Edit Question";
        $data['qustion_diff'] = Question_diff::all();
        $data['years'] = range(2016, date('Y'));

        if (strtolower($qtype) === 'mcq') {
            $file_name = 'edit_mcq';
        } elseif (strtolower($qtype) === 'seq') {
            $file_name = 'edit_seq';
        }
        $id       = base64url_decode($id);
        $question = Question::findOrFail($id);
        if (!$id) {
            return back()->with('msg_fail', 'Invalid Id. No record available against id');
        }
        $data['id']       = $id;
        $data['question'] = $question;
        return view('question.' . $file_name, $data);
    }

    public function update($qtype, $id, Request $request)
    {
        $id = base64url_decode($id);
        if (!$id) {
            return redirect('user/question')->with('msg_fail', 'Invalid Id. No record available against id');
        }

        $question_data = Question::findOrFail($id);
        $question_type = $request->input('question_type');
        $this->validate($request, [
            'cognitive_level'  => 'required',
            'relevance'        => 'required',
            'subject_id'       => 'required',
            'section_id'       => 'required',
            'diff_id'          => 'required',
            'book_reference[]' => 'array',
        ]);
        if ($question_type == 1) {

            $this->validate($request, [
                'topic_id'       => 'required',
                'question_marks' => 'required',
                'mcq_question'   => 'required',
                'answer[]'       => 'array',
                'option1'        => 'required',
                'option2'        => 'required',
                'option3'        => 'required',
                'option4'        => 'required',
                'option5'        => 'required',
            ]
        );

        } elseif ($question_type == 2) {
            $this->validate($request, [
                'topic_id[]' => 'array',
                'marks[]'    => 'array',
            ]
        );
        }

        $post_data = $request->all();

        $book_reference                      = implode(',', $post_data['book_reference']);
        $question_data->section_id           = $post_data['section_id'];
        $question_data->diff_id              = $post_data['diff_id'];
        $question_data->book_reference       = $book_reference;
        $question_data->chapter_no           = $post_data['chapter_no'];
        $question_data->year                 = $post_data['year'];
        $question_data->status               = 'No';
        $question_data->user_id              = session('user_info')['admin_id'];
        $question_data->author_comment       = addslashes($post_data['author_comment']);
        $question_data->question_status      = 2;
        $question_data->status               = 'No';
        $question_data->current_state        = 3;
        $question_data->assign_to            = $question_data->teacher1_id;
        $question_data->teacher3_id          = $question_data->teacher1_id;
        $question_data->teacher3_assign_date = date('Y-m-d H:i:s');

        if ($request->input('is_scenario_question')) {
            $question_data->is_scenario_question = 1;
        } else {
            $question_data->is_scenario_question = 0;
        }
        /* -- New Fields -- */
        $question_data->cognitive_level = $request->input('cognitive_level');
        $question_data->relevance       = $request->input('relevance');
        $question_data->theme_id        = $request->input('theme_id');
        $question_data->sub_theme_id    = $request->input('sub_theme_id');
        $question_data->objective       = $request->input('objective');
        $question_data->keywords        = $request->input('keywords');

        /*------------------Type MCQ's---------------------*/
        if ($question_type == 1) {

            $question_data->question = option_encrypt($post_data['mcq_question']);
            $question_data->option1  = option_encrypt($post_data['option1']);
            $question_data->option2  = option_encrypt($post_data['option2']);
            $question_data->option3  = option_encrypt($post_data['option3']);
            $question_data->option4  = option_encrypt($post_data['option4']);
            $question_data->option5  = option_encrypt($post_data['option5']);

            if (is_array($post_data['answer']))
                $question_data->answer = implode(",", $post_data['answer']);
            $question_data->topic_id = $post_data['topic_id'];
            $question_data->page_no  = $post_data['page_no'];

            /*------------------Type SEQ's---------------------*/
        } elseif ($question_type == 2) {
            $statement = array();
            if (!empty($post_data['seq_question']) && count($post_data['seq_question']) > 0) {
                $length = sizeof($post_data['seq_question']);
                for ($s = 0; $s < $length; $s++) {

                    /*-----is Scenario question*/
                    if (($question_data->is_scenario_question == 1) && ($s == 0)) {
                        $ans = '';
                    } else {
                        $ans = $post_data['seq_answer'][$s];
                    }
                    /*----Statement Level Question ------*/
                    $statement[$s] = array(
                        'question'   => option_encrypt($post_data['seq_question'][$s]),
                        'answer'     => option_encrypt($ans),
                        'topic_id'   => $post_data['topic_id'][$s],
                        'marks'      => $post_data['marks'][$s],
                        'page_no'    => $post_data['page_no'][$s],
                        'has_parent' => 0,
                        'child_no'   => 0,
                        'child_list' => array()

                    );

                    /*----Sub Statement Level Question ------*/
                    if (!empty($post_data['seq_child_question'][$s])) {
                        $lengthOfChild = sizeof($post_data['seq_child_question'][$s]);
                        for ($c = 0; $c < $lengthOfChild; $c++)
                            $statement[$s]['child_list'][$c] = array(
                                'question'   => option_encrypt($post_data['seq_child_question'][$s][$c]),
                                'answer'     => option_encrypt($post_data['seq_child_answer'][$s][$c]),
                                'topic_id'   => $post_data['child_topic'][$s][$c],
                                'marks'      => $post_data['child'][$s][$c],
                                'page_no'    => $post_data['child_page_no'][$s][$c],
                                'has_parent' => 1,
                                'child_no'   => ($c + 1),
                            );
                    }

                }
            }
            $question_data->topic_id = $post_data['topic_id'][0];
        }

        /*update question table*/
        $question_data->save();
        if ($question_data->id) {
            if ($question_type == 2) {
                if (!empty($statement) && count($statement) > 0) {
                    SeqQuestion::where(['question_id' => $id])->update(['is_deleted' => 1]);
                    foreach ($statement as $row) {
                        $seqQuestion              = new SeqQuestion();
                        $seqQuestion->question    = $row['question'];
                        $seqQuestion->answer      = $row['answer'];
                        $seqQuestion->topic_id    = $row['topic_id'];
                        $seqQuestion->marks       = $row['marks'];
                        $seqQuestion->page_no     = $row['page_no'];
                        $seqQuestion->has_parent  = $row['has_parent'];
                        $seqQuestion->child_no    = $row['child_no'];
                        $seqQuestion->question_id = $id;
                        $seqQuestion->group_id    = null;
                        $has_parent               = $row['has_parent'];
                        $child_list               = $row['child_list'];
                        unset($row['child_list']);
                        $seqQuestion->save();
                        $master_question = $seqQuestion->id;

                        if ((!is_null($child_list)) && (count($child_list) > 0) && ($has_parent == 0) && (!empty($master_question))) {
                            foreach ($child_list as $c_list) {
                                $childSeqQuestion              = new SeqQuestion();
                                $childSeqQuestion->question    = $c_list['question'];
                                $childSeqQuestion->answer      = $c_list['answer'];
                                $childSeqQuestion->topic_id    = $c_list['topic_id'];
                                $childSeqQuestion->marks       = $c_list['marks'];
                                $childSeqQuestion->page_no     = $c_list['page_no'];
                                $childSeqQuestion->has_parent  = $c_list['has_parent'];
                                $childSeqQuestion->child_no    = $c_list['child_no'];
                                $childSeqQuestion->group_id    = $master_question;
                                $childSeqQuestion->question_id = $id;
                                $childSeqQuestion->save();
                            }
                        }
                    }
                }
            }

            /*------------ Send Sms start -----------*/
            $mobileResult = User::where('id', $question_data->teacher1_id)->select('contact_no', 'email')->first();
            $sms_setting  = session('user_info')['setting'];
            $msgAssign    = 'Question has been assigned to you for final review.';
            if ($sms_setting->assign_question == 1) {
                if (!empty($mobileResult->contact_no)) {
                    sendMessage($mobileResult->contact_no, $msgAssign);
                }
            }
            return redirect('user/question')->with('msg_success', 'Successfully updated.');

        } else {
            return back()->with('msg_fail', 'Cannot be updated, Something Wrong');
        }


    }


    /*###########################################################################*/
    /*####################  Save Question               #########################*/
    /*###########################################################################*/

    /*SAVE QUESTION IN save_questions table */
    public function add_duplicate($qtype, $index_id = FALSE, Request $request)
    {

        $user                 = array();
        $data['page_title']   = "Add Question";
        $data['qustion_diff'] = Question_diff::all();
        $random_num           = $request->input('random_num');
        if (empty($random_num)) {
            $random_num = strtotime(date('Y-m-d H:i:s'));
        }

        $post_data = $request->all();
        if (!empty($request->input('book_reference'))) {
            $book_reference = implode(',', $post_data['book_reference']);
        } else {
            $book_reference = '';
        }
        $question_data                 = new QuestionSave();
        $program_type                  = Subject::find($post_data['subject_id']);
        $question_type                 = $request->input('question_type');
        $question_data->unique_id      = $random_num;
        $question_data->qtype_id       = $question_type;
        $question_data->subject_id     = $post_data['subject_id'];
        $question_data->section_id     = $post_data['section_id'];
        $question_data->diff_id        = $post_data['diff_id'];
        $question_data->book_reference = $book_reference;
        $question_data->chapter_no     = $post_data['chapter_no'];
        $question_data->year           = $post_data['year'];
        $question_data->status         = 'No';
        $question_data->user_id        = session('user_info')['admin_id'];
        $question_data->type_id        = $program_type->type_id;

        if ($request->input('is_scenario_question')) {
            $question_data->is_scenario_question = 1;
        } else {
            $question_data->is_scenario_question = 0;
        }

        /* -- New Fields -- */
        $question_data->cognitive_level = $request->input('cognitive_level');
        $question_data->relevance       = $request->input('relevance');
        $question_data->theme_id        = $request->input('theme_id');
        $question_data->sub_theme_id    = $request->input('sub_theme_id');
        $question_data->objective       = $request->input('objective');
        $question_data->keywords        = $request->input('keywords');

        /*------------------Type MCQ's---------------------*/
        if ($question_type == 1) {

            $question_data->question = option_encrypt($post_data['mcq_question']);
            $question_data->option1  = option_encrypt($post_data['option1']);
            $question_data->option2  = option_encrypt($post_data['option2']);
            $question_data->option3  = option_encrypt($post_data['option3']);
            $question_data->option4  = option_encrypt($post_data['option4']);
            $question_data->option5  = option_encrypt($post_data['option5']);

            if (is_array($post_data['answer']))
                $question_data->answer = implode(",", $post_data['answer']);
            $question_data->topic_id           = $post_data['topic_id'];
            $question_data->page_no            = $post_data['page_no'];
            $question_data->is_marker_question = $post_data['is_marker_question'];
            $question_data->marks              = $post_data['question_marks'];

            /*------------------Type SEQ's---------------------*/
        } elseif ($question_type == 2) {
            $statement = array();
            if (!empty($post_data['seq_question']) && count($post_data['seq_question']) > 0) {
                $length = sizeof($post_data['seq_question']);
                for ($s = 0; $s < $length; $s++) {
                    /*----Statement Level Question ------*/

                    /*-----is Scenario question*/
                    if (($question_data->is_scenario_question == 1) && ($s == 0)) {
                        $ans = '';
                    } else {
                        $ans = $post_data['seq_answer'][$s];
                    }

                    $statement[$s] = array(
                        'question'   => option_encrypt($post_data['seq_question'][$s]),
                        'answer'     => option_encrypt($ans),
                        'topic_id'   => $post_data['topic_id'][$s],
                        'marks'      => $post_data['marks'][$s],
                        'page_no'    => $post_data['page_no'][$s],
                        'has_parent' => 0,
                        'child_no'   => 0,
                        'child_list' => array()

                    );

                    /*----Sub Statement Level Question ------*/
                    if (!empty($post_data['seq_child_question'][$s])) {
                        $lengthOfChild = sizeof($post_data['seq_child_question'][$s]);
                        for ($c = 0; $c < $lengthOfChild; $c++)
                            $statement[$s]['child_list'][$c] = array(
                                'question'   => option_encrypt($post_data['seq_child_question'][$s][$c]),
                                'answer'     => option_encrypt($post_data['seq_child_answer'][$s][$c]),
                                'topic_id'   => $post_data['child_topic'][$s][$c],
                                'marks'      => $post_data['child'][$s][$c],
                                'page_no'    => $post_data['child_page_no'][$s][$c],
                                'has_parent' => 1,
                                'child_no'   => ($c + 1),
                            );
                    }

                }
            }
            $question_data->topic_id = $post_data['topic_id'][0];
        }

        $unique_id = QuestionSave::where(['unique_id' => $random_num])->first();
        if (!empty($unique_id->unique_id)) {
            unset($question_data->unique_id);
            $question_id = $unique_id->id;
            if ($unique_id->unique_id == $random_num) {
                QuestionSave::where(['unique_id' => $random_num])->update((array)$question_data);
            }
        } else {
            $question_data->unique_id = $random_num;
            $question_data->save();
            $question_id = $question_data->id;
        }


        /*----if SEQ Save then  ----*/
        if (!empty($question_id)) {
            if ($question_type == 2) {
                if (!empty($statement) && count($statement) > 0) {
                    foreach ($statement as $row) {
                        $row['question_id'] = $question_id;
                        $row['group_id']    = null;
                        $has_parent         = $row['has_parent'];
                        $child_list         = $row['child_list'];
                        unset($row['child_list']);
                        $master_question = SeqQuestionSave::create($row);
                        if ((!empty($child_list)) && (count($child_list) > 0) && ($has_parent == 0) && (!empty($master_question))) {
                            foreach ($child_list as $c_list) {
                                $c_list['group_id']    = $master_question->id;
                                $c_list['question_id'] = $question_id;
                                SeqQuestionSave::create($c_list);
                            }
                        }

                    }
                }
            }

        }

        if ($index_id) {
            return redirect('user/question/index')->with('msg_success', 'Successfully Saved.');
        } else {
            return redirect('user/question/add/' . $qtype)->with('msg_success', 'Successfully Saved.');
        }
    }

    /*EDIT SAVED QUESTION */
    public function edit_duplicate($qtype, $id = FALSE, $check = FALSE)
    {

        $id                   = base64url_decode($id);
        $question             = QuestionSave::findOrFail($id);
        $data['question']     = $question;
        $data['page_title']   = 'Edit Question';
        $data['qustion_diff'] = Question_diff::all();
        $data['years'] = range(2016, date('Y'));

        if (strtolower($qtype) === 'mcq') {
            return view('question.save.edit_duplicate_mcq', $data);
        } elseif (strtolower($qtype) === 'seq') {
            return view('question.save.edit_duplicate_seq', $data);
        }
    }

    /*Store SAVED QUESTION */
    public function store_duplicate($qtype, $id = FALSE, $check = FALSE, Request $request)
    {
        $id            = base64url_decode($id);
        $question_data = QuestionSave::findOrFail($id);

        $random_num = $request->input('random_num');
        if (empty($random_num)) {
            $random_num = strtotime(date('Y-m-d H:i:s'));
        }

        $post_data = $request->all();

        $program_type  = Subject::find($post_data['subject_id']);
        $question_type = $request->input('question_type');
        if ($request->input('book_reference')) {
            $book_reference = implode(',', $post_data['book_reference']);
        } else {
            $book_reference = '';
        }
        $question_data->unique_id      = $random_num;
        $question_data->qtype_id       = $question_type;
        $question_data->subject_id     = $post_data['subject_id'];
        $question_data->section_id     = $post_data['section_id'];
        $question_data->diff_id        = $post_data['diff_id'];
        $question_data->book_reference = $book_reference;
        $question_data->chapter_no     = $post_data['chapter_no'];
        $question_data->year           = $post_data['year'];
        $question_data->status         = 'No';
        $question_data->user_id        = session('user_info')['admin_id'];
        $question_data->type_id        = $program_type->type_id;

        if ($request->input('is_scenario_question')) {
            $question_data->is_scenario_question = 1;
        } else {
            $question_data->is_scenario_question = 0;
        }

        /* -- New Fields -- */
        $question_data->cognitive_level = $request->input('cognitive_level');
        $question_data->relevance       = $request->input('relevance');
        $question_data->theme_id        = $request->input('theme_id');
        $question_data->sub_theme_id    = $request->input('sub_theme_id');
        $question_data->objective       = $request->input('objective');
        $question_data->keywords        = $request->input('keywords');


        /*------------------Type MCQ's---------------------*/
        if ($question_type == 1) {

            $question_data->question = option_encrypt($post_data['mcq_question']);
            $question_data->option1  = option_encrypt($post_data['option1']);
            $question_data->option2  = option_encrypt($post_data['option2']);
            $question_data->option3  = option_encrypt($post_data['option3']);
            $question_data->option4  = option_encrypt($post_data['option4']);
            $question_data->option5  = option_encrypt($post_data['option5']);

            if (is_array($post_data['answer']))
                $question_data->answer = implode(",", $post_data['answer']);
            $question_data->topic_id           = $post_data['topic_id'];
            $question_data->page_no            = $post_data['page_no'];
            $question_data->is_marker_question = $post_data['is_marker_question'];
            $question_data->marks              = $post_data['question_marks'];

            /*------------------Type SEQ's---------------------*/
        } elseif ($question_type == 2) {
            $statement = array();
            if (!empty($post_data['seq_question']) && count($post_data['seq_question']) > 0) {
                $length = sizeof($post_data['seq_question']);
                for ($s = 0; $s < $length; $s++) {

                    /*-----is Scenario question*/
                    if (($question_data->is_scenario_question == 1) && ($s == 0)) {
                        $ans = '';
                    } else {
                        $ans = $post_data['seq_answer'][$s];
                    }
                    /*----Statement Level Question ------*/
                    $statement[$s] = array(
                        'question'   => option_encrypt($post_data['seq_question'][$s]),
                        'answer'     => option_encrypt($ans),
                        'topic_id'   => $post_data['topic_id'][$s],
                        'marks'      => $post_data['marks'][$s],
                        'page_no'    => $post_data['page_no'][$s],
                        'has_parent' => 0,
                        'child_no'   => 0,
                        'child_list' => array()

                    );

                    /*----Sub Statement Level Question ------*/
                    if (!empty($post_data['seq_child_question'][$s])) {
                        $lengthOfChild = sizeof($post_data['seq_child_question'][$s]);
                        for ($c = 0; $c < $lengthOfChild; $c++)
                            $statement[$s]['child_list'][$c] = array(
                                'question'   => option_encrypt($post_data['seq_child_question'][$s][$c]),
                                'answer'     => option_encrypt($post_data['seq_child_answer'][$s][$c]),
                                'topic_id'   => $post_data['child_topic'][$s][$c],
                                'marks'      => $post_data['child'][$s][$c],
                                'page_no'    => $post_data['child_page_no'][$s][$c],
                                'has_parent' => 1,
                                'child_no'   => ($c + 1),
                            );
                    }

                }
            }
            $question_data->topic_id = $post_data['topic_id'][0];

        }


        $unique_id = QuestionSave::where(['unique_id' => $random_num])->first();
        if (!empty($unique_id->unique_id)) {
            unset($question_data->unique_id);
            $question_id = $unique_id->id;
            if (($unique_id->unique_id == $random_num) && ($question_id == $post_data['duplicate_id'])) {
                //QuestionSave::where(['unique_id' => $random_num])->update($question_data);
                $question_data->save();
            }
        } else {
            $question_data->unique_id = $random_num;
            $question_data->save();
            $question_id = $question_data->id;
        }

        if (!empty($question_id)) {
            if ($question_type == 2) {
                if (!empty($statement) && count($statement) > 0) {
                    SeqQuestionSave::where('question_id', $question_id)->delete();
                    foreach ($statement as $row) {
                        $row['question_id'] = $question_id;
                        $row['group_id']    = null;
                        $has_parent         = $row['has_parent'];
                        $child_list         = $row['child_list'];
                        unset($row['child_list']);
                        $master_question = SeqQuestionSave::create($row);
                        if ((!empty($child_list)) && (count($child_list) > 0) && ($has_parent == 0) && (!empty($master_question))) {
                            foreach ($child_list as $c_list) {
                                $c_list['group_id']    = $master_question->id;
                                $c_list['question_id'] = $question_id;
                                SeqQuestionSave::create($c_list);
                            }
                        }

                    }
                }
            }

        }

        if ($check) {
            return redirect('user/question')->with('msg_success', 'Successfully Saved');
        } else {
            return redirect('user/question/add/' . $qtype)->with('msg_success', 'Successfully Saved');
        }

    }

    /*DELETE SAVED QUESTION */
    public function delete_duplicate($id)
    {
        //dd(session('user_info'));
        if (Auth::check()) {
            $id                             = base64url_decode($id);
            $save_question                  = QuestionSave::findOrFail($id);
            $save_question->status          = 'No';
            $save_question->question_status = 4;
            $save_question->is_deleted      = 1;
            $save_question->save();
            return redirect('user/question')->with('msg_success', 'Question successfully deleted.');
        } else {
            redirect('logout');
        }
    }

    /* SHOW INCORRECT QUESTION */
    public function show($qtype, $id)
    {
        //$data['back_to'] =  request()->segment(2);

        $data['page_title'] = "view Question";
        if ($qtype == 1) {
            $file_name = 'view_mcq';
        } elseif ($qtype == 2) {
            $file_name = 'view_seq';
        }

        $data['id']       = '';
        $data['chapters'] = '';

        $id = base64url_decode($id);
        if ($id) {
            $result = Question::findOrFail($id);
            if (!empty($result) && !is_null($result)) {
                $data['result'] = $result;
                return view('review.' . $file_name, $data);
            } else {
                return back()->with('msg_fail', 'Invalid Id. No record available against id');
            }
        } else {
            return back()->with('msg_fail', 'Invalid Id. No record available against id');
        }
    }

    /*DELETE "IN CORRECT" QUESTION. */
    public function delete($id)
    {
        $id                        = base64url_decode($id);
        $question                  = Question::findOrFail($id);
        $question->status          = 'No';
        $question->question_status = 4;
        $question->current_state   = 4;
        $question->is_deleted      = 1;
        $question->save();
        return redirect('user/question')->with('msg_success', 'Question successfully deleted.');
    }

    public function importIndex()
    {
        $data['page_title'] = 'Import MCQ Questions';
        return view('question.import.index', $data);
    }

    /*IMPORT MCQ QUESTIONS*/
    public function importMcq()
    {
        $data['page_title'] = 'Import MCQ Questions';
        $data['users']      = User::select('id', 'first_name', 'last_name', 'email')->where(['active' => 1, 'role_id' => 3, 'is_block' => 0])->get();
        $data['result']     = MCQImport::with(['user', 'subject', 'type'])->orderBy('id', 'DESC')->get();
        return view('question.import.importMcq', $data);
    }

    /*EXPORT TEMPLATE*/
    public function exportTemplete(Request $request)
    {
        $type_id    = $request->input('type_id');
        $subject_id = $request->input('subject_id');
        $subject    = Subject::where(['type_id' => $type_id, 'id' => $subject_id, 'status' => 1])->first();
        $topics     = Section::where(['type_id' => (int)$type_id, 'subject_id' => (int)$subject_id, 'is_deleted' => 0])->get();
        $subTopics  = Topic::where(['type_id' => $type_id, 'subject_id' => $subject_id, 'is_deleted' => 1])->orderBy('section_id', 'ASC')->get();
        $books      = Book::where(['level' => $type_id, 'subject_id' => $subject_id, 'status' => 1])->get();
        if (is_null($subject)) {
            return back()->with('msg_fail', 'Subject not exist in the system.');
        }
        if ($topics->isEmpty()) {
            return back()->with('msg_fail', 'Topic not exist in the system.');
        }
        if ($subTopics->isEmpty()) {
            return back()->with('msg_fail', 'Sub Topic not exist in the system.');
        }
        if ($books->isEmpty()) {
            return back()->with('msg_fail', 'Book not exist in the system.');
        }

        return Excel::download(new TempExport(), ucfirst($subject->professional['p_name']) . '_' . ucfirst($subject->subject_name) . '_' . date('Y-m-d H:i:s') . '.xlsx');

    }

    /*------------UPLOAD MCQ ----------*/
    public function uploadMcq(Request $request)
    {
        $this->validate($request,
            ['userfile', 'file|mimes:xlsx'],
            ['user_id', 'required']
        );
        $file             = $request->file('userfile');
        $type_id_popup    = $request->input('type_id_popup');
        $subject_id_popup = $request->input('subject_popup');
        $filename         = $file->getClientOriginalName();
        $extension        = $file->getClientOriginalExtension();
        $fileSize         = $file->getSize();
        $name             = explode(".", $filename);
        $realName         = $name[0] . time() . '.' . $extension;
        $valid_extension  = array("xlsx");

        // 25MB in Bytes

        $maxFileSize = 26214400;
        if (in_array(strtolower($extension), $valid_extension)) {
            // Check file size
            if ($fileSize <= $maxFileSize) {
                $record                = Excel::toArray(new CommonImport(), $request->file('userfile'));
                $totalQuestionUploaded = 0;
                $totalQuestionImport   = 0;
                if (!empty($record[0])) {
                    foreach ($record[0] as $row) {
                        if (array_key_exists(10, $row)) {
                            $que = trim($row[10]);
                            if ($que != null) {
                                $arrResult[] = array_slice($row, 0, 18);
                                $totalQuestionUploaded++;
                            }
                        } else {
                            return back()->with('msg_fail', 'You must have to follow file format.');
                            break;

                        }
                    }

                    $totalQuestionUploaded = ($totalQuestionUploaded > 0) ? ($totalQuestionUploaded - 1) : 0;
                    $titles                = array_shift($arrResult);
                    $validFormat           = $this->checkFileFormat($titles);
                    if (!$validFormat) {
                        return back()->with('msg_fail', 'You must have to follow file format.');
                    }

                    $keys  = array(
                        'program_type', 'subject_name', 'topic_name',
                        'sub_topic_name', 'question', 'option_a', 'option_b', 'option_c',
                        'option_d', 'option_e', 'answer', 'book_name', 'diff_level',
                        'cognitive', 'relevance', 'chapter_no', 'page_no', 'year'
                    );
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
                        $errorList = [];
                        $mcqArray  = [];
                        /*------Check Duplicate rows in file----- */
                        $dups      = array_count_values(array_column($final, 'question'));
                        $sameArray = array_filter($final, function ($item) use ($dups) {
                            return $dups[$item['question']] > 1;
                        });
                        if (!empty($sameArray)) {
                            foreach ($sameArray as $key => $same) {
                                foreach ($sameArray as $inner_key => $inn) {
                                    if ($inn['question'] == $same['question']) {
                                        if ($key != $inner_key) {
                                            $errorList[] = 'Row # ' . ($key + 2) . ', Question Duplicate with row # ' . ($inner_key + 2);
                                        }
                                    }
                                }
                            }
                        }

                        $subject_selected = Subject::findOrFail($subject_id_popup);

                        foreach ($final as $csv_ct) {
                            $row_num++;
                            $decision = 0;
                            /*-----Validate input Rules Start-----*/
                            $errors = $this->checkEmptyRule($csv_ct, $row_num);
                            if (!empty($errors) && count($errors) > 0) {
                                $errorList[] = $errors;
                                $decision    = 1;
                            }

                            /*----Check Max character Rule --------*/
                            $errors = $this->checkMaxCharacterRule($csv_ct, $row_num);
                            if (!empty($errors) && count($errors) > 0) {
                                $errorList[] = $errors;
                                $decision    = 1;
                            }


                            /*-- CHECK TYPE EXIST -- */
                            if (strtolower(trim($csv_ct['program_type'])) == 'mbbs') {
                                $type = 2;
                            } elseif (strtolower(trim($csv_ct['program_type'])) == 'bsc nursing') {
                                $type = 1;
                            } else {
                                $type        = 0;
                                $decision    = 1;
                                $msg         = 'Row # ' . $row_num . ', Program Type ' . $csv_ct['program_type'] . ' not exist in the system.';
                                $errorList[] = $msg;
                            }

                            /*----CHECK FILE UPLOAD LEVEL PROGRAM SELECT ------*/
                            if ($type_id_popup == 1) {//UP file for BSc Nusring
                                if (strtolower(trim($csv_ct['program_type'])) != 'bsc nursing') {
                                    $decision    = 1;
                                    $msg         = 'Row # ' . $row_num . ', Selected Program is BSc Nursing but file uploaded is of another program.';
                                    $errorList[] = $msg;
                                }
                            } elseif ($type_id_popup == 2) {//UP file for MBBS
                                if (strtolower(trim($csv_ct['program_type'])) != 'mbbs') {
                                    $decision    = 1;
                                    $msg         = 'Row # ' . $row_num . ', Selected Program is MBBS but file uploaded is of another program.';
                                    $errorList[] = $msg;
                                }
                            }


                            /*--  Check Subject --*/
                            $subject = Subject::where(['subject_name' => strtolower(trim($csv_ct['subject_name'])), 'status' => 1, 'type_id' => $type_id_popup])->first();
                            if (empty($subject)) {
                                $msg         = 'Row # ' . $row_num . ', Subject ' . $csv_ct['subject_name'] . ' not exist in the system.';
                                $errorList[] = $msg;
                                $decision    = 1;
                            } else {
                                if ($subject->id != $subject_id_popup) {
                                    $msg         = 'Row # ' . $row_num . ', Selected Subject is ' . ucwords($subject_selected->subject_name) . ' but file uploaded is of another subject ' . ucwords($csv_ct['subject_name']);
                                    $errorList[] = $msg;
                                    $decision    = 1;
                                }
                            }

                            /*-- CHECK TOPICS --*/
                            if (!is_null($subject_id_popup)) 
                            {
                                $topic_str = explode('[' , rtrim($csv_ct['topic_name'], ']'));
                                $topic_name = trim($topic_str[0]);
                                $exam_name = (isset($topic_str[1])) ? $topic_str[1] : '';

                                $professional_exam = ProfessionlExam::where(['p_exam' => $exam_name])->first();

                                if($professional_exam) 
                                {
                                    $topic = Section::where(['section_name' => strtolower($topic_name), 'subject_id' => $subject_id_popup, 'is_deleted' => 0, 'type_id' => $type_id_popup, 'prof_id' => $professional_exam->id])->first();

                                    if (empty($topic)) {
                                        $msg         = 'Row # ' . $row_num . ', Topic ' . $csv_ct['topic_name'] . ' doesnot exist in the system.';
                                        $errorList[] = $msg;
                                        $decision    = 1;
                                    } else {
                                        /*--  CHECK SUB TOPICS --*/
                                        $cond = array(
                                            'type_id'    => $type_id_popup,
                                            'subject_id' => $subject_id_popup,
                                            'section_id' => $topic->id,
                                            'topic_name' => strtolower(trim($csv_ct['sub_topic_name'])),
                                            'is_deleted' => 1
                                        );


                                        $sub_topic = Topic::where($cond)->first();
                                        if (is_null($sub_topic)) {
                                            $msg = "Row # " . $row_num . ", Subtopic '" . $csv_ct['sub_topic_name'] . "' doesnot exist in the system for Topic '". $csv_ct['topic_name'] ."'.";
                                            $errorList[] = $msg;
                                            $decision    = 1;
                                        }
                                    }
                                }
                                else
                                {
                                    $msg         = 'Row # ' . $row_num . ', Topic ' . $csv_ct['topic_name'] . ' doesnot exist in the system.';
                                    $errorList[] = $msg;
                                    $decision    = 1;
                                }
                            }
                            /*--  CHECK BOOK --*/
                            if (!empty($subject_id_popup)) {
                                $bookpart = explode("/", $csv_ct['book_name']);
                                if (!empty($bookpart[0]) && !empty($bookpart[1]) && !empty($bookpart[2])) {
                                    $bookCond = array(
                                        'status'     => 1,
                                        'subject_id' => $subject_id_popup,
                                        'book_name'  => strtolower(trim($bookpart[0])),
                                        'volumn'     => strtolower(trim($bookpart[1])),
                                        'author'     => strtolower(trim($bookpart[2])),
                                    );

                                } else if (!empty($bookpart[0]) && !empty($bookpart[1])) {
                                    $bookCond = array(
                                        'status'     => 1,
                                        'subject_id' => $subject_id_popup,
                                        'book_name'  => strtolower(trim($bookpart[0])),
                                        'volumn'     => strtolower(trim($bookpart[1]))

                                    );

                                } else if (!empty($bookpart[0])) {
                                    $bookCond = array(
                                        'status'     => 1,
                                        'subject_id' => $subject_id_popup,
                                        'book_name'  => strtolower(trim($bookpart[0]))

                                    );

                                } else {
                                    $bookCond = array(
                                        'status'     => 1,
                                        'subject_id' => 0,
                                        'book_name'  => ''

                                    );
                                }
                                $book = Book::where($bookCond)->first();
                                if (is_null($book)) {
                                    $msg         = 'Row # ' . $row_num . ', Book ' . $csv_ct['book_name'] . ' not exist in the system.';
                                    $errorList[] = $msg;
                                    $decision    = 1;
                                }
                            }
                            /*--  CHECK Difficulty Level --*/
                            $diff_level = Question_diff::where(['diff_level' => $csv_ct['diff_level']])->first();
                            if (is_null($diff_level)) {
                                $msg         = 'Row # ' . $row_num . ', Difficulty Level ' . trim($csv_ct['diff_level']) . ' not exist in the system.';
                                $errorList[] = $msg;
                                $decision    = 1;
                            }

                            /*--  CHECK Cognitive Level --*/
                            $cognitive_level = Cognitive_level::where(['cognitive_name' => trim($csv_ct['cognitive'])])->first();
                            if (is_null($cognitive_level)) {
                                $msg         = 'Row # ' . $row_num . ', Cognitive Level ' . $csv_ct['cognitive'] . ' not exist in the system.';
                                $errorList[] = $msg;
                                $decision    = 1;
                            }
                            /*--  CHECK Relevance --*/
                            $relevance = Relevance::where(['relevance_name' => trim($csv_ct['relevance'])])->first();
                            if (is_null($relevance)) {
                                $msg         = 'Row # ' . $row_num . ', Relevance ' . $csv_ct['relevance'] . ' not exist in the system.';
                                $errorList[] = $msg;
                                $decision    = 1;
                            }


                            if (trim($csv_ct['answer']) == 'A') {
                                $answer = 1;
                            } elseif (trim($csv_ct['answer']) == 'B') {
                                $answer = 2;
                            } elseif (trim($csv_ct['answer']) == 'C') {
                                $answer = 3;
                            } elseif (trim($csv_ct['answer']) == 'D') {
                                $answer = 4;
                            } elseif (trim($csv_ct['answer']) == 'E') {
                                $answer = 5;
                            } else {
                                $answer      = 0;
                                $decision    = 1;
                                $msg         = 'Row # ' . $row_num . ', Answer ' . $csv_ct['answer'] . ' not exist in the system.';
                                $errorList[] = $msg;
                            }

                            $year = empty(trim($csv_ct['year'])) ? date('Y') : $csv_ct['year'];


                            /*-----If there is no error in current Row then save it to new array----*/

                            if ($decision == 0) {
                                $correct = array(
                                    'qtype_id'             => 1,
                                    'type_id'              => $type,
                                    'subject_id'           => $subject->id,
                                    'section_id'           => $topic->id,
                                    'topic_id'             => $sub_topic->id,
                                    'question'             => $csv_ct['question'],
                                    'option1'              => $csv_ct['option_a'],
                                    'option2'              => $csv_ct['option_b'],
                                    'option3'              => $csv_ct['option_c'],
                                    'option4'              => $csv_ct['option_d'],
                                    'option5'              => $csv_ct['option_e'],
                                    'marks'                => 1,
                                    'answer'               => $answer,
                                    'diff_id'              => $diff_level->id,
                                    'chapter_no'           => $csv_ct['chapter_no'],
                                    'page_no'              => $csv_ct['page_no'],
                                    'year'                 => $year,
                                    'cognitive_level'      => $cognitive_level->id,
                                    'relevance'            => $relevance->id,
                                    'is_scenario_question' => 0,
                                    'book_reference'       => $book->id,
                                    'status'               => 'No',
                                    'user_id'              => $request->input('user_id'),
                                    'assign_to'            => '',
                                    'teacher1_id'          => '',
                                    'teacher1_assign_date' => '',
                                );

                                /*--CHECK DUPLICATE QUESTION FROM DATABASE*/
                                $where = array(
                                    'qtype_id'   => 1,
                                    'type_id'    => $correct['type_id'],
                                    'subject_id' => $correct['subject_id'],
                                    'section_id' => $correct['section_id'],
                                    'topic_id'   => $correct['topic_id'],
                                    'is_deleted' => 0
                                );
                                $exist = Question::questionMatching($where, $correct['question']);
                                if (!$exist) {
                                    $mcqArray[] = $correct;
                                    $totalQuestionImport++;
                                } else {
                                    $msg         = 'Row # ' . $row_num . ', Question ' . $correct['question'] . ' Already exist in the system.';
                                    $errorList[] = $msg;
                                }
                            }
                            /*print_r($row_num);
                            echo '</br>';*/

                        }


                    } else {
                        return back()->with('msg_fail', 'File is empty.');
                    }


                    if (!empty($mcqArray) && count($mcqArray) > 0) {
                        /*---- CHECK TEACHER AVAILABLE ----*/
                        $user_id  = $request->input('user_id');
                        $teachers = User::getTeacherForAssignQuestion($subject_id_popup, $type_id_popup, $user_id, $user_id);

                        if (is_null($teachers)) {
                            return back()->with('msg_fail', 'There is no teacher available against subject.');

                        }
                        $duplicatedQuestion = 0;
                        /*------SAVE IMPORTS ERRORS-----*/
                        $finalErros = array();
                        if (!empty($errorList)) {
                            foreach ($errorList as $r) {
                                if (is_array($r)) {
                                    foreach ($r as $innerError) {
                                        $finalErros[] = '<br>' . $innerError;
                                    }

                                } else {
                                    $finalErros[] = '<br>' . $r;
                                }
                            }
                        }
                        $mcqImport                          = new  MCQImport();
                        $mcqImport->error_log               = implode("|", $finalErros);
                        $mcqImport->for_teacher             = $request->input('user_id');
                        $mcqImport->subject_id              = $subject->id;
                        $mcqImport->type_id                 = $type;
                        $mcqImport->total_question_uploaded = $totalQuestionUploaded;
                        $mcqImport->total_question_imported = $totalQuestionImport;
                        $mcqImport->created_by              = session('user_info')['admin_id'];
                        $mcqImport->save();
                        foreach ($mcqArray as $row) {
                            /*--CHECK DUPLICATE QUESTION FROM DATABASE*/
                            $where = array(
                                'qtype_id'   => 1,
                                'type_id'    => $row['type_id'],
                                'subject_id' => $row['subject_id'],
                                'section_id' => $row['section_id'],
                                'topic_id'   => $row['topic_id'],
                                'is_deleted' => 0
                            );
                            $exist = Question::questionMatching($where, $row['question']);
                            if (!$exist) {
                                $teachers                       = User::getTeacherForAssignQuestion($row['subject_id'], $row['type_id'], $user_id, $user_id);
                                $question                       = new Question();
                                $question->qtype_id             = $row['qtype_id'];
                                $question->type_id              = $row['type_id'];
                                $question->subject_id           = $row['subject_id'];
                                $question->section_id           = $row['section_id'];
                                $question->topic_id             = $row['topic_id'];
                                $question->question             = option_encrypt($row['question']);
                                $question->option1              = option_encrypt($row['option1']);
                                $question->option2              = option_encrypt($row['option2']);
                                $question->option3              = option_encrypt($row['option3']);
                                $question->option4              = option_encrypt($row['option4']);
                                $question->option5              = option_encrypt($row['option5']);
                                $question->marks                = $row['marks'];
                                $question->answer               = $row['answer'];
                                $question->diff_id              = $row['diff_id'];
                                $question->chapter_no           = $row['chapter_no'];
                                $question->page_no              = $row['page_no'];
                                $question->year                 = $row['year'];
                                $question->cognitive_level      = $row['cognitive_level'];
                                $question->relevance            = $row['relevance'];
                                $question->is_scenario_question = $row['is_scenario_question'];
                                $question->book_reference       = $row['book_reference'];
                                $question->status               = $row['status'];
                                $question->unique_id            = mt_rand(100000, 999999);
                                $question->user_id              = $row['user_id'];
                                $question->assign_to            = $teachers->id;
                                $question->teacher1_id          = $teachers->id;
                                $question->teacher1_assign_date = date('Y-m-d H:i:s');
                                $question->save();
                                if ($mcqImport->id && $question->id) {
                                    $log                = new MCQImportLogs();
                                    $log->mcq_import_id = $mcqImport->id;
                                    $log->question_id   = $question->id;
                                    $log->created_by    = session('user_info')['admin_id'];
                                    $log->save();
                                }

                            } else {
                                $duplicatedQuestion++;
                            }

                        }
                        if ($mcqImport->id) {
                            $totalQuestionImport                      = ($totalQuestionImport > 0) ? ($totalQuestionImport - $duplicatedQuestion) : 0;
                            $mcqImportUpdate                          = MCQImport::find($mcqImport->id);
                            $mcqImportUpdate->total_question_imported = $totalQuestionImport;
                            $mcqImportUpdate->save();
                        }
                        return back()->with('msg_success', 'File successfully Imported');
                    } else {
                        if (!empty($errorList) && count($errorList) > 0) {

                            $error_msg = '';
                            foreach ($errorList as $r) {
                                if (is_array($r)) {
                                    foreach ($r as $innerError) {
                                        $error_msg = $error_msg . '<br>' . $innerError;
                                    }

                                } else {
                                    $error_msg = $error_msg . '<br>' . $r;
                                }
                            }
                            return back()->with('msg_warning', $error_msg);

                        } else {
                            return back()->with('msg_warning', 'Record already exist in the system.');
                        }
                    }

                } else {
                    return back()->with('msg_fail', 'File is Empty.');
                }
            } else {
                return back()->with('msg_fail', 'File Size is greater then allowed size.');
            }
        } else {
            return back()->with('msg_fail', 'File Extension Should be xlsx.');
        }
    }

    public function checkEmptyRule($row = array(), $row_num)
    {
        $errors = array();
        if (!empty($row)) {
            if (empty($row['program_type'])) {
                $errors[] = 'Row # ' . $row_num . ', Program Name cannot be empty.';
            }
            if (empty($row['subject_name'])) {
                $errors[] = 'Row # ' . $row_num . ', Subject Name cannot be empty.';
            }
            if (empty($row['topic_name'])) {
                $errors[] = 'Row # ' . $row_num . ', Topic Name cannot be empty.';
            }
            if (empty($row['sub_topic_name'])) {
                $errors[] = 'Row # ' . $row_num . ', Sub Topic Name cannot be empty.';
            }
            if (empty($row['book_name'])) {
                $errors[] = 'Row # ' . $row_num . ', Book Name cannot be empty.';
            }
            if (empty($row['diff_level'])) {
                $errors[] = 'Row # ' . $row_num . ', Difficulty Level cannot be empty.';
            }
            if (empty($row['cognitive'])) {
                $errors[] = 'Row # ' . $row_num . ', Cognitive Level cannot be empty.';
            }
            if (empty($row['relevance'])) {
                $errors[] = 'Row # ' . $row_num . ', Relevance cannot be empty.';
            }
            if (empty($row['chapter_no'])) {
                $errors[] = 'Row # ' . $row_num . ', Chapter No cannot be empty.';
            }
            if (empty($row['page_no'])) {
                $errors[] = 'Row # ' . $row_num . ', Page No cannot be empty.';
            }
            if (empty($row['question'])) {
                $errors[] = 'Row # ' . $row_num . ', Question cannot be empty.';
            }
            if (empty($row['option_a'])) {
                $errors[] = 'Row # ' . $row_num . ', Option A cannot be empty.';
            }
            if (empty($row['option_b'])) {
                $errors[] = 'Row # ' . $row_num . ', Option B cannot be empty.';
            }
            if (empty($row['option_c'])) {
                $errors[] = 'Row # ' . $row_num . ', Option C cannot be empty.';
            }
            if (empty($row['option_d'])) {
                $errors[] = 'Row # ' . $row_num . ', Option D cannot be empty.';
            }
            if (empty($row['option_e'])) {
                $errors[] = 'Row # ' . $row_num . ', Option E cannot be empty.';
            }
            if (empty($row['answer'])) {
                $errors[] = 'Row # ' . $row_num . ', Answer cannot be empty.';
            }


        }
        return $errors;
    }

    /*------------Character Allowed------------*/
    public function checkMaxCharacterRule($csv_ct = array(), $row_num)
    {
        $errors = array();
        if (!empty($csv_ct)) {
            /*---Check Max 250 character --- */
            foreach ($csv_ct as $key => $val) {
                $validString = trim($val);
                if ($key == 'chapter_no') {
                    if (strlen($validString) > 250) {
                        $errors[] = 'Row # ' . $row_num . ', Max 250 character allowed in Chapter No.';
                    }
                }
                if ($key == 'page_no') {
                    if (strlen($validString) > 250) {
                        $errors[] = 'Row # ' . $row_num . ', Max 250 character allowed in Page No.';
                    }
                }
                if ($key == 'answer') {
                    if (strlen($validString) != 1) {
                        $errors[] = 'Row # ' . $row_num . ', Max 1 character allowed in Answer.';
                    }
                }

                if ($key != 'page_no' && $key != 'chapter_no' && $key != 'question'
                    && $key != 'option_a' && $key != 'option_b' && $key != 'option_c' && $key != 'option_d'
                    && $key != 'option_e'
                ) {
                    if (str_contains($validString, '<')) {
                        $errors[] = 'Row # ' . $row_num . ', Symbol < Not allowed in ' . str_replace('_', ' ', $key);

                    }
                }
            }


        }
        return $errors;
    }

    public function viewError(Request $request)
    {
        if ($request->ajax()) {
            $id   = $request->input('id');
            $logs = MCQImport::findOrFail($id);
            $res  = '';
            if (!is_null($logs) && !empty($logs->error_log)) {
                $error = explode("|", $logs->error_log);
                foreach ($error as $err) {
                    $res .= $err;
                }
            } else {
                $res = 'No Error Found.';
            }
            echo $res;
        } else {
            redirect('home');
        }

    }

    public function checkFileFormat($arrs)
    {
        if ($arrs[0] != 'Program'
            || $arrs[1] != 'Subject'
            || $arrs[2] != 'Topic'
            || $arrs[3] != 'Sub Topic'
            || $arrs[4] != 'Question'
            || $arrs[5] != 'Option A'
            || $arrs[6] != 'Option B'
            || $arrs[7] != 'Option C'
            || $arrs[8] != 'Option D'
            || $arrs[9] != 'Option E'
            || $arrs[10] != 'Answers'
            || $arrs[11] != 'Name of Book/Source/Reference'
            || $arrs[12] != 'Difficulty Level'
            || $arrs[13] != 'Cognitive Level'
            || $arrs[14] != 'Relevance'
            || $arrs[15] != 'Chapter No'
            || $arrs[16] != 'Page No'
        ) {
            return FALSE;
    } else {
        return TRUE;
    }
}
}
