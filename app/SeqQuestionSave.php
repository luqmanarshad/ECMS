<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;

class SeqQuestionSave extends Model
{
    use RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $table   = 'seq_question_save';
    protected $guarded = [];



    /*GET SEQ SAVED PARENT QUESTION*/
    public static function getSavedSeqParentQuestion($questionId){
        return SeqQuestionSave::join('topic','topic.id','=','seq_question_save.topic_id')
            ->where('seq_question_save.question_id',$questionId )
            ->where('seq_question_save.has_parent',0 )
            ->select('seq_question_save.*','topic.topic_name')
            ->orderBy('seq_question_save.id','ASC')->get();
    }

    /*GET SEQ SAVED CHILD QUESTION*/
    public static function getSavedSeqChildQuestion($group_id,$question_id){
        return SeqQuestionSave::join('topic','topic.id','=','seq_question_save.topic_id')
            ->where('seq_question_save.question_id',$question_id )
            ->where('seq_question_save.has_parent',1 )
            ->where('seq_question_save.group_id', $group_id)
            ->select('seq_question_save.*','topic.topic_name')
            ->orderBy('seq_question_save.id','ASC')->get();
    }
}
