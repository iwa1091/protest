<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            /**
             * 取引（sold_items）に紐づく外部キー
             * チャットは「取引成立後」に紐づくため item_id ではなく sold_item_id を使用
             */
            $table->foreignId('sold_item_id')
                ->constrained('sold_items')
                ->cascadeOnDelete();

            /**
             * メッセージ送信者（ユーザー）
             */
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /**
             * メッセージ本文（400文字程度）
             * 添付画像のみの投稿も許容するため nullable
             */
            $table->text('message')->nullable();

            /**
             * 添付画像パス
             * Storage::url() で参照するため string + nullable
             */
            $table->string('image_url')->nullable();

            /**
             * 既読フラグ
             * false = 未読 / true = 既読
             */
            $table->boolean('is_read')->default(false);

            /**
             * タイムスタンプ
             * created_at / updated_at 自動生成
             */
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
