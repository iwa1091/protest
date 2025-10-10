<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SoldItem;
use Illuminate\Support\Carbon;

class SoldItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ユーザーIDの対応:
        // 1=テストユーザーA, 2=テストユーザーB, 3=テストユーザーC, 4=テストユーザーD
        // 商品IDの対応（itemsテーブルに存在するID）
        // 1=腕時計, 2=HDD, 3=玉ねぎ3束, 4=革靴, 5=ノートPC, 6=マイク,
        // 7=ショルダーバッグ, 8=タンブラー, 9=コーヒーミル, 10=メイクセット

        $params = [
            // ------------------------------
            // 取引完了データのみ
            // ------------------------------
            [
                'item_id' => 1, // 腕時計
                'buyer_id' => 1,
                'sending_postcode' => '1000001',
                'sending_address' => '東京都千代田区',
                'sending_building' => 'テストビル1F',
                'price' => 15000,
                'is_completed' => true,
                'buyer_last_read_at' => Carbon::now()->subDays(5),
                'seller_last_read_at' => Carbon::now()->subDays(5),
                'created_at' => Carbon::now()->subDays(5),
                'updated_at' => Carbon::now()->subDays(5),
            ],
            [
                'item_id' => 7, // ショルダーバッグ
                'buyer_id' => 3,
                'sending_postcode' => '5300001',
                'sending_address' => '大阪府大阪市北区',
                'sending_building' => null,
                'price' => 3500,
                'is_completed' => true,
                'buyer_last_read_at' => Carbon::now()->subDays(3),
                'seller_last_read_at' => Carbon::now()->subDays(3),
                'created_at' => Carbon::now()->subDays(3),
                'updated_at' => Carbon::now()->subDays(3),
            ],
            [
                'item_id' => 9, // コーヒーミル
                'buyer_id' => 4,
                'sending_postcode' => '7300000',
                'sending_address' => '広島県',
                'sending_building' => null,
                'price' => 4000,
                'is_completed' => true,
                'buyer_last_read_at' => Carbon::now()->subDays(10),
                'seller_last_read_at' => Carbon::now()->subDays(10),
                'created_at' => Carbon::now()->subDays(10),
                'updated_at' => Carbon::now()->subDays(10),
            ],
        ];

        foreach ($params as $param) {
            SoldItem::create($param);
        }
    }
}
