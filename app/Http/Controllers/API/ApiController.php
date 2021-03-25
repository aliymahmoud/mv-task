<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleSubcategory;
use App\Models\Hashtag;
use App\Models\ArticleHashtag;
use Illuminate\Database\Eloquent\Builder;
class ApiController extends Controller
{
    
    // store a category into database, requires a name that hasn't been taken by another
    //  category before
    public function store_category(Request $request)
    {
        $rootCheck = Category::get();
        if ($rootCheck->isEmpty()) {
            return response()->json([
                'message' => "something wrong happened, please communicate with the developer",
                'code' => '412',
            ]);
        }
        $rules = [
            'name' => 'required|unique:categories,name|alpha',
            'category_id' => 'required|exists:categories,id',
        ];
        $validation = $this->apiValidate($request, $rules);
        if($validation){
            return $validation;
        }

        $category = Category::create([
            "name" => $request->name,
            "category_id" => $request->category_id,
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
        $rules = [
            'category_id' => 'required|numeric|exists:categories,id',
            'name' => 'required|unique:subcategories,name|alpha|'.Rule::notIn(Category::where('id', $request->category_id)->pluck('name')->toArray()),
        ];
        $validation = $this->apiValidate($request, $rules);
        if($validation){
            return $validation;
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

    // auxillary function to detect new hashtags then insert them in database 
    public function insertNewHashtags($body)
    {
        preg_match_all("/#(\\w+)/", $body, $matches);
        $article_hashtags = $matches[0];
        $article_hashtags = array_unique($article_hashtags);
        $hashtagsQuery = Hashtag::whereIn('title', $article_hashtags);
        $hashtagsTitle = $hashtagsQuery->pluck('title')->toArray();
        $hashtag_diff = array_diff($article_hashtags, $hashtagsTitle);
        if($hashtag_diff){
            $newTags = [];
            foreach ($hashtag_diff as $key => $hashtag) {
                $newTags[$key] = [
                    'title' => $hashtag,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Hashtag::insert($newTags);
        }
        $hashtags = Hashtag::whereIn('title', $article_hashtags)->get();
        return ($hashtags)? : null;
    }

    // auxillary function to update existing article's hashtags
    public function updateExistingHashtags($body, $article_id)
    {
        preg_match_all("/#(\\w+)/", $body, $matches);
        $article_hashtags = $matches[0];
        $article_hashtags = array_unique($article_hashtags);
        $hashtagsQuery = Article::find($article_id)->hashtags;
        $existingTags = $hashtagsQuery->pluck('title')->toArray();
        $newTags = array_diff($article_hashtags, $existingTags);
        $oldTags = array_diff($existingTags, $article_hashtags);
        if($newTags){
            $allHashtagsQuery = Hashtag::whereIn('title', $article_hashtags);
            $hashtagsTitle = $allHashtagsQuery->pluck('title')->toArray();
            $hashtag_diff = array_diff($newTags, $hashtagsTitle);
            if($hashtag_diff){
                $tags = [];
                foreach ($hashtag_diff as $key => $hashtag) {
                    $tags[$key] = [
                        'title' => $hashtag,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                Hashtag::insert($tags);
            }
        }
        if($oldTags){
            $oldTagsIds = Hashtag::whereIn('title', $oldTags)->pluck('id');
            ArticleHashtag::where('article_id', $article_id)->whereIn('hashtag_id', $oldTagsIds)->delete();
        }
        $hashtags = Hashtag::whereIn('title', $newTags)->get();
        return ($hashtags)? $hashtags : null;
    }

    // store article to database, requires a subcategory id that this article
    //  is assigned for, and from this subcategory id, the article is also 
    // assigned to its category, which means article is assigned to a category
    //  and subcategory, also it detects hashtags automatically using regular 
    //  expression and store it then assign it for the article with a many to 
    //  many relationship
    public function store_article(Request $request)
    {
        // 
        $rules = [
            "category_id" => "required|numeric|exists:categories,id",
            "title" => "required",
            "body" => "required",
        ];
        $validation = $this->apiValidate($request, $rules);
        if($validation){
            return $validation;
        }
        // 
        $category = Category::find($request->category_id);
        $article = Article::create([
            'title' => $request->title,
            'body' => $request->body,
        ]);
        $hashtags = $this->insertNewHashtags($request->body);
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
        // 
        if($article and $articleCategory){
            return response()->json([
                'message' => "Article Created Successfully",
                'code' => '200'
            ],200);
        }
    }

    // return all categories paginated with a tree view with its subcategories
    public function get_categories(Request $request)
    {
        $rules = [
            'category_id' => 'numeric|exists:categories,id',
        ];
        $validation = $this->apiValidate($request, $rules);
        if($validation){
            return $validation;
        }
        if($request->has('category_id')){
            $categories = Category::where('id', $request->category_id)->with('children')->paginate(10);
        }
        else{
            $categories = Category::where('category_id', 0)->with('children')->paginate(10);
        }
        return response()->json([
            "categories" => $categories,
        ]);

    }

    // return articles for specific category or a subcategory or a specific hashtag
    public function get_articles(Request $request)
    {
        $rules = [
            'category_id' => 'numeric|exists:categories,id',
            'hashtag' => "regex:/\B(\#[a-zA-Z_0-9]+\b)(?!;)(?! )/|exists:hashtags,title",
        ];
        $validation = $this->apiValidate($request, $rules);
        if($validation){
            return $validation;
        }
        $articles = Article::paginate('10');
        if($request->has('category_id')){
            $articles = Category::where('id', $request->category_id)->with('articles')->with('childrenHasArticles')->paginate('10');
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

        $rules = [
            'article_id' => 'required|numeric|exists:articles,id',
            'title' => 'string',
            'body' => 'string'
        ];
        $validation = $this->apiValidate($request, $rules);
        if($validation){
            return $validation;
        }
        $article = Article::find($request->article_id);
        if($article){
            if($request->has('title')){
                $article->update([
                    'title' => $request->title,
                ]);
            }
            if($request->has('body')){
                $hashtags = $this->updateExistingHashtags($request->body, $request->article_id);
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
