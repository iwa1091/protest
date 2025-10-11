<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\AddressRequest;
use App\Models\Item;
use App\Models\SoldItem;
use App\Models\Profile;
use App\Models\Rating;
use App\Mail\TradeCompletedMail; // ✅ 追加
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
     * ✅ 決済成功ページ (Webhook不要版)
     * StripeセッションID確認後、取引登録＋出品者へメール通知を実施
     */
    public function success(Request $request, $item_id)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) {
            return redirect()->route('user.mypage')->with('flashError', 'セッション情報が見つかりません。');
        }

        $stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
        $session = $stripe->checkout->sessions->retrieve($sessionId, []);

        // SoldItem 登録（取引開始）
        $this->markAsSold($session);

        // メール送信処理
        $soldItem = SoldItem::where('item_id', $item_id)
            ->with(['item.user', 'buyer'])
            ->first();

        if ($soldItem && $soldItem->item && $soldItem->item->user) {
            $sellerEmail = $soldItem->item->user->email;
            $sellerName  = $soldItem->item->user->name ?? '出品者';

            try {
                Mail::to($sellerEmail)->send(new TradeCompletedMail($soldItem));

                Log::info('✅ 出品者宛てメール送信成功', [
                    'seller_name'  => $sellerName,
                    'seller_email' => $sellerEmail,
                    'item_id'      => $item_id,
                    'buyer_id'     => $soldItem->buyer_id,
                ]);
            } catch (\Exception $e) {
                Log::error('❌ 出品者宛メール送信エラー: ' . $e->getMessage(), [
                    'seller_email' => $sellerEmail,
                    'item_id'      => $item_id,
                ]);
            }
        } else {
            Log::warning('⚠️ SoldItemまたはSeller情報が見つかりません', ['item_id' => $item_id]);
        }

        return redirect()->route('user.mypage', ['page' => 'in-progress'])
            ->with('flashSuccess', '決済が完了しました！出品者に通知を送りました。取引チャットで連絡を取りましょう。');
    }

    /**
     * ✅ SoldItem 登録（共通関数）
     */
    protected function markAsSold($session)
    {
        $metadata = $session->metadata ?? null;
        $userId = $metadata->user_id ?? null;
        $itemId = $metadata->item_id ?? null;

        if (!$userId || !$itemId) {
            Log::warning("⚠️ Missing metadata for SoldItem creation", ['session' => (array)$session]);
            return;
        }

        $item = Item::find($itemId);
        if (!$item || $item->is_sold) {
            Log::info("⚠️ Item not found or already sold", ['item_id' => $itemId]);
            return;
        }

        // 新規取引登録
        SoldItem::create([
            'buyer_id' => $userId,
            'item_id' => $item->id,
            'price' => $session->amount_total ?? $session->amount ?? $item->price,
            'sending_postcode' => $metadata->sending_postcode ?? 'unknown',
            'sending_address' => $metadata->sending_address ?? 'unknown',
            'sending_building' => $metadata->sending_building ?? null,
            'is_completed' => false,
            'buyer_last_read_at' => now(),
            'seller_last_read_at' => now(),
        ]);

        $item->update(['is_sold' => true]);

        Log::info("✅ SoldItem created successfully", [
            'item_id'  => $item->id,
            'buyer_id' => $userId,
        ]);
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
            'address'  => $request->address,
            'building' => $request->building,
        ]);

        return redirect()->route('purchase.index', ['item_id' => $request->item_id]);
    }
}
