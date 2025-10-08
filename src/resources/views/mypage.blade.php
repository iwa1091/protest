@extends('layouts.default')

<!-- タイトル -->

@section('title','マイページ')

<!-- css読み込み -->

@section('css')
{{-- index.css は既存のまま残します --}}

<link rel="stylesheet" href="{{ asset('/css/index.css') }}" >
<link rel="stylesheet" href="{{ asset('css/mypage.css') }}" >
@endsection

<!-- 本体 -->

@section('content')

@include('components.header')

<div class="container">
<div class="user">
<div class="user__info">
<div class="user__img">
@if ($user->profile && $user->profile->img_url)
{{-- 画像が存在する場合、Storageから取得 --}}
<img class="user__icon" src="{{ Storage::url($user->profile->img_url) }}" alt="プロフィール画像">
@else
{{-- 画像がない場合、デフォルト画像を使用 --}}
{{-- asset('img/icon.png') がデフォルト画像と仮定 --}}
<img class="user__icon" src="{{ asset('img/icon.png') }}" alt="デフォルト画像">
@endif
</div>

        <div class="user-details-group">
            <p class="user__name">{{$user->name}}</p>

            {{-- FN005: 評価平均の表示 --}}
            <div class="rating-box">
                <h3>出品者評価</h3>
                @if ($averageRating !== null)
                    <div class="rating-score">
                        {{-- 評価を星で表示 (例: 4.0 => ★★★★☆) --}}
                        @php
                            $fullStars = floor($averageRating);
                            $hasHalfStar = ($averageRating - $fullStars) >= 0.1; // 0.1以上で半星と見なす
                        @endphp

                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= $fullStars)
                                <span class="star filled">★</span>
                            @elseif ($i == $fullStars + 1 && $hasHalfStar)
                                <span class="star half">★</span>
                            @else
                                <span class="star empty">★</span>
                            @endif
                        @endfor
                        <span class="score-text">({{ number_format($averageRating, 1) }})</span>
                    </div>
                @else
                    <p class="no-rating">まだ評価がありません。</p>
                @endif
            </div>

        </div>
    </div>
    <div class="mypage__user--btn">
        {{-- ルート名を指定してプロフィール編集ページへ遷移 --}}
        <a class="btn2" href="{{ route('user.profile') }}">プロフィールを編集</a>
    </div>
</div>

{{-- 出品/購入/取引中の切り替えタブ --}}

<div class="border">
    <ul class="border__list">
        {{-- 'sell' タブ (デフォルト) --}}
        <li>
            <a href="{{ route('user.mypage', ['page' => 'sell']) }}" class="{{ (request()->query('page') === 'sell' || !request()->query('page')) ? 'active-tab' : '' }}">
                出品した商品
            </a>
        </li>
        {{-- 'buy' タブ --}}
        <li>
            <a href="{{ route('user.mypage', ['page' => 'buy']) }}" class="{{ request()->query('page') === 'buy' ? 'active-tab' : '' }}">
                購入した商品
            </a>
        </li>
        {{-- 【FN001対応】'in-progress' タブ (取引中の商品) を追加 --}}
        <li>
            <a href="{{ route('user.mypage', ['page' => 'in-progress']) }}" class="{{ request()->query('page') === 'in-progress' ? 'active-tab' : '' }}">
                取引中の商品
            </a>
        </li>
    </ul>
</div>

{{-- アイテム一覧 --}}

<div class="items">
    {{-- 【FN001, FN005対応】取引中の商品リストの表示 --}}
    @if (request()->query('page') === 'in-progress')
        {{-- $inProgressItems はコントローラーから渡される取引中の商品コレクションを想定 --}}
        @forelse ($inProgressItems as $item)
            <div class="item">
                {{-- FN005: 取引チャット遷移機能 --}}
                <a href="{{ route('chat.show', ['item_id' => $item->id]) }}">
                    {{-- 新規メッセージ通知 (FN005: 取引商品新規通知確認機能) --}}
                    @if ($item->unread_count > 0)
                        <div class="unread-notification">{{ $item->unread_count }}</div>
                    @endif

                    <div class="item__img--container">
                        <img src="{{ Storage::url($item->img_url) }}" class="item__img" alt="商品画像">
                    </div>
                    <p class="item__name">{{ $item->name }}</p>
                    <p class="item__price">¥{{ number_format($item->price) }}</p>
                </a>
            </div>
        @empty
            <p class="no-items">現在、取引中の商品はありません。</p>
        @endforelse

    {{-- 出品/購入した商品リストの表示 (既存ロジック) --}}
    @else
        @forelse ($items as $item)
            <div class="item">
                {{-- アイテム詳細ページへのリンク --}}
                <a href="{{ route('item.detail', ['item' => $item->id]) }}">
                    {{-- 商品画像コンテナ --}}
                    <div class="item__img--container @if ($item->sold()) sold @endif">
                        {{-- `$item` が Item モデルのインスタンスであることを前提とする --}}
                        <img src="{{ Storage::url($item->img_url) }}" class="item__img" alt="商品画像">
                    </div>
                    <p class="item__name">{{$item->name}}</p>
                    <p class="item__price">¥{{ number_format($item->price) }}</p>
                </a>
            </div>
        @empty
            <p class="no-items">
                @if(request()->query('page') === 'buy')
                    購入履歴はありません。
                @else
                    出品中の商品はありません。
                @endif
            </p>
        @endforelse
    @endif

</div>

</div>
@endsection