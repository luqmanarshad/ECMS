<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AjaxController extends Controller
{
    //

    /*------ Get section by subject_id ---------*/
    public function getSectionBySubjectId(Request $request)
    {
        $subject_id        = $request->input('subject_id');
        $result            = getSectionBySubjectId($subject_id);
        $reponse['result'] = $result;
        echo json_encode($reponse);
    }

    /*----- GET TOPIC BY SECTION ID -------*/
    public function get_topics_by_section_id(Request $request)
    {
        $section_id        = $request->input('section_id');
        $result            = getTopicsBySectionId($section_id);
        $reponse['result'] = $result;
        echo json_encode($reponse);
        exit;
    }

    /* ----GET THEME BY TOPIC(OLD SECTION) -------*/
    public function get_themes_by_section_id(Request $request)
    {
        $section_id        = $request->input('section_id');
        $result            = themeDropDownBySectionID($section_id);
        $reponse['result'] = $result;
        echo json_encode($reponse);
        exit;
    }

    /*-------GET THEME BY SUB TOPIC(OLD TOPIC) --------*/
    public function get_theme_by_subtopic_id(Request $request)
    {
        
        $sub_topic_id      = $request->input('sub_topic_id');
        $topic_id          = $request->input('topic_id');
        $result            = themeDropDown($topic_id,$sub_topic_id);
        $reponse['result'] = $result['str'];
        $reponse['found']  = $result['found'];
        echo json_encode($reponse);
        exit;
    }

    /* --- GET THEME BY SUB theme ID ---- */
    public function getSubThemeByThemeId(Request $request)
    {
        $theme_id          = $request->input('theme_id');
        $result            = subThemeDropDown($theme_id);
        $reponse['result'] = $result;
        echo json_encode($reponse);
        exit;
    }

    /* --- GET BOOKS BY SUBJECT ID ---- */
    public function get_book_reference(Request $request)
    {
        $subject_id        = $request->input('subject_id');
        $result            = getBookReference($subject_id);
        $reponse['result'] = $result;
        echo json_encode($reponse);
        exit;
    }

    public function changePassword(Request $request)
    {
        $this->validate($request, [
            'c_password'     => 'required',
            'password'       => 'required',
            'password_again' => 'required'
        ]);

        $user = User::find(Auth::id());
        if (!Hash::check($request->c_password, $user->password)) {
            return response()->json(['error' => 'Current password does not match']);
        }
        $user->password = Hash::make($request->password);
        $user           = $user->save();
        if ($user) {
            Auth::logout();
            return response()->json(['success' => 'password changed']);

        }
    }

}
