<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedReply extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->morphTo('user');
    }

    public function order()
    {
        return $this->morphTo('order');
    }

    public static function add_saved_reply($user, $order_id, $title, $message, $image_file)
    {
        $new_saved_reply = new SavedReply();
        $new_saved_reply->user()->associate($user);
        $new_saved_reply->order()->associate($order_id);
        $new_saved_reply->title = $title;
        $new_saved_reply->message = $message;
        $new_saved_reply->image = $image_file;

        $new_saved_reply->save();
    }

}
