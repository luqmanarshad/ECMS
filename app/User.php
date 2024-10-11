<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use \Venturecraft\Revisionable\RevisionableTrait;


class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    use RevisionableTrait;
    protected $revisionEnabled          = true;
    protected $revisionCreationsEnabled = true;

    protected $table    = 'users';
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->hasOne('App\Role', 'id', 'role_id');
    }

    public function institute()
    {
        return $this->hasOne('App\Institute', 'id', 'institute_id');
    }

    public function network()
    {
        return $this->hasOne('App\Mobile_netwok', 'id', 'network_id');
    }

    public static function checkPendingQuestion($userId)
    {
        return Question::where('status', 'No')
            ->where('current_state', '!=', 4)
            ->where('is_deleted', 0)
            ->where(function ($q) use ($userId) {
                $q->where('assign_to', $userId)
                    ->orWhere('user_id', $userId);
            })
            ->whereRaw('(questions.question_status = 0 OR questions.question_status = 1 OR questions.question_status = 2)')
            ->first();
    }

    public static function checkTeachers($subject_id)
    {
        return User::where('active', 1)
            ->whereRaw("FIND_IN_SET($subject_id,'subject_id')")
            ->first();
    }

    public static function getTeacherForAssignQuestion($subjectId, $typeId, $user_id, $teacher1_id)
    {
        $sql = User::where('active', 1)
            ->whereRaw("FIND_IN_SET($subjectId,subject_id)")
            ->whereRaw("FIND_IN_SET($typeId,type_id)");
        if (!empty($user_id)) {
            $sql->where('id', '<>', (int)$user_id);
        }
        if (!empty($user_id)) {
            $sql->where('id', '<>', (int)$teacher1_id);
        }
        return $sql->orderByRaw('RAND()')->first();
    }

    public static function getTeacherBySubjectId($subjectId)
    {
        $sql = User::where('active', 1)
               ->whereRaw("FIND_IN_SET($subjectId,subject_id)");
        return $sql->get();
    }
}


