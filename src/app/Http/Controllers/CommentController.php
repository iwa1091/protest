<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CommentRequest;
use App\Models\Comment;

class CommentController extends Controller
{
    public function create($item_id, CommentRequest $request)
    {
        $comment = new Comment();
        $comment->user_id = Auth::id();
        $comment->item_id = $item_id;
        $comment->comment = $request->comment;
        $comment->save();

        return back()->with('flashSuccess', 'コメントを送信しました！');;
    }
}
