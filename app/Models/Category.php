<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'category_id',
    ];

    public function children()
    {
        return $this->hasMany(Category::class, 'category_id')->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_categories');
    }

    public function childrenHasArticles()
    {
        return $this->hasMany(Category::class, 'category_id')->has('articles')->with('articles')->with('childrenHasArticles');
    }
}
