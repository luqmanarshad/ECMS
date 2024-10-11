<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;

class Section extends Model
{
    use RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    //
    protected $table = 'section';
    protected $fillable = ['subject_id'];

    public static function checkSection($subjectId)
    {
        return Section::where('subject_id', $subjectId)
                        ->where('is_deleted',0)
                        ->first();
    }
    public function subject()
    {
        return $this->hasOne('App\Subject','id','subject_id');
    }

    public function professional()
    {
        return $this->hasOne('App\Professional','id','type_id');
    }
    public function ProfessionlExam()
    {
        return $this->hasOne('App\ProfessionlExam','id','prof_id');
    }



}
