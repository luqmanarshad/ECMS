<?php

namespace App\Http\Controllers;

use App\Institute;
use App\Rules\CheckInstituteCode;
use App\Rules\CheckInstituteName;
use App\Rules\CheckSubjectSingleQuote;
use App\User;
use Illuminate\Http\Request;

class InstituteController extends Controller
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
        $data['page_title'] = 'Institutions List';
        $data['result']     = Institute::where('is_deleted', 0)->orderBy('id', 'DESC')->get();
        return view('institute.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['page_title'] = 'Add Institute';
        return view('institute.add', $data);
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
            'institute_name'    => ['required', new CheckInstituteName($request->all()), new CheckSubjectSingleQuote()],
            /* 'institute_code'    => new CheckInstituteCode($request->all()),*/
            'institute_address' => 'required'
        ]);
        $book                    = new Institute();
        $book->institute_name    = strtolower(trim($request->input('institute_name')));
        $book->institute_code    = Institute::max('id') + 1;
        $book->institute_address = $request->input('institute_address');
        $book->created_by        = session('user_info')['admin_id'];
        $book->updated_by        = 0;
        $book->save();
        return redirect('admin/institute')->with('msg_success', 'institute created successfully.');
        //
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
        $data['page_title'] = 'Edit Institute';
        $row                = Institute::findOrFail($id);
        if ($row) {
            $data['row'] = $row;
            return view('institute.edit', $data);
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
        $id        = base64url_decode($id);
        $institute = Institute::findOrFail($id);
        $this->validate($request, [
            'institute_name'    => ['required', new CheckInstituteName($request->all()), new CheckSubjectSingleQuote()],
            /*'institute_code'    => new CheckInstituteCode($request->all()),*/
            'institute_address' => 'required'
        ]);
        $institute->institute_name    = strtolower(trim($request->input('institute_name')));
        $institute->institute_address = $request->input('institute_address');
        $institute->updated_by        = session('user_info')['admin_id'];
        $institute->save();
        return redirect('admin/institute')->with('msg_success', 'institute update successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $id        = base64url_decode($id);
        $institute = Institute::findOrFail($id);
        if (User::where(['institute_id' => $id, 'active' => 1])->first()) {
            return back()->with('msg_warning', 'This Institute have Teachers, cannot delete it.');
        }

        $institute->is_deleted = 1;
        $institute->save();
        return redirect('admin/institute')->with('msg_success', 'Institute deleted successfully');
    }

}
