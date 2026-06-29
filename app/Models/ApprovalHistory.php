<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalHistory extends Model
{
    protected $guarded = ['id'];

    public function request()
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function actedBy()
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
