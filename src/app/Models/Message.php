<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sold_item_id',  // 取引(SoldItem)に紐づくメッセージ
        'user_id',       // 送信者ユーザー
        'message',       // メッセージ本文
        'image_url',     // 添付画像パス
        'is_read',       // 既読フラグ
    ];

    /**
     * このメッセージを送信したユーザー
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このメッセージが属する取引（SoldItem）
     */
    public function soldItem(): BelongsTo
    {
        return $this->belongsTo(SoldItem::class);
    }

    /**
     * このメッセージが属する商品（Item）
     * SoldItem経由で取得可能にしておく（利便性向上）
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'sold_item_id', 'id');
    }
}
