@extends('layouts.default')

@section('title', $user->name . 'のプロフィール')

@section('css')

<link rel="stylesheet" href="{{ asset('css/user-profile.css') }}">
{{-- Font Awesomeのアイコンが使用されているため、layouts/default.blade.phpで読み込まれていることを確認しています --}}
@endsection

@section('content')
{{-- ヘッダーコンポーネントを読み込む --}}
@include('components.header')

<div class="profile-container">
<div class="profile-header">
<h2 class="profile-name">{{ $user->name }}のプロフィール</h2>
</div>

<div class="profile-info-area">
    <div class="profile-img-box">
        @if ($user->profile && $user->profile->img_url)
            {{-- 画像が存在する場合、Storageから取得 --}}
            <img src="{{ Storage::url($user->profile->img_url) }}" alt="プロフィール画像" class="profile-img">
        @else
            {{-- 画像がない場合、デフォルト画像を使用 --}}
            <img src="{{ asset('img/default_user.png') }}" alt="デフォルト画像" class="profile-img">
        @endif
    </div>

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
                        {{-- 半星を表現するために、font-awesomeなどのアイコンクラスを使用するか、見た目をCSSで工夫する必要がありますが、今回はシンプルに「★」で表現します --}}
                        <span class="star half">★</span>
                    @else
                        <span class="star empty">★</span>
                    @endif
                @endfor
                <span class="score-text">({{ $averageRating }})</span>
            </div>
        @else
            <p class="no-rating">まだ評価がありません。</p>
        @endif
    </div>
</div>

{{-- 出品アイテム一覧 --}}
<div class="item-list-container">
    <h3 class="list-title">{{ $user->name }}の出品アイテム</h3>
    <div class="item-list">
        @forelse ($items as $item)
            {{-- アイテム詳細ページへのリンク --}}
            <a href="{{ route('item.detail', ['item' => $item->id]) }}" class="item-card">
                <div class="item-image-box">
                    <img src="{{ $item->img_url }}" alt="{{ $item->name }}" class="item-image">
                    <div class="item-like-count">
                         {{-- いいね数を表示するためにFont Awesomeを使用 --}}
                        <i class="fas fa-heart"></i> {{ $item->likes->count() ?? 0 }}
                    </div>
                </div>
                <div class="item-price">¥{{ number_format($item->price) }}</div>
            </a>
        @empty
            <p class="no-items">{{ $user->name }}は現在出品中の商品がありません。</p>
        @endforelse
    </div>
</div>

</div>

@endsection