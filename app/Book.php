<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;
class Book extends Model
{
    use RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    //
    protected $table = 'book';

    public function book()
    {
        return $this->hasOne('App\Book','id','subject_id');
    }

    public function subject()
    {
        return $this->hasOne('App\Subject', 'id', 'subject_id');
    }

    public function section()
    {
        return $this->hasOne('App\Section', 'id', 'section_id');
    }

    public function professional()
    {
        return $this->hasOne('App\Professional', 'id', 'level');
    }
    public function topic()
    {
        return $this->hasOne('App\Topic', 'id', 'topic_id');
    }

}
