<?php

namespace SellerAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class SellerUser extends Model
{
    protected $table = 'seller_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'store_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
