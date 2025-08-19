<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
        
    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = ['exercise_name', 'weight', 'sets', 'phone_number', 'customer_name', 'wam_id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sets' => 'array',
        ];
    }

}
