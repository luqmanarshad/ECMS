<?php

namespace App\Http\Controllers;

use App\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:Admin']);
    }
    //

    public function index()
    {
        $data['page_title'] = 'Setting';
        $data['setting']    = Setting::first();
        return view('setting.index', $data);
    }

    public function update(Request $request, $id)
    {
        $id                          = base64url_decode($id);
        $setting                     = Setting::findOrFail($id);
        $setting->incorrect_question = $request->input('incorrect_question') ? $request->input('incorrect_question') : 0;
        $setting->assign_question    = $request->input('assign_question') ? $request->input('assign_question') : 0;
        $setting->approve_question   = $request->input('approve_question') ? $request->input('approve_question') : 0;
        $setting->reject_question    = $request->input('reject_question') ? $request->input('reject_question') : 0;
        $setting->user_creation      = $request->input('user_creation') ? $request->input('user_creation') : 0;
        $setting->email_incorrect_question         = $request->input('email_incorrect_question') ? $request->input('email_incorrect_question') : 0;
        $setting->email_assign_question         = $request->input('email_assign_question') ? $request->input('email_assign_question') : 0;
        $setting->email_approve_question         = $request->input('email_approve_question') ? $request->input('email_approve_question') : 0;
        $setting->email_reject_question         = $request->input('email_reject_question') ? $request->input('email_reject_question') : 0;
        $setting->email_user_creation         = $request->input('email_user_creation') ? $request->input('email_user_creationincorrect_question') : 0;
        $setting->save();
        return back()->with('msg_success', 'Setting update successfully');
    }
}
