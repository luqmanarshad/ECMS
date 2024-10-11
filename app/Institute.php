<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;
class Institute extends Model
{
    //
    use RevisionableTrait;
    protected $table = 'institute';
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
}
