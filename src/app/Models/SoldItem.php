<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoldItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',           // 購入者ID
        'item_id',            // 元の商品ID
        'sending_postcode',   // 郵便番号
        'sending_address',    // 住所
        'sending_building',   // 建物名
        'price',              // 購入金額
        'is_completed',       // 取引完了フラグ (チャット機能実装のコンテキストで重要)
    ];
    
    // is_completed のデフォルト値を false に設定しておくと便利かもしれません
    protected $attributes = [
        'is_completed' => false,
    ];

    /**
     * この取引の購入者情報を取得
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * この取引の元となった商品情報を取得
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * この取引に関連するチャットメッセージを取得 (SoldItemに直接紐づく)
     */
    public function messages(): HasMany
    {
        // Messageモデルは sold_item_id を外部キーとして持っているため、hasManyで定義
        return $this->hasMany(Message::class);
    }

    /**
     * この取引に関連する評価情報を取得
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
