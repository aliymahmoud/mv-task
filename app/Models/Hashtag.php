<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hashtag extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    public function articles()
    {
        $this->belongsToMany(Article::class, 'article_hashtags');
    }
}
