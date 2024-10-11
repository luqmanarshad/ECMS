<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProfessionlExam extends Model
{
    //
    protected $table     = 'professional_exam';

    public function professionalByType()
    {
        return $this->hasMany(ProfessionalByType::class, 'id', 'prof_id');
    }
}
