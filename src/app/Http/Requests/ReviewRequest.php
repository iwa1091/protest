<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReviewRequest extends FormRequest
{
    /**
     * 認証ユーザーのみがリクエストを許可されるように設定
     *
     * @return bool
     */
    public function authorize()
    {
        // ログインユーザーのみ評価を送信可能
        return Auth::check();
    }

    /**
     * バリデーションルールを設定 (FN013のルールに基づく)
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // 必須であり、1から5の整数であること
            'rating' => 'required|integer|min:1|max:5', 
            
            // 任意であり、最大500文字の文字列であること
            'comment' => 'nullable|string|max:500',
        ];
    }
    
    /**
     * エラーメッセージの定義
     *
     * @return array
     */
    public function messages()
    {
        return [
            'rating.required' => '評価（星の数）を選択してください。',
            'rating.integer' => '評価は整数である必要があります。',
            'rating.min' => '評価は1以上である必要があります。',
            'rating.max' => '評価は5以下である必要があります。',
            'comment.max' => 'コメントは500文字以内で入力してください。',
        ];
    }
}
