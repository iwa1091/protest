@extends('layouts.default')

@section('title','マイページ')

@section('css')
<link rel="stylesheet" href="{{ asset('/css/index.css') }}">
<link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')

@include('components.header')

<div class="container">
    {{-- ユーザー情報 --}}
    <div class="user">
        <div class="user__info">
            <div class="user__img">
                @if ($user->profile && $user->profile->img_url)
                    <img class="user__icon" src="{{ Storage::url($user->profile->img_url) }}" alt="プロフィール画像">
                @else
                    <img class="user__icon" src="{{ asset('img/icon.png') }}" alt="デフォルト画像">
                @endif
            </div>

            <div class="user-details-group">
                <p class="user__name">{{ $user->name }}</p>

                {{-- 出品者評価 --}}
                <div class="rating-box">
                    <h3>出品者評価</h3>
                    @if ($averageRating !== null)
                        <div class="rating-score">
                            @php
                                $fullStars = floor($averageRating);
                                $hasHalfStar = ($averageRating - $fullStars) >= 0.1;
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
            <a class="btn2" href="{{ route('user.profile') }}">プロフィールを編集</a>
        </div>
    </div>

    {{-- タブ --}}
    <div class="border">
        <ul class="border__list">
            <li>
                <a href="{{ route('user.mypage', ['page' => 'sell']) }}" 
                   class="{{ (request()->query('page') === 'sell' || !request()->query('page')) ? 'active-tab' : '' }}">
                    出品した商品
                </a>
            </li>
            <li>
                <a href="{{ route('user.mypage', ['page' => 'buy']) }}" 
                   class="{{ request()->query('page') === 'buy' ? 'active-tab' : '' }}">
                    購入した商品
                </a>
            </li>
            <li class="tab-in-progress">
                <a href="{{ route('user.mypage', ['page' => 'in-progress']) }}" 
                   class="{{ request()->query('page') === 'in-progress' ? 'active-tab' : '' }}">
                    取引中の商品
                </a>
                {{-- ✅ 未読メッセージ総数バッジ --}}
                @if (!empty($totalUnread) && $totalUnread > 0)
                    <span class="unread-badge-tab">{{ $totalUnread }}</span>
                @endif
            </li>
        </ul>
    </div>

    {{-- アイテム一覧 --}}
    <div class="items">
        {{-- 取引中（SoldItemベース） --}}
        @if (request()->query('page') === 'in-progress')
            @forelse ($inProgressItems as $soldItem)
                <div class="item">
                    <a href="{{ route('chat.show', ['item_id' => $soldItem->item->id]) }}">
                        <div class="item__img--container">
                            {{-- ✅ 商品ごとの未読数バッジ --}}
                            @if ($soldItem->unread_count > 0)
                                <span class="unread-badge-item">{{ $soldItem->unread_count }}</span>
                            @endif
                            <img src="{{ Storage::url($soldItem->item->img_url) }}" 
                                 class="item__img" alt="商品画像">
                        </div>
                        <p class="item__name">{{ $soldItem->item->name }}</p>
                        <p class="item__price">¥{{ number_format($soldItem->item->price) }}</p>
                    </a>
                </div>
            @empty
                <p class="no-items">現在、取引中の商品はありません。</p>
            @endforelse

        {{-- 出品/購入（Itemベース） --}}
        @else
            @forelse ($items as $item)
                <div class="item">
                    <a href="{{ route('item.detail', ['item' => $item->id]) }}">
                        <div class="item__img--container @if ($item->sold()) sold @endif">
                            <img src="{{ Storage::url($item->img_url) }}" class="item__img" alt="商品画像">
                        </div>
                        <p class="item__name">{{ $item->name }}</p>
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
