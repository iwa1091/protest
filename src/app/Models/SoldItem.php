<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoldItem extends Model
{
    use HasFactory;

    // ★修正: idカラムが追加されたため、主キーのカスタム設定と増分無効設定を削除
    // protected $primaryKey = 'item_id';
    // public $incrementing = false;

    protected $fillable = [
        'buyer_id', // ★修正: user_idからbuyer_idに変更 (購入者ID)
        'item_id', // 元の商品ID
        'sending_postcode',
        'sending_address',
        'sending_building',
        'price', // ★追加: priceカラムをfillableに追加
        // 既読管理用のカラム（buyer_last_read_at, seller_last_read_at）はmessagesテーブルに移動したため削除
    ];

    /**
     * この取引（SoldItem）の購入者を取得
     */
    public function user(): BelongsTo
    {
        // ★修正: 外部キーをbuyer_idに明示的に設定
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
     * この取引に関連するチャットメッセージを取得
     */
    public function messages(): HasMany
    {
        // messagesテーブルがまだ作成されていませんが、先んじてリレーションを定義
        return $this->hasMany(Message::class);
    }

    /**
     * この取引に関連する評価を取得
     */
    public function ratings(): HasMany
    {
        // ratingsテーブルがまだ作成されていませんが、先んじてリレーションを定義
        return $this->hasMany(Rating::class);
    }
}
