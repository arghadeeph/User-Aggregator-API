<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
    ];

    public function detail(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }

    public function location(): HasOne
    {
        return $this->hasOne(Location::class);
    }
}
