<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Vartotojai extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vartotojai';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'naudotojo_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'naudotojo_id',
        'ms_id',
        'vardas',
        'pavarde',
        'el_pastas',
        'role',
        'pedagoginis_vardas',
        'lytis',
        'prisijungimo_statusas',
        'paskutinis_prisijungimas',
    ];

        /**
     * The attributes that should be cast.
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];


    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'naudotojo_id' => 'integer',
            'ms_id' => 'string',
            'vardas' => 'string',
            'pavarde' => 'string',
            'el_pastas' => 'string',
            'role' => 'string',
            'pedagoginis_vardas' => 'string',
            'lytis' => 'boolean',
            'prisijungimo_statusas' => 'boolean',
            'paskutinis_prisijungimas' => 'date',
        ];
    }
}

