<?php

namespace App\Rules;

use App\Topic;
use Illuminate\Contracts\Validation\Rule;

class CheckTopic implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $data;
    public function __construct($result)
    {  $this->data =  $result;
        //
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
        $topic_name = strtolower(trim($value));
        $where = '';
        if(isset($this->data['id'])){
            $where.= " id != ".$this->data['id']."";
        }else{
            $where.= '1=1';
        }
        //$where.=" AND is_deleted = 1";
        //$exist = Topic::whereRaw("LOWER(topic_name) = '".trim($topic_name)."' AND subject_id= '".$this->data['subject_id']."' AND  type_id = '".$this->data['type_id']."'    ".$where."")->first();
        $exist = Topic::where(['topic_name'=>$topic_name, 'subject_id'=>$this->data['subject_id'],'type_id' =>$this->data['type_id'],'is_deleted'=>1,'section_id'=>$this->data['section_id']])
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
        return 'Sub Topic Name already Exist.';
    }
}
