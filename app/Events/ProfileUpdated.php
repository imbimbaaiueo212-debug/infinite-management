<?php

namespace App\Events;

use App\Models\Profile;
use App\Models\ProfileHistory;   // ← Tambahkan ini
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProfileUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $profile;

    public function __construct(Profile $profile)
    {
        $this->profile = $profile;

        // 🔥 Simpan history otomatis setiap kali event dipanggil
        $this->saveHistory();
    }

    /**
     * Simpan snapshot history untuk bulan saat ini
     */
    protected function saveHistory(): void
    {
        $periode = Carbon::now()->format('Y-m');   // contoh: 2026-04

        // Cek apakah sudah ada history untuk periode ini
        $exists = ProfileHistory::where('profile_id', $this->profile->id)
                    ->where('periode', $periode)
                    ->exists();

        if ($exists) {
            return; // sudah ada, tidak perlu simpan lagi
        }

        ProfileHistory::create([
            'profile_id'          => $this->profile->id,
            'periode'             => $periode,
            'status_karyawan'     => $this->profile->status_karyawan,
            'tgl_magang'          => $this->profile->tgl_magang,
            'tgl_non_aktif'       => $this->profile->tgl_non_aktif,
            'tgl_resign'          => $this->profile->tgl_resign,
            'tgl_selesai_magang'  => $this->profile->tgl_selesai_magang,
            'tgl_masuk'           => $this->profile->tgl_masuk,
            'jumlah_murid_mba'    => $this->profile->jumlah_murid_mba,
            'jumlah_murid_jadwal' => $this->profile->jumlah_murid_jadwal,
            'jumlah_rombim'       => $this->profile->jumlah_rombim,
            'rb'                  => $this->profile->rb,
            'ktr'                 => $this->profile->ktr,
            'ktr_tambahan'        => $this->profile->ktr_tambahan,
            'rp'                  => $this->profile->rp,
            'masa_kerja'          => $this->profile->masa_kerja,
            'masa_kerja_jabatan'  => $this->profile->masa_kerja_jabatan,
            'data_lengkap'        => $this->profile->toArray(),   // backup semua data
        ]);
    }
}