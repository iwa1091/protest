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
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            // ★修正: 商品ID(item_id)ではなく、取引ID(sold_item_id)に紐付け (チャットは取引後に行われるため)
            $table->foreignId('sold_item_id')->constrained('sold_items')->cascadeOnDelete();
            // 誰が投稿したか (user_id)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); 
            
            // メッセージ本文 (FN007: 最大400文字の要件に合わせてTEXT型。stringより長い文字を許容)
            $table->text('message')->nullable(); // メッセージ本文は画像のみの場合もあるためnullableに変更
            // 添付画像 (FN006: 画像がオプションのためNULL許容)
            $table->string('image_url')->nullable(); 
            
            // 新規追加: 既読管理フラグ (FN001/FN005)
            $table->boolean('is_read')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
