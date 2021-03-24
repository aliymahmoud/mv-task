<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleSubcategory;
use App\Models\Hashtag;
use App\Models\ArticleHashtag;
use Illuminate\Validation\Rule;

class ApiController extends Controller
{
    // store a category into database, requires a name that hasn't been taken by another
    //  category before
    public function store_category(Request $request)
    {
        $validation = Validator::make($request->all(),[
            'name' => 'required|unique:categories,name|alpha',
        ]);
        if($validation->fails()){
            return response()->json([
                "message" => $validation->errors()->first('name'),
            ]);
        }
        $category = Category::create([
            "name" => $request->name,
        ]);
        if($category){
            return response()->json([
                "message" => "Category Created Successfully",
                "code" => "200",
            ],200);
        }
    }

    // store subcategory into database, requires a name for the subcategory and a category 
    // id so that the subcategory assigned to a category
    public function store_subcategory(Request $request)
    {
        $validation = Validator::make($request->all(),[
            'category_id' => 'required|numeric|exists:categories,id',
            'name' => 'required|unique:subcategories,name|alpha|'.Rule::notIn(Category::where('id', $request->category_id)->pluck('name')->toArray()),
        ]);
        
        if($validation->fails()){
            return response()->json([
                "message" => $validation->errors()->first(),
            ]);
        }
        $subcategory = Subcategory::create([
            "category_id" => $request->category_id,
            "name" => $request->name,
        ]);
        if($subcategory){
            return response()->json([
                "message" => "Subcategory Created Successfully",
                "code" => "200",
            ],200);
        }

    }

    // store article to database, requires a subcategory id that this article
    //  is assigned for, and from this subcategory id, the article is also 
    // assigned to its category, which means article is assigned to a category
    //  and subcategory, also it detects hashtags automatically using regular 
    //  expression and store it then assign it for the article with a many to 
    //  many relationship
    public function store_article(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "subcategory_id" => "required|numeric|exists:subcategories,id",
            "title" => "required",
            "body" => "required",
        ]);
        if($validation->fails()){
            return response()->json([
                "message" => $validation->errors()->first(),
            ]);
        }
        preg_match_all("/#(\\w+)/", $request->body, $matches);
        $article_hashtags = $matches[0];
        $hashtagsQuery = Hashtag::whereIn('title', $article_hashtags);
        $hashtagsTitle = $hashtagsQuery->pluck('title')->toArray();
        $hashtag_diff = array_diff($article_hashtags, $hashtagsTitle);
        if($hashtag_diff){
            $newtags = [];
            foreach ($hashtag_diff as $key => $hashtag) {
                $newtags[$key] = [
                    'title' => $hashtag,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Hashtag::insert($newtags);
        }
        $hashtags = Hashtag::whereIn('title', $article_hashtags)->get();
        $subcategory = Subcategory::find($request->subcategory_id);
        $category = $subcategory->category;
        $article = Article::create([
            'title' => $request->title,
            'body' => $request->body,
        ]);
        if($hashtags){
            $article_hashtag_data = [];
            foreach ($hashtags as $key => $hashtag) {
                $article_hashtag_data[$key] = [
                    'hashtag_id' => $hashtag->id,
                    'article_id' => $article->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            ArticleHashtag::insert($article_hashtag_data);
        }
        $articleCategory = ArticleCategory::create([
            'article_id' => $article->id,
            'category_id' => $category->id,
        ]);
        $articleSubcategory = ArticleSubcategory::create([
            'article_id' => $article->id,
            'subcategory_id' => $subcategory->id,
        ]);
        
        if($article and $articleCategory and $articleSubcategory){
            return response()->json([
                'message' => "Article Created Successfully",
                'code' => '200'
            ],200);
        }
    }

    // return all categories paginated with a tree view with its subcategories
    public function get_categories()
    {
        $categories = Category::paginate(10);
        foreach ($categories as $key => $category) {
            $subcategory = $category->subcategories;
            $categories[$key]['subcategories'] = $subcategory;
        }
        return response()->json([
            "categories" => $categories,
        ]);

    }

    // return articles for specific category or a subcategory or a specific hashtag
    public function get_articles(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'category_id' => 'numeric',
            'subcategory_id' => 'numeric',
            'hashtag' => "regex:/\B(\#[a-zA-Z_0-9]+\b)(?!;)(?! )/|exists:hashtags,title",
        ]);
        if($validation->fails()){
            return response()->json([
                "message" => $validation->errors()->first(),
            ]);
        }
        $articles = Article::paginate('10');
        if($request->has('category_id')){
            $articles = Category::find($request->category_id)->articles()->paginate('10');
        }
        if($request->has('subcategory_id')){
            $articles = Subcategory::find($request->subcategory_id)->articles()->paginate('10');
        }
        if($request->has('hashtag')){
            $hashtag = Hashtag::where('title', $request->hashtag)->first();
            $articles = Article::whereHas('hashtags', function($query)use($hashtag){
                return $query->where('title', $hashtag->title);
            })->get();
        }
        return response()->json([
            'articles' => $articles,
            'code' => '200',
        ]);
    }
    // update article that already exists in database, requires article id,
    // title and body are optional
    public function update_article(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'article_id' => 'required|numeric|exists:articles,id',
            'title' => 'string',
            'body' => 'string'
        ]);
        if($validation->fails()){
            return response()->json([
                'message' => $validation->errors()->first(),
            ]);
        }
        $article = Article::find($request->article_id)->first();
        if($article){
            if($request->has('title')){
                $article->update([
                    'title' => $request->title,
                ]);
            }
            if($request->has('body')){
                $article->update([
                    'body' => $request->body,
                ]);
            }
        }
        return response()->json([
            'message' => "Article Updated Successfully",
            'code' => "200",
        ],200);
    }
    // return all the trending hashtags in a descending order with a top 
    // parameter that controls the top hashtags to be returned, if not specified,
    // all hashtags are returned
    public function trending_tags(Request $request)
    {
        $hashtags = ArticleHashtag::all()->groupBy('hashtag_id');
        $tagsNum = $hashtags->count();
        $top = ($request->has('top'))? $request->top: $tagsNum;
        ($top > $tagsNum)? $top = $tagsNum: '';
        $trendings = [];
        foreach ($hashtags as $key => $value) {
            $tempTag = Hashtag::find($key);
            $tempTagNum = $value->count();
            array_push($trendings, [
                "hashtag" => $tempTag->title,
                "trend" => $tempTagNum,
            ]);
        }
        $trend = array_column($trendings, 'trend');
        array_multisort($trend, SORT_DESC, $trendings);
        $orderedTrend = $trendings;
        $final = [];
        for ($i=0; $i < $top; $i++) { 
            \array_push($final,$orderedTrend[$i]);
        }
        return response()->json([
            "top ten trending hashtags" => $final,
        ]);
    }
}
