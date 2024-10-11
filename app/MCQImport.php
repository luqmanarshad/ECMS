<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;

class MCQImport extends Model
{
    use RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;


    protected  $table = 'mcq_import';

    public function user()
    {
        return $this->hasOne('App\User', 'id', 'for_teacher');
    }
    public function subject()
    {
        return $this->hasOne('App\Subject', 'id', 'subject_id');
    }
    public function type()
    {
        return $this->hasOne('App\Professional', 'id', 'type_id');
    }
}
