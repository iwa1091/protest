<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sold_item_id',
        'reviewer_id',   // 評価をしたユーザー
        'rated_user_id', // 評価をされたユーザー
        'rating',        // 評価点 (1〜5)
        'comment',       // 評価コメント
    ];

    /**
     * この評価が属する取引（SoldItem）を取得
     */
    public function soldItem(): BelongsTo
    {
        return $this->belongsTo(SoldItem::class);
    }

    /**
     * 評価を付与したユーザー（レビューア）を取得
     */
    public function reviewer(): BelongsTo
    {
        // usersテーブルを参照するが、reviewer_idカラムを使用
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * 評価をされたユーザーを取得
     */
    public function ratedUser(): BelongsTo
    {
        // usersテーブルを参照するが、rated_user_idカラムを使用
        return $this->belongsTo(User::class, 'rated_user_id');
    }
}
