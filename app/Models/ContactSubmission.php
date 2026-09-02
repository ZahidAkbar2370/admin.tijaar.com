<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    protected $fillable = ['name', 'email', 'subject', 'message', 'status', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
