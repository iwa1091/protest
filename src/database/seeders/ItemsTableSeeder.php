<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Like;

class ItemsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ユーザーA: ID=1, ユーザーB: ID=2 が出品している前提
        
        $params = [
            [
                'name' => '腕時計',
                'price' => 15000,
                'brand'=> 'Rolax',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                // S3のURLに合わせるため、img_urlを修正
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Armani+Mens+Clock.jpg',
                'user_id' => 2,
                'condition_id' => 1, // 良好
            ],
            [
                'name' => 'HDD',
                'price' => 5000,
                'brand' => '西芝',
                'description' => '高速で信頼性の高いハードディスク',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/HDD+Hard+Disk.jpg',
                'user_id' => 2,
                'condition_id' => 2, // 目立った傷や汚れなし
            ],
            [
                'name' => '玉ねぎ3束',
                'price' => 300,
                'brand' => '地場産', // brandを空欄から修正
                'description' => '新鮮な玉ねぎ3束のセット',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/iLoveIMG+d.jpg',
                'user_id' => 2,
                'condition_id' => 3, // やや傷や汚れあり
            ],
            [
                'name' => '革靴',
                'price' => 4000,
                'brand' => 'Classic', // brandを空欄から修正
                'description' => 'クラシックなデザインの革靴',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Leather+Shoes+Product+Photo.jpg',
                'user_id' => 2,
                'condition_id' => 4, // 状態が悪い
            ],
            [
                'name' => 'ノートPC',
                'price' => 45000,
                'brand' => 'Lenovo', // brandを空欄から修正
                'description' => '高性能なノートパソコン',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Living+Room+Laptop.jpg',
                'user_id' => 2,
                'condition_id' => 1, // 良好
            ],
            [
                'name' => 'マイク',
                'price' => 8000,
                'brand' => 'AudioPro', // brandを空欄から修正
                'description' => '高音質のレコーディング用マイク',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Music+Mic+4632231.jpg',
                'user_id' => 2,
                'condition_id' => 2, // 目立った傷や汚れなし
            ],
            [
                'name' => 'ショルダーバッグ',
                'price' => 3500,
                'brand' => 'Casual', // brandを空欄から修正
                'description' => 'おしゃれなショルダーバッグ',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Purse+fashion+pocket.jpg',
                'user_id' => 1,
                'condition_id' => 3, // やや傷や汚れあり
            ],
            [
                'name' => 'タンブラー',
                'price' => 500,
                'brand' => 'Mizumi', // brandを空欄から修正
                'description' => '使いやすいタンブラー',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Tumbler+souvenir.jpg',
                'user_id' => 1,
                'condition_id' => 4, // 状態が悪い
            ],
            [
                'name' => 'コーヒーミル',
                'price' => 4000,
                'brand' => 'Starbucks', // スペル修正
                'description' => '手動のコーヒーミル',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Waitress+with+Coffee+Grinder.jpg',
                'user_id' => 1,
                'condition_id' => 1, // 良好
            ],
            [
                'name' => 'メイクセット',
                'price' => 2500,
                'brand' => 'Beauty', // brandを空欄から修正
                'description' => '便利なメイクアップセット',
                'img_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/%E5%A4%96%E5%87%BA%E3%83%A1%E3%82%A4%E3%82%AF%E3%82%A2%E3%83%83%E3%83%95%E3%82%9A%E3%82%BB%E3%83%83%E3%83%88.jpg',
                'user_id' => 1,
                'condition_id' => 2, // 目立った傷や汚れなし
            ],
        ];

        foreach ($params as $param) {
            Item::create($param);
        }

        // Likeデータはそのまま残します
        Like::create([
            'user_id' => 1,
            'item_id' => 1,
        ]);
        Like::create([
            'user_id' => 2,
            'item_id' => 7,
        ]);
    }
}
