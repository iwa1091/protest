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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * 取引チャットに関する処理を管理するコントローラ
 * FN001: 取引チャット確認機能
 * FN002: 取引チャット遷移機能
 * FN006: 取引チャット機能
 * FN012: 取引完了機能
 * FN013: 評価機能
 */
class ChatController extends Controller
{
    /**
     * 特定の商品のチャット画面を表示する (FN001, FN002, FN005)
     * ルート: /chat/{item_id} (GET)
     *
     * @param int $item_id ルートパラメータ
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(int $item_id)
    {
        // ルートパラメータのIDを使用してItemを取得
        $item = Item::findOrFail($item_id);
        $user = Auth::user();

        // 1. 取引情報（SoldItem）の取得とアクセス制御
        $soldItem = SoldItem::where('item_id', $item->id)->first();

        // 商品が売れていない、または取引情報がない場合はアクセス不可
        if (!$soldItem) {
            return redirect()->route('item.detail', $item->id)->with('error', 'この商品はまだ購入されていません。');
        }

        $isSeller = $item->user_id === $user->id;
        // 修正: SoldItemの購入者IDを参照
        $isBuyer = $soldItem->buyer_id === $user->id;

        // dd([
        //     'item' => $item,
        //     'soldItem' => $soldItem,
        //     'isSeller' => $isSeller,
        // ]);

        if (!$isSeller && !$isBuyer) {
            // 取引に関係ないユーザーはアクセス不可
            abort(403, 'この取引チャットにアクセスする権限がありません。');
        }

        // 2. 取引相手の特定
        // 修正: 販売者($isSeller=true)の場合、相手は購入者($soldItem->buyer_id)である。
        // 購入者($isSeller=false)の場合、相手は販売者($item->user_id)である。
        $partnerId = $isSeller ? $soldItem->buyer_id : $item->user_id;

        $partner = User::find($partnerId);

        // 3. チャット履歴の取得
        $chats = Message::where('sold_item_id', $soldItem->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
        
        // 4. 既読処理
        Message::where('sold_item_id', $soldItem->id)
            ->where('user_id', '!=', $user->id) // 相手からのメッセージ
            ->where('is_read', false)
            ->update(['is_read' => true]);


        // 5. サイドバー表示用の取引中商品一覧の準備
        $inProgressItems = Item::whereHas('soldItem', function (Builder $query) use ($user) {
            $query->where('is_completed', false)
                ->where(function (Builder $q) use ($user) {
                    // 自分が購入者であるか、自分が販売者である商品
                    // 修正: SoldItemの購入者IDを参照
                    $q->where('buyer_id', $user->id) // 自分が購入した商品（SoldItemのbuyer_id）
                        ->orWhereHas('item', function (Builder $itemQuery) use ($user) {
                            $itemQuery->where('user_id', $user->id); // 自分が販売した商品（Itemのuser_id）
                        });
                });
        })
        ->with(['soldItem.messages' => function ($query) {
            $query->latest()->limit(1);
        }])
        ->get();

        // 各取引中アイテムの未読メッセージ数を計算
        $inProgressItems = $inProgressItems->map(function ($ipItem) use ($user) {
            $soldItemId = optional($ipItem->soldItem)->id;
            
            $ipItem->unread_count = $soldItemId
                ? Message::where('sold_item_id', $soldItemId)
                    ->where('user_id', '!=', $user->id)
                    ->where('is_read', false)
                    ->count()
                : 0;
            
            $ipItem->latest_message_at = optional(optional($ipItem->soldItem)->messages->first())->created_at;
            return $ipItem;
        });

        // 最新メッセージ時刻でソート
        $inProgressItems = $inProgressItems->sortByDesc('latest_message_at');
        
        // 6. 評価済みかどうかをチェック
        // 自分がこの取引（SoldItem）に対して評価を投稿済みか
        $isReviewed = Rating::where('sold_item_id', $soldItem->id)
            ->where('reviewer_id', $user->id)
            ->exists();
        
        return view('chat', [
            'item' => $item,
            'soldItem' => $soldItem,
            'partner' => $partner,
            'chats' => $chats,
            'isSeller' => $isSeller,
            'isBuyer' => $isBuyer,
            'inProgressItems' => $inProgressItems,
            'isReviewed' => $isReviewed,
        ]);
    }

    /**
     * 新しいメッセージを投稿する (FN006, FN007, FN008, FN009)
     * ルート: /chat/{item_id} (POST)
     *
     * @param ChatMessageRequest $request
     * @param int $item_id ルートパラメータ
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ChatMessageRequest $request, int $item_id)
    {
        // ルートパラメータのIDを使用してItemを取得
        $item = Item::findOrFail($item_id);
        $user = Auth::user();
        $imgUrl = null;
        $soldItem = $item->soldItem; // リレーションからSoldItemを取得

        // 1. 取引が完了していないことを確認
        if (optional($soldItem)->is_completed) {
            return back()->with('error', 'この取引は既に完了しているため、メッセージを送信できません。');
        }

        // 取引情報がない場合はエラー
        if (!$soldItem) {
            return back()->with('error', '取引が開始されていません。');
        }

        // 2. 画像アップロード処理 (FN006)
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = Storage::disk('local')->put('public/chat_images', $image);
            // public/chat_images/... を storage/chat_images/... に変換してURLとして扱う
            $imgUrl = str_replace('public/', 'storage/', $path); 
        }

        // 3. メッセージの保存処理
        Message::create([
            'user_id' => $user->id,
            'sold_item_id' => $soldItem->id, // SoldItemのIDを保存
            'message' => $request->message,
            'image_url' => $imgUrl,
            'is_read' => false,
        ]);

        return back();
    }

    /**
     * 取引を完了し、評価画面へリダイレクトする (FN012)
     * ルート: /trade/complete/{item_id} (POST)
     *
     * @param int $item_id ルートパラメータ
     * @return \Illuminate\Http\RedirectResponse
     */
    public function completeTrade(int $item_id)
    {
        // ルートパラメータのIDを使用してItemを取得
        $item = Item::findOrFail($item_id);
        $user = Auth::user();
        $soldItem = $item->soldItem;

        if (!$soldItem) {
            return back()->with('error', '取引情報が見つかりませんでした。');
        }

        // アクセス制御: 出品者または購入者のみが操作可能
        // 修正: SoldItemの購入者IDを参照
        if ($item->user_id !== $user->id && $soldItem->buyer_id !== $user->id) {
            abort(403, 'この操作を行う権限がありません。');
        }

        // 取引完了フラグを更新
        try {
            DB::transaction(function () use ($soldItem) {
                // すでに完了している場合はスキップ
                if (!$soldItem->is_completed) {
                    $soldItem->is_completed = true;
                    $soldItem->save();
                }
            });
        } catch (\Exception $e) {
            return back()->with('error', '取引完了処理中にエラーが発生しました。');
        }
        
        // 取引完了後、評価画面へリダイレクト (FN013)
        return redirect()->route('trade.review.show', ['item_id' => $item->id])
            ->with('success', '取引を完了しました。続いて相手を評価してください。');
    }

    /**
     * 評価画面を表示する (FN013)
     * ルート: /trade/review/{item_id} (GET)
     *
     * @param int $item_id ルートパラメータ
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function reviewView(int $item_id)
    {
        // ルートパラメータのIDを使用してItemを取得
        $item = Item::findOrFail($item_id);
        $user = Auth::user();
        $soldItem = $item->soldItem;

        if (!$soldItem || !$soldItem->is_completed) {
            // 取引が完了していない場合はチャット画面に戻す
            return redirect()->route('chat.show', ['item_id' => $item->id])->with('error', 'この取引はまだ完了していません。');
        }

        // 1. アクセス制御
        // 修正: SoldItemの購入者IDを参照
        if ($item->user_id !== $user->id && $soldItem->buyer_id !== $user->id) {
            abort(403, 'この評価画面にアクセスする権限がありません。');
        }
        
        // 2. 評価対象ユーザーを特定
        // ログインユーザーが出品者なら、購入者($soldItem->buyer_id)が評価対象。逆もまた然り。
        // 修正: SoldItemの購入者IDを参照
        $ratedUserId = ($item->user_id === $user->id) ? $soldItem->buyer_id : $item->user_id;
        $ratedUser = User::find($ratedUserId);

        // 3. 評価済みかチェック
        $isReviewed = Rating::where('sold_item_id', $soldItem->id)
            ->where('reviewer_id', $user->id)
            ->exists();

        if ($isReviewed) {
             return redirect()->route('user.mypage')->with('success', 'この取引の評価は既に完了しています。');
        }
        
        return view('review', [
            'item' => $item,
            'soldItem' => $soldItem,
            'ratedUser' => $ratedUser, // 評価対象のユーザー
        ]);
    }

    /**
     * 評価をデータベースに保存する (FN013)
     * ルート: /trade/review/{item_id} (POST)
     *
     * @param ReviewRequest $request
     * @param int $item_id ルートパラメータ
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submitReview(ReviewRequest $request, int $item_id)
    {
        // ルートパラメータのIDを使用してItemを取得
        $item = Item::findOrFail($item_id);
        $user = Auth::user();
        $soldItem = $item->soldItem;

        // 1. 取引が完了しているかなどのチェック
        if (!$soldItem || !$soldItem->is_completed) {
            return back()->with('error', '評価を行う前に取引を完了してください。');
        }
        
        // 2. 評価済みかチェック
        $isReviewed = Rating::where('sold_item_id', $soldItem->id)
            ->where('reviewer_id', $user->id)
            ->exists();

        if ($isReviewed) {
             return redirect()->route('user.mypage')->with('error', 'この取引の評価は既に完了しています。');
        }

        // 3. 評価対象ユーザーを再特定
        // 修正: SoldItemの購入者IDを参照
        $ratedUserId = ($item->user_id === $user->id) ? $soldItem->buyer_id : $item->user_id;
        
        try {
            Rating::create([
                'sold_item_id' => $soldItem->id,
                'reviewer_id' => $user->id,
                'rated_user_id' => $ratedUserId,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', '評価の保存中にエラーが発生しました。');
        }

        // マイページの完了した取引セクションへリダイレクト（'page' => 'completed'は仮定）
        return redirect()->route('user.mypage')->with('success', '評価が完了しました。ご協力ありがとうございました！');
    }
}
