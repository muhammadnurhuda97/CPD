<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Membership extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'membership_type',
        'commission_rate',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime', // Menggunakan 'datetime' untuk casting otomatis ke Carbon instance
        'end_date' => 'datetime',   // Menggunakan 'datetime' untuk casting otomatis ke Carbon instance
    ];

    /**
     * Set the commission rate based on membership type.
     */
    public function setCommissionRateAttribute($value = null)
    {
        // Jika nilai sudah disediakan, gunakan itu. Jika tidak, tentukan berdasarkan tipe.
        if (!is_null($value)) {
            $this->attributes['commission_rate'] = $value;
            return;
        }

        switch ($this->membership_type) {
            case 'premium':
                $this->attributes['commission_rate'] = 40.00;
                break;
            case 'ultimate':
                $this->attributes['commission_rate'] = 50.00;
                break;
            default: // basic
                $this->attributes['commission_rate'] = 30.00;
                break;
        }
    }

    /**
     * Set the end_date based on start_date (default 1 year).
     * This mutator is called when setting the 'end_date' attribute.
     */
    public function setEndDateAttribute($value = null)
    {
        if (!is_null($value)) {
            $this->attributes['end_date'] = $value;
            return;
        }

        if ($this->start_date && $this->start_date instanceof Carbon) {
            $this->attributes['end_date'] = $this->start_date->addYear();
        } elseif ($this->start_date) {
            $this->attributes['end_date'] = Carbon::parse($this->start_date)->addYear();
        } else {
            // Jika start_date belum ada saat ini, bisa diatur nanti atau default
            // Atau tangani error/default value sesuai kebutuhan aplikasi Anda
        }
    }

    /**
     * The "booting" method of the model.
     * Ensures commission_rate and end_date are set before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($membership) {
            // Set default start_date jika belum ada
            if (is_null($membership->start_date)) {
                $membership->start_date = now();
            }
            // Panggil mutator untuk mengatur commission_rate dan end_date otomatis
            $membership->setCommissionRateAttribute();
            $membership->setEndDateAttribute();
        });

        static::updating(function ($membership) {
            // Panggil mutator juga saat update jika ada perubahan type atau jika ingin diperbarui
            // Hanya panggil jika atribut terkait berubah atau jika kita ingin selalu otomatis
            if ($membership->isDirty('membership_type') || is_null($membership->commission_rate)) {
                $membership->setCommissionRateAttribute();
            }
            // Anda bisa menambahkan logika serupa untuk end_date jika diperlukan saat update
        });
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
