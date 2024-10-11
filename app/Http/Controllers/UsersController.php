<?php

namespace App\Http\Controllers;

use App\Institute;
use App\Rules\ValidCnic;
use App\Subject;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;


class UsersController extends Controller
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
        $data['page_title'] = 'Users List';
        $data['results']    = User::all();
        return view('user.index', $data);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['page_title']  = 'Add User';
        $data['id_subjects'] = array();
        return view('user.add', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //$data['id_subjects'] = array();
        $this->validate($request, [
            'first_name' => 'required',
            'last_name'  => 'required',
            'email'      => 'required|email|unique:users',
            'contact_no' => 'required|unique:users,contact_no',
            'network_id' => 'required',
            'cnic'       => new ValidCnic($request->all()),
            'password'   => 'required',
            'passconf'   => 'required'
        ]);
        $role = $request->get('role_id');
        if ($role == 3) {
            $this->validate($request, [
                'institute_id' => 'required',
                'subject_id[]' => 'array',
            ]);
        }

        $type_id     = '';
        $cnic_no     = str_replace("-", "", $request->input('cnic'));
        $subjects    = $request->input('subject_id');
        $subjectList = null;
        if (!empty($subjects) && count($subjects) > 0) {
            $subjectList = implode(",", $subjects);
            $types       = Subject::whereIN('id', $subjects)->get();
            if (!empty($types) && count($subjects) > 0) {
                foreach ($types as $type) {
                    $type_ids[] = $type['type_id'];
                }
            }
            $types = array_unique($type_ids);
            if (!empty($types)) {
                $type_id = implode(",", $types);
            }
        }

        $user                 = new User();
        $user->first_name     = $request->input('first_name');
        $user->last_name      = $request->input('last_name');
        $user->email          = $request->input('email');
        $user->password       = Hash::make($request->input('password'));
        $user->role_id        = $request->input('role_id');
        $user->subject_id     = $subjectList;
        $user->contact_no     = $request->input('contact_no');
        $user->network_id     = $request->input('network_id');
        $user->cnic           = $cnic_no;
        $user->type_id        = $type_id;
        $user->institute_id   = $request->input('institute_id');
        $user->active         = 1;
        $user->faculty_type   = $request->input('faculty_type');
        $user->otp            = 0000;
        $user->remember_token = '';
        $user->save();
        /*-- ASSIGN PERMISSION TO USER --*/
        if (!is_null($role)) {
            $role_id = Role::where('id', '=', $role)->firstOrFail();
            $user->assignRole($role_id);
            $allPermission = $role_id->load('permissions');
            foreach ($allPermission->permissions as $row) {
                $user->givePermissionTo($row->name);
            }
        }
        return redirect('admin/user')->with('msg_success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $id                 = (int)base64url_decode($id);
        $data['page_title'] = 'User Detail';
        $user               = User::findOrFail($id);
        if ($user) {
            $data['user'] = $user;
            return view('user.show', $data);
        } else {
            return back()->with('msg_fail', 'No Record found against Id.');
        }

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
        $data['page_title'] = 'Edit User';
        $user               = User::findOrFail($id);
        if ($user) {
            $data['row'] = $user;
            return view('user.edit', $data);
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
        $id   = base64url_decode($id);
        $user = User::findOrFail($id);

        $this->validate($request, [
            'first_name' => 'required',
            'last_name'  => 'required',
            'contact_no' => 'required|unique:users,contact_no,' . $id,
            'network_id' => 'required',
            'cnic'       => 'required',
            'network_id' => 'required'
        ]);
        $this->validate($request, ['cnic' => new ValidCnic($request->all())]);
        $password = $request->get('password');
        if ($password) {
            $this->validate($request, [
                'password' => 'required',
                'passconf' => 'required'
            ]);
        }

        $type_id  = '';
        $cnic_no  = str_replace("-", "", $request->input('cnic'));
        $subjects = $request->input('subject_id');

        /*--Assign new Subject to teacher ---*/
        if ($request->input('assign_new_subject')) {
            $new_subject = $request->input('subject_id', true);
            $old_subject = explode(',', $user->subject_id);
            $subjects    = array_unique(array_merge($new_subject, $old_subject));

            if (!empty($subjects) && count($subjects) > 0) {
                $subjectList = implode(",", $subjects);
                $types       = Subject::whereIN('id', $subjects)->get();
                if (!empty($types) && count($subjects) > 0) {
                    foreach ($types as $type) {
                        $type_ids[] = $type->type_id;
                    }
                }
                $old_type = explode(',', $user->type_id);
                $types    = array_unique(array_merge($type_ids, $old_type));
                if (!empty($types)) {
                    $type_id = implode(",", $types);
                }
            }
        }

        /*-----------------------------------------*/

        //$user = new User();
        $user->first_name = $request->input('first_name');
        $user->last_name  = $request->input('last_name');
        $user->cnic       = $cnic_no;
        $user->contact_no = $request->input('contact_no');
        $user->network_id = $request->input('network_id');
        $user->faculty_type = $request->input('faculty_type');
        
        if (!empty($subjectList)) {
            $user->subject_id = $subjectList;
        }
        if (!empty($type_id)) {
            $user->type_id = $type_id;
        }
        if ($request->input('password')) {
            $user->password = bcrypt($request->input('password'));
        }
        $user->save();
        return redirect('admin/user')->with('msg_success', 'Information updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, $action)
    {
        //

        $id   = (int)base64url_decode($id);
        $user = User::findOrFail($id);
        if ($action == 'inactive') {
            $val = '0';
        } else {
            $val = '1';
        }

        //CHECK ITS PENDING QUESTION AVAILABLE FOR REVIEW.
        if ($action == 'inactive') {
            if (User::checkPendingQuestion($id)) {
                return back()->with('msg_warning', 'This user have Pending Questions, cannot be marked as Inactive.');
            }
        }
        //CHECK its institute active or not.
        if ($action == 'active') {
            if (Institute::where(['is_deleted'=>1,'id'=>$user->institute_id])->first()) {
                return back()->with('msg_warning', 'Institute Of this user not exist in the system, cannot active.');
            }
        }
        $user->active = $val;
        $user->save();
        return back()->with('msg_success', 'Status set to ' . $action . ' successfully.');
    }

    public function getTeacherBySubjectID(Request $request)
    {
        if($request->ajax()) {
            $subject_id        = $request->input('subject_id');
            $result            = getTeacherBySubjectId($subject_id);
            $reponse['result'] = $result;
            echo json_encode($reponse);
            exit;
        }

    }
}
