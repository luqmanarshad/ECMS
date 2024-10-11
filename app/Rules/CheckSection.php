<?php

namespace App\Rules;

use App\Section;
use Illuminate\Contracts\Validation\Rule;

class CheckSection implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $data;
    public function __construct($result)
    {
        //
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
        $section = strtolower(trim($value));
        $where = '';
        if(isset($this->data['id'])){
            $where.= " id != ".$this->data['id']."";
        }else{
            $where.= '1=1';
        }
        //$where.=" AND is_deleted = 0  AND prof_id =".$this->data['prof_id'];
        //$exist = Section::whereRaw("LOWER(section_name) = '".$subject."' AND type_id = '".$this->data['type_id']."'    ".$where."")->first();
        $exist = Section::where(['subject_id'=>$this->data['subject_id'],'section_name'=>$section,'type_id'=>$this->data['type_id'],'is_deleted'=>0,'prof_id'=>$this->data['prof_id']])
            ->whereRaw($where)->first();
        if($exist){
            return False;
        }else{
            return True;
        }
        //
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Topic already exists in the system.';
    }
}
