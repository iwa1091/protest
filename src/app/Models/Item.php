<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // 追加
use Illuminate\Database\Eloquent\Relations\HasManyThrough; // 追加

class Item extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'price',
        'brand',
        'description',
        'img_url',
        'user_id', 
        'condition_id',
    ];

    /**
     * この商品を登録したユーザーを取得
     */
    public function user(): BelongsTo // タイプヒントに修正
    {
        return $this->belongsTo(User::class); // クラス参照に修正
    }

    /**
     * この商品が属する状態（Condition）を取得
     */
    public function condition(): BelongsTo // タイプヒントに修正
    {
        return $this->belongsTo(Condition::class); // クラス参照に修正
    }

    /**
     * この商品に付けられた「いいね」（Like）を全て取得
     */
    public function likes(): HasMany // タイプヒントに修正
    {
        return $this->hasMany(Like::class); // クラス参照に修正
    }

    /**
     * この商品に付けられたコメント（Comment）を全て取得
     */
    public function comments(): HasMany // タイプヒントに修正
    {
        return $this->hasMany(Comment::class); // クラス参照に修正
    }

    /**
     * この商品に紐づくカテゴリ中間テーブル（CategoryItem）を取得
     */
    public function categoryItems(): HasMany // メソッド名を複数形に修正
    {
        return $this->hasMany(CategoryItem::class); // クラス参照に修正
    }

    /**
     * この商品に紐づくカテゴリを取得（リレーション定義ではなくカスタムアクセサ）
     */
    public function categories()
    {
        // 修正: categoryItem() ではなく categoryItems() を利用
        $categories = $this->categoryItems->map(function ($item) {
            return $item->category;
        });
        return $categories;
    }

    public function liked()
    {
        return Like::where(['item_id' => $this->id, 'user_id' => Auth::id()])->exists();
    }

    public function likeCount()
    {
        return Like::where('item_id', $this->id)->count();
    }

    public function getComments(){
        $comments = Comment::where('item_id', $this->id)->get();
        return $comments;
    }

    /**
     * この商品に紐づく取引情報（SoldItem）を取得
     */
    public function soldItem(): HasOne
    {
        return $this->hasOne(SoldItem::class);
    }
    
    /**
     * この商品の取引が完了しているか判定
     */
    public function sold(){
        // SoldItemが存在し、is_completedがtrueの場合を完了と見なす
        // SoldItemが存在するだけであれば、hasOne('App\Models\SoldItem')->exists() を使う
        // return $this->soldItem()->exists(); // soldItem()->exists() は取引中の判定に使える
        return $this->soldItem()->where('is_completed', true)->exists(); // 取引完了を厳密にチェック
    }

    public function mine(){
        return $this->user_id == Auth::id();
    }

    public static function scopeItem($query, $item_name){
        return $query->where('name', 'like', '%'.$item_name.'%');
    }

    /* -------------------------------------
     * チャット機能（FN001, FN004, FN005）
     * ------------------------------------- */

    /**
     * この商品に関連するすべてのチャットメッセージを取得 (chats)
     * Messageモデルがitem_idにリレーションを持っている前提。
     */
    public function chats(): HasMany
    {
        return $this->hasMany(Message::class, 'item_id');
    }

    /**
     * この商品に関連するすべてのチャットメッセージを取得 (messages)
     * messages() の名前で呼ばれた場合も対応できるように定義。
     * Messageモデルがitem_idにリレーションを持っている前提。
     */
    public function messages(): HasMany // 追加: RelationNotFoundException対策
    {
        return $this->hasMany(Message::class, 'item_id');
    }

    /**
     * この商品の最新のチャットメッセージを1件取得（ソート用）
     */
    public function latestChat(): HasOne
    {
        return $this->hasOne(Message::class, 'item_id')->latest();
    }
}
