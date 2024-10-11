<?php

namespace App\Rules;

use App\User;
use Illuminate\Contracts\Validation\Rule;

class ValidCnic implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
     protected  $data;
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
        //
        $cnic_no       = str_replace("-", "", $value);
        $where = '';
        if(isset($this->data['id'])){
            $where= " AND id != ".$this->data['id']."";
        }
        $cnic_no_exist = User::whereRaw("cnic = '".$cnic_no."'".$where."")->first();
        if($cnic_no_exist){
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
        return 'CNIC No already exist in the system.';
    }
}
