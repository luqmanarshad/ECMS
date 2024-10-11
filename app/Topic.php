<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;

class Topic extends Model
{
    use RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $table = 'topic';

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
        return $this->hasOne('App\Professional', 'id', 'type_id');
    }

    public static function SubTopicExist($row)
    {
        $search['subject_id'] = $row['subject_id'];
        $search['type_id']    = $row['type_id'];
        $search['section_id'] = $row['section_id'];
        $search['topic_name'] = $row['topic_name'];
        $search['is_deleted'] = 1;
        return Topic::where($search)->first();
    }

}
