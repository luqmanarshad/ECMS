<?php

namespace App\Rules;

use App\Institute;
use Illuminate\Contracts\Validation\Rule;

class CheckInstituteName implements Rule
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
        $institue_name = strtolower(trim($value));
        $where = '';
        if(isset($this->data['id'])){
            $where.= " id !=".$this->data['id']."";
        }else{
            $where.= '1=1';
        }
        $exist = Institute::where(['institute_name'=>$institue_name,'is_deleted'=>0])->whereRaw($where)->first();

        if ($exist) {
            return False;
        } else {
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
        return 'Institute name already exists in the system.';
    }
}
