<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Professional extends Model
{
    //
    protected $table     = 'professional';

    public function ProfessionalByType()
    {
        return $this->hasMany(ProfessionalByType::class, 'id', 'type_id');
    }


}
