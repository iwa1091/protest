<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Message;
use App\Models\User;
use App\Models\SoldItem;
use App\Models\Rating;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ChatMessageRequest;
use App\Http\Requests\ReviewRequest;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * 取引チャット画面を表示する
     */
    public function show(int $item_id)
    {
        $item = Item::findOrFail($item_id);
        $user = Auth::user();

        $soldItem = SoldItem::with(['item', 'ratings', 'messages.user.profile'])
            ->where('item_id', $item_id)
            ->firstOrFail();

        // 権限チェック
        if (!$this->isTradeParticipant($soldItem, $user->id)) {
            abort(403, 'この取引チャットにアクセスする権限がありません。');
        }

        // 相手ユーザー
        $partner = $this->getPartnerUser($item, $soldItem, $user->id);

        // メッセージ履歴
        $chats = $soldItem->messages()
            ->with('user.profile')
            ->orderBy('created_at', 'asc')
            ->get();

        // 未読を既読化
        $soldItem->messages()
            ->where('user_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // 取引中のリスト（最新メッセージ順）
        $inProgressItems = $this->getInProgressItems($user);

        // 各種フラグ
        $isBuyer = ($soldItem->buyer_id === $user->id);
        $isSeller = ($soldItem->item->user_id === $user->id);
        $showBuyerModal = false;
        $shouldShowReviewModal = false;

        // 購入者：完了直後モーダル表示
        if ($isBuyer && session()->has('show_review_modal')) {
            $showBuyerModal = true;
            session()->forget('show_review_modal');
        }

        // 出品者：購入者が評価済み＆自分未評価時モーダル表示
        if ($isSeller) {
            $buyerReviewed = Rating::where('sold_item_id', $soldItem->id)
                ->where('reviewer_id', $soldItem->buyer_id)
                ->exists();

            $sellerReviewed = Rating::where('sold_item_id', $soldItem->id)
                ->where('reviewer_id', $soldItem->item->user_id)
                ->exists();

            if ($buyerReviewed && !$sellerReviewed) {
                $shouldShowReviewModal = true;
            }
        }

        $isReviewed = Rating::where('sold_item_id', $soldItem->id)
            ->where('reviewer_id', $user->id)
            ->exists();

        return view('chat', compact(
            'item',
            'soldItem',
            'partner',
            'chats',
            'inProgressItems',
            'isBuyer',
            'isSeller',
            'showBuyerModal',
            'shouldShowReviewModal',
            'isReviewed'
        ));
    }

    /**
     * メッセージ送信
     */
    public function store(ChatMessageRequest $request, int $item_id)
    {
        $item = Item::findOrFail($item_id);
        $user = Auth::user();
        $soldItem = $item->soldItem;

        if (!$soldItem) {
            return back()->with('error', '取引が開始されていません。');
        }
        if ($soldItem->is_completed) {
            return back()->with('error', '完了済みの取引には送信できません。');
        }

        $imgUrl = null;
        if ($request->hasFile('image')) {
            $path = Storage::disk('local')->put('public/chat_images', $request->file('image'));
            $imgUrl = str_replace('public/', 'storage/', $path);
        }

        $soldItem->messages()->create([
            'user_id'   => $user->id,
            'message'   => $request->message,
            'image_url' => $imgUrl,
            'is_read'   => false,
        ]);

        return back();
    }

    /**
     * メッセージ編集
     */
    public function update(Request $request, int $chat_id)
    {
        $chat = Message::findOrFail($chat_id);

        if ($chat->user_id !== Auth::id()) {
            abort(403, '自分のメッセージ以外は編集できません。');
        }

        $request->validate([
            'message' => 'required|string|max:400',
        ]);

        $chat->update([
            'message' => $request->message,
        ]);

        return back()->with('success', 'メッセージを更新しました。');
    }

    /**
     * メッセージ削除
     */
    public function destroy(int $chat_id)
    {
        $chat = Message::findOrFail($chat_id);

        if ($chat->user_id !== Auth::id()) {
            abort(403, '自分のメッセージ以外は削除できません。');
        }

        $chat->delete();

        return back()->with('success', 'メッセージを削除しました。');
    }

    /**
     * 取引完了（購入者）→ モーダル開く
     */
    public function completeTrade(int $item_id)
    {
        $item = Item::findOrFail($item_id);
        $user = Auth::user();
        $soldItem = $item->soldItem;

        if (!$soldItem) {
            return back()->with('error', '取引情報が見つかりません。');
        }

        if (!$this->isTradeParticipant($soldItem, $user->id)) {
            abort(403, '権限がありません。');
        }

        session()->put('show_review_modal', true);

        return redirect()->route('chat.show', ['item_id' => $item->id])
            ->with('success', '取引を完了しました。評価をお願いします。');
    }

    /**
     * 評価送信（共通）
     */
    public function submitReview(ReviewRequest $request, int $item_id)
    {
        $item = Item::findOrFail($item_id);
        $user = Auth::user();
        $soldItem = SoldItem::where('item_id', $item_id)->firstOrFail();

        $isReviewed = Rating::where('sold_item_id', $soldItem->id)
            ->where('reviewer_id', $user->id)
            ->exists();

        if ($isReviewed) {
            return redirect()->route('items.list')
                ->with('error', '既に評価済みです。');
        }

        $ratedUser = $this->getPartnerUser($item, $soldItem, $user->id);

        try {
            DB::transaction(function () use ($soldItem, $user, $ratedUser, $request) {
                Rating::create([
                    'sold_item_id'  => $soldItem->id,
                    'reviewer_id'   => $user->id,
                    'rated_user_id' => $ratedUser->id,
                    'rating'        => $request->rating,
                    'comment'       => null,
                ]);

                $buyerReviewed = Rating::where('sold_item_id', $soldItem->id)
                    ->where('reviewer_id', $soldItem->buyer_id)
                    ->exists();
                $sellerReviewed = Rating::where('sold_item_id', $soldItem->id)
                    ->where('reviewer_id', $soldItem->item->user_id)
                    ->exists();

                if ($buyerReviewed && $sellerReviewed) {
                    $soldItem->update(['is_completed' => true]);
                }
            });
        } catch (\Exception $e) {
            return back()->with('error', '評価登録中にエラーが発生しました。');
        }

        return redirect()->route('items.list')
            ->with('success', '評価を送信しました。');
    }

    /* ==========================
       ▼ ヘルパー
       ========================== */

    private function isTradeParticipant(SoldItem $soldItem, int $userId): bool
    {
        return $soldItem->buyer_id === $userId || $soldItem->item->user_id === $userId;
    }

    private function getPartnerUser(Item $item, SoldItem $soldItem, int $userId): User
    {
        $partnerId = ($item->user_id === $userId)
            ? $soldItem->buyer_id
            : $item->user_id;

        return User::findOrFail($partnerId);
    }

    /**
     * ✅ 最新メッセージ順に取引中リストを取得
     */
    private function getInProgressItems(User $user)
    {
        $soldItems = SoldItem::where('is_completed', false)
            ->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhereHas('item', fn($iq) => $iq->where('user_id', $user->id));
            })
            ->with(['item'])
            ->withMax('messages', 'created_at') // 🔹 最新メッセージ日時を取得
            ->orderByDesc('messages_max_created_at') // 🔹 最新順に並べ替え
            ->get();

        return $soldItems->map(function ($soldItem) use ($user) {
            $item = $soldItem->item;

            $item->unread_count = $soldItem->messages()
                ->where('user_id', '!=', $user->id)
                ->where('is_read', false)
                ->count();

            $item->latest_message_at = $soldItem->messages_max_created_at;
            $item->soldItem = $soldItem;

            return $item;
        });
    }
}
