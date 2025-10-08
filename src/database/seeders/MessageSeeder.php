<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Message;
use App\Models\SoldItem;
use App\Models\Item; // Itemモデルを追加
use Illuminate\Support\Carbon;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ユーザーIDの対応: 1=テストユーザーA, 2=テストユーザーB, 3=テストユーザーC
        
        // SoldItemのIDを取得 (SoldItemSeederが実行済みである前提)
        $soldItem1 = SoldItem::find(1); // 取引1: 購入者A(ID=1) が 商品1(出品者ID=2)を購入
        $soldItem2 = SoldItem::find(2); // 取引2: 購入者C(ID=3) が 商品7(出品者ID=1)を購入
        
        $item1 = Item::find(1);
        $item7 = Item::find(7);

        if ($soldItem1 && $item1) {
            // --- 取引 1 (ID=1: 腕時計) のメッセージ履歴 ---
            $buyerId1 = $soldItem1->buyer_id; // ★【修正】購入者IDとして 'buyer_id' を使用
            $sellerId1 = $item1->user_id; // 出品者ID=2
            
            // 1. 購入者Aからの最初の挨拶
            $message1 = Message::create([
                'sold_item_id' => $soldItem1->id,
                'user_id' => $buyerId1, // ユーザーA (購入者)
                'message' => 'この度は購入させていただきありがとうございます。発送はいつ頃の予定でしょうか？',
                'created_at' => Carbon::parse($soldItem1->created_at)->addMinutes(10),
            ]);
            
            // 2. 出品者Bからの返信
            $message2 = Message::create([
                'sold_item_id' => $soldItem1->id,
                'user_id' => $sellerId1, // ユーザーB (出品者)
                'message' => 'ありがとうございます！本日中に発送手続きを完了させる予定です。発送完了後に再度ご連絡しますね。',
                'created_at' => Carbon::parse($soldItem1->created_at)->addMinutes(20),
            ]);
            
            // 3. 出品者Bからの追跡番号連絡（未読メッセージテスト用）
            $message3 = Message::create([
                'sold_item_id' => $soldItem1->id,
                'user_id' => $sellerId1, // ユーザーB (出品者)
                'message' => '発送が完了しました。追跡番号は1234-5678です。ご確認ください。',
                'created_at' => Carbon::parse($soldItem1->created_at)->addDays(1),
            ]);

            // [チャット未読テストの準備]
            // 購入者Aはメッセージ2まで読み、メッセージ3は未読の状態にする
            $soldItem1->update([
                // メッセージ2の作成日時まで既読
                'buyer_last_read_at' => $message2->created_at, 
                // 出品者Bはメッセージ3まで既読
                'seller_last_read_at' => $message3->created_at, 
            ]);
        }

        if ($soldItem2 && $item7) {
            // --- 取引 2 (ID=2: ショルダーバッグ) のメッセージ履歴 ---
            $buyerId2 = $soldItem2->buyer_id; // ★【修正】購入者IDとして 'buyer_id' を使用
            $sellerId2 = $item7->user_id; // 出品者ID=1
            
            // 1. 購入者Cからの連絡（画像添付テスト用）
            $message4 = Message::create([
                'sold_item_id' => $soldItem2->id,
                'user_id' => $buyerId2, // ユーザーC (購入者)
                'message' => '届きました！素敵な商品でした。確認ですが、この小さなポケットは取り外し可能ですか？',
                'image_url' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/pocket_detail.jpg', // ダミー画像URL
                'created_at' => Carbon::parse($soldItem2->created_at)->addDays(2),
            ]);
            
            // 2. 出品者Aからの返信
            $message5 = Message::create([
                'sold_item_id' => $soldItem2->id,
                'user_id' => $sellerId2, // ユーザーA (出品者)
                'message' => 'ご確認ありがとうございます。そのポケットは縫い付けられていますので、取り外しはできません。引き続きよろしくお願いいたします。',
                'created_at' => Carbon::parse($soldItem2->created_at)->addDays(2)->addHours(1),
            ]);

            // [チャット既読テストの準備]
            // 両者とも最新メッセージまで読み終えた状態にする
            $soldItem2->update([
                'buyer_last_read_at' => $message5->created_at,
                'seller_last_read_at' => $message5->created_at,
            ]);
        }
    }
}
