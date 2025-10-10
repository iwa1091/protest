<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ItemRequest;
use App\Models\Item;
use App\Models\Category;
use App\Models\Condition;
use App\Models\CategoryItem;

class ItemController extends Controller
{
    /**
     * トップページ（商品一覧）
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'recommend');
        $search = $request->query('search');
        $query = Item::query();

        // 自分の商品は除外
        $query->where('user_id', '<>', Auth::id());

        // マイリスト表示
        if ($tab === 'mylist') {
            $query->whereIn('id', function ($q) {
                $q->select('item_id')
                  ->from('likes')
                  ->where('user_id', auth()->id());
            });
        }

        // 検索キーワード
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->get();

        return view('index', compact('items', 'tab', 'search'));
    }

    /**
     * 商品詳細
     */
    public function detail(Item $item)
    {
        return view('detail', compact('item'));
    }

    /**
     * 検索機能
     */
    public function search(Request $request)
    {
        $search_word = $request->search_item;
        $query = Item::query();
        $query = Item::scopeItem($query, $search_word);
        $items = $query->get();

        return view('index', compact('items'));
    }

    /**
     * 出品フォーム表示
     */
    public function sellView()
    {
        $categories = Category::all();
        $conditions = Condition::all();

        return view('sell', compact('categories', 'conditions'));
    }

    /**
     * 出品登録処理
     */
    public function sellCreate(ItemRequest $request)
    {
        $img = $request->file('img_url');

        try {
            // ✅ publicディスクを使用して保存（Storage::url対応）
            // 保存先: storage/app/public/img/
            // 戻り値例: "img/abc123.jpg"
            $path = $img->store('img', 'public');
        } catch (\Throwable $th) {
            throw $th;
        }

        // DB登録
        $item = Item::create([
            'name'         => $request->name,
            'price'        => $request->price,
            'brand'        => $request->brand,
            'description'  => $request->description,
            'img_url'      => $path, // ✅ "img/xxxxx.jpg" の形で保存
            'condition_id' => $request->condition_id,
            'user_id'      => Auth::id(),
        ]);

        // カテゴリ中間テーブル登録
        foreach ($request->categories as $category_id) {
            CategoryItem::create([
                'item_id'    => $item->id,
                'category_id'=> $category_id
            ]);
        }

        return redirect()->route('item.detail', ['item' => $item->id]);
    }
}
