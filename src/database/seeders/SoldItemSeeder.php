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
        // ユーザーIDの対応: 1=テストユーザーA(購入者), 2=テストユーザーB(出品者), 3=テストユーザーC(購入者), 4=テストユーザーD(購入者)
        // 商品IDの対応: 1=腕時計 (出品者ID=2), 7=ショルダーバッグ (出品者ID=1), 9=コーヒーミル (出品者ID=1)
        
        $params = [
            // 取引 1: ユーザーA(ID=1) が 商品1(出品者ID=2)を購入
            [
                'item_id' => 1,
                'buyer_id' => 1,
                
                // 配送先情報 (購入時に必須)
                'sending_postcode' => '1000001',
                'sending_address' => '東京都千代田区',
                'sending_building' => 'テストビル1F',
                
                'price' => 15000, 
                // 既読管理カラムはマイグレーションで削除されたため、シーダーからも削除
                
                'created_at' => Carbon::now()->subDays(5), 
                'updated_at' => Carbon::now()->subDays(5),
                'is_completed' => false, // 取引中として設定
            ],
            // 取引 2: ユーザーC(ID=3) が 商品7(出品者ID=1)を購入
            [
                'item_id' => 7,
                'buyer_id' => 3,

                // 配送先情報 (購入時に必須)
                'sending_postcode' => '5300001',
                'sending_address' => '大阪府大阪市北区',
                'sending_building' => null,

                'price' => 3500,
                // 既読管理カラムはマイグレーションで削除されたため、シーダーからも削除

                'created_at' => Carbon::now()->subDays(3), 
                'updated_at' => Carbon::now()->subDays(3),
                'is_completed' => false, // 取引中として設定
            ],
            // 取引 3: ユーザーD(ID=4) が 商品9(出品者ID=1)を購入 (RatingSeederで使用するため追加)
            [
                'item_id' => 9,
                'buyer_id' => 4,

                // 配送先情報 (購入時に必須)
                'sending_postcode' => '7300000',
                'sending_address' => '広島県',
                'sending_building' => null,

                'price' => 4000,
                // 既読管理カラムはマイグレーションで削除されたため、シーダーからも削除

                'created_at' => Carbon::now()->subDays(10),
                'updated_at' => Carbon::now()->subDays(10),
                'is_completed' => false, // 取引中として設定
            ],
        ];

        foreach ($params as $param) {
            SoldItem::create($param);
        }
    }
}
