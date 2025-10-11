<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SoldItem;
use Illuminate\Support\Facades\Log;

class TradeCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $soldItem;

    /**
     * コンストラクタ
     */
    public function __construct(SoldItem $soldItem)
    {
        $this->soldItem = $soldItem;
    }

    /**
     * メール構築
     */
    public function build()
    {
        $seller = optional($this->soldItem->item->user);
        $buyer  = optional($this->soldItem->buyer);
        $item   = optional($this->soldItem->item);

        $data = [
            'sellerName' => $seller->name ?? '出品者',
            'buyerName'  => $buyer->name ?? '購入者',
            'itemName'   => $item->name ?? '商品不明',
            'price'      => $this->soldItem->price ?? 0,
        ];

        Log::info('📨 TradeCompletedMail build data', $data);

        return $this->subject('【COACHTECHフリマ】取引完了のお知らせ')
            ->view('emails.trade_completed')
            ->with($data);
    }
}
