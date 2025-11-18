<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'question',
        'answer',
        'sort_order'
    ];

    /**
     * Get the product this FAQ belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
