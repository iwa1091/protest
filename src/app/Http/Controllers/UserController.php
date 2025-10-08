<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Item;
use App\Models\Like;
use App\Models\SoldItem;
use App\Models\Rating; // Ratingモデルを追加
use App\Http\Requests\ProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder; // Builderをuse

class UserController extends Controller
{
    // プロフィール編集画面表示
    public function profile(){
        $profile = Auth::user()->profile; // ユーザーリレーションから取得
        return view('profile',compact('profile'));
    }

    // プロフィール情報更新
    public function updateProfile(ProfileRequest $request){

        $user = Auth::user();
        $profile = $user->profile;
        $img_url = optional($profile)->img_url; // 既存のURLを保持

        // 1. 画像アップロード処理
        if ($request->hasFile('img_url')) {
            $img = $request->file('img_url');
            // 画像を保存し、Storage::url()でアクセス可能なパスに変換
            $path = Storage::disk('local')->put('public/img', $img);
            $img_url = str_replace('public/', 'storage/', $path);
        }

        // 2. Profileデータの更新/作成
        $profileData = [
            'user_id' => $user->id,
            'img_url' => $img_url,
            'postcode' => $request->postcode,
            'address' => $request->address,
            'building' => $request->building
        ];

        if ($profile){
            $profile->update($profileData);
        }else{
            $user->profile()->create($profileData);
        }

        // 3. User名の更新
        $user->update([
            'name' => $request->name
        ]);
        
        // マイページへリダイレクト
        return redirect()->route('user.mypage')->with('success', 'プロフィールを更新しました。');
    }

    /**
     * マイページを表示する (FN001, FN005, FN013)
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function mypage(Request $request)
    {
        $user = Auth::user();
        $page = $request->query('page', 'sell'); // デフォルトは'sell'

        $items = collect(); // 'sell' または 'buy' 用のItemコレクションを初期化
        $inProgressItems = collect(); // 'in-progress' 用のItemコレクションを初期化

        // 1. 出品/購入/取引中の商品リストを取得
        if ($page === 'sell') {
            // 出品した商品
            $items = Item::where('user_id', $user->id)
                ->with(['soldItem'])
                ->get();
        } elseif ($page === 'buy') {
            // 【FN012/FN013対応】購入した商品（取引完了したもののみ）
            $items = Item::whereHas('soldItem', function (Builder $query) use ($user) {
                // 購入者として取引完了
                $query->where('user_id', $user->id) 
                      ->where('is_completed', true); // 取引完了済みのみ
            })
            ->with(['soldItem'])
            ->get();
        } elseif ($page === 'in-progress') {
            // FN001: 取引中の商品リスト (未完了)
            
            // Itemに紐づくSoldItemが存在し、is_completed=false であることを確認
            $inProgressItems = Item::whereHas('soldItem', function (Builder $query) {
                $query->where('is_completed', false);
            })
            // さらに、その取引に自分が関わっているかを確認 (Itemの出品者 OR SoldItemの購入者)
            ->where(function (Builder $query) use ($user) {
                // 条件 A: Itemの出品者（自分）であること
                $query->where('user_id', $user->id)
                      // 条件 B: または、SoldItemの購入者（自分）であること
                      ->orWhereHas('soldItem', function (Builder $subQuery) use ($user) {
                          $subQuery->where('user_id', $user->id);
                      });
            })
            // SoldItemと、SoldItem経由のMessageをロード
            ->with(['soldItem.messages' => function ($query) use ($user) {
                // 未読メッセージは、相手から送られてきたもの (user_id != 認証ユーザーID) かつ is_read=false のもの
                $query->where('user_id', '!=', $user->id)
                      ->where('is_read', false);
            }])
            ->get();
            
            // 未読メッセージ数を SoldItem 経由でカウントする
            $inProgressItems = $inProgressItems->map(function ($item) {
                // Item -> SoldItem -> messages のリレーションを辿ってカウント
                $item->unread_count = optional($item->soldItem)->messages->count() ?? 0;
                return $item;
            });
        }
        
        // 2. FN005: 評価平均の算出
        $ratings = Rating::where('rated_user_id', $user->id)->get();
        $averageRating = $ratings->isNotEmpty() ? number_format($ratings->avg('rating'), 1) : null;


        // 3. ビューへ変数を渡す
        return view('mypage', [
            'user' => $user,
            'items' => $items, // sell/buyタブ用
            'averageRating' => $averageRating,
            // in-progressタブ用
            'inProgressItems' => $inProgressItems, 
        ]);
    }

    // 【新規追加】任意のユーザーのプロフィールを公開表示するメソッド (FN005対応)
    public function showProfile($user_id)
    {
        // プロフィール情報と合わせてユーザー情報を取得
        $user = User::with('profile')->find($user_id);

        if (!$user) {
            abort(404, 'ユーザーが見つかりません');
        }

        // 評価平均の計算
        $averageRating = Rating::where('rated_user_id', $user->id)->avg('rating');
        $averageRating = $averageRating !== null ? round($averageRating, 1) : null;

        // 出品アイテムの取得
        $items = Item::where('user_id', $user->id)->get();

        // user_profileビューを作成し、このデータを表示
        return view('user_profile', compact('user', 'items', 'averageRating'));
    }
}
