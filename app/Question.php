<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use \Venturecraft\Revisionable\RevisionableTrait;

class Question extends Model
{
    use RevisionableTrait;
    protected $revisionEnabled          = true;
    protected $revisionCreationsEnabled = true;
    //
    protected $table = 'questions';

    public function subject()
    {
        return $this->hasOne('App\Subject', 'id', 'subject_id');
    }

    public function section()
    {
        return $this->hasOne('App\Section', 'id', 'section_id');
    }

    public function professional()
    {
        return $this->hasOne('App\Professional', 'id', 'type_id');
    }

    public function topic()
    {
        return $this->hasOne('App\Topic', 'id', 'topic_id');
    }

    public function theme()
    {
        return $this->hasOne('App\Theme', 'id', 'theme_id');
    }

    public function subTheme()
    {
        return $this->hasOne('App\SubTheme', 'id', 'sub_theme_id');
    }


    public function cognitive()
    {
        return $this->hasOne('App\Cognitive_level', 'id', 'cognitive_level');
    }

    public function getRelevance()
    {
        return $this->hasOne('App\Relevance', 'id', 'relevance');
    }

    public function difficulty()
    {
        return $this->hasOne('App\Question_diff', 'id', 'diff_id');
    }

    public function questionType()
    {
        return $this->hasOne('App\QuestionType', 'id', 'qtype_id');
    }


    public static function getAssignQuestion()
    {
        $role_id    = session('user_info')['role_id'];
        $user_id    = session('user_info')['admin_id'];
        $type_id    = session('user_info')['type_id'];
        $subject_id = session('user_info')['subject_id'];
        $subject_id = explode(',', $subject_id);

 
        $sql = Question::select('questions.id', 'questions.qtype_id', 'questions.question_status', 'subjects.subject_name', 'section.section_name', 'professional.p_name')
            ->join('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->join('section', 'section.id', '=', 'questions.section_id')
            ->join('professional', 'professional.id', '=', 'questions.type_id')
            ->where('questions.status', 'No')
            ->where('questions.current_state', '<>', 4)
            ->where('questions.is_deleted', 0)
            ->whereRaw('(questions.question_status = 0 OR questions.question_status = 1 OR questions.question_status = 2)');
        if (is_array($subject_id)) {
            $sql->whereIn('questions.subject_id', $subject_id);
        } else {
            $sql->where('questions.subject_id', $subject_id);
        }
 
        if ($role_id != 1) { 
            $sql->where('questions.assign_to', $user_id);
            $sql->where('questions.user_id', '<>', $user_id);
        }
 
        if (!empty($type_id)) {
            $types = explode(",", $type_id);
            if (is_array($subject_id)) {
                $sql->whereIn('questions.type_id', $types);
            } else {
                $sql->where('questions.type_id', $types);
            }
        }
        $sql->orderBy('questions.id');
        $result = $sql->first(); 
        return $result;
    }

    public static function getQustionView($id, $qtype_id)
    {

        $user_id = session('user_info')['admin_id'];
        $role_id = session('user_info')['role_id'];
        if ($qtype_id == 1) {
            $sql = Question::select('questions.*', 'subjects.subject_name', 'topic.topic_name', 'section.section_name',
                'question_types.question_type', 'questions.question', 'professional.p_name', 'is_scenario_question')
                ->join('question_types', 'question_types.id', '=', 'questions.qtype_id')
                ->join('subjects', 'subjects.id', '=', 'questions.subject_id')
                ->join('section', 'section.id', '=', 'questions.section_id')
                ->join('topic', 'topic.id', '=', 'questions.topic_id')
                ->join('professional', 'professional.id', '=', 'questions.type_id')
                ->where('questions.id', $id)
                ->where('questions.is_deleted', 0);
        } elseif ($qtype_id == 2) {
            $sql = Question::select('questions.*', 'subjects.subject_name', 'question_types.question_type', 'professional.p_name', 'section.section_name', 'is_scenario_question')
                ->from("questions")
                ->join('question_types', 'question_types.id', '=', 'questions.qtype_id')
                ->join('subjects', 'subjects.id', '=', 'questions.subject_id')
                ->join('section', 'section.id', '=', 'questions.section_id')
                ->join('professional', 'professional.id', '=', 'questions.type_id')
                ->where('questions.id', $id)
                ->where('questions.is_deleted', 0);
        }
        if ($user_id && ($role_id == 3)) {
            $sql->where('questions.user_id', '<>', $user_id);
            $sql->where('questions.assign_to', '=', $user_id);
        }
        $sql->orderBy('questions.id');
        $result = $sql->first();

        return $result;
    }

    public static function getAdminDashboardData()
    {
        $role_id = session('user_info')['role_id'];
        $user_id = session('user_info')['admin_id'];
        $result  = DB::table('questions')
            ->select(DB::raw("COUNT(id) as total,
                    SUM(CASE WHEN (current_state = 1 OR current_state =2 OR  current_state =3 ) 
                                        AND (question_status =0 OR question_status =1 OR question_status =2 ) 
                                        AND (`status`= 'No' OR `status`= 'Yes') THEN 1 ELSE 0 END ) as pending,                    
                    SUM(CASE WHEN (current_state = 4 AND question_status = 3 AND `status`= 'Yes') THEN 1 ELSE 0 END ) as approved,					
                    SUM(CASE WHEN (current_state = 4 AND question_status = 4 AND `status`= 'No') THEN 1 ELSE 0 END ) as rejected,					
                    SUM(CASE WHEN (current_state = 1 AND question_status = 0 AND `status`= 'No') THEN 1 ELSE 0 END ) as first_review,					
                    SUM(CASE WHEN (current_state = 2 AND question_status = 1 AND `status`= 'No') THEN 1 ELSE 0 END ) as second_review,					
                    SUM(CASE WHEN (current_state = 3 AND question_status = 2 AND `status`= 'No') THEN 1 ELSE 0 END ) as third_review					
                    "))->where('is_deleted', 0);
        if ($role_id != 1) {
            $result = $result->where('user_id', $user_id);
        }
        $result = $result->first();
        return $result;
    }

    public static function getTeacherMostApproved()
    {
        $result = DB::select("SELECT CONCAT(users.first_name,' ',users.last_name) as teacher_name,
                              COUNT(questions.id) As approved,users.type_id as type_id,
                              SUM(IF(is_deleted = 0, 1, 0)) AS total_question
                            FROM
                            questions
                            JOIN users ON users.id = questions.user_id
                            WHERE is_deleted = 0 AND question_status = 3 AND current_state = 4 AND `status`='Yes'
                            GROUP BY questions.user_id
                            ORDER BY COUNT(questions.id) DESC
                            LIMIT 5");
        return $result;

    }

    public static function getTeacherMostRejected()
    {
        $result = DB::select("SELECT CONCAT(users.first_name,' ',users.last_name) as teacher_name,
                            COUNT(questions.id) As rejected,users.type_id as type_id,
                            SUM(IF(is_deleted = 0, 1, 0)) AS total_question
                            FROM
                            questions
                            JOIN users ON users.id = questions.user_id
                            WHERE is_deleted = 0 AND question_status = 4 AND current_state = 4 AND `status`='No'
                            GROUP BY questions.user_id
                            ORDER BY COUNT(questions.id) DESC
                            LIMIT 5");
        return $result;
    }

    public static function getTeacherMostPending()
    {
        $result = DB::select("SELECT CONCAT(users.first_name,' ',users.last_name) as teacher_name,
                              COUNT(questions.id) As pending,users.type_id as type_id,
                              SUM(IF(is_deleted = 0, 1, 0)) AS total_question
                            FROM
                            questions
                            JOIN users ON users.id = questions.assign_to
                            WHERE is_deleted = 0 AND (question_status = 0 OR question_status = 1 OR question_status = 2 )  AND current_state != 4 
                            GROUP BY questions.assign_to
                            ORDER BY COUNT(questions.id) DESC
                            LIMIT 5");
        return $result;

    }

    public static function getTeacherDashboardData()
    {
        $user_id = session('user_info')['admin_id'];
        $result  = DB::table('questions')
            ->select(DB::raw(" 
		SUM(CASE WHEN (teacher1_id = $user_id AND (teacher1_rejected = 1 OR teacher1_accepted = 1)  AND question_status <> 5) THEN 1 ELSE 0 END ) as first_review,
		
		SUM(CASE WHEN (teacher2_id = $user_id AND (teacher2_rejected = 1 OR teacher2_accepted = 1 OR teacher2_incorrect = 1) 
									AND question_status <> 5) THEN 1 ELSE 0 END ) as second_review,					
		SUM(CASE WHEN (teacher3_id = $user_id AND (teacher3_rejected = 1 OR teacher3_accepted = 1) AND current_state = 4  AND question_status <> 5)
									THEN 1 ELSE 0 END ) as third_review,
									
	  SUM(CASE WHEN ((current_state = 1 OR current_state = 3 OR current_state = 2) AND (question_status = 0 OR question_status = 1  OR question_status = 2)
				AND question_status != 5 AND `status`='No' AND assign_to =$user_id AND user_id <> $user_id ) THEN 1 ELSE 0 END ) as pending,					
		
		SUM(CASE WHEN ((teacher1_accepted = 1 OR teacher2_accepted = 1 OR teacher3_accepted = 1) AND current_state = 4 AND question_status = 3 
			AND `status` = 'Yes' AND assign_to = $user_id ) THEN 1 ELSE 0 END ) as approved,					
		
		SUM(CASE WHEN (teacher1_id = $user_id AND teacher1_rejected = 1)
				THEN 1 ELSE 0 END ) as first_reject,
		SUM(CASE WHEN (teacher2_id = $user_id AND teacher2_rejected = 1  ) 
			THEN 1 ELSE 0 END ) as second_reject,
		SUM(CASE WHEN (teacher3_id = $user_id AND teacher3_rejected = 1  )
			THEN 1 ELSE 0 END ) as third_reject,
		SUM(CASE WHEN (teacher2_id = $user_id AND teacher2_incorrect = 1)
			THEN 1 ELSE 0 END ) as incorrect"))
            ->where('is_deleted', 0)->first();
        return $result;
    }

    public static function getReports($where, $groupBy = FALSE)
    {
        $user_id = session('user_info')['admin_id'];
        $role_id = session('user_info')['role_id'];

        $userData = Auth::user();
        if ($groupBy == 'professional.id') {
            $mainModel    = 'professional';
            $table        = $groupBy;
            $forignKey    = 'type_id';
            $subTableJoin = 'topic.type_id';
        } elseif ($groupBy == 'subjects.id') {
            $mainModel    = 'subjects';
            $table        = $groupBy;
            $forignKey    = 'subject_id';
            $subTableJoin = 'topic.subject_id';
        } elseif ($groupBy == 'section.id') {
            $mainModel    = 'section';
            $table        = $groupBy;
            $forignKey    = 'section_id';
            $subTableJoin = 'topic.section_id';
        } elseif ($groupBy == 'topic.id') {
            $mainModel = 'topic';
            $table     = $groupBy;
            $forignKey = 'topic_id';
            /*--There are 4 cases to handel----*/
            if (empty($where['questions.type_id']) && empty($where['questions.qtype_id'])) {
                $subTableJoin = 'topic.type_id';
            } elseif (!empty($where['questions.type_id']) && !empty($where['questions.qtype_id'])) {
                $subTableJoin = 'topic.id';
            } elseif (empty($where['questions.type_id']) && !empty($where['questions.qtype_id'])) {
                $subTableJoin = 'topic.id';
            } elseif (!empty($where['questions.type_id']) && empty($where['questions.qtype_id'])) {
                $subTableJoin = 'topic.type_id';
            } else {
                $subTableJoin = 'topic.type_id';
            }


        } elseif ($groupBy == 'professional_exam.id') {
            $mainModel = 'professional_exam';
            $table     = $groupBy;
            $forignKey = 'prof_id';
        } elseif ($groupBy == 'question_types.id') {
            $mainModel = 'question_types';
            $table     = $groupBy;
            $forignKey = 'qtype_id';
        } elseif ($groupBy == 'question_diffs.id') {
            $mainModel = 'question_diffs';
            $table     = $groupBy;
            $forignKey = 'diff_id';
        } else {
            $mainModel    = 'professional';
            $table        = 'professional.id';
            $forignKey    = 'type_id';
            $subTableJoin = 'topic.type_id';
        }
        //dd($subTableJoin);

        if (!empty($groupBy)) {
            $subGroup = 'GROUP BY ' . $groupBy;
            $subTable = $groupBy;
            if ($subTable == 'topic.id') {
                $subTable     = 'questions.topic_id';
                $subTableJoin = 'topic.id';

            }
        } else {
            $subTable     = 'professional.id';
            $subTableJoin = 'topic.type_id';
            $subGroup     = 'GROUP BY professional.id';
        }
        $real  = "1=1";
        $inner = '';
        if (!empty($where)) {
            $innerWhere = $where;
            unset($innerWhere['questions.is_deleted']);
            unset($innerWhere['questions.qtype_id']);
            unset($innerWhere['questions.diff_id']);
            foreach ($innerWhere as $key => $val) {
                $key = str_replace('questions', 'topic', $key);
                if ($key == 'topic.topic_id') {
                    $key = 'topic.id';
                }
                $inner .= $key . '=' . $val . ' AND ';
            }
        }
        $inner = $inner . $real;
        if ($groupBy == 'topic.id') {
            $seqQueru = 'topic.total_seqs';
            $mcqQueru = 'topic.total_mcqs';
        } else {
            $seqQueru = "(SELECT SUM(topic.total_seqs) FROM topic WHERE $subTable=$subTableJoin AND  $inner $subGroup)";
            $mcqQueru = "(SELECT SUM(topic.total_mcqs) FROM topic WHERE $subTable=$subTableJoin AND  $inner $subGroup)";
        }


        $result = DB::table('professional')->select(
            DB::raw("COUNT(questions.id) as total"),
            DB::raw("COUNT(DISTINCT (CASE WHEN questions.diff_id = 1 THEN questions.id END)) AS easy"),
            DB::raw("COUNT(DISTINCT (CASE WHEN questions.diff_id = 2 THEN questions.id END)) AS average"),
            DB::raw("COUNT(DISTINCT (CASE WHEN questions.diff_id = 3 THEN questions.id END)) AS hard"),
            DB::raw("COUNT(DISTINCT (CASE WHEN questions.qtype_id = 1 AND current_state = 4 AND question_status = 3 AND questions.status= 'Yes' THEN questions.id  END)) AS approved_mcq"),
            DB::raw("COUNT(DISTINCT (CASE WHEN questions.qtype_id = 2 AND current_state = 4 AND question_status = 3 AND questions.status= 'Yes' THEN questions.id  END)) AS approved_seq"),
            DB::raw("COUNT(DISTINCT (CASE WHEN (current_state = 1 OR current_state =2 OR  current_state =3 ) 
                                   AND (question_status =0 OR question_status =1 OR question_status =2 ) 
                                   AND (questions.status= 'No' OR questions.status= 'Yes') THEN questions.id END )) as pending"),
            DB::raw("COUNT(DISTINCT(CASE WHEN (current_state = 4 AND question_status = 4 AND questions.status= 'No') THEN questions.id END )) as rejected"),
            'subjects.subject_name', 'topic.topic_name',
            'section.section_name', 'question_types.question_type', 'professional.p_name',
            'professional_exam.p_exam',
            DB::raw("$seqQueru as total_seqs"),
            DB::raw("$mcqQueru as total_mcqs"));

        $result->leftJoin('subjects', 'subjects.type_id', '=', 'professional.id');

        $result->leftJoin('section', 'section.subject_id', '=', 'subjects.id');

        $result->leftJoin('professional_exam', 'professional_exam.id', '=', 'section.prof_id');

        $result->leftJoin('topic', 'topic.section_id', '=', 'section.id');


        $result->leftJoin('questions', function ($join) use ($where, $table, $forignKey) {
            if ($table == 'professional_exam.id') {
                $join->on('questions.type_id', '=', 'professional.id');
                $join->where($where);
            } else {
                if (!empty($where['questions.subject_id'])) {
                    $table     = 'topic.id';
                    $forignKey = 'topic_id';
                }
                $join->on('questions.' . $forignKey, '=', $table);
                $join->where($where);
            }


        });
        if ($mainModel != 'question_types') {
            $result->leftJoin('question_types', 'question_types.id', '=', 'questions.qtype_id');
        }
        if ($mainModel != 'question_diffs') {
            $result->leftJoin('question_diffs', 'question_diffs.id', '=', 'questions.diff_id');
        }
        /*--When only group by selected--*/
        if (empty($groupBy) && empty($where['questions.type_id'])) {
            $result->where($where);
        } else if (!empty($where['questions.type_id']) && $where['questions.type_id'] > 0) {
            $result->where($where);
        }
        if (!empty($groupBy)) {
            $result->groupBy($groupBy);
        } else {
            $result->groupBy($table);
        }
        return $result->get();
    }

    /*____________________Teacher Wise Report _____________________*/
    public static function getTeacherCreatedQuestions($type_id = NULL, $subject_id = NULL)
    {
        $result = User::selectRaw("CONCAT(first_name,' ',last_name) as user_name,
                                    users.type_id,
                                    users.subject_id,
                                    users.id as user_id,
                                    SUM(IF(questions.id>0 AND questions.is_deleted=0,1,0))  as total_created,
                                    SUM(IF(question_status = 4 AND questions.is_deleted=0, 1, 0)) AS rejected,
                                    SUM(IF(question_status = 3 AND questions.is_deleted=0, 1, 0)) AS approved,
                                    SUM(IF(question_status = 0 AND questions.is_deleted=0, 1, 0)) AS first_pending,
                                    SUM(IF(question_status = 1 AND questions.is_deleted=0, 1, 0)) AS second_pending,
                                    SUM(IF(question_status = 2 AND questions.is_deleted=0, 1, 0)) AS third_pending")
            ->leftJoin('questions', 'questions.user_id', '=', 'users.id')
            ->where('users.active', 1)
            ->where('users.role_id', 3);
        if (!is_null($type_id)) {
            $result->whereRaw("FIND_IN_SET($type_id,users.type_id)");
        }
        if (!is_null($subject_id)) {
            $result->whereRaw("FIND_IN_SET($subject_id,users.subject_id)");
        }
        $result->groupBy('users.id')
            ->orderBY('users.id', 'ASCE');

        return $result->get();
    }

    /*________________Teacher Review Question Count_________________*/
    public static function getTeacherReviewedQuestions($type_id = NULL, $subject_id = NULL)
    {
        $result = DB::table('users')->select(
            DB::raw("CONCAT(users.first_name,' ',users.last_name) as user_name,
                                    users.type_id,
                                    users.subject_id,
                                    users.id as user_id,
                                    SUM(IF(question_status = 0 , 1, 0)) AS first_pending,
                                    SUM(IF(question_status = 1 , 1, 0)) AS second_pending,
                                    SUM(IF(question_status = 2 , 1, 0)) AS third_pendiang,
                                    SUM(IF(question_status = 3 , 1, 0)) AS approved,
                                    (SELECT COALESCE(SUM(IF(teacher1_rejected = 1 , 1, 0)),0) FROM questions WHERE users.id = questions.teacher1_id) as first_rejected,
                                    (SELECT COALESCE(SUM(IF(teacher2_rejected = 1 , 1, 0)),0) FROM questions WHERE users.id = questions.teacher2_id ) as second_rejected,
                                    (SELECT COALESCE(SUM(IF(teacher2_incorrect = 1 , 1, 0)),0) FROM questions WHERE users.id = questions.teacher2_id) as incorrect,
                                    (SELECT COALESCE(SUM(IF(teacher3_rejected = 1 , 1, 0)),0) FROM questions WHERE users.id = questions.teacher3_id) as third_rejected"))
            ->leftJoin('questions', function ($join) {
                $join->on('questions.assign_to', '=', 'users.id');
                $join->on('questions.user_id', '!=', 'users.id');
                $join->where('questions.is_deleted', 0);
            })
            ->where('users.active', 1)
            ->where('users.role_id', 3);
        if (!is_null($type_id)) {
            $result->whereRaw("FIND_IN_SET($type_id,users.type_id)");
        }
        if (!is_null($subject_id)) {
            $result->whereRaw("FIND_IN_SET($subject_id,users.subject_id)");
        }
        $result->groupBy('users.id')
            ->orderBY('users.id', 'ASCE');


        return $result->get();
    }

    public static function getTeacherSubjectReport($userID)
    {
        $CurrentUser = Auth::user();
        $user        = User::findOrFail($userID);

        //Admin
        if ($CurrentUser->role_id == 1) {
            $subjectIds = explode(',', $user->subject_id);
        } else { //if Teacher
            $subjectIds = explode(',', $CurrentUser->subject_id);
        }

        $result = Professional::selectRaw("professional.p_name,
                                            subjects.subject_name,
                                            section.section_name,
                                            topic.topic_name,
                                            topic.total_mcqs,
                                            topic.total_seqs,
                                            SUM(IF(questions.qtype_id=1,1,0)) as mcq_created,
                                            SUM(IF(questions.qtype_id=2,1,0)) as seq_created")
            ->leftJoin('subjects', 'subjects.type_id', '=', 'professional.id')
            ->leftJoin('section', 'section.subject_id', '=', 'subjects.id')
            ->leftJoin('topic', 'topic.section_id', '=', 'section.id')
            ->leftJoin('questions', function ($join) use ($userID) {
                $join->on('questions.topic_id', '=', 'topic.id');
                $join->where('questions.is_deleted', 0);
                $join->where('questions.user_id', $userID);
            });

        $result->WhereIn('subjects.id', $subjectIds);
        $result->groupBy(['p_name', 'subject_name', 'section_name', 'topic_name']);

        return $result->get();
    }

    /*------DUBLICATE QUESTION MATCHING--------*/
    public static function questionMatching($where, $newQuestion)
    {
        $question = Question::select('question')->where($where)->get();

        if (!empty($question) && count($question) > 0) {
            foreach ($question as $row) {
                $dbQuestion = option_decrypt($row->question);
                if (trim($newQuestion) == $dbQuestion) {
                    return TRUE;
                }
            }
            return FALSE;
        }
        return FALSE;
    }
}


