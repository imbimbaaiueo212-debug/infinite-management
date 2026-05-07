<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use App\Models\BukuInduk;
use App\Models\BukuIndukBeasiswaHistory;
use App\Models\HargaSaptataruna;
use App\Models\Profile;
use App\Models\LevelHistory;
use App\Models\Paket72History;
use App\Models\GaransiBCA;
use App\Models\Unit;
use App\Models\PengajuanGaransi;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BukuIndukImport;
use App\Exports\BukuIndukExport;
use App\Models\BukuIndukHistory;
use App\Services\GoogleFormService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Tambahan untuk ambil data units
use Illuminate\Support\Facades\Auth; // Tambahan untuk cek user login

class BukuIndukController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;

        BukuInduk::where('status', 'Baru')
        ->whereNotNull('tgl_masuk')
        ->whereDate('tgl_masuk', '<=', now()->subDays(30))
        ->update([
            'status' => 'Aktif'
        ]);

        // ========================================================
        // PAGINATION & QUERY
        // ========================================================
        $perPage = $request->input('perPage', 50);

        $query = BukuInduk::query()
            ->orderByRaw("
            CASE 
                WHEN status = 'Aktif' THEN 0
                WHEN status = 'Baru' THEN 1
                ELSE 2
            END
        ")
            ->orderBy('nim', 'asc');

        // ========================================================
        // DROPDOWN OPTIONS — KEMBALI KE BENTUK COLLECTION (AMAN UNTUK BLADE LAMA)
        // ========================================================
        $muridOptionsQuery = BukuInduk::orderBy('nim', 'asc');

if ($request->filled('unit')) {
    $muridOptionsQuery->where('bimba_unit', $request->unit);
}

$muridOptions = $muridOptionsQuery->get(['nim', 'nama']);

        $unitOptions = DB::table('units')
            ->whereNotNull('bimba_unit')
            ->where('bimba_unit', '!=', '')
            ->distinct()
            ->orderBy('bimba_unit')
            ->pluck('bimba_unit');

        // ========================================================
        // FILTERS
        // ========================================================
        if ($request->filled('murid')) {
            $query->where('nim', $request->murid);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'baru') {
                $query->where('status', 'baru');
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tgl_masuk', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('unit')) {
            $query->where('bimba_unit', $request->unit);
        }

        // ========================================================
        // HITUNG TOTAL
        // ========================================================
        $filteredQuery = clone $query;
        $totalBaru = (clone $filteredQuery)->where('status', 'Baru')->count();
        $totalAktif = (clone $filteredQuery)->where('status', 'Aktif')->count();
        $totalKeluar = (clone $filteredQuery)->where('status', 'Keluar')->count();

        // ========================================================
        // PAGINATE & RETURN
        // ========================================================
        $bukuInduk = $query->paginate($perPage)->appends($request->all());

        // Tambahkan info jadwal untuk setiap murid
    foreach ($bukuInduk as $item) {
        $item->info_jadwal = $this->hitungPertemuanTerlewatDiBulanMasuk($item);
    }
        return view('buku_induk.index', compact(
            'bukuInduk',
            'perPage',
            'totalBaru',
            'totalAktif',
            'totalKeluar',
            'muridOptions',
            'unitOptions'
        ));
    }

    public function create()
{
    $HargaSaptataruna = HargaSaptataruna::all();
    $profil = Profile::where('jabatan', '!=', 'Kepala Unit')->get();

    // ==================== FILTER GOL (INI YANG KAMU BUTUHKAN) ====================
    $excluded = [
    'S1_MB', 'S1_MU', 'S3_MB', 'S3_MU',
    'KA01', 'RBAS', 'TAS', 'STPB', 'STF', 'KPK', 'KA'
];

$golOptions = $HargaSaptataruna
    ->filter(function ($item) use ($excluded) {

        $kode = strtoupper(trim($item->kode));

        return (
            // ambil hanya S, P, K
            (str_starts_with($kode, 'S') ||
             str_starts_with($kode, 'P') ||
             str_starts_with($kode, 'K') ||
             str_starts_with($kode, 'D'))

            // kecuali yang di blacklist
            && !in_array($kode, $excluded)
        );
    })
    ->unique('kode')
    ->values();
    // ============================================================================

    // KD options
    $firstRow = $HargaSaptataruna->first();
    $kdOptions = [];

    if ($firstRow) {
        foreach ($firstRow->getAttributes() as $key => $value) {
            if (in_array($key, ['a', 'b', 'c', 'd', 'e', 'f'])) {
                $kdOptions[] = strtoupper($key);
            }
        }
    }

    // SPP Mapping
    $sppMapping = [];
    foreach ($HargaSaptataruna as $item) {
        foreach ($kdOptions as $kd) {
            $columnName = strtolower($kd);
            $sppMapping[$item->kode][$kd] = $item->$columnName ?? 0;
        }
    }

    // Unit
    $units = Unit::orderBy('bimba_unit')
        ->pluck('no_cabang', 'bimba_unit')
        ->toArray();

    $unitsJson = json_encode($units);

    // User
    $user = Auth::user();
    $isAdmin = $user->is_admin ?? false;

    $userUnit = null;
    $userNoCabang = null;

    if (!$isAdmin) {
        $userUnit = $user->bimba_unit;
        if ($userUnit) {
            $userNoCabang = Unit::where('bimba_unit', $userUnit)->value('no_cabang');
        }
    }

    // Options
    $tahapanOptions = ['Persiapan', 'Lanjutan'];
    $kategoriKeluarOptions = ['Belum bayar SPP', 'Belum kondusif', 'Ganti Golongan', 'Masuk SD', 'Masuk TK', 'Sudah SD', 'Sudah TK', 'Perpanjang Bea', 'Pindah biMBA', 'Pindah rumah', 'Sakit/rehat', 'Tdk ada yg antar', 'Tidak ada kabar', 'Lain-lain', 'Order Sertifikat', 'Order STPB', 'Order Sertifikat & STPB'];
    $kelasOptions = ['biMBA-AIUEO', 'English biMBA'];
    $noteOptions = ['Aktif Kembali', 'Cuti', 'Ganti Gol', 'Pindahan', 'Belum Bayar SPP', 'Murid Mutasi Masuk', 'Murid Mutasi Keluar', 'Garansi'];
    $noteGaransiOptions = ['Berkebutuhan Khusus', 'Tidak Memenuhi Syarat'];
    $periodeOptions = ['Ke1','Ke2','Ke3','Ke4','Ke5','Ke6','Ke7','Ke8','Ke9','Ke10','Ke11','Ke12'];
    $asalModulOptions = ['biMBA IM', 'biMBA Unit'];
    $levelOptions = ['Level 1', 'Level 2', 'Level 3', 'Level 4'];
    $jenisKbmOptions = ['Full TM', 'Full DLC', 'Kombinasi TM & DLC'];
    $kodeJadwalOptions = ['108','109','110','111','112','113','114','115','116','208','209','210','211','308','309','310','311'];
    $statusPindahOptions = ['1'];
    $keBimbaIntervioOptions = ['biMba Intervio'];
    $statusOptions = ['Aktif', 'Keluar', 'Baru'];
    $infoOptions = ['Brosur', 'Event', 'Humas', 'Internet', 'Spanduk', 'Lainnya'];

    // ==================== AUTO NIM ====================
    $autoNim = null;
    $autoNimSuffix = null;

    if (!$isAdmin && $userUnit) {
        $lastNim = BukuInduk::where('bimba_unit', $userUnit)
            ->orderBy('nim', 'desc')
            ->value('nim');

        $nextSuffix = $lastNim ? ((int) substr($lastNim, -4) + 1) : 1;

        $autoNimSuffix = str_pad($nextSuffix, 4, '0', STR_PAD_LEFT);
        $autoNim = $userNoCabang . $autoNimSuffix;
    }
    // =================================================

    // ==================== GURU BY UNIT ====================
    $guruByUnit = [];
    foreach ($profil as $guru) {
        $unit = $guru->bimba_unit ?? $guru->unit ?? null;

        if ($unit && isset($units[$unit])) {
            $guruByUnit[$unit][] = $guru->nama;
        }
    }

    foreach ($guruByUnit as $unit => &$gurus) {
        $gurus = array_unique($gurus);
        sort($gurus);
    }
    // =====================================================

    return view('buku_induk.create', compact(
        'HargaSaptataruna',
        'golOptions', // 🔥 INI PENTING
        'profil',
        'kdOptions',
        'tahapanOptions',
        'kategoriKeluarOptions',
        'kelasOptions',
        'noteOptions',
        'noteGaransiOptions',
        'periodeOptions',
        'asalModulOptions',
        'levelOptions',
        'jenisKbmOptions',
        'kodeJadwalOptions',
        'statusPindahOptions',
        'keBimbaIntervioOptions',
        'sppMapping',
        'statusOptions',
        'units',
        'unitsJson',
        'infoOptions',
        'isAdmin',
        'userUnit',
        'userNoCabang',
        'autoNim',
        'autoNimSuffix',
        'guruByUnit'
    ));
}

    public function store(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->is_admin ?? false;

        // Force unit & cabang untuk non-admin yang sudah punya unit
        if (!$isAdmin && $user->bimba_unit) {
            $request->merge([
                'bimba_unit' => $user->bimba_unit,
                'no_cabang'  => Unit::where('bimba_unit', $user->bimba_unit)->value('no_cabang')
            ]);
        }

        // Hitung usia & lama belajar
        $request->merge([
            'usia' => $request->filled('tgl_lahir')
                ? Carbon::parse($request->tgl_lahir)->age
                : null,

            'lama_bljr' => $request->filled('tgl_masuk') ? (function () use ($request) {
                $masuk = Carbon::parse($request->tgl_masuk);
                $today = Carbon::now();
                $bulan = ($today->year - $masuk->year) * 12 + ($today->month - $masuk->month);
                if ($today->day < $masuk->day) $bulan--;
                return ($bulan >= 0 ? $bulan : 0) . ' bulan';
            })() : null,
        ]);

        // Kosongkan tanggal jika tidak diisi
        $dateFields = ['tgl_lahir', 'tgl_masuk', 'tgl_keluar', 'tgl_mulai', 'tgl_akhir', 'tgl_bayar', 'tgl_selesai', 'tanggal_pindah', 'tgl_surat_garansi', 'tgl_tahapan'];
        foreach ($dateFields as $field) {
            if (!$request->filled($field)) $request->merge([$field => null]);
        }

        // Bersihkan SPP
        if ($request->has('spp') && $request->spp !== null) {
            $cleanSpp = (int) preg_replace('/[^0-9]/', '', $request->spp);
            $request->merge(['spp' => $cleanSpp]);
        }

        // Validasi lengkap
        $data = $request->validate([
            'nim_suffix' => 'required|string|max:10',
            'nim' => 'required|string|max:20|unique:buku_induk,nim',
            'nama' => 'required|string|max:100',
            'tmpt_lahir' => 'nullable|string|max:50',
            'tgl_lahir' => 'required|date',
            'tgl_masuk' => 'required|date',
            'usia' => 'nullable|integer',
            'lama_bljr' => 'nullable|string|max:50',
            'tahap' => 'required|string|in:Persiapan,Lanjutan',
            'tgl_keluar' => 'nullable|date',
            'kategori_keluar' => 'nullable|string',
            'alasan' => 'nullable|string',
            'kelas' => 'required|string|in:biMBA-AIUEO,English biMBA',
            'gol' => 'required|string',
            'kd' => 'required|string|size:1|in:A,B,C,D,E,F',
            'spp' => 'required|integer|min:0|max:999999999',
            'petugas_trial' => 'nullable|string',
            'guru' => 'required|string',
            'orangtua' => 'nullable|string',
            'no_telp_hp' => 'required|string|max:20',
            'note' => 'nullable|string',
            'no_cab_merge' => 'nullable|string',
            'no_pembayaran_murid' => 'nullable|string',
            'note_garansi' => 'nullable|string',
            'periode' => 'nullable|string',
            'tgl_mulai' => 'nullable|date',
            'tgl_akhir' => 'nullable|date',
            'alert' => 'nullable|string',
            'tgl_bayar' => 'nullable|date',
            'tgl_selesai' => 'nullable|date',
            'alert2' => 'nullable|string',
            'asal_modul' => 'required|string',
            'keterangan_optional' => 'nullable|string',
            'level' => 'required|string|in:Level 1,Level 2,Level 3,Level 4',
            'jenis_kbm' => 'required|string',
            'kode_jadwal' => 'required|string',
            'hari_jam' => 'nullable|string',
            'alamat_murid' => 'required|string',
            'status_pindah' => 'nullable|string',
            'tanggal_pindah' => 'nullable|date',
            'ke_bimba_intervio' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'info' => 'required|string|in:Brosur,Event,Humas,Internet,Spanduk,Lainnya',
            'bimba_unit' => 'required|string|exists:units,bimba_unit',
            'no_cabang' => 'required|string|max:20',
            'keterangan_info' => 'nullable|string',
            'tgl_surat_garansi' => 'nullable|date',
            'tgl_tahapan'   => 'required|date',
            'tgl_daftar'    => 'required|date',
        ]);

        // ← TAMBAHKAN INI setelah $data = $request->validate([...])
        $data['tgl_daftar'] = $request->filled('tgl_daftar') ? $request->tgl_daftar : now()->toDateString();

        // Verifikasi no_cabang sesuai bimba_unit
        $correctNoCabang = Unit::where('bimba_unit', $data['bimba_unit'])->value('no_cabang');
        if (!$correctNoCabang || $data['no_cabang'] !== $correctNoCabang) {
            return back()->withErrors(['bimba_unit' => 'No. Cabang tidak sesuai unit terpilih.'])->withInput();
        }

        if (!str_starts_with($data['nim'], $correctNoCabang)) {
            return back()->withErrors(['nim' => 'NIM harus diawali dengan no cabang unit (' . $correctNoCabang . ')'])->withInput();
        }

        $data['nim'] = trim($data['nim']);
        $data['no_cabang'] = $correctNoCabang;
        $data['status'] = 'Baru';

        $existing = BukuInduk::whereRaw('TRIM(nim) = ?', [$data['nim']])->first();

        if ($existing) {
            $oldData = $existing->toArray();
            foreach ($data as $key => $value) {
                if (is_null($existing->$key) || $existing->$key === '' || $existing->$key === '0') {
                    $existing->$key = $value ?? null;
                }
            }
            $existing->save();

            BukuIndukHistory::create([
                'buku_induk_id' => $existing->id,
                'action' => 'update_partial',
                'user' => $user->name ?? 'system',
                'old_data' => $oldData,
                'new_data' => $existing->toArray(),
            ]);

            $message = 'Data murid berhasil diperbarui (NIM sudah ada sebelumnya)';
        } else {
            $newRecord = BukuInduk::create($data);

            // 🔥 sync juga
            $this->syncGaransiBCA($newRecord);
            $this->syncBukuIndukToBeasiswa($newRecord);

            BukuIndukHistory::create([
                'buku_induk_id' => $newRecord->id,
                'action' => 'create',
                'user' => $user->name ?? 'system',
                'old_data' => null,
                'new_data' => $data,
            ]);

            $message = 'Data murid baru berhasil ditambahkan!';
        }

        return redirect()->route('buku_induk.index')->with('success', $message);
    }

    public function edit($id)
{
    $bukuInduk = BukuInduk::findOrFail($id);

    $HargaSaptataruna = HargaSaptataruna::all();
    $profil = Profile::whereIn('jabatan', ['Guru', 'Kepala Unit', 'Kepala', 'Unit Head'])
                 ->orderBy('nama')
                 ->get();

    // ==================== FILTER GOL (SAMA SEPERTI CREATE) ====================
    $excluded = [
        'S1_MB', 'S1_MU', 'S3_MB', 'S3_MU',
        'KA01', 'RBAS', 'TAS', 'STPB', 'STF', 'KPK', 'KA'
    ];

    $golOptions = $HargaSaptataruna
        ->filter(function ($item) use ($excluded) {
            $kode = strtoupper(trim($item->kode));

            return (
                // Hanya ambil yang mulai dengan S, P, K, D
                (str_starts_with($kode, 'S') ||
                 str_starts_with($kode, 'P') ||
                 str_starts_with($kode, 'K') ||
                 str_starts_with($kode, 'D'))
                // Kecualikan yang di-blacklist
                && !in_array($kode, $excluded)
            );
        })
        ->unique('kode')
        ->values();
    // =========================================================================

    // KD Options
    $kdOptions = ['A', 'B', 'C', 'D', 'E', 'F'];

    // SPP Mapping
    $sppMapping = [];
    foreach ($HargaSaptataruna as $item) {
        foreach ($kdOptions as $kd) {
            $col = strtolower($kd);
            $sppMapping[$item->kode][$kd] = $item->$col ?? 0;
        }
    }

    // Units
    $units = DB::table('units')
        ->orderBy('bimba_unit')
        ->pluck('no_cabang', 'bimba_unit')
        ->toArray();

    // Dropdown options
    $tahapanOptions = ['Persiapan', 'Lanjutan'];
    $kategoriKeluarOptions = [
        'Belum bayar SPP','Belum kondusif','Ganti Golongan','Masuk SD','Masuk TK',
        'Sudah SD','Sudah TK','Perpanjang Bea','Pindah biMBA','Pindah rumah',
        'Sakit/rehat','Tdk ada yg antar','Tidak ada kabar','Lain-lain',
        'Order Sertifikat','Order STPB','Order Sertifikat & STPB'
    ];

    $kelasOptions = ['biMBA-AIUEO', 'English biMBA'];
    $noteOptions = [
        'Aktif Kembali','Cuti','Ganti Gol','Pindahan',
        'Belum Bayar SPP','Murid Mutasi Masuk','Murid Mutasi Keluar','Garansi'
    ];
    $asalModulOptions = ['biMBA IM', 'biMBA Unit'];

    $periodeOptions = ['Ke-1','Ke-2','Ke-3','Ke-4','Ke-5','Ke-6','Ke-7','Ke-8','Ke-9','Ke-10','Ke-11','Ke-12'];
    $levelOptions = ['Level 1','Level 2','Level 3','Level 4'];
    $jenisKbmOptions = ['Full TM','Full DLC','Kombinasi TM & DLC'];
    $kodeJadwalOptions = [
        '108','109','110','111','112','113','114','115','116',
        '208','209','210','211',
        '308','309','310','311'
    ];
    

    $noteGaransiOptions = [
        'Tidak Memenuhi Syarat',
        'Berkebutuhan Khusus',
    ];

    $infoOptions = ['Brosur','Event','Humas','Internet','Spanduk','Lainnya'];

    return view('buku_induk.edit', compact(
        'bukuInduk',
        'HargaSaptataruna',
        'golOptions',           // ← TAMBAHKAN INI
        'profil',
        'kdOptions',
        'sppMapping',
        'tahapanOptions',
        'kategoriKeluarOptions',
        'noteGaransiOptions',
        'kelasOptions',
        'noteOptions',
        'periodeOptions',
        'levelOptions',
        'jenisKbmOptions',
        'kodeJadwalOptions',
        'units',
        'infoOptions',
        'asalModulOptions'
        
    ));
}


    public function update(Request $request, $id)
{
    $bukuInduk = BukuInduk::findOrFail($id);
    $oldData = $bukuInduk->toArray();

    // pastikan NIM bersih
    $request->merge(['nim' => trim($request->nim)]);

 if (empty($request->tgl_surat_garansi)) {
    $request->merge([
        'tgl_surat_garansi' => null
    ]);

}
    /* =====================================================
     * VALIDASI — SELARAS 100% DENGAN MODEL
     * ===================================================== */
    $data = $request->validate([
        'nim' => ['required','string','max:20', Rule::unique('buku_induk','nim')->ignore($id)],
        'nama' => 'required|string|max:100',

        'tmpt_lahir' => 'nullable|string|max:50',
        'tgl_lahir'  => 'nullable|date',
        'tgl_daftar' => 'nullable|date',
        'tgl_masuk'  => 'required|date',
        'tgl_keluar' => 'nullable|date',

        'usia'      => 'nullable|integer',
        'lama_bljr' => 'nullable|string',

        'tahap' => 'nullable|string|in:Persiapan,Lanjutan',
        'kelas' => 'required|string|in:biMBA-AIUEO,English biMBA',
        'gol'   => 'required|string',
        'kd'    => 'required|string|in:A,B,C,D,E,F',

        'spp' => 'required|integer|min:0',

        'guru' => 'required|string',
        'petugas_trial' => 'nullable|string',
        'orangtua' => 'nullable|string',
        'no_telp_hp' => 'nullable|string',

        'kategori_keluar' => 'nullable|string',
        'alasan'          => 'nullable|string',

        'note' => 'nullable|string',
        'note_garansi' => 'nullable|string',
        'no_cab_merge' => 'nullable|string',
        'no_pembayaran_murid' => 'nullable|string',

        // ==== BEASISWA / GARANSI ====
        'periode'   => 'nullable|string',
        'tgl_mulai' => 'nullable|date',
        'tgl_akhir' => 'nullable|date',
        'alert'     => 'nullable|string',
        'alert2'    => 'nullable|string',

        'tgl_bayar'   => 'nullable|date',
        'tgl_selesai' => 'nullable|date',

        'asal_modul' => 'nullable|string',
        'level' => 'nullable|string',
        'tgl_level' => 'nullable|date',
        'keterangan_level' => 'nullable|string',
        'jenis_kbm' => 'nullable|string',
        'kode_jadwal' => 'required|string',
        'hari_jam' => 'nullable|string',

        'alamat_murid' => 'nullable|string',
        'status_pindah' => 'nullable|string',
        'tanggal_pindah' => 'nullable|date',
        'ke_bimba_intervio' => 'nullable|string',

        'keterangan' => 'nullable|string',
        'keterangan_optional' => 'nullable|string',

        'bimba_unit' => 'required|string|exists:units,bimba_unit',
        'no_cabang'  => 'required|string',
        'info'       => 'required|string',
        'tgl_surat_garansi' => 'nullable|date',
        'tgl_aktif'   => 'nullable|date',
        'tgl_tahapan' => 'nullable|date',
        'keterangan_info' => 'nullable|string',
        'tgl_pengajuan_garansi' => 'nullable|date',
        'tgl_selesai_garansi' => 'nullable|date',
        'perpanjang_garansi' => 'nullable|string',
        'alasan_garansi' => 'nullable|string',
        'jumlah_beasiswa' => 'nullable|numeric|min:0',
        'modul_terakhir'    => 'nullable|string',
    ]);

    // ← TAMBAHKAN INI
    if (!$data['tgl_daftar']) {
        $data['tgl_daftar'] = now()->toDateString();
    }

    /* =====================================================
     * NORMALISASI TANGGAL KOSONG
     * ===================================================== */
    foreach ([
    'tgl_lahir','tgl_masuk','tgl_keluar','tgl_mulai',
    'tgl_akhir','tgl_bayar','tgl_selesai','tanggal_pindah',
    'tgl_level',
    'tgl_tahapan',
    'tgl_aktif',
    'tgl_surat_garansi',
    'tgl_pengajuan_garansi',
    'tgl_selesai_garansi',
] as $f) {
    if (empty($data[$f])) {
        $data[$f] = null;
    }
}

    /* =====================================================
     * HITUNG USIA & LAMA BELAJAR
     * ===================================================== */
    if ($data['tgl_lahir']) {
        $data['usia'] = Carbon::parse($data['tgl_lahir'])->age;
    }

    if ($data['tgl_masuk']) {
        $masuk = Carbon::parse($data['tgl_masuk']);
        $today = Carbon::now();

        $bulan = ($today->year - $masuk->year) * 12 + ($today->month - $masuk->month);
        if ($today->day < $masuk->day) {
            $bulan--;
        }

        $data['lama_bljr'] = max($bulan, 0) . ' bulan';
    }

    /* =====================================================
     * ALERT BEASISWA OTOMATIS
     * ===================================================== */
if (!empty($data['tgl_mulai'])) {

    $tglMulai = Carbon::parse($data['tgl_mulai']);
    $tglAkhir = !empty($data['tgl_akhir'])
        ? Carbon::parse($data['tgl_akhir'])
        : $tglMulai->copy()->addMonths(6);

    $data['tgl_akhir'] = $tglAkhir;

    $data['alert'] = Carbon::today()->between($tglMulai, $tglAkhir)
        ? 'aktif'
        : 'nonaktif';

} else {

    // 🔥 reset total kalau bukan beasiswa
    $data['tgl_akhir'] = null;
    $data['alert'] = null;
    $data['jumlah_beasiswa'] = null;
}

/* =====================================================
 * FIX JUMLAH BEASISWA (ANTI ERROR MYSQL)
 * ===================================================== */
$data['jumlah_beasiswa'] = is_numeric($data['jumlah_beasiswa'] ?? null)
    ? (float) $data['jumlah_beasiswa']
    : null;

    // ===============================
    // PAKET 72 SAFE HANDLING
    // ===============================
    if ($request->filled('tgl_bayar')) {

    try {
        $bayar = Carbon::parse($request->tgl_bayar);
    } catch (\Exception $e) {
        $bayar = null;
    }

    if ($bayar) {
        $selesai = $bayar->copy()->addDays(72);

        $data['tgl_selesai'] = $selesai->format('Y-m-d');

        $today = Carbon::today();

        if ($today->between($bayar, $selesai)) {
            $data['alert2'] = 'aktif';
        } elseif ($today->gt($selesai)) {
            $data['alert2'] = 'expired';
        } else {
            $data['alert2'] = 'menunggu';
        }
    }

} else {
    $data['tgl_selesai'] = null;
    $data['alert2'] = null;
}
    /* =====================================================
     * VALIDASI CABANG & NIM
     * ===================================================== */
    $correctNoCabang = DB::table('units')
        ->where('bimba_unit', $data['bimba_unit'])
        ->value('no_cabang');

    if ($data['no_cabang'] !== $correctNoCabang) {
        return back()->withErrors(['bimba_unit' => 'No cabang tidak sesuai'])->withInput();
    }

    if (!str_starts_with($data['nim'], $correctNoCabang)) {
        return back()->withErrors(['nim' => "NIM harus diawali {$correctNoCabang}"])->withInput();
    }

/* =====================================================
 * GARANSI 372 FINAL LOGIC (FIX ALL CASE)
 * ===================================================== */
if (!empty($data['tgl_pengajuan_garansi'])) {

    // ✅ SUDAH DIAJUKAN
    $tglPengajuan = Carbon::parse($data['tgl_pengajuan_garansi']);
    $tglSelesai = $tglPengajuan->copy()->addMonths(6);

    $data['tgl_selesai_garansi'] = $tglSelesai;
    $data['masa_aktif_garansi']  = 6;

    $today = Carbon::today();
    $sisaHari = $today->diffInDays($tglSelesai, false);

    if ($sisaHari <= 30 && $sisaHari >= 0) {
        $data['perpanjang_garansi'] = 'Segera perpanjang (' . $sisaHari . ' hari lagi)';
    } elseif ($sisaHari < 0) {
        $data['perpanjang_garansi'] = 'Habis';
    } else {
        $data['perpanjang_garansi'] = 'Aktif';
    }

} elseif (!empty($data['tgl_surat_garansi'])) {

    // ✅ BARU DIBERIKAN (BELUM DIAJUKAN)
    $data['tgl_pengajuan_garansi'] = null;   // 🔥 paksa bersih
    $data['tgl_selesai_garansi']   = null;
    $data['masa_aktif_garansi']    = null;
    $data['perpanjang_garansi']    = 'Diberikan';

} else {

    // ❌ TIDAK ADA GARANSI
    $data['tgl_pengajuan_garansi'] = null;
    $data['tgl_selesai_garansi']   = null;
    $data['masa_aktif_garansi']    = null;
    $data['perpanjang_garansi']    = null;
}

    $bukuInduk->update($data);

    $this->syncBukuIndukToBeasiswa($bukuInduk);
    

/**
 * ambil perubahan setelah update
 */
$changes = $bukuInduk->getChanges();

/* =====================================================
 * BEASISWA HISTORY (AMAN + TERFILTER)
 * ===================================================== */
if (
    !empty($data['periode']) &&
    (
        isset($changes['periode']) ||
        isset($changes['tgl_mulai']) ||
        isset($changes['tgl_akhir']) ||
        isset($changes['jumlah_beasiswa'])
    )
) {
    $this->storeBeasiswaHistory($bukuInduk);
}

/* =====================================================
 * PAKET 72 HISTORY (HANYA JIKA ADA PERUBAHAN)
 * ===================================================== */
if ($request->filled('tgl_bayar')) {

    $bayar = Carbon::parse($request->tgl_bayar);
    $selesai = $bayar->copy()->addDays(72);

    $data['tgl_selesai'] = $selesai->format('Y-m-d');

    $today = Carbon::today();

    $data['alert2'] =
        $today->between($bayar, $selesai) ? 'aktif' :
        ($today->gt($selesai) ? 'expired' : 'menunggu');
}
// === SINKRONISASI GARANSI BCA ===
    if (!empty($bukuInduk->tgl_surat_garansi)) {
    $this->syncGaransiBCA($bukuInduk);
}

    // 🔥 SIMPAN HISTORY LEVEL
if ($bukuInduk->wasChanged('level') && !empty($data['level'])) {

    $last = LevelHistory::where('buku_induk_id', $bukuInduk->id)
        ->latest()
        ->first();

    // hindari duplicate level yang sama
    if (!$last || $last->level !== $data['level']) {

        LevelHistory::create([
            'buku_induk_id' => $bukuInduk->id,
            'level' => $data['level'],

            // pakai input kalau ada, kalau tidak pakai sekarang
            'tgl_level' => $data['tgl_level'] ?? now(),
            'keterangan' => $data['keterangan_level'] ?? null,
        ]);
    }
}

    $newData = $bukuInduk->fresh()->toArray();
    $changedOld = [];
    $changedNew = [];

    foreach ($oldData as $k => $v) {
        if (array_key_exists($k, $newData) && $newData[$k] != $v) {
            $changedOld[$k] = $v;
            $changedNew[$k] = $newData[$k];
        }
    }

    if ($changedOld) {
        BukuIndukHistory::create([
            'buku_induk_id' => $bukuInduk->id,
            'action' => 'update',
            'user' => Auth::user()?->name ?? 'system',
            'old_data' => $changedOld,
            'new_data' => $changedNew,
        ]);
    }

            // HAPUS pengajuan lama kalau ternyata status hanya "Diberikan"
            if ($data['perpanjang_garansi'] === 'Diberikan') {

                PengajuanGaransi::where('nim', $bukuInduk->nim)->delete();

            } elseif (!empty($data['tgl_pengajuan_garansi'])) {

                PengajuanGaransi::updateOrCreate(
                    ['nim' => $bukuInduk->nim],
                    [
                        'nama_murid'    => $bukuInduk->nama,
                        'bimba_unit'    => $bukuInduk->bimba_unit,
                        'tgl_pengajuan' => $data['tgl_pengajuan_garansi'],
                        'alasan'        => $data['alasan_garansi'],
                        'status'        => 'pending',
                    ]
                );
            }

    return redirect()
        ->route('buku_induk.index')
        ->with('success', 'Data murid berhasil diperbarui!');
}



    public function destroy($id)
    {
        $bukuInduk = BukuInduk::findOrFail($id);
        $bukuInduk->delete();
        return redirect()->route('buku_induk.index')->with('success', 'Data berhasil dihapus!');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new BukuIndukImport, $request->file('file'));

        return redirect()->route('buku_induk.index')->with('success', 'Data berhasil diimport!');
    }

    public function history($id)
    {
        // Cek apakah user login dan punya role 'admin'
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Hanya admin yang diizinkan melihat riwayat perubahan.');
        }

        $bukuInduk = BukuInduk::findOrFail($id);
        $histories = $bukuInduk->histories()->latest()->paginate(10);

        return view('buku_induk.history', compact('bukuInduk', 'histories'));
    }

public function show($id)
{
    $bukuInduk = BukuInduk::findOrFail($id);

    // ambil history yang terkait
    $histories = $bukuInduk->histories()->latest()->get();

    // Hitung info pertemuan terlewat di bulan masuk
    $infoJadwal = $this->hitungPertemuanTerlewatDiBulanMasuk($bukuInduk);

    return view('buku_induk.show', compact(
        'bukuInduk',
        'histories',
        'infoJadwal'   // ← ini yang hilang sebelumnya
    ));
}
    public function allHistory()
    {
        // Sama seperti di atas
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Hanya admin yang diizinkan melihat semua riwayat.');
        }

        $histories = BukuIndukHistory::with('bukuInduk')->latest()->paginate(20);

        return view('buku_induk.all_history', compact('histories'));
    }
    public function export(Request $request)
    {
        $filters = $request->only(['murid', 'unit', 'status']);

        return Excel::download(
            new BukuIndukExport($filters),
            'Data_Buku_Induk_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

  /**
 * Menghitung pertemuan terlewat dan sisa jadwal di bulan mulai efektif
 * - Jika masuk di tanggal >= 21 → mulai efektif bulan depan (terlewat = 0)
 * - Jika masuk < 21 → hitung terlewat di bulan masuk
 * - Exclude tanggal merah nasional & cuti bersama
 *
 * @param BukuInduk $murid
 * @return array
 */
 private function hitungPertemuanTerlewatDiBulanMasuk($murid)
{
    if (!$murid->tgl_masuk) {
        return [
            'status'            => 'error',
            'pesan'             => 'Tanggal masuk kosong',
            'pertemuan_diambil' => 0,
            'sisa'              => null,
            'total_hardcode'    => 0,
            'bulan_tampil'      => '-',
            'shift'             => '-',
            'catatan'           => null,
        ];
    }

    $masuk = \Carbon\Carbon::parse($murid->tgl_masuk);
    $kode  = (int) ($murid->kode_jadwal ?? 0);

    // Mapping shift, total hardcode, dan hari jadwal
    $namaShift     = '-';
    $totalHardcode = 0;
    $hariJadwal    = [];

    if ($kode >= 108 && $kode <= 116) {
        $namaShift     = 'SRJ';
        $totalHardcode = 12;
        $hariJadwal    = [0, 2, 4]; // Senin, Rabu, Jumat
    } elseif ($kode >= 208 && $kode <= 211) {
        $namaShift     = 'SKS';
        $totalHardcode = 12;
        $hariJadwal    = [1, 3, 5]; // Selasa, Kamis, Sabtu
    } elseif ($kode >= 308 && $kode <= 311) {
        $namaShift     = 'S6';
        $totalHardcode = 24;
        $hariJadwal    = [0, 1, 2, 3, 4, 5]; // Senin–Sabtu
    }

    if ($totalHardcode === 0) {
        return [
            'status'            => 'error',
            'pesan'             => 'Kode jadwal tidak dikenali',
            'pertemuan_diambil' => 0,
            'sisa'              => null,
            'total_hardcode'    => 0,
            'bulan_tampil'      => '-',
            'shift'             => '-',
            'catatan'           => null,
        ];
    }

    // Threshold: jika masuk >= tanggal 21 → aktif mulai bulan depan
    $thresholdHari = 21;
    $bulanAktif    = $masuk->copy();
    $catatan       = null;
    $pertemuanDiambil = 0;

    if ($masuk->day >= $thresholdHari) {
        $bulanAktif->addMonthNoOverflow()->startOfMonth();
        $catatan = "Masuk akhir bulan → aktif mulai {$bulanAktif->translatedFormat('F Y')}";
        // Karena aktif full bulan depan → pertemuan_diambil = 0, sisa = total full
        $pertemuanDiambil = 0;
    } else {
        // Masuk di bulan yang sama → hitung pertemuan dari tanggal masuk sampai akhir bulan
        $start = $masuk->copy();
        $end   = $masuk->copy()->endOfMonth();

        $current = $start->copy();
        while ($current->lte($end)) {
            if (in_array($current->weekday(), $hariJadwal)) {
                $pertemuanDiambil++;
            }
            $current->addDay();
        }
    }

    // Sisa = total_hardcode - pertemuan_diambil
    $sisa = $totalHardcode - $pertemuanDiambil;
    $sisa = max(0, $sisa); // jaga-jaga kalau negatif

    return [
        'status'            => 'ok',
        'shift'             => $namaShift,
        'bulan_tampil'      => $bulanAktif->translatedFormat('F Y'),
        'tanggal_masuk'     => $masuk->translatedFormat('d F Y'),
        'hari_masuk'        => $masuk->translatedFormat('l, d F Y'),
        'pertemuan_diambil' => $pertemuanDiambil,
        'sisa'              => $sisa,
        'total_hardcode'    => $totalHardcode,
        'catatan'           => $catatan,
    ];
}
public function nextSuffix(Request $request)
{
    $bimbaUnit = $request->query('bimba_unit');
    if (!$bimbaUnit) return response()->json(['next_suffix' => '0001']);

    $lastNim = BukuInduk::where('bimba_unit', $bimbaUnit)
        ->orderBy('nim', 'desc')
        ->value('nim');

    if (!$lastNim) return response()->json(['next_suffix' => '0001']);

    $lastSuffix = (int) substr($lastNim, -4);
    $next = $lastSuffix + 1;

    return response()->json(['next_suffix' => $next]);
}
public function approve($id)
{
    $pengajuan = PengajuanGaransi::findOrFail($id);

    // update status pengajuan
    $pengajuan->update([
        'status' => 'disetujui'
    ]);

    // ambil buku induk berdasarkan NIM
    $buku = BukuInduk::where('nim', $pengajuan->nim)->first();

    if ($buku) {

        $tglPengajuan = \Carbon\Carbon::parse($pengajuan->tgl_pengajuan);
        $tglSelesai   = $tglPengajuan->copy()->addMonths(6);

        $buku->update([
            'tgl_surat_garansi'     => now(), // ← tanggal diberikan
            'note_garansi'          => 'Garansi BCA Disetujui',
            'tgl_pengajuan_garansi' => $pengajuan->tgl_pengajuan,
            'tgl_selesai_garansi'   => $tglSelesai,
            'masa_aktif_garansi'    => 6,
            'perpanjang_garansi'    => 'Aktif',
        ]);
    }

    return back()->with('success', 'Pengajuan berhasil disetujui & garansi aktif');
}



public function exportToSheet(GoogleFormService $service)
{
    $data = BukuInduk::select(
        'nim',
        'nama',
        'bimba_unit as cabang',
        'status'
    )->get();

    $service->exportBukuInduk($data, 'buku_induk');

    return 'Export berhasil ke Google Sheet';
}

private function syncBukuIndukToBeasiswa($bukuInduk)
{
    // ❌ JANGAN BUAT JIKA TIDAK ADA DATA BEASISWA
    if (
        empty($bukuInduk->tgl_mulai) &&
        empty($bukuInduk->tgl_akhir) &&
        empty($bukuInduk->periode) &&
        empty($bukuInduk->jumlah_beasiswa)
    ) {
        return; // 🔥 STOP disini
    }

    $beasiswa = \App\Models\SertifikatBeasiswa::where('nim', $bukuInduk->nim)->first();

    $data = [
        'nim'               => $bukuInduk->nim,
        'nama'              => $bukuInduk->nama,
        'bimba_unit'        => $bukuInduk->bimba_unit,
        'periode_bea_ke'    => $bukuInduk->periode,
        'tanggal_mulai'     => $bukuInduk->tgl_mulai,
        'tanggal_selesai'   => $bukuInduk->tgl_akhir,
        'golongan'          => $bukuInduk->gol,
        'nama_orang_tua'    => $bukuInduk->orangtua,
        'alamat'            => $bukuInduk->alamat_murid,
        'tanggal_lahir'     => $bukuInduk->tgl_lahir,
        'jumlah_beasiswa'   => $bukuInduk->jumlah_beasiswa,
        'virtual_account'   => $bukuInduk->no_pembayaran_murid,
    ];

    if ($beasiswa) {
        $beasiswa->update($data);
    } else {
        \App\Models\SertifikatBeasiswa::create($data);
    }
}

private function storeBeasiswaHistory($bukuInduk)
{
    // ❌ jangan jalan kalau tidak ada periode
    if (empty($bukuInduk->periode)) {
        return;
    }

    // ❌ jangan jalan kalau semua field beasiswa kosong
    if (
        empty($bukuInduk->tgl_mulai) &&
        empty($bukuInduk->tgl_akhir) &&
        empty($bukuInduk->jumlah_beasiswa)
    ) {
        return;
    }

    $last = BukuIndukBeasiswaHistory::where('nim', $bukuInduk->nim)
        ->where('status', 'aktif')
        ->latest()
        ->first();

    // kalau sama persis periode + tanggal → skip
    if ($last && $last->periode === $bukuInduk->periode) {
        return;
    }

    // tutup history lama
    if ($last) {
        $last->update(['status' => 'selesai']);
    }

    BukuIndukBeasiswaHistory::create([
        'nim' => $bukuInduk->nim,
        'nama' => $bukuInduk->nama,
        'alamat_murid' => $bukuInduk->alamat_murid ?? null,
        'orangtua' => $bukuInduk->orangtua ?? null,

        'periode' => $bukuInduk->periode,
        'tgl_mulai' => $bukuInduk->tgl_mulai,
        'tgl_akhir' => $bukuInduk->tgl_akhir,
        'jumlah_beasiswa' => $bukuInduk->jumlah_beasiswa ?? null,

        'status' => 'aktif',
    ]);
}
private function storePaket72History($bukuInduk)
{
    if (empty($bukuInduk->tgl_bayar)) {
        return;
    }

    $exists = \App\Models\Paket72History::where('buku_induk_id', $bukuInduk->id)
        ->whereDate('tgl_bayar', $bukuInduk->tgl_bayar)
        ->exists();

    if ($exists) return;

    \App\Models\Paket72History::create([
        'buku_induk_id' => $bukuInduk->id,
        'nim' => $bukuInduk->nim,
        'nama' => $bukuInduk->nama,

        'tgl_bayar' => $bukuInduk->tgl_bayar,
        'tgl_selesai' => $bukuInduk->tgl_selesai,
        'alert2' => $bukuInduk->alert2,
    ]);
}
private function syncGaransiBCA($bukuInduk)
{
    // Jika tidak ada tanggal garansi, keluar
    if (empty($bukuInduk->tgl_surat_garansi)) {
        return;
    }

    // Pastikan tanggal dalam format yang benar (Carbon)
    try {
        $tanggalDiberikan = Carbon::parse($bukuInduk->tgl_surat_garansi);
    } catch (\Exception $e) {
        // Jika tanggal tidak valid, gunakan hari ini sebagai fallback
        $tanggalDiberikan = Carbon::today();
    }

    // Cek apakah sudah ada record garansi untuk murid ini
    $garansi = GaransiBCA::where('nama_murid', $bukuInduk->nama)->first();

    $dataGaransi = [
        'nim'   => $bukuInduk->nim,
        'nama_murid'            => $bukuInduk->nama ?? '-',
        'tempat_tanggal_lahir'  => trim(
            ($bukuInduk->tmpt_lahir ?: '-') . ', ' .
            ($bukuInduk->tgl_lahir 
                ? Carbon::parse($bukuInduk->tgl_lahir)->format('d-m-Y') 
                : '-'
            )
        ),
        'tanggal_masuk'         => $bukuInduk->tgl_masuk,
        'nama_orang_tua_wali'   => $bukuInduk->orangtua ?? '-',
        'bimba_unit'            => $bukuInduk->bimba_unit,
        'tanggal_diberikan'     => $tanggalDiberikan,           // ← pakai Carbon object
        'sumber'                => 'Pemberian',
        'virtual_account'       => $bukuInduk->no_pembayaran_murid ?? null,
    ];

    if ($garansi) {
        // Update yang sudah ada
        $garansi->update($dataGaransi);
    } else {
        // Buat record baru
        GaransiBCA::create($dataGaransi);
    }
}

/**
 * Generate Surat Keterangan Pindah Murid (Mutasi)
 */
public function suratPindah($id)
{
    $murid = BukuInduk::findOrFail($id);

    $unit = Unit::where('bimba_unit', $murid->bimba_unit)->first();

    // ===============================
    // PENANDATANGAN (FIX PRIORITAS)
    // ===============================
    $penandatangan = Profile::where('bimba_unit', $murid->bimba_unit)
        ->whereIn('jabatan', ['Kepala Unit', 'Mitra', 'Kepala', 'Unit Head'])
        ->whereIn('status_karyawan', ['aktif', 'magang']) // 🔥 buang non-aktif
        ->orderByRaw("
            CASE 
                WHEN status_karyawan = 'aktif' THEN 1
                WHEN status_karyawan = 'magang' THEN 2
                ELSE 3
            END
        ")
        ->first();

    // fallback jika tidak ada
    if (!$penandatangan) {
        $penandatangan = (object) [
            'name_relawan' => 'Kepala Unit',
            'nama' => 'Kepala Unit',
            'jabatan' => 'Kepala Unit',
            'no_telp' => ''
        ];
    }

    // ===============================
    // ALAMAT LENGKAP UNIT
    // ===============================
    $alamatUnit = [];

    if ($unit) {
        if (!empty($unit->alamat_jalan)) {
            $alamatUnit[] = $unit->alamat_jalan;
        }
        if (!empty($unit->alamat_rt_rw)) {
            $alamatUnit[] = $unit->alamat_rt_rw;
        }
        if (!empty($unit->alamat_kel_des)) {
            $alamatUnit[] = $unit->alamat_kel_des;
        }
        if (!empty($unit->alamat_kecamatan)) {
            $alamatUnit[] = 'Kec. ' . $unit->alamat_kecamatan;
        }
        if (!empty($unit->alamat_kota_kab)) {
            $alamatUnit[] = $unit->alamat_kota_kab;
        }
        if (!empty($unit->alamat_provinsi)) {
            $alamatUnit[] = $unit->alamat_provinsi;
        }
    }

    $alamat_lengkap = !empty($alamatUnit) ? implode(', ', $alamatUnit) : '-';

    // ===============================
    // NORMALISASI KOTA/KABUPATEN
    // ===============================
    $alamat_kota_kab_raw = $unit->alamat_kota_kab ?? '-';

    $kota_clean = strtoupper(trim($alamat_kota_kab_raw));

    $kota_clean = preg_replace('/\b(KABUPATEN|KAB\.?|KOTA)\s*/i', '', $kota_clean);

    $alamat_kota_kab = trim($kota_clean);

    // ===============================
    // DATA UNTUK PDF
    // ===============================
    $data = [
        'judul' => 'SURAT KETERANGAN PINDAH MURID biMBA AIUEO',

        'nama_penandatangan' => $penandatangan->name_relawan 
            ?? $penandatangan->nama 
            ?? 'Kepala Unit',

        'jabatan' => $penandatangan->jabatan ?? 'Kepala Unit',

        'unit' => $murid->bimba_unit ?? '',
        'no_cabang' => $murid->no_cabang ?? '',
        'alamat_unit' => $alamat_lengkap,
        'alamat_kota_kab' => $alamat_kota_kab,

        // prioritas no telp: unit → penandatangan
        'no_telp' => $unit->telp 
            ?? $penandatangan->no_telp 
            ?? '',

        'nama_murid' => $murid->nama,
        'nim' => $murid->nim,
        'alamat_murid' => $murid->alamat_murid ?? '-',

        'tgl_masuk' => $murid->tgl_masuk
            ? Carbon::parse($murid->tgl_masuk)->format('d/m/Y')
            : '',

        'tgl_terakhir' => $murid->tgl_keluar
            ? Carbon::parse($murid->tgl_keluar)->format('d/m/Y')
            : now()->format('d/m/Y'),

        'level' => $murid->level ?? '1',
        'modul_terakhir' => $murid->modul_terakhir ?? '-',

        'tanggal_surat' => now()->isoFormat('D MMMM Y'),
    ];

    // ===============================
    // GENERATE PDF
    // ===============================
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('buku_induk.surat_pindah', $data)
        ->setPaper('a4', 'portrait');

    $filename = "Surat_Pindah_{$murid->nim}_" 
        . str_replace([' ', '/'], '_', $murid->nama) 
        . ".pdf";

    return $pdf->stream($filename);
}

}
