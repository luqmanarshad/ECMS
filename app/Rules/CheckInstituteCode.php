<?php

namespace App\Rules;

use App\Institute;
use Illuminate\Contracts\Validation\Rule;

class CheckInstituteCode implements Rule
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
        $institue_code = strtolower(trim($value));
        $where = '';
        if(isset($institue_code)){
            $where.= "institute_code =".$institue_code;
        }
        if(isset($this->data['id'])){
            $where.= " AND id !=".$this->data['id']."";
        }
        $where.= " AND is_deleted = 0";
        $exist = Institute::whereRaw($where)->first();
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
        return 'Institute code already exists in the system.';
    }
}
