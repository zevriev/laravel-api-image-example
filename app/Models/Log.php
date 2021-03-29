<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    public static function error($code, $message) {
        $log = new Log();
        $log->code = $code;
        $log->$message = $message;
        $log->save();
    }
}
