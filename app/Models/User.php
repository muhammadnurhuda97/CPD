<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Membership;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'whatsapp',
        'email',
        'password',
        'affiliate_id',
        'date_of_birth',
        'gender',
        'photo',
        'gender',
        'address',
        'city',
        'zip',
        'country',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relasi ke tabel memberships
     * Seorang pengguna dapat memiliki banyak membership.
     */
    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }
    public function isProfileComplete()
    {
        return $this->address && $this->city && $this->zip && $this->whatsapp && $this->dob;
    }
}
