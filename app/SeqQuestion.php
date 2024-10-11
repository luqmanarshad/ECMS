<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;

class SeqQuestion extends Model
{
    use RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;

    //
    protected $table    = 'seq_question';
    //protected $fillable = ['question','answer','topic_id','marks','page_no','has_parent','child_no','question_id','group_id'];
    protected $guarded = [];

    /*GET SEQ PARENT QUESTION*/
    public static function getSeqParentQuestion($questionId){
        return SeqQuestion::join('topic','topic.id','=','seq_question.topic_id')
            ->where('seq_question.question_id',$questionId )
            ->where('seq_question.has_parent',0 )
            ->where('seq_question.is_deleted',0)
            ->select('seq_question.*','topic.topic_name')
            ->orderBy('seq_question.id','ASC')->get();
    }

    /*GET SEQ CHILD QUESTION*/
    public static function getSeqChildQuestion($group_id,$question_id){
        return SeqQuestion::join('topic','topic.id','=','seq_question.topic_id')
            ->where('seq_question.question_id',$question_id )
            ->where('seq_question.has_parent',1 )
            ->where('seq_question.group_id', $group_id)
            ->where('seq_question.is_deleted',0)
            ->select('seq_question.*','topic.topic_name')
            ->orderBy('seq_question.id','ASC')->get();
    }
}
