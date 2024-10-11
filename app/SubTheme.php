<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;

class SubTheme extends Model
{
    use RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;

    protected $table = 'tbl_sub_theme';

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
    public function topic()
    {
        return $this->hasOne('App\Topic', 'id', 'topic_id');
    }
    public function theme()
    {
        return $this->hasOne('App\Theme', 'id', 'theme_id');
    }
}
