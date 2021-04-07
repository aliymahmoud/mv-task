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
        'directChildren'
    ];

    public function children()
    {
        return $this->hasMany(Category::class, 'category_id')->with('children');
    }
    public function directChildren()
    {
        return $this->hasMany(Category::class, 'category_id');
    }
    public function parent()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_categories');
    }

    public function childrenWithArticles()
    {
        return $this->hasMany(Category::class, 'category_id')->has('articles')->with('articles')->with('childrenHasArticles');
    }
    public function childrenHasArticles()
    {
        return $this->hasMany(Category::class, 'category_id')->has('articles')->with('childrenHasArticles');
    }
    public function flat_descendants($category)
    {
        $result = [];
        foreach ($category->directChildren as $child) {
            $result[] = $child;
            if ($child->directChildren) {
                $result = array_merge($result, $this->flat_descendants($child));
            }
        }
        return $result;
    }
    public function getFlatChildrenAttribute()
    {
        return collect($this->flat_descendants($this));
    }
}
