<?php

namespace App\Rules;

use App\Subject;
use Illuminate\Contracts\Validation\Rule;

class CheckSubject implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $data;

    public function __construct($result)
    {
        $this->data =  $result;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        //
        $subject = strtolower($value);
        $where = '';
        if(isset($this->data['id'])){
            $where.= " id != ".$this->data['id']."";
        }else{
            $where.= '1=1';
        }
       // $where.= " AND status = 1";
       // $exist = Subject::whereRaw("LOWER(subject_name) = ".$subject." AND type_id = ".$this->data['type_id']."    ".$where."")->first();
        $exist = Subject::where(['subject_name'=>$subject,'type_id'=>$this->data['type_id'],'status'=>1 ])
                        ->whereRaw($where)->first();
        if($exist){
            return False;
        }else{
            return True;
        }

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Subject Name already Exist.';
    }
}
