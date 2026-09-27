<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductHppDetail extends Model
{
    use HasFactory;

    protected $table = 'product_hpp_detail';

    protected $fillable = [
        'product_hpp_id',
        'hpp_id',
    ];

    protected function casts(): array
    {
        return [
            'product_hpp_id' => 'integer',
            'hpp_id'         => 'integer',
        ];
    }

    /**
     * Relasi ke ProductHpp header.
     */
    public function productHpp()
    {
        return $this->belongsTo(ProductHpp::class, 'product_hpp_id');
    }

    /**
     * Relasi ke Master HPP.
     */
    public function hpp()
    {
        return $this->belongsTo(Hpp::class, 'hpp_id');
    }
}