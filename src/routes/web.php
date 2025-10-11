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
| アプリケーションのメインルート設定
|--------------------------------------------------------------------------
*/

// --- Stripe Webhook（現在未使用・必要に応じて有効化） ---
// Route::post('/stripe/webhook', [PurchaseController::class, 'webhook']);

// --- 公開ルート（非ログインユーザー向け） ---
Route::get('/', [ItemController::class, 'index'])->name('items.list');
Route::get('/item/{item}', [ItemController::class, 'detail'])->name('item.detail');
Route::get('/item', [ItemController::class, 'search']);

// 任意ユーザーのプロフィール閲覧
Route::get('/user/{user_id}', [UserController::class, 'showProfile'])->name('user.profile.show');

// --- Stripe Checkout 成功後（決済完了＆出品者へメール通知） ---
Route::get('/purchase/{item_id}/success', [PurchaseController::class, 'success'])
    ->name('purchase.success');

// --- 認証済みユーザー専用ルート ---
Route::middleware(['auth', 'verified'])->group(function () {

    /* ----------------------------
     * 出品関連
     * ---------------------------- */
    Route::get('/sell', [ItemController::class, 'sellView'])->name('item.sell.view');
    Route::post('/sell', [ItemController::class, 'sellCreate'])->name('item.sell.create');

    /* ----------------------------
     * いいね・コメント
     * ---------------------------- */
    Route::post('/item/like/{item_id}', [LikeController::class, 'create'])->name('item.like');
    Route::post('/item/unlike/{item_id}', [LikeController::class, 'destroy'])->name('item.unlike');
    Route::post('/item/comment/{item_id}', [CommentController::class, 'create'])->name('item.comment');

    /* ----------------------------
     * 購入・配送
     * ---------------------------- */
    Route::get('/purchase/{item_id}', [PurchaseController::class, 'index'])
        ->middleware('purchase')
        ->name('purchase.index');

    Route::post('/purchase/{item_id}', [PurchaseController::class, 'purchase'])
        ->middleware('purchase')
        ->name('purchase.store');

    // 配送先住所変更
    Route::get('/purchase/address/{item_id}', [PurchaseController::class, 'address'])->name('purchase.address');
    Route::post('/purchase/address/{item_id}', [PurchaseController::class, 'updateAddress'])->name('purchase.address.update');

    /* ----------------------------
     * マイページ・プロフィール
     * ---------------------------- */
    Route::get('/mypage', [UserController::class, 'mypage'])->name('user.mypage');
    Route::get('/mypage/profile', [UserController::class, 'profile'])->name('user.profile');
    Route::post('/mypage/profile', [UserController::class, 'updateProfile'])->name('user.profile.update');

    /* ----------------------------
     * 取引チャット・評価
     * ---------------------------- */
    Route::prefix('chat')->group(function () {
        Route::get('/{item_id}', [ChatController::class, 'show'])->name('chat.show');
        Route::post('/{item_id}', [ChatController::class, 'store'])->name('chat.store');
        Route::put('/{message_id}', [ChatController::class, 'update'])->name('chat.update');
        Route::delete('/{message_id}', [ChatController::class, 'destroy'])->name('chat.delete');
    });

    // ✅ 「取引完了」はメール送信ではなくレビュー開始用として残す
    Route::post('/trade/complete/{item_id}', [ChatController::class, 'completeTrade'])
        ->name('trade.complete');

    // 双方評価後 → is_completed = true に更新
    Route::post('/trade/review/{item_id}', [ChatController::class, 'submitReview'])
        ->name('trade.review.submit');
});

// --- 認証関連 (Fortify) ---
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('email');
Route::post('/register', [RegisteredUserController::class, 'store']);

// --- メール認証 ---
Route::get('/email/verify', fn() => view('auth.verify-email'))->name('verification.notice');

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
