<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;

class Subject extends Model
{
    protected $table     = 'subjects';
    use RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    public function professional()
    {
        return $this->hasOne('App\Professional','id','type_id');
    }



}

