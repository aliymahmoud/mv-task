<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;


class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if($this->command->confirm('this will truncate categories table, are you sure you want to continue?')){
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Category::truncate();
            $root = Category::create([
                    'name' => 'categoriesRootNode',
                    'category_id' => '0',
            ]);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return $this->command->info('truncate complete, category root node initialized.');
        }
        return $this->command->info("truncate aborted.");
    }
}
