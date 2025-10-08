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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            // どの取引（sold_item）に対する評価か (FN012/FN013)
            $table->foreignId('sold_item_id')->constrained('sold_items')->cascadeOnDelete(); 
            
            // 評価をしたユーザー (レビューア)
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete(); 
            // 評価をされたユーザー (被レビューア)
            $table->foreignId('rated_user_id')->constrained('users')->cascadeOnDelete(); 
            
            // 評価点 (1〜5など。FN005: 評価平均計算のベース)
            $table->unsignedTinyInteger('rating'); 
            
            // 評価コメント（オプション）
            $table->string('comment')->nullable(); 
            
            // 同じ取引で同じユーザーが二度評価できないようにする（例: 購入者が出品者を評価するのは1回のみ）
            $table->unique(['sold_item_id', 'reviewer_id']);
            
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
        Schema::dropIfExists('ratings');
    }
};
