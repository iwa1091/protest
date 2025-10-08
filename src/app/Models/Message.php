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
        'item_id',       // どの商品（Item）に関するメッセージか (Itemに直接紐付け)
        'user_id',       // 送信者
        'message',       // メッセージ本文 (FN006)
        'image_url',     // 添付画像URL (FN006)
        'is_read',       // 既読フラグ (未読メッセージ数カウントに使用)
    ];

    /**
     * このメッセージを送信したユーザーを取得
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * このメッセージが属する商品（Item）を取得
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
