<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CheckSubjectSingleQuote implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
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
        $subject = strtolower($value);
        $lastCharachter = substr($subject,-1);
        if($lastCharachter === "'"){
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
        return 'At Least one character after single quote.';
    }
}
