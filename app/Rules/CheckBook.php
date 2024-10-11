<?php

namespace App\Rules;

use App\Book;
use Illuminate\Contracts\Validation\Rule;

class CheckBook implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $data;

    public function __construct($result)
    {
        $this->data = $result;
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $book_name = strtolower(trim($value));
        $where = '';

        if(isset($this->data['id'])){
            $where.= " id != ".$this->data['id']."";
        }else{
            $where.= '1=1';
        }
        $exist = Book::where(['book_name'=>$book_name,'level'=>$this->data['type_id'],'volumn'=>$this->data['volumn'],'status'=>1])->whereRaw($where)->first();
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
        return 'Book Name Already Exist.';
    }
}
