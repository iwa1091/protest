<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ChatMessageRequest extends FormRequest
{
    /**
     * 認証ユーザーのみがリクエストを許可されるように設定
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }

    /**
     * バリデーションルールを設定
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // FN007: 本文(最大400文字)と画像(jpeg/png)のルールを定義。
        return [
            // 'message' または 'image' のどちらか一方は必須。
            'message' => 'nullable|string|max:400', 
            'image' => 'nullable|file|mimes:jpeg,png',
        ];
    }
    
    /**
     * バリデーション前のフック
     * messageとimageのどちらかが入力されていることを確認するカスタムバリデーションロジックを追加
     */
    protected function prepareForValidation()
    {
        // messageが空文字列の場合、nullに変換してバリデーションを容易にする
        if (empty($this->message)) {
            $this->merge(['message' => null]);
        }
    }
    
    /**
     * カスタムバリデーションルール
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // messageとimageの両方が空の場合はエラーとする
            if (empty($this->message) && empty($this->image)) {
                $validator->errors()->add('message', '本文または画像をアップロードしてください。');
            }
        });
    }

    /**
     * FN008: エラーメッセージの定義
     *
     * @return array
     */
    public function messages()
    {
        return [
            // 1. 本文が未入力の場合（カスタムバリデーションで処理）
            // 2. 画像が.pngまたは.jpeg形式以外の場合
            'image.mimes' => '「.png」または「.jpeg」形式でアップロードしてください',
            // 3. 本文が401文字以上の場合
            'message.max' => '本文は400文字以内で入力してください',
        ];
    }
}
