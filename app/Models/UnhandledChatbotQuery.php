<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnhandledChatbotQuery extends Model
{
    protected $fillable = ['user_message', 'ip_address'];
}