<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReviewRequest extends FormRequest
{
    /**
     * ログインユーザーのみ許可
     */
    public function authorize()
    {
        return Auth::check();
    }

    public function rules()
    {
        return [
            'rating' => 'nullable|numeric',

            'comment' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'rating.numeric' => '評価は数値で入力してください。',
            'comment.string' => 'コメントは文字で入力してください。',
        ];
    }
}
