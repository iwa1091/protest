<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ChatMessageRequest;

class ChatMessageRequestTest extends TestCase
{
    /**
     * message と image の両方が空 → エラー
     */
    public function test_both_message_and_image_are_empty_should_fail()
    {
        $request = new ChatMessageRequest();
        $request->merge([]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertContains('本文を入力してください', $validator->errors()->all());
    }

    /**
     * message が400文字以内 → 通過
     */
    public function test_valid_message_under_400_chars_should_pass()
    {
        $request = new ChatMessageRequest();
        $request->merge(['message' => str_repeat('あ', 400)]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $this->assertFalse($validator->fails());
    }

    /**
     * message が401文字以上 → エラー
     */
    public function test_message_over_400_chars_should_fail()
    {
        $request = new ChatMessageRequest();
        $request->merge(['message' => str_repeat('あ', 401)]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertContains('本文は400文字以内で入力してください', $validator->errors()->all());
    }

    /**
     * .png画像がアップロードされた場合 → 通過
     */
    public function test_png_image_should_pass()
    {
        $file = UploadedFile::fake()->image('valid.png', 100, 100);

        $request = new ChatMessageRequest();
        $request->merge(['image' => $file]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $this->assertFalse($validator->fails());
    }

    /**
     * .jpeg画像がアップロードされた場合 → 通過
     */
    public function test_jpeg_image_should_pass()
    {
        $file = UploadedFile::fake()->image('valid.jpeg', 100, 100);

        $request = new ChatMessageRequest();
        $request->merge(['image' => $file]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $this->assertFalse($validator->fails());
    }

    /**
     * .gif画像がアップロードされた場合 → エラー
     */
    public function test_gif_image_should_fail()
    {
        $file = UploadedFile::fake()->create('invalid.gif', 100, 'image/gif');

        $request = new ChatMessageRequest();
        $request->merge(['image' => $file]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $this->assertTrue($validator->fails());
        $this->assertContains('「.png」または「.jpeg」形式でアップロードしてください', $validator->errors()->all());
    }
}
