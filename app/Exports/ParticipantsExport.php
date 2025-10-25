<?php

namespace App\Exports;

use App\Models\Participant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\User; // Import model User

class ParticipantsExport implements FromCollection, WithHeadings
{
    protected $eventType;
    protected ?string $search; // Tipe diubah
    protected ?string $city;   // Tipe diubah
    protected ?User $user;     // Tipe diubah

    // ===== AWAL PERUBAHAN (KONSTRUKTOR DIPERBAIKI) =====
    public function __construct(
        $eventType,
        ?string $search = null,
        ?string $city = null,
        ?User $user = null
    ) {
        $this->eventType = $eventType;
        $this->search = $search;
        $this->city = $city;
        $this->user = $user;
    }
    // ===== AKHIR PERUBAHAN =====

    public function collection()
    {
        $query = Participant::with('affiliateUser')->where('event_type', $this->eventType);

        // Jika $user ada (bukan admin), filter berdasarkan affiliate_id
        if ($this->user) {
            $query->where(function ($q) {
                $q->where('affiliate_id', $this->user->id)
                    ->orWhere('affiliate_id', $this->user->username);
            });
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('whatsapp', 'like', "%{$this->search}%")
                    ->orWhere('affiliate_id', 'like', "%{$this->search}%")
                    ->orWhereHas('affiliateUser', function ($q2) {
                        $q2->where('username', 'like', "%{$this->search}%");
                    })
                    ->orWhere('city', 'like', "%{$this->search}%");
            });
        }

        if ($this->city) {
            $query->where('city', 'like', "%{$this->city}%");
        }

        // Mengubah collection untuk menampilkan username pengundang
        return $query->get()->map(function ($participant) {
            return [
                'name' => $participant->name,
                'email' => $participant->email,
                'whatsapp' => $participant->whatsapp,
                'city' => $participant->city,
                'business' => $participant->business,
                // Mengambil username dari relasi affiliateUser
                'affiliate_username' => $participant->affiliateUser->username ?? $participant->affiliate_id,
            ];
        });
    }

    public function headings(): array
    {
        // Mengubah heading untuk kolom pengundang
        return ['Nama', 'Email', 'WhatsApp', 'Kota', 'Bisnis', 'Pengundang'];
    }
}
