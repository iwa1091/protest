<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rating;
use App\Models\SoldItem;
use App\Models\Item; // Itemモデルを追加
use Illuminate\Support\Carbon;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ユーザーIDの対応: 1=テストユーザーA, 2=テストユーザーB, 3=テストユーザーC, 4=テストユーザーD
        // ItemID: 1(出品者ID=2), 7(出品者ID=1), 9(出品者ID=1)
        
        // SoldItemSeederが実行済みであることを前提とする
        
        // --- 事前準備: 取引情報の取得 ---
        // SoldItem ID=1: 購入者A(1) が 商品1(出品者B=2)を購入
        $soldItem1 = SoldItem::find(1);
        $item1 = Item::find(1);
        $sellerId1 = $item1->user_id; // ユーザーB (出品者ID=2)
        $buyerId1 = $soldItem1->buyer_id; // ユーザーA (購入者ID=1) - ★修正: buyer_idを使用

        // SoldItem ID=2: 購入者C(3) が 商品7(出品者A=1)を購入
        $soldItem2 = SoldItem::find(2);
        $item7 = Item::find(7);
        $sellerId2 = $item7->user_id; // ユーザーA (出品者ID=1)
        $buyerId2 = $soldItem2->buyer_id; // ユーザーC (購入者ID=3) - ★修正: buyer_idを使用
        
        // SoldItem ID=3: 購入者D(4) が 商品9(出品者A=1)を購入 (SoldItemSeederで作成済み)
        $soldItem3 = SoldItem::find(3);
        $item9 = Item::find(9);
        $sellerId3 = $item9->user_id; // ユーザーA (出品者ID=1)
        $buyerId3 = $soldItem3->buyer_id; // ユーザーD (購入者ID=4) - ★修正: buyer_idを使用

        // 取引データが不足している場合はスキップ
        if (!$soldItem1 || !$soldItem2 || !$soldItem3) {
             echo "SoldItemSeederが実行されていないか、SoldItemデータが不足しています。\n";
             return;
        }
        
        // --- 評価データの作成 ---
        
        // 1. 取引2: ユーザーA(出品者ID=1) への評価 (レビュアー: C / 評価点: 5)
        Rating::create([
            'sold_item_id' => $soldItem2->id,
            'reviewer_id' => $buyerId2,  // ユーザーC (購入者) が評価
            'rated_user_id' => $sellerId2,  // ユーザーA (出品者) が評価される
            'rating' => 5,
            'comment' => '迅速な発送と丁寧な梱包でした。とても信頼できる出品者様です！',
            'created_at' => Carbon::parse($soldItem2->created_at)->addDays(4),
        ]);

        // 2. 取引3: ユーザーA(出品者ID=1) への評価 (レビュアー: D / 評価点: 3)
        Rating::create([
            'sold_item_id' => $soldItem3->id,
            'reviewer_id' => $buyerId3,      // ユーザーD (購入者) が評価
            'rated_user_id' => $sellerId3, // ユーザーA (出品者) が評価される
            'rating' => 3,
            'comment' => '商品自体は問題ありませんでしたが、メッセージへの返信が少し遅かったです。',
            'created_at' => Carbon::parse($soldItem3->created_at)->addDays(5),
        ]);

        // 3. 取引1: ユーザーB(出品者ID=2) への評価 (レビュアー: A / 評価点: 4)
        Rating::create([
            'sold_item_id' => $soldItem1->id,
            'reviewer_id' => $buyerId1,  // ユーザーA (購入者) が評価
            'rated_user_id' => $sellerId1, // ユーザーB (出品者) が評価される
            'rating' => 4,
            'comment' => '概ね満足です。また機会がありましたらよろしくお願いします。',
            'created_at' => Carbon::parse($soldItem1->created_at)->addDays(6),
        ]);
        
        // 4. 取引2: ユーザーC(購入者ID=3) への評価 (レビュアー: A / 評価点: 4)
        Rating::create([
            'sold_item_id' => $soldItem2->id,
            'reviewer_id' => $sellerId2,  // ユーザーA (出品者) が評価
            'rated_user_id' => $buyerId2, // ユーザーC (購入者) が評価される
            'rating' => 4,
            'comment' => 'スムーズにお取引いただきありがとうございました。',
            'created_at' => Carbon::parse($soldItem2->created_at)->addDays(4),
        ]);

        // [評価平均の確認]
        // ユーザーA (ID=1) は、(取引2の出品者としての評価=5) + (取引3の出品者としての評価=3) = 平均 4.0
        // ユーザーB (ID=2) は、(取引1の出品者としての評価=4) = 平均 4.0
        // ユーザーC (ID=3) は、(取引2の購入者としての評価=4) = 平均 4.0
    }
}
