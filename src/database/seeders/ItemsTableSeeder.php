<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Like;
use App\Models\Condition;

class ItemsTableSeeder extends Seeder
{
    public function run()
    {
        $params = [
            [
                'name' => '腕時計',
                'price' => 15000,
                'brand'=> 'Rolax',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'img_url' => 'img/mens_clock.jpg',
                'user_id' => 1,
                'condition_id' => Condition::$UNUSED,
            ],
            [
                'name' => 'HDD',
                'price' => 5000,
                'brand' => '西芝',
                'description' => '高速で信頼性の高いハードディスク',
                'img_url' => 'img/hard_disk.jpg',
                'user_id' => 1,
                'condition_id' => Condition::$HARMLESS,
            ],
            [
                'name' => '玉ねぎ3束',
                'price' => 300,
                'brand' => '地場産',
                'description' => '新鮮な玉ねぎ3束のセット',
                'img_url' => 'img/onion.jpg',
                'user_id' => 1,
                'condition_id' => Condition::$HARMED,
            ],
            [
                'name' => '革靴',
                'price' => 4000,
                'brand' => 'Classic',
                'description' => 'クラシックなデザインの革靴',
                'img_url' => 'img/leather_shoes.jpg',
                'user_id' => 1,
                'condition_id' => Condition::$BAD_CONDITION,
            ],
            [
                'name' => 'ノートPC',
                'price' => 45000,
                'brand' => 'Lenovo',
                'description' => '高性能なノートパソコン',
                'img_url' => 'img/laptop_PC.jpg',
                'user_id' => 1,
                'condition_id' => Condition::$UNUSED,
            ],
            [
                'name' => 'マイク',
                'price' => 8000,
                'brand' => 'AudioPro',
                'description' => '高音質のレコーディング用マイク',
                'img_url' => 'img/mic.jpg',
                'user_id' => 2,
                'condition_id' => Condition::$HARMLESS,
            ],
            [
                'name' => 'ショルダーバッグ',
                'price' => 3500,
                'brand' => 'Casual',
                'description' => 'おしゃれなショルダーバッグ',
                'img_url' => 'img/shoulder_bag.jpg',
                'user_id' => 2,
                'condition_id' => Condition::$HARMED,
            ],
            [
                'name' => 'タンブラー',
                'price' => 500,
                'brand' => 'Mizumi',
                'description' => '使いやすいタンブラー',
                'img_url' => 'img/tumbler.jpg',
                'user_id' => 2,
                'condition_id' => Condition::$BAD_CONDITION,
            ],
            [
                'name' => 'コーヒーミル',
                'price' => 4000,
                'brand' => 'Starbucks',
                'description' => '手動のコーヒーミル',
                'img_url' => 'img/coffee_mill.jpg',
                'user_id' => 2,
                'condition_id' => Condition::$UNUSED,
            ],
            [
                'name' => 'メイクセット',
                'price' => 2500,
                'brand' => 'Beauty',
                'description' => '便利なメイクアップセット',
                'img_url' => 'img/make_set.jpg',
                'user_id' => 2,
                'condition_id' => Condition::$HARMLESS,
            ],
        ];

        foreach ($params as $param) {
            Item::create($param);
        }

        // Likeデータ
        Like::create(['user_id' => 1, 'item_id' => 1]);
        Like::create(['user_id' => 2, 'item_id' => 7]);
    }
}
