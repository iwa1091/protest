<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\Item;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    public function create($item_id, CommentRequest $request)
    {
        $comment = new Comment();
        $comment->user_id = Auth::id();
        $comment->item_id = $item_id;
        $comment->comment = $request->comment;
        $comment->save();

        $commentJson = [
            'user_profile' => Storage::url($comment->user->profile->img_url),
            'comment' => $comment->comment,
            'user_name' => $comment->user->name,
        ];

        $item = Item::find($item_id);
        $commentCounts = $item->getComments()->count();

        return response()->json([
            'success' => true,
            'comment' => $commentJson,
            'count' => $commentCounts,
        ]);
    }
}
