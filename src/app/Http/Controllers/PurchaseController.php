<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AddressRequest;
use App\Models\Item;
use App\Models\User;
use App\Models\SoldItem;
use App\Models\Profile;
use Stripe\StripeClient;

class PurchaseController extends Controller
{
    /**
     * 購入画面を表示する
     */
    public function index($item_id, Request $request){
        $item = Item::find($item_id);
        $user = User::find(Auth::id());
        return view('purchase',compact('item','user'));
    }

    /**
     * 決済処理を実行する
     */
    public function purchase($item_id, Request $request){
        $item = Item::find($item_id);
        $stripe = new StripeClient(config('stripe.stripe_secret_key'));

        // item->price を使用し、クエリパラメータを組み立てる
        [
            $user_id,
            $amount,
            $sending_postcode,
            $sending_address,
            $sending_building
        ] = [
            Auth::id(),
            $item->price, // Itemモデルから取得した price を使用
            $request->destination_postcode,
            // 住所と建物名はURLエンコード
            urlencode($request->destination_address),
            urlencode($request->destination_building),
        ];

        // success_urlを生成
        $success_url = "http://localhost/purchase/{$item_id}/success?user_id={$user_id}&amount={$amount}&sending_postcode={$sending_postcode}&sending_address={$sending_address}";

        // 建物名がある場合のみ追加
        if ($sending_building) {
            $success_url .= "&sending_building={$sending_building}";
        }
         Log::info('Stripe Success URL to be used: ' . $success_url);
        $checkout_session = $stripe->checkout->sessions->create([
            'payment_method_types' => [$request->payment_method],
            'payment_method_options' => [
                'konbini' => [
                    'expires_after_days' => 7,
                ],
            ],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => ['name' => $item->name],
                        'unit_amount' => $item->price,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            // 決済成功時のリダイレクトURL
            'success_url' => $success_url,
        ]);

        return redirect($checkout_session->url);
    }

    /**
     * 決済成功後の処理（SoldItemの作成と商品削除）
     */
    public function success($item_id, Request $request){
        // ★★★ デバッグログを追加：受け取ったクエリパラメータを全て記録 ★★★
        Log::debug('Success Query Parameters:', $request->query());

        // 必須クエリパラメータの検閲
        if(!$request->user_id || !$request->amount || !$request->sending_postcode || !$request->sending_address){
            // 必須パラメータが不足している場合は例外をスロー
            // Log::error('Missing required query parameters after Stripe redirect.'); // ログは上のdebugでカバー
            // throw new Exception("You need all Query Parameters (user_id, amount, sending_postcode, sending_address)");
            // エラーを出さずにホームへ戻す（Stripeの挙動によってはパラメータが消えることがあるため）
            return redirect('/')->with('flashError', '購入情報の一部が確認できませんでした。再度お試しください。');
        }

        $item = Item::find($item_id);

        if (!$item) {
            // 商品が見つからない場合は、既に購入済みとみなしリダイレクト
            return redirect('/')->with('flashSuccess', 'この商品は既に取引が完了しています。');
        }
        
        try {
            // 1. SoldItemの作成を実行
            SoldItem::create([
                'buyer_id' => $request->user_id, 
                'item_id' => $item_id,
                'price' => (int)$request->amount, // クエリパラメータの amount を使用し、念のため (int) でキャスト
                // URLエンコードされている住所情報をデコードして保存
                'sending_postcode' => $request->sending_postcode,
                'sending_address' => urldecode($request->sending_address),
                'sending_building' => $request->sending_building ? urldecode($request->sending_building) : null,
            ]);
            
            // 2. 元の items テーブルから商品を削除して、再購入を防止
            $item->delete(); 

            Log::info("SoldItem created successfully for item_id: {$item_id}");

            return redirect('/')->with('flashSuccess', '決済が完了しました！取引チャットで出品者と連絡を取りましょう。');

        } catch (\Illuminate\Database\QueryException $e) {
            // データベースエラーを捕捉
            Log::error('Database Query Error during SoldItem creation: ' . $e->getMessage(), [
                'item_id' => $item_id,
                'query_data' => $request->query(),
            ]);
            // 開発環境であれば、詳細なDBエラーを例外としてスロー
            throw new Exception("Database Constraint Error during SoldItem creation: " . $e->getMessage());

        } catch (Exception $e) {
             // その他の予期せぬエラー
            Log::error('Unexpected error during purchase success: ' . $e->getMessage(), ['item_id' => $item_id]);
            throw $e;
        }
    }

    /**
     * 住所編集画面を表示する
     */
    public function address($item_id, Request $request){
        $user = User::find(Auth::id());
        return view('address', compact('user','item_id'));
    }

    /**
     * 住所情報を更新する
     */
    public function updateAddress(AddressRequest $request){

        $user = User::find(Auth::id());
        Profile::where('user_id', $user->id)->update([
            'postcode' => $request->postcode,
            'address' => $request->address,
            'building' => $request->building
        ]);

        return redirect()->route('purchase.index', ['item_id' => $request->item_id]);
    }
}
