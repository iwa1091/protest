@extends('layouts.default')

@section('title', ($item?->name ?? 'チャットが見つかりません') . ' - 取引チャット')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/index.css') }}">
<link rel="stylesheet" href="{{ asset('css/chat.css') }}">
@endsection

@section('content')

@include('components.header')

<div class="chat-wrapper">

    {{-- 左側: 他の取引一覧 --}}
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>取引中の商品</h3>
        </div>
        <ul class="chat-list">
            @forelse ($inProgressItems as $ipItem)
                <li class="chat-list-item @if($ipItem->id === $item?->id) active @endif">
                    <a href="{{ route('chat.show', $ipItem->id) }}">
                        <div class="item-img-box">
                            <img src="{{ Storage::url($ipItem->img_url) }}" alt="{{ $ipItem->name }}" class="item-img">
                        </div>
                        <div class="item-info">
                            <p class="item-name">{{ Str::limit($ipItem->name, 15) }}</p>
                            @if (!empty($ipItem->unread_count))
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

    {{-- 右側: チャット本体 --}}
    @if ($item)
    <div class="main-chat-area">

        {{-- 商品情報 --}}
        <div class="item-detail-bar">
            <div class="partner-info">
                @if ($isSeller)
                    <span class="role seller">出品者</span>
                @else
                    <span class="role buyer">購入者</span>
                @endif
                <a href="{{ route('user.profile.show', ['user_id' => $partner?->id]) }}" class="partner-name-link">
                    {{ $partner?->name ?? '取引相手' }} さんとの取引
                </a>
            </div>

            <div class="item-info-header">
                <img src="{{ Storage::url($item->img_url) }}" alt="{{ $item->name }}" class="item-header-img">
                <div class="item-text-details">
                    <p class="item-name-header">{{ $item->name }}</p>
                    <p class="item-price-header">¥{{ number_format($item->price) }}</p>
                </div>
            </div>
        </div>

        {{-- メッセージ履歴 --}}
        <div class="chat-history">
            @forelse ($soldItem->messages as $chat)
                {{-- 自分のメッセージ --}}
                @if ($chat->user_id === Auth::id())
                    <div class="message-bubble sender">
                        <div class="message-header">
                            <div class="user-info">
                                <img class="user-icon"
                                    src="{{ $chat->user->profile?->img_url ? Storage::url($chat->user->profile->img_url) : asset('img/icon.png') }}"
                                    alt="プロフィール画像">
                                <span class="user-name">{{ $chat->user->name }}</span>
                            </div>
                            <span class="message-time">{{ $chat->created_at->format('Y/m/d H:i') }}</span>
                        </div>

                        <div class="message-body">
                            <p class="message-text">{{ $chat->message }}</p>
                            @if ($chat->image_url)
                                <img src="{{ Storage::url($chat->image_url) }}" alt="添付画像" class="message-image">
                            @endif
                        </div>

                        {{-- 編集／削除 --}}
                        <div class="message-actions">
                            <a href="#edit-{{ $chat->id }}" class="edit-toggle-btn">編集</a>

                            <form action="{{ route('chat.delete', $chat->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="delete-btn"
                                    onclick="return confirm('本当に削除しますか？');">削除</button>
                            </form>
                        </div>

                        {{-- 編集フォーム (CSS :targetで表示) --}}
                        <div id="edit-{{ $chat->id }}" class="edit-form">
                            <form action="{{ route('chat.update', $chat->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <textarea name="message" rows="2" maxlength="400" required>{{ $chat->message }}</textarea>
                                <div class="edit-controls">
                                    <button type="submit" class="save-btn">保存</button>
                                    <a href="#" class="cancel-btn">キャンセル</a>
                                </div>
                            </form>
                        </div>
                    </div>

                {{-- 相手のメッセージ --}}
                @else
                    <div class="message-bubble receiver">
                        <div class="message-header">
                            <div class="user-info">
                                <img class="user-icon"
                                    src="{{ $chat->user->profile?->img_url ? Storage::url($chat->user->profile->img_url) : asset('img/icon.png') }}"
                                    alt="プロフィール画像">
                                <span class="user-name">{{ $chat->user->name }}</span>
                            </div>
                            <span class="message-time">{{ $chat->created_at->format('Y/m/d H:i') }}</span>
                        </div>

                        <div class="message-body">
                            <p class="message-text">{{ $chat->message }}</p>
                            @if ($chat->image_url)
                                <img src="{{ Storage::url($chat->image_url) }}" alt="添付画像" class="message-image">
                            @endif
                        </div>
                    </div>
                @endif
            @empty
                <p class="no-chats-yet">取引メッセージはまだありません。</p>
            @endforelse
        </div>

        {{-- 入力欄 --}}
        <div class="chat-input-area">
            <form action="{{ route('chat.store', $item->id) }}" method="POST" enctype="multipart/form-data" class="chat-form">
                @csrf
                <textarea name="message" class="message-input" placeholder="メッセージを入力してください" rows="3">{{ old('message') }}</textarea>
                <div class="form-controls">
                    <label for="image_upload" class="image-upload-label">
                        <i class="fas fa-camera"></i> 画像を追加
                        <input type="file" name="image" id="image_upload" accept=".jpeg,.png" style="display:none;">
                    </label>
                    <button type="submit" class="send-button"><i class="fas fa-paper-plane"></i></button>
                </div>
            </form>

            {{-- 取引完了・評価 --}}
            @if (!$soldItem->is_completed)
                @if (!$isSeller)
                    <form action="{{ route('trade.complete', $item->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="complete-trade-button">取引を完了する</button>
                    </form>
                @else
                    <p class="trade-pending-text">購入者の取引完了操作をお待ちください。</p>
                @endif
            @else
                @if (!$isReviewed)
                    <p class="trade-pending-text">評価がまだ完了していません。</p>
                @else
                    <p class="trade-completed-text">この取引は完了しています。</p>
                @endif
            @endif
        </div>

        {{-- ✅ 評価モーダル（自動表示機能あり） --}}
        <div id="complete-modal"
             class="modal-overlay"
             style="@if($showBuyerModal || $shouldShowReviewModal) display:flex; @else display:none; @endif">
            <div class="modal-content">
                <h3>取引完了の確認と評価</h3>
                <p>この取引を完了し、相手を星で評価してください。</p>
                <form action="{{ route('trade.review.submit', $item->id) }}" method="POST">
                    @csrf
                    <div class="rating-area">
                        <input type="radio" id="star5" name="rating" value="5"><label for="star5"></label>
                        <input type="radio" id="star4" name="rating" value="4"><label for="star4"></label>
                        <input type="radio" id="star3" name="rating" value="3" checked><label for="star3"></label>
                        <input type="radio" id="star2" name="rating" value="2"><label for="star2"></label>
                        <input type="radio" id="star1" name="rating" value="1"><label for="star1"></label>
                    </div>
                    <button type="submit" class="submit-btn complete-confirm-btn">送信する</button>
                </form>
            </div>
        </div>
    </div>
    @else
        <div class="main-chat-area">
            <div style="padding: 40px; text-align: center; margin-top: 100px;">
                <p style="font-size: 1.2rem; color: #dc3545; font-weight: bold;">
                    エラー: 指定された商品または取引が見つかりません。
                </p>
            </div>
        </div>
    @endif
</div>

@endsection
