<?php

namespace App\Exports;

use App\Models\Participant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ParticipantsExport implements FromCollection, WithHeadings
{
    protected $eventType;

    public function __construct($eventType)
    {
        $this->eventType = $eventType;
    }

    public function collection()
    {
        return Participant::where('event_type', $this->eventType)->get([
            'name', 'email', 'whatsapp', 'city', 'business', 'affiliate_id'
        ]);
    }

    public function headings(): array
    {
        return ['Nama', 'Email', 'WhatsApp', 'Kota', 'Bisnis', 'Pengundang'];
    }
}
