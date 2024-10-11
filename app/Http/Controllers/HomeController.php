<?php

namespace App\Http\Controllers;

use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $data['result']        = Question::getAdminDashboardData();
        $data['teacher']       = Question::getTeacherDashboardData();
        //echo "<pre>"; print_r( $data['teacher']);exit;
        if($user->hasRole('Admin')) {
            $data['most_approved'] = Question::getTeacherMostApproved();
            $data['most_rejected'] = Question::getTeacherMostRejected();
            $data['most_pending']  = Question::getTeacherMostPending();
        }
        return view('dashboard.dashboard', $data);
    }
}
