<?php

namespace App\Rules;

use App\Theme;
use Illuminate\Contracts\Validation\Rule;

class CheckTheme implements Rule
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
        $theme = strtolower(trim($value));
        $where = '';
        if (isset($this->data['id'])) {
            $where .= " id != " . $this->data['id'] . "";
        } else {
            $where .= '1=1';
        }
        if (isset($this->data['topic_id'])) {
            $where .= " AND topic_id = " . $this->data['topic_id'];
        }
        //$where .= " AND is_deleted = 0  AND subject_id =" . $this->data['subject_id'] . " AND section_id = " . $this->data['section_id'];
        $exist = Theme::where(
            [
                'theme_name' => $theme,
                'type_id'    => $this->data['type_id'],
                'is_deleted' => 0,
                'subject_id' => $this->data['subject_id'],
                'section_id' => $this->data['section_id']
            ])->whereRaw($where)->first();

        if ($exist) {
            return False;
        } else {
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
        return 'Theme name already exists in the system.';
    }
}
