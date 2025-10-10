<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AddressRequest;
use App\Models\Item;
use App\Models\SoldItem;
use App\Models\Profile;
use App\Models\Rating; // ⭐ 追加
use Stripe\StripeClient;

class PurchaseController extends Controller
{
    /**
     * 購入画面表示
     */
    public function index($item_id)
    {
        $item = Item::findOrFail($item_id);
        $user = Auth::user();
        return view('purchase', compact('item', 'user'));
    }

    /**
     * Checkout セッション作成
     */
    public function purchase($item_id, Request $request)
    {
        $item = Item::findOrFail($item_id);
        $stripe = new StripeClient(env('STRIPE_SECRET_KEY'));

        $checkout_session = $stripe->checkout->sessions->create([
            'payment_method_types' => [$request->payment_method],
            'payment_method_options' => [
                'konbini' => ['expires_after_days' => 7],
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => ['name' => $item->name],
                    'unit_amount' => $item->price,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('purchase.success', ['item_id' => $item_id]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('purchase.index', ['item_id' => $item_id]),
            'metadata' => [
                'user_id' => Auth::id(),
                'item_id' => $item_id,
                'sending_postcode' => $request->destination_postcode ?? '',
                'sending_address' => $request->destination_address ?? '',
                'sending_building' => $request->destination_building ?? '',
            ],
        ]);

        return redirect($checkout_session->url);
    }

    /**
     * 決済成功ページ (Webhook不要版)
     */
    public function success(Request $request, $item_id)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return redirect()->route('user.mypage')->with('flashError', 'セッション情報が見つかりません。');
        }

        $stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
        $session = $stripe->checkout->sessions->retrieve($sessionId, []);

        // SoldItem 登録（新規取引開始）
        $this->markAsSold($session);

        return redirect()->route('user.mypage', ['page' => 'in-progress'])
            ->with('flashSuccess', '決済が完了しました！取引チャットで出品者と連絡を取りましょう。');
    }

    /**
     * SoldItem 登録（Checkout Session 共通）
     */
    protected function markAsSold($session)
    {
        $metadata = $session->metadata ?? null;
        $userId = $metadata->user_id ?? null;
        $itemId = $metadata->item_id ?? null;

        if (!$userId || !$itemId) {
            Log::warning("Missing metadata for SoldItem creation", ['session' => (array)$session]);
            return;
        }

        $item = Item::find($itemId);
        if (!$item || $item->is_sold) {
            Log::info("Item not found or already sold", ['item_id' => $itemId]);
            return;
        }

        // ✅ 取引中（未完了）状態で登録する
        SoldItem::create([
            'buyer_id' => $userId,
            'item_id' => $item->id,
            'price' => $session->amount_total ?? $session->amount ?? $item->price,
            'sending_postcode' => $metadata->sending_postcode ?? 'unknown',
            'sending_address' => $metadata->sending_address ?? 'unknown',
            'sending_building' => $metadata->sending_building ?? null,
            'is_completed' => false, // ← 重要：取引中ステータスで登録
            'buyer_last_read_at' => now(),
            'seller_last_read_at' => now(),
        ]);

        // 商品を「売却済み」に更新
        $item->update(['is_sold' => true]);

        Log::info("SoldItem created successfully", ['item_id' => $item->id, 'buyer_id' => $userId]);
    }

    /**
     * ✅ 取引完了（＋評価保存）処理
     */
    public function complete(Request $request, $item_id)
    {
        $soldItem = SoldItem::where('item_id', $item_id)->firstOrFail();

        // 取引を完了状態に更新
        $soldItem->update(['is_completed' => true]);

        // 評価があれば保存
        if ($request->filled('rating')) {
            Rating::create([
                'sold_item_id' => $soldItem->id,
                'reviewer_id' => Auth::id(),
                'rated_user_id' => $soldItem->seller_id === Auth::id()
                    ? $soldItem->buyer_id
                    : $soldItem->seller_id,
                'rating' => $request->rating,
                'comment' => null,
            ]);
        }

        return redirect()->route('chat.show', $item_id)
            ->with('flashSuccess', '取引を完了し、評価を送信しました。');
    }

    /**
     * 住所編集画面
     */
    public function address($item_id)
    {
        $user = Auth::user();
        return view('address', compact('user', 'item_id'));
    }

    /**
     * 住所更新
     */
    public function updateAddress(AddressRequest $request)
    {
        $user = Auth::user();
        Profile::where('user_id', $user->id)->update([
            'postcode' => $request->postcode,
            'address' => $request->address,
            'building' => $request->building,
        ]);

        return redirect()->route('purchase.index', ['item_id' => $request->item_id]);
    }

    /**
     * Stripe Webhook (未使用)
     */
    /*
    public function webhook(Request $request)
    {
        // Webhook対応版の処理を記述（必要に応じて）
    }
    */
}
