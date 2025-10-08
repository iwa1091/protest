@extends('layouts.default')

@section('title', ($item?->name ?? 'チャットが見つかりません') . ' - 取引チャット')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/index.css') }}" >
<link rel="stylesheet" href="{{ asset('css/chat.css') }}">
<style>


</style>
@endsection

@section('content')

@include('components.header')

<div class="chat-wrapper">
{{-- 左側: 取引中の他のチャット一覧 (FN003: 別取引遷移機能) --}}
<div class="sidebar">
<div class="sidebar-header">
<h3>取引中の商品一覧</h3>
</div>
<ul class="chat-list">
@forelse ($inProgressItems as $ipItem)
{{-- FN004: 取引自動ソート機能（新規メッセージ順でソートされていることを想定） --}}
{{-- 現在のチャットがアクティブ --}}
{{-- 修正2: $itemがnullの場合に $item->id でエラーとならないようオプショナルチェーン '?->' を使用 --}}
<li class="chat-list-item @if($ipItem->id === $item?->id) active @endif">
{{-- FN002: 取引チャット遷移機能 --}}
{{-- 【修正】route('chat.show', ['item' => ...]) を route('chat.show', $ipItem->id) に変更 --}}
<a href="{{ route('chat.show', $ipItem->id) }}">
<div class="item-img-box">
<img src="{{ Storage::url($ipItem->img_url) }}" alt="{{ $ipItem->name }}" class="item-img">
</div>
<div class="item-info">
<p class="item-name">{{ Str::limit($ipItem->name, 15) }}</p>
{{-- FN005: 新規通知確認機能 (未読メッセージがある場合) --}}
@if (isset($ipItem->unread_count) && $ipItem->unread_count > 0)
<span class="unread-badge">{{ $ipItem->unread_count }}</span>
@endif
</div>
</a>
</li>
@empty
<li class="no-chats">取引中の商品はありません。</li>
@endforelse
</ul>
<a href="{{ route('user.mypage', ['page' => 'in-progress']) }}" class="back-to-mypage">
<i class="fas fa-arrow-left"></i> マイページへ戻る
</a>
</div>

{{-- 右側: メインチャットエリア --}}
{{-- 修正3: $itemがnullの場合はメインチャットエリアの表示を避ける（コントローラーでの処理を推奨するが、ビュー側で安全性を高める） --}}
@if ($item)
<div class="main-chat-area">
    <div class="item-detail-bar">
        {{-- 取引相手の情報 --}}
        <div class="partner-info">
            @if ($isSeller)
                <span class="role seller">出品者</span>
            @else
                <span class="role buyer">購入者</span>
            @endif
            <a href="{{ route('user.profile.show', ['user_id' => $partner?->id]) }}" class="partner-name-link">{{ $partner?->name ?? '取引相手' }}との取引
            </a>
        </div>

        {{-- 商品情報 (item-info-headerクラスを使用) --}}
        <div class="item-info-header"> {{-- 商品情報を新しいラッパーで囲みました --}}
            <img src="{{ Storage::url($item->img_url) }}" alt="{{ $item->name }}" class="item-header-img">
            <div class="item-text-details">
                <p class="item-name-header">{{ $item->name }}
                ¥{{ number_format($item->price) }}</p>
            </div>
        </div> {{-- item-info-headerを閉じました --}}
    </div>

    {{-- メッセージ履歴表示エリア (FN001) --}}
    <div class="chat-history">
        @forelse ($chats as $chat)
            {{-- メッセージ表示ロジック --}}
            @if ($chat->user_id === Auth::id())
                <div class="message-bubble sender">
                    <p class="message-text">

{{ $chat->message }}
{{-- 画像添付がある場合は表示 --}}
@if ($chat->image_url)
<img src="{{ Storage::url($chat->image_url) }}" alt="添付画像" class="message-image">
@endif

</p>
{{-- FN010/FN011: 編集・削除ボタンは今回は省略 --}}
<span class="message-time">{{ $chat->created_at->format('Y/m/d H:i') }}</span>
</div>
@else
<div class="message-bubble receiver">
<div class="receiver-header">
<span class="receiver-name">{{ $partner?->name ?? '不明なユーザー' }}</span>
<span class="message-time">{{ $chat->created_at->format('Y/m/d H:i') }}</span>
</div>
<p class="message-text">
{{ $chat->message }}
{{-- 画像添付がある場合は表示 --}}
@if ($chat->image_url)
<img src="{{ Storage::url($chat->image_url) }}" alt="添付画像" class="message-image">
@endif
</p>
</div>
@endif
@empty
<p class="no-chats-yet">取引メッセージはまだありません。</p>
@endforelse
</div>

    {{-- メッセージ投稿フォーム (FN006: 取引チャット機能) --}}
    <div class="chat-input-area">
        {{-- 【修正】route('chat.store', ['item' => ...]) を route('chat.store', $item->id) に変更 --}}
        <form action="{{ route('chat.store', $item->id) }}" method="POST" enctype="multipart/form-data" class="chat-form">
            @csrf
            
            <textarea name="message" class="message-input" placeholder="メッセージを入力してください (400文字以内)" rows="3">{{ old('message') }}</textarea>

{{-- メッセージのバリデーションエラー表示 --}}
@error('message')

<p class="error-message">{{ $message }}</p>
@enderror

            <div class="form-controls">
                <div>
                    <label for="image_upload" class="image-upload-label">
                        <i class="fas fa-camera"></i> 画像を選択
                        <input type="file" name="image" id="image_upload" accept=".jpeg, .png" style="display:none;">
                    </label>

{{-- 画像のバリデーションエラー表示 --}}
@error('image')

<p class="error-message" style="margin-top:5px; margin-bottom:0;">{{ $message }}</p>
@enderror
</div>
<button type="submit" class="send-button">送信 <i class="fas fa-paper-plane"></i></button>
</div>
</form>

        {{-- FN012/FN013: 取引完了・評価ボタン（取引が未完了の場合のみ表示） --}}
        @if (!$item->is_completed)
            {{-- FN012: 購入者はボタンクリックで取引完了モーダルを表示 --}}
            @if (!$isSeller)
                <button id="complete-trade-btn" class="complete-trade-button">取引を完了する</button>
            @else
                {{-- 出品者は相手の完了待ち --}}
                <p class="trade-pending-text">購入者の取引完了操作をお待ちください。</p>
            @endif
        @else
            {{-- 取引が完了している場合のメッセージ --}}
            <p class="trade-completed-text">この取引は完了済みです。評価をお願いします。</p>
        @endif
    </div>
</div>
@else
{{-- $item が null の場合に表示する代替コンテンツ --}}
<div class="main-chat-area">
    <div style="padding: 40px; text-align: center; margin-top: 100px;">
        <p style="font-size: 1.2rem; color: #dc3545; font-weight: bold;">エラー: 指定された商品または取引が見つかりません。</p>
        <p style="color: #6c757d; margin-top: 10px;">取引中の商品一覧から別の商品を選択するか、URLが正しいか確認してください。</p>
    </div>
</div>
@endif

</div>

{{-- 取引完了＆評価モーダル (FN012/FN013) --}}
@if ($item) {{-- $itemが存在する場合のみモーダルを表示し、$itemのプロパティ参照エラーを回避 --}}

<div id="review-modal" class="modal-overlay">
<div class="modal-content">

    {{-- 1. 取引完了確認画面（購入者がボタンを押した場合にJSで表示切り替え） --}}
    <div id="completion-form-wrapper" style="display: none;">
        <h3>取引を完了しますか？</h3>
        <p>この操作を行うと、取引相手を評価する画面に進みます。商品の受け取りを確認後に行ってください。</p>
        <form id="trade-complete-form" action="{{ route('trade.complete', $item->id) }}" method="POST">
            @csrf
            <button type="submit" class="submit-btn complete-confirm-btn">完了して評価に進む</button>
        </form>
        <button class="submit-btn close-modal-btn" data-form="completion" style="background-color:#ccc; margin-top:10px;">キャンセル</button>
    </div>

    {{-- 2. 評価フォーム本体（取引完了後にJS/Controllerで表示） --}}
    <div id="review-form-wrapper" style="display: none;">
        <h3>{{ $partner?->name ?? '取引相手' }}さんを評価してください</h3>
        <p>星のクリックで評価を入力してください。</p>

        <form action="{{ route('trade.review.store', $item->id) }}" method="POST" class="review-form">
            @csrf

            {{-- 1. 星評価エリア（必須） --}}
            <div class="review-form-group">
                <div class="rating-area">
                    {{-- 5.0 --}}
                    <input type="radio" id="m-star5" name="rating" value="5" required>
                    <label for="m-star5" title="最高">★</label>
                    {{-- 4.0 --}}
                    <input type="radio" id="m-star4" name="rating" value="4">
                    <label for="m-star4" title="とても良い">★</label>
                    {{-- 3.0 --}}
                    <input type="radio" id="m-star3" name="rating" value="3">
                    <label for="m-star3" title="良い">★</label>
                    {{-- 2.0 --}}
                    <input type="radio" id="m-star2" name="rating" value="2">
                    <label for="m-star2" title="普通">★</label>
                    {{-- 1.0 --}}
                    <input type="radio" id="m-star1" name="rating" value="1">
                    <label for="m-star1" title="悪い">★</label>
                </div>
            </div>

        {{-- @error('rating')の表示はここでは省略（サーバー側バリデーションを想定） --}}

            {{-- 2. コメントエリア（任意） --}}
            <div class="review-form-group">
                <textarea name="comment" class="review-textarea" placeholder="取引の感想を任意で入力してください..." rows="3"></textarea>
            </div>

            {{-- 送信ボタン --}}
            <button type="submit" class="review-submit-btn">評価を送信する</button>
        </form>

<button class="submit-btn close-modal-btn" data-form="review" style="background-color:#ccc; margin-top:10px;">キャンセル</button>
</div>

</div>

</div>
@endif

{{-- $itemが存在する場合のみスクリプトを実行し、$itemのプロパティ参照エラーを回避 --}}
@if ($item)

<script>
document.addEventListener('DOMContentLoaded', () => {
// -------------------------------------------
// モーダル表示/非表示の要素定義
// -------------------------------------------
const reviewModal = document.getElementById('review-modal');
const completionWrapper = document.getElementById('completion-form-wrapper');
const reviewFormWrapper = document.getElementById('review-form-wrapper');
const completeTradeBtn = document.getElementById('complete-trade-btn');
const closeModalBtns = document.querySelectorAll('.close-modal-btn');
const chatHistory = document.querySelector('.chat-history');

    // Controllerから渡される変数を取得
    const shouldShowReviewModal = @json($shouldShowReviewModal ?? false);
    const itemIsCompleted = @json($item->is_completed ?? false);
    const isSeller = @json($isSeller ?? false);

// -------------------------------------------
// チャット履歴の自動スクロール (FN001の補助)
// -------------------------------------------
if (chatHistory) {
    chatHistory.scrollTop = chatHistory.scrollHeight;
}

    // ★FN012: 購入者によるボタンクリックでモーダルを表示 (未完了時)
    if (completeTradeBtn) {
        completeTradeBtn.addEventListener(&#39;click&#39;, () =&gt; {
            // 取引完了確認フォームを表示
            if (completionWrapper) completionWrapper.style.display = &#39;block&#39;;
            if (reviewFormWrapper) reviewFormWrapper.style.display = &#39;none&#39;;
            reviewModal.style.display = &#39;flex&#39;;
        });
    }

    // モーダルを閉じる処理
    closeModalBtns.forEach(btn =&gt; {
        btn.addEventListener(&#39;click&#39;, () =&gt; {
            reviewModal.style.display = &#39;none&#39;;
        });
    });

    // -------------------------------------------
    // FN013: 評価モーダルの自動表示 (ページ読み込み時)
    // -------------------------------------------
    // 取引完了後、かつ未評価の場合に自動表示
    if (shouldShowReviewModal) {
        // 取引完了確認モーダルはスキップし、直接評価フォームを表示
        if (completionWrapper) completionWrapper.style.display = &#39;none&#39;;
        if (reviewFormWrapper) reviewFormWrapper.style.display = &#39;block&#39;;
        reviewModal.style.display = &#39;flex&#39;;
    }
});

</script>

@endif

@endsection