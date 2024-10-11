<?php
/**
 * Created by PhpStorm.
 * User: Fawad
 * Date: 4/26/2019
 * Time: 11:21 AM
 */

if (!function_exists('return_TypesDesign')) {
    function return_TypesDesign($selected = false)
    {
        $str         = '';
        $selectArray = array();
        if ($selected) {
            $selectArray = explode(",", $selected);
        }
        $r    = 0;
        $prof = \App\Professional::all();
        if ($prof) {
            foreach ($prof as $sbname) {
                if (in_array($sbname['id'], $selectArray)) {
                    if ($r == 1) {
                        $str .= ', ' . $sbname['p_name'];
                    } else {
                        $str .= $sbname['p_name'];
                    }

                    $r = 1;
                }
            }
        }
        return $str;
    }
}


/*--------------its for admin log out panel --------- */
function return_SubjectsNameDesign($selected = false)
{
    $str         = array();
    $selectArray = array();
    if ($selected) {
        $selectArray = explode(",", $selected);
    }
    $subject = \App\Subject::where('status', 1)->get();

    if ($subject) {
        foreach ($subject as $sbname) {
            if (in_array($sbname['id'], $selectArray)) {
                $str [] = $sbname['subject_name'];
            }
        }
    }
    return $str;
}

/*--------------Return Type  --------- */
if (!function_exists('return_Types')) {
    function return_Types($selected = false)
    {
        $str         = '';
        $selectArray = array();
        if ($selected) {
            $selectArray = explode(",", $selected);
        }
        $prof = \App\Professional::all();
        if (!empty($prof)) {
            foreach ($prof as $sbname) {
                if (in_array($sbname['id'], $selectArray)) {
                    $str .= $sbname['p_name'] . ' / ';
                }
            }
        }
        return $str;
    }
}

function base64url_encode($data)
{
    return generate_string(30) . rtrim(strtr(base64_encode($data), '+/', '-_'), '=') . generate_string(30);
}

function base64url_decode($data)
{
    $str_start = substr($data, 0, 30);
    $str_end   = substr($data, -30);
    $find      = array($str_start, $str_end);
    $rep       = array('', '');
    $data      = str_replace($find, $rep, $data);
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function generate_string($strength = 30)
{
    $input         = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $input_length  = strlen($input);
    $random_string = '';
    for ($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string    .= $random_character;
    }

    return $random_string;
}

if (!function_exists('mobileNetworkDropDown')) {
    function mobileNetworkDropDown($selected = FALSE)
    {
        $str = '';
        if (!$selected) $str .= '<option value="" disabled selected>-- Please Select --</option>';
        $mobile = \App\Mobile_netwok::all();
        if (!empty($mobile)) {
            foreach ($mobile as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option value="' . $sbname['id'] . '"' . $sel . '>' . ucfirst($sbname['network_name']) . '</option>';
            }
        }

        return $str;

    }
}

if (!function_exists('roleDropDown')) {
    function roleDropDown($selected = FALSE)
    {
        $str = '';
        if (!$selected) $str .= '<option value="" disabled selected>-- Please Select --</option>';
        $role = \App\Role::all();
        if (!empty($role)) {
            foreach ($role as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['name']) . '</option>';
            }
        }
        return $str;

    }
}

if (!function_exists('instituteDropDown')) {
    function instituteDropDown($selected = FALSE)
    {
        $str = '';
        if (!$selected) $str .= '<option value="" disabled selected>-- Please Select --</option>';
        $institute = \App\Institute::where('is_deleted', 0)->get();
        if (!empty($institute)) {
            foreach ($institute as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['institute_name']) . '</option>';
            }
        }
        return $str;

    }
}

function return_SubjectsWithoutSelect($selected = false)
{
    $str         = '';
    $selectArray = array();

    if ($selected) {
        if (is_array($selected)) {
            $selectArray = $selected;
        } else {
            $selectArray = explode(",", $selected);
        }
    }
    $subject = \App\Subject::where('status', 1)->orderBy('subject_name', 'ASC')->get();
    if (!empty($subject)) {
        foreach ($subject as $sbname) {
            $sel = (in_array($sbname['id'], $selectArray)) ? 'selected' : '';
            $str .= '<option  value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['subject_name']) . ' [' . $sbname->professional['p_name'] . ']</option>';
        }
    }
    return $str;
}

if (!function_exists('return_SubjectsName')) {
    function return_SubjectsName($selected = false)
    {
        $str         = '';
        $selectArray = array();
        if ($selected) {
            $selectArray = explode(",", $selected);
        }
        $subject = \App\Subject::where('status', 1)->orderBy('subject_name', 'ASC')->get();
        if ($subject) {
            foreach ($subject as $sbname) {
                if (in_array($sbname['id'], $selectArray)) {
                    $str .= $sbname['subject_name'] . ' / ';
                }
            }
        }
        return $str;
    }
}

if (!function_exists('profession_type')) {
    function profession_type($selected = FALSE)
    {
        $str = '';
        if (!$selected) $str .= '<option value="" disabled selected>-- Please Select --</option>';
        $pro = \App\Professional::all();
        if ($pro) {
            foreach ($pro as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option value="' . $sbname['id'] . '" ' . $sel . '>' . $sbname['p_name'] . '</option>';
            }
        }
        return $str;

    }
}

if (!function_exists('validate_alpha_numeric')) {
    function validate_alpha_numeric($string)
    {
        if (preg_match('/^[a-zA-Z ]+[a-zA-Z0-9._ -]+$/', $string)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

if (!function_exists('validate_numeric')) {
    function validate_numeric($input)
    {
        if (preg_match('/^[0-9]+$/i', $input)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

if (!function_exists('validate_alpha_numeric_symbol')) {
    function validate_alpha_numeric_symbol($string)
    {
        if (preg_match("/^[a-zA-Z ]+[a-zA-Z0-9. '-]+$/", $string)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

if (!function_exists('return_badge')) {
    function return_badge($str)
    {
        $result = '';
        if ($str == 'First') {
            $result = '<span class="label label-default">' . $str . '</span>';
        } elseif ($str == 'Second') {
            $result = '<span class="label label-info">' . $str . '</span>';
        } elseif ($str == 'Third') {
            $result = '<span class="label label-warning">' . $str . '</span>';
        } elseif ($str == 'Fourth') {
            $result = '<span class="label label-primary">' . $str . '</span>';
        } elseif ($str == 'Final') {
            $result = '<span class="label label-danger">' . $str . '</span>';
        }
        return $result;

    }

    if (!function_exists('return_Subjects_by_type_id_forBook')) {
        function return_Subjects_by_type_id_forBook($type = FALSE, $selected = FALSE)
        {
            $str   = '';
            $where = '';
            if (!$selected) $str .= '<option value="">-- Please Select --</option>';
            if ($type) {
                $where = ' AND type_id =' . $type;
            }
            $subject = \App\Subject::whereRaw("status = 1 $where")->orderBy('subject_name', 'ASC')->get();
            if ($subject) {
                foreach ($subject as $sbname) {
                    $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                    $str .= '<option  value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['subject_name']) . '</option>';
                }
            }
            return $str;
        }
    }

    if (!function_exists('professional_exam_drop_down')) {
        function professional_exam_drop_down($type_id = FALSE, $selected = FALSE)
        {
            $str = '';
            if (!$selected) $str .= '<option value="">-- Please Select --</option>';
            $exam = \App\ProfessionlExam::join('professional_bytype', 'professional_bytype.prof_id', '=', 'professional_exam.id')
                ->join('professional', 'professional.id', '=', 'professional_bytype.type_id')
                ->select('professional_exam.id', 'p_exam')
                ->where('professional_bytype.type_id', $type_id)->get();

            if ($exam) {
                foreach ($exam as $sbname) {
                    $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                    $str .= '<option value="' . $sbname['id'] . '" ' . $sel . '>' . $sbname['p_exam'] . '</option>';
                }
            }
            return $str;


        }
    }
}

if (!function_exists('getSectionBySubjectId')) {
    function getSectionBySubjectId($subject_id, $selected = false)
    {
        $str = '';
        if (empty($selected)) {
            $str .= '<option value="" disabled selected>-- Please Select --</option>';
        }
        $section = \App\Section::where(['is_deleted' => 0, 'subject_id' => $subject_id])->orderBy('section_name', 'ASC')->get();
        if ($section) {
            foreach ($section as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['section_name']) . '  ['.$sbname["ProfessionlExam"]->p_exam.']</option>';
            }
        }
        return $str;
    }
}

if (!function_exists('getTopicsBySectionId')) {
    function getTopicsBySectionId($section_id, $selected = FALSE)
    {
        $str = '';
        $str .= '<option value="">-- Please Select --</option>';

        $theme = \App\Topic::where(['is_deleted' => 1, 'section_id' => $section_id])->orderBy('topic_name', 'ASC')->get();
        if ($theme) {
            foreach ($theme as $row) {
                $sel = ($row['id'] == $selected) ? ' selected ' : '';
                $str .= '<option  value="' . $row['id'] . '" ' . $sel . '>' . ucfirst($row['topic_name']) . '</option>';
            }
        }
        return $str;
    }
}

if (!function_exists('themeDropDownBySectionID')) {
    function themeDropDownBySectionID($section_id, $selected = FALSE)
    {
        $str = '';

        if (!$selected) $str .= '<option value="" disabled selected>-- Please Select --</option>';
        $cond  = array('section_id' => $section_id, 'topic_id' => 0, 'is_deleted' => 0);
        $theme = \App\Theme::where($cond)->get();
        if ($theme) {
            foreach ($theme as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['theme_name']) . '</option>';
            }
        }
        return $str;

    }
}

if (!function_exists('subThemeDropDown')) {
    function subThemeDropDown($id, $selected = FALSE)
    {
        $str = '';

        if (!$selected)
            $str .= '<option value="" disabled selected>-- Please Select --</option>';
        $cond     = array('theme_id' => $id, 'is_deleted' => 0);
        $subtheme = \App\SubTheme::where($cond)->get();
        if ($subtheme) {
            foreach ($subtheme as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option  value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['sub_theme_name']) . '</option>';
            }
        }
        return $str;


    }
}

if (!function_exists('themeDropDown')) {
    function themeDropDown($topic_id,$sub_topic_id, $selected = FALSE)
    {
        $str   = '';
        $found = 0;
        if (!$selected) $str .= '<option value="" disabled selected>-- Please Select --</option>';

        if(is_null($sub_topic_id) && !is_null($topic_id)){
            $cond  = array('section_id' => $topic_id,'topic_id'=>0 ,'is_deleted' => 0);
        }else{
            $cond  = array('topic_id' => $sub_topic_id, 'is_deleted' => 0);
        }
        $theme = \App\Theme::where($cond)->get();
        if ($theme) {
            foreach ($theme as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option  value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['theme_name']) . '</option>';
            }
            $found = 1;
        }
        $result['str']   = $str;
        $result['found'] = $found;
        return $result;

    }
}
/*----return all subjects by Login program type that is (MBBS OR BSc Nursing) ------- */
if (!function_exists('return_Subjects_by_login')) {
    function return_Subjects_by_login($selected = FALSE)
    {
        $session    = session('user_info');
        $str        = '';
        $subject_id = $session['subject_id'];
        if (!$selected) $str .= '<option value="" selected disabled>-- Please Select --</option>';

        $subject = \App\Subject::select('subjects.id', 'subject_name', 'type_id', 'professional.p_name')
            ->leftJoin('professional', 'professional.id', '=', 'subjects.type_id')
            ->whereIn('subjects.id', explode(',', $subject_id))//accept only array
            ->where('status', 1)
            ->orderBy('subject_name', 'ASC')
            ->get();
        if ($subject) {
            foreach ($subject as $row) {
                $sel = ($row['id'] == $selected) ? ' selected ' : '';
                $str .= '<option  value="' . $row['id'] . '" ' . $sel . '>' . $row['subject_name'] . ' [' . $row['p_name'] . ']' . '</option>';
            }
        }
        return $str;
    }
}

if (!function_exists('cognitiveDropDown')) {
    function cognitiveDropDown($selected = FALSE)
    {

        $str = '';

        if (!$selected)
            $str .= '<option value="" disabled selected>-- Please Select --</option>';

        $c_level = \App\Cognitive_level::all();

        if ($c_level) {
            foreach ($c_level as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['cognitive_name']) . '</option>';
            }
        }
        return $str;


    }
}

if (!function_exists('relevanceDropDown')) {
    function relevanceDropDown($selected = FALSE)
    {

        $str = '';
        if (!$selected)
            $str .= '<option value="" disabled selected>-- Please Select --</option>';
        $relevance = \App\Relevance::all();
        if ($relevance) {
            foreach ($relevance as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['relevance_name']) . '</option>';
            }
        }
        return $str;


    }
}

if (!function_exists('getBookReference')) {
    function getBookReference($subject_id, $selected = false)
    {
        $str = '';
        if ($subject_id) {
            $books = \App\Book::where(array('subject_id' => $subject_id, 'status' => 1))->orderBy('book_name', 'ASC')->get();
            $str   .= '<option value="">-- Please Select -- </option>';
            foreach ($books as $row) {
                $sel = ($row->id == $selected) ? ' selected ' : '';
                $str .= '<option  value="' . $row->id . '" ' . $sel . '>' . $row->book_name . ' / ' . $row->volumn . ' / ' . $row->author . '</option>';
            }
        }
        return $str;
    }
}

if (!function_exists('option_decrypt')) {
    function option_decrypt($option)
    {
        $str = '';
        if (!empty($option)) {
            $str = stripslashes(decrypt($option));
        }
        return $str;
    }
}

if (!function_exists('option_encrypt')) {
    function option_encrypt($option)
    {
        $str = '';
        if (!empty($option)) {
            $str = encrypt(addslashes($option));
        }
        return $str;
    }
}

if (!function_exists('SendSms')) {
    function SendSms($number, $message, $language = 'english')
    {
        $model = array(
            'phone_no' => $number, 'sms_text' => "" . $message, //'sec_key'    =>'daf2a63ddd419feae1f8684d5932ac24',
            'sec_key'  => 'ebddfa0ce37e0e332b7233e3d0ab79e2', 'sms_language' => $language
        );

        $post_string = http_build_query($model);


        //$sms_url = 'http://103.226.217.138/api/send_sms';
        $sms_url = 'https://smsgateway.pitb.gov.pk/api/send_sms';

        $ch = curl_init();// or die("Cannot init");
        curl_setopt($ch, CURLOPT_URL, $sms_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($post_string)));

        $curl_response = curl_exec($ch);//
        $gr            = $curl_response;
        $res_status    = json_decode($gr);

        return $res_status;
    }
}

if (!function_exists('sendMessage')) {
    function sendMessage($number, $msg)
    {
        $smsMessgae = 'Dear Sir/Madam' . PHP_EOL;
        $smsMessgae .= ' ' . PHP_EOL;
        $smsMessgae .= $msg . ' ' . PHP_EOL;
        $smsMessgae .= ' ' . PHP_EOL;
        $smsMessgae .= 'Regards,' . PHP_EOL;
        $smsMessgae .= 'UHS ITEM BANK';
        $response   = SendSms($number, $smsMessgae);

        if (!empty($response->status)) {
            if (!empty($response->message)) {
                $data = array('mobile_no' => $number, 'sms' => $smsMessgae, 'sms_date' => date('Y-m-d H:i:s'), 'response_message' => $response->message, 'response_status' => $response->status);

            }
        }
    }
}

if (!function_exists('getMultiSelectBooksBySubject_id')) {
    function getMultiSelectBooksBySubject_id($subject_id, $selected = false)
    {
        $str = '';
        if ($subject_id) {
            $optionlist     = \App\Book::where(['subject_id' => $subject_id, 'status' => 1])->get();
            $selected_array = array();
            if (!empty($selected)) {
                $selected_array = explode(',', $selected);
            }


            $str .= '<option value="">-- Please Select -- </option>';
            foreach ($optionlist as $row) {
                if (in_array($row->id, $selected_array)) {
                    $sel = ' selected ';
                } else {
                    $sel = ' ';
                }
                $str .= '<option value="' . $row->id . '" ' . $sel . '>' . $row->book_name . ' / ' . $row->volumn . ' / ' . $row->author . '</option>';
            }
        }
        return $str;
    }
}

if (!function_exists('difficultyName')) {
    function difficultyName($id)
    {
        $diff = \App\Question_diff::where('id', $id)->first();
        $str  = $diff->diff_level;
        return ucfirst($str);
    }
}

if (!function_exists('booksReferenceByID')) {
    function booksReferenceByID($id)
    {
        $id  = explode(',', $id);
        $str = '';
        if ($id) {
            $optionlist = \App\Book::whereIn('id', $id)->where('status', 1)->orderBy('book_name', 'ASC')->get();
            foreach ($optionlist as $row) {
                $str .= $row->book_name . ' / ' . $row->volumn . ' / ' . $row->author . '</br>';
            }
        }
        return $str;
    }
}

if (!function_exists('getNotification')) {
    function getNotification()
    {

        $sql = \App\Question::getAssignQuestion();
        if (!is_null($sql)) {
            $return = 1;
        } else {
            $return = 0;
        }
        return $return;
    }
}

if (!function_exists('getRejectedNotification')) {
    function getRejectedNotification()
    {
        $rejected = array(
            'is_deleted'      => 0,
            'question_status' => 2,
            'current_state'   => 3,
            'status'          => 'No',
            'assign_to'       => session('user_info')['admin_id'],
            'user_id'         => session('user_info')['admin_id']
        );
        $sql      = \App\Question::where($rejected)->first();
        if (!is_null($sql)) {
            $return = 1;
        } else {
            $return = 0;
        }
        return $return;
    }
}

if (!function_exists('themeDropDownBySectionIdAndTopicId')) {
    function themeDropDownByTopicAndSubTopic($section_id, $subtopicId, $selected = FALSE)
    {
        $str = '';

        if (!$selected) $str .= '<option value="" disabled selected>-- Please Select --</option>';
        $cond  = array('section_id' => $section_id, 'topic_id' => $subtopicId, 'is_deleted' => 0);
        $theme = \App\Theme::where($cond)->get();
        if ($theme) {
            foreach ($theme as $sbname) {
                $sel = ($sbname['id'] == $selected) ? ' selected ' : '';
                $str .= '<option   value="' . $sbname['id'] . '" ' . $sel . '>' . ucfirst($sbname['theme_name']) . '</option>';
            }
        }
        return $str;

    }
}


if (!function_exists('questionTypeDropDown')) {
    function questionTypeDropDown($selected = FALSE)
    {

        $str = '';

        $type = \App\QuestionType::all();
        if (!empty($type)) {
            foreach ($type as $row) {
                $sel = ($row['id'] == $selected) ? ' selected ' : '';
                $str .= '<option value="' . $row['id'] . '" ' . $sel . '>' . ucfirst($row['question_type']) . '</option>';
            }
        }
        echo $str;


    }
}


if (!function_exists('questionDiffDropDown')) {
    function questionDiffDropDown($selected = FALSE)
    {

        $str = '';

        $type = \App\Question_diff::all();
        if (!empty($type)) {
            foreach ($type as $row) {
                $sel = ($row['id'] == $selected) ? ' selected ' : '';
                $str .= '<option value="' . $row['id'] . '" ' . $sel . '>' . ucfirst($row['diff_level']) . '</option>';
            }
        }
        echo $str;


    }
}



if (!function_exists('getTeacherBySubjectId')) {
    function getTeacherBySubjectId($id, $selected = FALSE)
    {
        $str = '';
        if (!$selected)
            $str .= '<option value="" disabled selected>-- Please Select --</option>';
        $users = \App\User::getTeacherBySubjectId($id);
        if (!empty($users) && count($users)>0) {
            foreach ($users as $row) {

                $sel = ($row->id == $selected) ? ' selected ' : '';
                $str .= '<option value="'.$row->id.'">'.ucfirst($row->first_name).' '.ucfirst($row->last_name).' ['.$row->email.']'. '</option>';
            }
        }
        return $str;


    }
}
