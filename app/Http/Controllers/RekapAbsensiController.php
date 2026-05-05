<?php

namespace App\Http\Controllers;

use App\Models\RekapAbsensi;
use App\Models\Profile;
use App\Models\BukuInduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekapAbsensiController extends Controller
{
    /**
     * Halaman Utama Rekap Jadwal & Absensi
     */
    public function index()
    {
        // Sinkronisasi lengkap setiap kali halaman dibuka
        $this->syncGuruAktif();
        $this->hitungJadwalDariBukuInduk();

        $rekap = RekapAbsensi::orderBy('bimba_unit')
                            ->orderBy('no_cabang')
                            ->orderBy('nik', 'asc')
                            ->get();

        return view('rekap.index', compact('rekap'));
    }

    /**
     * Sinkronisasi Guru Aktif + jumlah_rombim dari Profile
     */
    private function syncGuruAktif()
{
    $guruProfiles = Profile::where('jabatan', 'Guru')
        ->where('status_karyawan', 'Aktif')
        ->get();

    foreach ($guruProfiles as $guru) {

        $nama = trim(strtoupper($guru->nama));

        RekapAbsensi::updateOrCreate(
            [
                'nama_relawan' => $nama,
                'bimba_unit'   => $guru->bimba_unit,
                'no_cabang'    => $guru->no_cabang,
            ],
            [
                'nik'            => $guru->nik ?? null,
                'jabatan'        => $guru->jabatan,
                'departemen'     => $guru->departemen ?? null,
                'penyesuaian_rb' => 0,
                'jumlah_rombim'  => $guru->jumlah_rombim ?? 0,
            ]
        );
    }
}

    /**
     * Hitung Jadwal & Jumlah Murid dari Buku Induk
     * (jumlah_rombim tidak direset)
     */
   private function hitungJadwalDariBukuInduk()
{
    // Reset hanya kolom perhitungan
    RekapAbsensi::query()->update([
        'srj_108' => 0, 'srj_109' => 0, 'srj_110' => 0, 'srj_111' => 0,
        'srj_112' => 0, 'srj_113' => 0, 'srj_114' => 0, 'srj_115' => 0,
        'sks_208' => 0, 'sks_209' => 0, 'sks_210' => 0, 'sks_211' => 0,
        's6_308'  => 0, 's6_309'  => 0, 's6_310'  => 0, 's6_311'  => 0,
        'jumlah_murid' => 0,
    ]);

    $data = BukuInduk::whereNotNull('kode_jadwal')
        ->whereNotNull('guru')
        ->whereRaw("LOWER(TRIM(status)) != 'keluar'") // 🔥 KUNCI UTAMA
        ->select(
            'guru',
            'bimba_unit',
            'no_cabang',
            'kode_jadwal',
            DB::raw('COUNT(*) as total_murid')
        )
        ->groupBy('guru', 'bimba_unit', 'no_cabang', 'kode_jadwal')
        ->get();

    foreach ($data as $item) {
        $namaGuru = trim(strtoupper($item->guru));

        $rekap = RekapAbsensi::whereRaw('UPPER(TRIM(nama_relawan)) = ?', [$namaGuru])
            ->where('bimba_unit', $item->bimba_unit)
            ->where('no_cabang', $item->no_cabang)
            ->first();

        if ($rekap && $item->total_murid > 0) {
            $kolom = null;
            $kode  = $item->kode_jadwal;

            if (in_array($kode, [108,109,110,111,112,113,114,115,116])) {
                $kolom = 'srj_' . $kode;
            } elseif (in_array($kode, [206,207,208,209,210,211])) {
                $kolom = 'sks_' . $kode;
            } elseif (in_array($kode, [306,307,308,309,310,311])) {
                $kolom = 's6_' . $kode;
            }

            if ($kolom) {
                $rekap->increment($kolom, $item->total_murid);
                $rekap->increment('jumlah_murid', $item->total_murid);
            }
        }
    }
}

    /**
     * Tombol "Sinkron Data" di halaman Rekap
     */
    public function updateKodeJadwal()
    {
        $this->syncGuruAktif();
        $this->hitungJadwalDariBukuInduk();

        return redirect()->route('rekap.index')
            ->with('success', '✅ Sinkronisasi berhasil! Jumlah Rombim sudah diupdate dari Profile.');
    }

    // ====================== CRUD ======================

    public function create()
    {
        $guruProfiles = Profile::where('jabatan', 'Guru')
            ->where('status_karyawan', 'Aktif')
            ->get();

        foreach ($guruProfiles as $guru) {
            RekapAbsensi::updateOrCreate(
                ['nama_relawan' => $guru->nama],
                [
                    'nik'            => $guru->nik ?? null,
                    'jabatan'        => $guru->jabatan,
                    'departemen'     => $guru->departemen ?? null,
                    'bimba_unit'     => $guru->bimba_unit ?? null,
                    'no_cabang'      => $guru->no_cabang ?? null,
                    'penyesuaian_rb' => 0,
                    'jumlah_rombim'  => $guru->jumlah_rombim ?? 0,
                ]
            );
        }

        return redirect()->route('rekap.index')
            ->with('success', 'Rekap guru aktif berhasil dibuat/diupdate.');
    }

    public function store(Request $request)
    {
        $guru = Profile::where('nama', $request->nama_relawan)->first();

        RekapAbsensi::create([
            'nama_relawan'   => $request->nama_relawan,
            'nik'            => $guru->nik ?? null,
            'jabatan'        => $request->jabatan ?? $guru->jabatan ?? 'Guru',
            'departemen'     => $request->departemen ?? $guru->departemen ?? null,
            'bimba_unit'     => $guru->bimba_unit ?? null,
            'no_cabang'      => $guru->no_cabang ?? null,
            'penyesuaian_rb' => $request->penyesuaian_rb ?? 0,
            'jumlah_rombim'  => $guru->jumlah_rombim ?? 0,
        ]);

        return redirect()->route('rekap.index')
            ->with('success', 'Data rekap berhasil ditambahkan.');
    }

    public function edit(RekapAbsensi $rekap)
    {
        return view('rekap.edit', compact('rekap'));
    }

    public function update(Request $request, RekapAbsensi $rekap)
    {
        $guru = Profile::where('nama', $rekap->nama_relawan)->first();

        $rekap->update([
            'nik'            => $guru->nik ?? $rekap->nik,
            'jabatan'        => $request->jabatan ?? $rekap->jabatan,
            'departemen'     => $request->departemen ?? $rekap->departemen,
            'bimba_unit'     => $guru->bimba_unit ?? $rekap->bimba_unit,
            'no_cabang'      => $guru->no_cabang ?? $rekap->no_cabang,
            'penyesuaian_rb' => $request->penyesuaian_rb ?? $rekap->penyesuaian_rb,
            'jumlah_rombim'  => $guru->jumlah_rombim ?? 0,
        ]);

        return redirect()->route('rekap.index')
            ->with('success', 'Data rekap berhasil diperbarui.');
    }

    public function destroy(RekapAbsensi $rekap)
    {
        $rekap->delete();
        return redirect()->route('rekap.index')
            ->with('success', 'Data rekap berhasil dihapus.');
    }
}