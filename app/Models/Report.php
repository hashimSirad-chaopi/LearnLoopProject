<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['title', 'reported_by', 'reported_user_id', 'listing_id', 'reason', 'status'];

    public function reporter() { return $this->belongsTo(User::class, 'reported_by'); }
    public function reportedUser() { return $this->belongsTo(User::class, 'reported_user_id'); }
    public function listing() { return $this->belongsTo(Listing::class); }
}
