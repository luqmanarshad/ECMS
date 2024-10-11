<?php

namespace App\Http\Controllers;

use App\Question;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ReviewController extends Controller
{

    public function index()
    {
        $data['page_title'] = "Question Review";
        $pending            = Question::getAssignQuestion();
        $data['row']        = $pending;
        return view('review.index', $data);
    }

    public function show($id, $qtype_id)
    { 
        // $data['back_to'] =  request()->segment(2);

        $data['page_title'] = "Question Review";
        if ($qtype_id == 1) {
            $file_name = 'view_mcq';
        } elseif ($qtype_id == 2) {
            $file_name = 'view_seq';
        }

        $data['id']       = '';
        $data['chapters'] = '';

        $id = base64url_decode($id);
        if ($id) {
            $result = Question::getQustionView($id, $qtype_id); 
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

    public function changeStatus($state = false, $id = false, Request $request)
    {
        $user_id      = session('user_info')['admin_id'];
        $question_id  = $request->input('question_idIn');
        $currentState = $request->input('status_idIn');
        $comment      = $request->input('reject_commentIn');
        $incorrect    = $request->input('incorrect');
        $ratting      = $request->input('rating');


        if (!empty($question_id)) {
            $id = $question_id;
        }
        if (!empty($currentState)) {
            $state = $currentState;
        }
        $sms_setting = session('user_info')['setting'];
        if ($id) {

            $question = Question::findOrFail($id);
            //$createdBy_id = Question::where('id', $id)->select('user_id', 'assign_to')->first();
            // Get user detail who created question
            $mobileResult = User::where('id', $question->user_id)->select('contact_no', 'email')->first();

            //if teacher first Accept in 1st time.
            if ($state == 1) {
                $question->status               = 'Yes';
                $question->question_status      = 3;
                $question->current_state        = 4;
                $question->assign_to            = $user_id;
                $question->teacher1_accept_date = date('Y-m-d H:i:s');
                $question->teacher1_accepted    = 1;
                $question->ratting              = $ratting;

                $msg = 'Question has been approved successfully .';
            } elseif ($state == 2) {
                if ($incorrect) {
                    $question->status               = 'No';
                    $question->question_status      = 2;
                    $question->current_state        = 3;
                    $question->teacher2_reason      = addslashes($comment);
                    $question->teacher2_reject_date = date('Y-m-d H:i:s');
                    $question->teacher2_id          = $question->assign_to;
                    $question->assign_to            = $question->user_id;
                    $question->teacher2_incorrect   = 1;

                    $msg = 'Question has been sent for correction.';
                    /*------------ Send Sms start -----------*/
                    if (($sms_setting->incorrect_question == 1) && (!empty($incorrect))) {
                        if (!empty($mobileResult->contact_no)) {
                            $msgIncorrect = 'Question has been assigned to you for correction.';
                            sendMessage($mobileResult->contact_no, $msgIncorrect);

                        }
                    }


                }
                //if teacher first Accept in 1st time or in 3rd time then
            } elseif ($state == 3) {
                //$updateQuestion = array('status' => 'Yes', 'question_status' => 3, 'current_state' => 4, 'assign_to' => $user_id, 'teacher3_accept_date' => date('Y-m-d H:i:s'), 'teacher3_accepted' => 1, 'ratting' => $ratting);
                $question->status               = 'Yes';
                $question->question_status      = 3;
                $question->current_state        = 4;
                $question->assign_to            = $user_id;
                $question->teacher3_accept_date = date('Y-m-d H:i:s');
                $question->teacher3_accepted    = 1;
                $question->ratting              = $ratting;
                $msg                            = 'Question has been approved successfully .';
            }

            // Question::where(['id' => $id])->update($updateQuestion);
            $question->save();
            /*------------ Send Sms start -----------*/

            if (($sms_setting->approve_question == 1) && (empty($incorrect))) {
                if (!empty($mobileResult->contact_no)) {
                    sendMessage($mobileResult->contact_no, $msg);
                }

            }

            /*------------ Send Sms End -----------*/

            return redirect('review/question')->with('msg_success', $msg);
        } else {
            return redirect('review/question')->with('msg_fail', 'Invalid Id. No record available against id');
        }


    }

    public function statusReject(Request $request)
    {

        $currentState = $request->input('status_id');
        $question_id  = $request->input('question_id');
        $comment      = $request->input('reject_comment');


        // $question_detail = Question::where('id', $question_id)->select('subject_id', 'user_id', 'type_id', 'teacher1_id')->first();
        $question_detail = Question::findOrfail($question_id);
        $subject_id      = $question_detail->subject_id;
        $type_id         = $question_detail->type_id;
        $created_by      = $question_detail->user_id;
        $teacher1_id     = $question_detail->teacher1_id;
        $sms_setting     = session('user_info')['setting'];
        $teacher         = User::getTeacherForAssignQuestion($subject_id, $type_id, $created_by, $teacher1_id);

        if (!empty($teacher)) {
            $random_teacher_id = $teacher->id;
        } else {
            return redirect('review/question')->with('msg_fail', 'No teacher available against subject. Please contact to Admin for more information.');
        }

        if ($currentState == 1) { // 1st reject
            $question_detail->status               = 'No';
            $question_detail->question_status      = 1;
            $question_detail->current_state        = 2;
            $question_detail->teacher1_rejected    = 1;
            $question_detail->teacher1_reject_date = date('Y-m-d H:i:s');
            $question_detail->teacher1_reason      = addslashes($comment);
            $question_detail->teacher2_id          = $random_teacher_id;
            $question_detail->assign_to            = $random_teacher_id;
            $question_detail->teacher2_assign_date = date('Y-m-d H:i:s');
            // Get user detail who created question
            $mobileResult = User::where('id', $random_teacher_id)->select('contact_no', 'email')->first();
            /*------------ Send Sms start -----------*/
            if ($currentState == 1) {
                if ($sms_setting->assign_question == 1) {
                    if (!empty($mobileResult->contact_no)) {
                        $msgAssign = 'Question has been assigned to you for second review.';
                        sendMessage($mobileResult->contact_no, $msgAssign);
                    }
                }

            }


        } elseif ($currentState == 2) { //2nd Reject
            $question_detail->status               = 'No';
            $question_detail->question_status      = 4;
            $question_detail->current_state        = 4;
            $question_detail->teacher2_rejected    = 1;
            $question_detail->teacher2_reject_date = date('Y-m-d H:i:s');
            $question_detail->teacher2_reason      = addslashes($comment);

        } elseif ($currentState == 3) { //3rd Reject
            $question_detail->status               = 'No';
            $question_detail->question_status      = 4;
            $question_detail->current_state        = 4;
            $question_detail->teacher3_rejected    = 1;
            $question_detail->teacher3_reject_date = date('Y-m-d H:i:s');
            $question_detail->teacher3_reason      = addslashes($comment);

        }

        // Get user detail who created question
        $mobileResult = User::where('id', $question_detail->user_id)->select('contact_no', 'email')->first();
        /*------------ Send Sms start -----------*/
        if ($currentState == 2 || $currentState == 3) {
            if ($sms_setting->reject_question == 1) {
                if (!empty($mobileResult->contact_no)) {
                    $msgReject = 'Your Question has been rejected.';
                    sendMessage($mobileResult->contact_no, $msgReject);
                }
            }
        }
        /*------------ Send Sms End -----------*/

        if ($question_id) {
           // Question::where('id', $question_id)->update($updateArray);
            $question_detail->save();
            return redirect('review/question')->with('msg_success', 'Question has been rejected.');
        } else {
            return redirect('review/question')->with('msg_fail', 'Invalid Id. No record available against id');
        }

    }

}
