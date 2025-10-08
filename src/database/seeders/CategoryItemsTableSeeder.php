<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategoryItem;

class CategoryItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // -------------------------
        // CO01: 腕時計 (ID=1)
        // カテゴリ: ファッション(1), メンズ(5), アクセサリー(12)
        // -------------------------
        $item1_categories = [1, 5, 12];
        foreach($item1_categories as $category_id){
            CategoryItem::create([
                'item_id' => 1,
                'category_id' => $category_id,
            ]);
        }

        // -------------------------
        // CO02: HDD (ID=2)
        // カテゴリ: 家電(2)
        // -------------------------
        CategoryItem::create(['item_id' => 2, 'category_id' => 2]);
        
        // -------------------------
        // CO03: 玉ねぎ3束 (ID=3)
        // カテゴリ: 食品（※今回は食品カテゴリがないため、キッチン(10)に紐づけ）
        // -------------------------
        CategoryItem::create(['item_id' => 3, 'category_id' => 10]);

        // -------------------------
        // CO04: 革靴 (ID=4)
        // カテゴリ: ファッション(1), メンズ(5)
        // -------------------------
        $item4_categories = [1, 5];
        foreach ($item4_categories as $category_id) {
            CategoryItem::create([
                'item_id' => 4,
                'category_id' => $category_id,
            ]);
        }

        // -------------------------
        // CO05: ノートPC (ID=5)
        // カテゴリ: 家電(2)
        // -------------------------
        CategoryItem::create(['item_id' => 5, 'category_id' => 2]);
        
        // -------------------------
        // CO06: マイク (ID=6)
        // カテゴリ: 家電(2)
        // -------------------------
        CategoryItem::create(['item_id' => 6, 'category_id' => 2]);

        // -------------------------
        // CO07: ショルダーバッグ (ID=7)
        // カテゴリ: ファッション(1), レディース(4)
        // -------------------------
        $item7_categories = [1, 4];
        foreach ($item7_categories as $category_id) {
            CategoryItem::create([
                'item_id' => 7,
                'category_id' => $category_id,
            ]);
        }

        // -------------------------
        // CO08: タンブラー (ID=8)
        // カテゴリ: キッチン(10), インテリア(3)
        // -------------------------
        $item8_categories = [3, 10];
        foreach ($item8_categories as $category_id) {
            CategoryItem::create([
                'item_id' => 8,
                'category_id' => $category_id,
            ]);
        }
        
        // -------------------------
        // CO09: コーヒーミル (ID=9)
        // カテゴリ: キッチン(10), 家電(2)
        // -------------------------
        $item9_categories = [2, 10];
        foreach ($item9_categories as $category_id) {
            CategoryItem::create([
                'item_id' => 9,
                'category_id' => $category_id,
            ]);
        }
        
        // -------------------------
        // CO10: メイクセット (ID=10)
        // カテゴリ: コスメ(6)
        // -------------------------
        CategoryItem::create(['item_id' => 10, 'category_id' => 6]);
    }
}
