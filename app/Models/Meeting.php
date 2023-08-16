<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = ['subject', 'start_time','end_time' ,'organizer_id' , 'google_event_id'];

    public function attendees()
    {
        return $this->hasMany(Attendee::class);
    }

    public function organiser(){
        return $this->belongsTo(User::class , 'organizer_id');
    }
}
