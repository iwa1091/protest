<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sold_items', function (Blueprint $table) {
            $table->id();

            // 外部キー
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();

            // 配送先情報
            $table->string('sending_postcode');
            $table->string('sending_address');
            $table->string('sending_building')->nullable();

            // 価格
            $table->integer('price');

            // 取引完了フラグ
            $table->boolean('is_completed')->default(false);

            // 既読管理タイムスタンプ
            $table->timestamp('buyer_last_read_at')->nullable();
            $table->timestamp('seller_last_read_at')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sold_items');
    }
};
