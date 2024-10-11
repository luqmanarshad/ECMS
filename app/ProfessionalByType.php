<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProfessionalByType extends Model
{
    //
    protected  $table = 'professional_bytype';

    public function professional()
    {
        return $this->belongsTo(Professional::class, 'id', 'type_id');
    }

    public function prof()
    {
        return $this->belongsTo(ProfessionlExam::class, 'id', 'prof_id');
    }
}
