<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Requests\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/',[ItemController::class, 'index'])->name('items.list');
Route::get('/item/{item}',[ItemController::class, 'detail'])->name('item.detail');
Route::get('/item', [ItemController::class, 'search']);

// 任意のユーザーのプロフィール閲覧ルート
Route::get('/user/{user_id}', [UserController::class, 'showProfile'])->name('user.profile.show');

Route::get('/purchase/{item_id}/success', [PurchaseController::class, 'success'])->name('purchase.success');

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/sell',[ItemController::class, 'sellView']);
    Route::post('/sell',[ItemController::class, 'sellCreate']);
    Route::post('/item/like/{item_id}',[LikeController::class, 'create']);
    Route::post('/item/unlike/{item_id}',[LikeController::class, 'destroy']);
    Route::post('/item/comment/{item_id}',[CommentController::class, 'create']);
    
    // 購入処理ルート
    Route::get('/purchase/{item_id}',[PurchaseController::class, 'index'])->middleware('purchase')->name('purchase.index');
    Route::post('/purchase/{item_id}',[PurchaseController::class, 'purchase'])->middleware('purchase');
    //Route::get('/purchase/{item_id}/success', [PurchaseController::class, 'success']);
    Route::get('/purchase/address/{item_id}',[PurchaseController::class, 'address']);
    Route::post('/purchase/address/{item_id}',[PurchaseController::class, 'updateAddress']);

    // マイページ・プロフィールルート
    Route::get('/mypage', [UserController::class, 'mypage'])->name('user.mypage');
    Route::get('/mypage/profile', [UserController::class, 'profile'])->name('user.profile'); 
    Route::post('/mypage/profile', [UserController::class, 'updateProfile']);

    // 【修正】取引チャット関連ルートのパラメータ名を {item_id} に統一 (暗黙的なモデルバインディングを避ける)
    Route::get('/chat/{item_id}', [ChatController::class, 'show'])->name('chat.show'); // ★ 修正
    Route::post('/chat/{item_id}', [ChatController::class, 'store'])->name('chat.store'); // ★ 修正

    // 【修正】取引完了・評価関連ルートのパラメータ名を {item_id} に統一
    Route::post('/trade/complete/{item_id}', [ChatController::class, 'completeTrade'])->name('trade.complete'); // ★ 修正
    Route::get('/trade/review/{item_id}', [ChatController::class, 'reviewView'])->name('trade.review.show'); // ★ 修正
    Route::post('/trade/review/{item_id}', [ChatController::class, 'submitReview'])->name('trade.review.store'); // ★ 修正
});

Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('email');
Route::post('/register', [RegisteredUserController::class, 'store']);

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->name('verification.notice');

Route::post('/email/verification-notification', function (Request $request) {
    session()->get('unauthenticated_user')->sendEmailVerificationNotification();
    session()->put('resent', true);
    return back()->with('message', 'Verification link sent!');
})->name('verification.send');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    session()->forget('unauthenticated_user');
    return redirect('/mypage/profile');
})->name('verification.verify');
