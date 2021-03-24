<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class, 'article_hashtags');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'article_categories');
    }

    public function subcategories()
    {
        return $this->belongsToMany(Subcategory::class, 'article_subcategory');
    }
}
