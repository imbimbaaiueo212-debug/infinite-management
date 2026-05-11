<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;   // TAMBAHAN

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nim','nama','kelas','tgl_lahir','usia','orangtua','no_telp','alamat','guru_wali',
        'source','murid_trial_id','promoted_at',

        // kolom dari Google Form
        'form_timestamp','email','sumber_pendaftaran','tempat_lahir','jenis_kelamin','agama_murid',
        'kode_pos','no_rumah','rt','rw','kelurahan','kecamatan','kodya_kab','provinsi',
        'nama_ayah','agama_ayah','pekerjaan_ayah','alamat_kantor_ayah','telepon_kantor_ayah','hp_ayah',
        'nama_ibu','agama_ibu','pekerjaan_ibu','alamat_kantor_ibu','telepon_kantor_ibu','hp_ibu',
        'tanggal_masuk','biaya_pendaftaran','spp_bulanan','informasi_bimba','informasi_humas_nama','hari','jam',
        'bimba_unit', 'no_cabang', 'foto_kk', 'foto_mutasi',
    ];

    protected $casts = [
        'form_timestamp'    => 'datetime',
        'tgl_lahir'         => 'date',
        'tanggal_masuk'     => 'date',
        'promoted_at'       => 'datetime',
        'biaya_pendaftaran' => 'decimal:2',
        'spp_bulanan'       => 'decimal:2',
    ];

    protected $appends = ['status_trial_label', 'telepon_utama'];

    // ===================================================================
    // GLOBAL SCOPE: FILTER OTOMATIS BERDASARKAN UNIT USER YANG LOGIN
    // ===================================================================
   protected static function booted()
{
    static::addGlobalScope('unit', function (Builder $builder) {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Admin & Superadmin boleh lihat semua
        if ($user->is_admin ?? false || in_array($user->role ?? '', ['admin', 'superadmin'])) {
            return;
        }

        // User biasa
        if (empty($user->bimba_unit) && empty($user->no_cabang)) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $unitName = trim($user->bimba_unit ?? '');
        $noCabang = trim($user->no_cabang ?? '');

        $builder->where(function ($q) use ($unitName, $noCabang) {
            // Matching utama berdasarkan data user
            if ($unitName) {
                $q->where('bimba_unit', 'LIKE', "%{$unitName}%");
            }
            if ($noCabang) {
                $q->orWhere('no_cabang', $noCabang);
            }

            // Unit khusus yang sudah diketahui
            $q->orWhere('bimba_unit', 'LIKE', '%VILLA BEKASI INDAH 2%')
              ->orWhere('no_cabang', '00340')
              ->orWhere('bimba_unit', 'LIKE', '%GRIYA PESONA MADANI%')
              ->orWhere('no_cabang', '05141')
              ->orWhere('bimba_unit', 'LIKE', '%05141%');
        });
    });
}

    /* =======================
       RELASI
       ======================= */
    public function muridTrial()
    {
        return $this->belongsTo(MuridTrial::class, 'murid_trial_id');
    }

    public function bukuInduk()
    {
        return $this->hasOne(\App\Models\BukuInduk::class, 'nim', 'nim');
    }

    public function histories()
    {
        return $this->hasMany(\App\Models\StudentHistory::class);
    }

    public function mutations()
    {
        return $this->hasMany(\App\Models\Mutation::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class, 'student_id', 'id');
    }

    /* =======================
       SCOPE
       ======================= */
    public function scopeSearch($q, $term)
    {
        $term = trim((string)$term);
        if ($term === '') return $q;

        return $q->where(function ($qq) use ($term) {
            $qq->where('nim', 'like', "%{$term}%")
               ->orWhere('nama', 'like', "%{$term}%")
               ->orWhere('email', 'like', "%{$term}%")
               ->orWhere('no_telp', 'like', "%{$term}%");
        });
    }

    /* =======================
       ACCESSOR
       ======================= */

    // Label status trial untuk tampilan
    public function getStatusTrialLabelAttribute(): string
    {
        if (is_null($this->murid_trial_id)) {
            return 'Tanpa Trial';
        }

        $status = optional($this->muridTrial)->status_trial;

        return match ($status) {
            'aktif'         => 'Trial Aktif',
            'baru'          => 'Trial Baru',
            'lanjut_daftar' => 'Lanjut Daftar',
            'batal'         => 'Trial Batal',
            null            => 'Tidak Diketahui',
            default         => ucwords(str_replace('_', ' ', (string)$status)),
        };
    }

    // Telepon utama (no_telp → hp_ayah → hp_ibu)
    public function getTeleponUtamaAttribute(): ?string
    {
        return $this->no_telp ?: ($this->hp_ayah ?: $this->hp_ibu);
    }

    // Nama petugas trial (prioritas: guru_wali → guru_trial dari trial)
    public function getPetugasTrialAttribute(): ?string
    {
        if ($this->guru_wali) {
            return $this->guru_wali;
        }

        return $this->muridTrial?->guru_trial;
    }

    /**
     * Helper: Daftar guru di unit yang sama (untuk dropdown)
     */
    public function unitGuru()
    {
        return Profile::where('bimba_unit', $this->bimba_unit)
            ->whereIn('jabatan', ['Guru', 'Pengajar', 'Guru Trial', 'Tutor'])
            ->orderBy('nama')
            ->pluck('nama', 'id');
    }

    /**
     * Accessor: Format biaya pendaftaran & SPP jadi Rupiah
     */
    public function getBiayaPendaftaranRpAttribute(): string
    {
        return 'Rp ' . number_format($this->biaya_pendaftaran ?? 0, 0, ',', '.');
    }

    public function getSppBulananRpAttribute(): string
    {
        return 'Rp ' . number_format($this->spp_bulanan ?? 0, 0, ',', '.');
    }
}   