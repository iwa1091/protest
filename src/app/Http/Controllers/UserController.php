<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Item;
use App\Models\SoldItem;
use App\Models\Rating;
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class UserController extends Controller
{
    /**
     * プロフィール編集画面表示
     */
    public function profile()
    {
        $profile = Auth::user()->profile;
        return view('profile', compact('profile'));
    }

    /**
     * プロフィール情報更新
     */
    public function updateProfile(ProfileRequest $request)
    {
        $user = Auth::user();
        $profile = $user->profile;
        $img_url = optional($profile)->img_url;

        // プロフィール画像更新
        if ($request->hasFile('img_url')) {
            $img = $request->file('img_url');
            $path = Storage::disk('local')->put('public/img', $img);
            $img_url = str_replace('public/', 'storage/', $path);
        }

        $profileData = [
            'user_id'  => $user->id,
            'img_url'  => $img_url,
            'postcode' => $request->postcode,
            'address'  => $request->address,
            'building' => $request->building,
        ];

        if ($profile) {
            $profile->update($profileData);
        } else {
            $user->profile()->create($profileData);
        }

        // 名前更新
        $user->update(['name' => $request->name]);

        return redirect()->route('user.mypage')->with('success', 'プロフィールを更新しました。');
    }

    /**
     * マイページ表示
     */
    public function mypage(Request $request)
    {
        $user = Auth::user();
        $page = $request->query('page', 'sell');

        $items = collect();
        $inProgressItems = collect();
        $totalUnread = 0;

        if ($page === 'sell') {
            // 出品した商品
            $items = Item::where('user_id', $user->id)
                ->with(['soldItem'])
                ->get();

        } elseif ($page === 'buy') {
            // 購入した商品（完了済のみ）
            $items = Item::whereHas('soldItem', function (Builder $q) use ($user) {
                    $q->where('buyer_id', $user->id)
                      ->where('is_completed', true);
                })
                ->with(['soldItem'])
                ->get();

        } elseif ($page === 'in-progress') {
            /**
             * ✅ 取引中の商品を「最新メッセージ順」で取得
             */
            $inProgressItems = SoldItem::with(['item'])
                ->withMax('messages', 'created_at') // 最新メッセージ日時を取得
                ->where('is_completed', false)
                ->where(function (Builder $q) use ($user) {
                    $q->where('buyer_id', $user->id)
                      ->orWhereHas('item', fn($iq) => $iq->where('user_id', $user->id));
                })
                ->orderByDesc('messages_max_created_at') // 最新メッセージ順にソート
                ->get()
                ->map(function ($soldItem) use ($user) {
                    // 各商品の未読数カウント
                    $soldItem->unread_count = $soldItem->messages()
                        ->where('user_id', '!=', $user->id)
                        ->where('is_read', false)
                        ->count();

                    // itemが存在しない場合に備えて補完
                    if (!$soldItem->relationLoaded('item') || !$soldItem->item) {
                        $soldItem->setRelation('item', null);
                    }

                    return $soldItem;
                });

            // 🔹 全体の未読メッセージ数を合計
            $totalUnread = $inProgressItems->sum('unread_count');
        }

        /**
         * ✅ 出品者としての平均評価算出
         */
        $ratings = Rating::where('rated_user_id', $user->id)->get();
        $averageRating = $ratings->isNotEmpty()
            ? number_format($ratings->avg('rating'), 1)
            : null;

        /**
         * ✅ ビューへデータ渡し
         */
        return view('mypage', [
            'user'            => $user,
            'items'           => $items,
            'inProgressItems' => $inProgressItems,
            'averageRating'   => $averageRating,
            'totalUnread'     => $totalUnread,
        ]);
    }

    /**
     * 任意ユーザーのプロフィール公開表示
     */
    public function showProfile($user_id)
    {
        $user = User::with('profile')->find($user_id);
        if (!$user) {
            abort(404, 'ユーザーが見つかりません');
        }

        $averageRating = Rating::where('rated_user_id', $user->id)->avg('rating');
        $averageRating = $averageRating !== null ? round($averageRating, 1) : null;

        $items = Item::where('user_id', $user->id)->get();

        return view('user_profile', compact('user', 'items', 'averageRating'));
    }
}
