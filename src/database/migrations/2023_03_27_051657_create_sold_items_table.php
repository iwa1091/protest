<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// class CreateSoldItemsTable extends Migration // ララベルの新しい記法に統一
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sold_items', function (Blueprint $table) {
            // 主キー
            $table->id(); 
            
            // どの商品が購入されたか
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            // 購入者ID (user_id から buyer_id に名称を統一)
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete(); 
            
            // 配送先情報
            $table->string('sending_postcode');
            $table->string('sending_address');
            $table->string('sending_building')->nullable();

            // 価格カラム (シード実行エラー修正のため、念のため残す)
            $table->integer('price');
            
            // 新規追加: 取引完了フラグ (取引中判定に使用)
            $table->boolean('is_completed')->default(false); 
            
            // ★追加: 既読管理用のタイムスタンプ (MessageSeederのエラー原因)
            $table->timestamp('buyer_last_read_at')->nullable(); 
            $table->timestamp('seller_last_read_at')->nullable(); 
            
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
        Schema::dropIfExists('sold_items');
    }
};
