<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PoliticalParty extends Model
{
    protected $table = 'political_parties';
    protected $primaryKey = 'party_id';
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'name',
        'abbreviation',
        'country_id',
        'founded_year',
        'ideology',
        'headquarters',
    ];

    // Relationship: PoliticalParty belongs to Country
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}