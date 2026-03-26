<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ImbalanRekap;
use App\Models\AbsensiVolunteer;
use App\Models\Adjustment;
use App\Models\CashAdvanceInstallment;
use App\Models\CashAdvance;
use App\Models\DurasiKegiatan;
use App\Models\Profile;
use App\Models\PotonganTunjangan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Events\ProfileUpdated; // Tambahkan ini di atas class
use Illuminate\Support\Facades\Cache;
use Throwable;

class ImbalanRekapController extends Controller
{
    public function index(Request $request)
{
    /* ======================================================
     * 1. FILTER PARAM
     * ====================================================== */
    $start_bulan = $request->get('start_bulan', now()->format('m'));
    $start_tahun = $request->get('start_tahun', now()->format('Y'));
    $end_bulan   = $request->get('end_bulan', $start_bulan);
    $end_tahun   = $request->get('end_tahun', $start_tahun);
    $bimba_unit  = $request->get('bimba_unit');
    $nama        = $request->get('nama'); // ✅ FILTER NAMA
    $showAll     = $request->has('all');

    /* ======================================================
     * 2. RANGE TANGGAL
     * ====================================================== */
    try {
        $startDate = Carbon::createFromFormat('Y-m', "$start_tahun-$start_bulan")->startOfMonth();
        $endDate   = Carbon::createFromFormat('Y-m', "$end_tahun-$end_bulan")->endOfMonth();
    } catch (\Exception $e) {
        $startDate = now()->startOfMonth();
        $endDate   = now()->endOfMonth();
    }
    /* ======================================================
 * LIST BULAN YANG DIFILTER
 * ====================================================== */
$bulanLabels = [];

$tmp = $startDate->copy();
while ($tmp <= $endDate) {
    $bulanLabels[] = $tmp->locale('id')->translatedFormat('F Y');
    $tmp->addMonth();
}

    /* ======================================================
     * 3. LABEL PERIODE
     * ====================================================== */
    $periodeLabel = $startDate->locale('id')->translatedFormat('F Y');
    if (! $startDate->isSameMonth($endDate)) {
        $periodeLabel .= ' — ' . $endDate->locale('id')->translatedFormat('F Y');
    }

    /* ======================================================
     * 4. CEK ADMIN
     * ====================================================== */
    $user    = auth()->user();
    $isAdmin = $user && (
        $user->role === 'admin' ||
        ($user->is_admin ?? false)
    );

    /* ======================================================
     * 5. UNIT OPTIONS (ADMIN ONLY)
     * ====================================================== */
    $unitOptions = $isAdmin
        ? \App\Models\Unit::orderBy('biMBA_unit')->pluck('biMBA_unit')
        : collect();

    /* ======================================================
     * 6. NAMA RELAWAN OPTIONS (DARI PROFILE + ADA REKAP)
     * ====================================================== */
    $namaOptionsQuery = \App\Models\Profile::query()
        ->select('profiles.nama')
        ->join('imbalan_rekaps', 'profiles.nama', '=', 'imbalan_rekaps.nama')
        ->distinct()
        ->orderBy('profiles.nama');

    if (! $showAll) {
    $namaOptionsQuery->whereIn('imbalan_rekaps.bulan', $bulanLabels);
}

    if ($bimba_unit) {
        $namaOptionsQuery->where('profiles.biMBA_unit', $bimba_unit);
    }

    $namaOptions = $namaOptionsQuery->pluck('nama');

    /* ======================================================
     * 7. QUERY UTAMA
     * ====================================================== */
    $query = \App\Models\ImbalanRekap::query()
        ->select('imbalan_rekaps.*')
        ->with('profile')
        ->join('profiles', 'imbalan_rekaps.nama', '=', 'profiles.nama')
        ->orderByRaw("CAST(profiles.nik AS UNSIGNED) ASC")
        ->orderBy('imbalan_rekaps.nama', 'ASC');

    if (! $showAll) {
    $query->whereIn('imbalan_rekaps.bulan', $bulanLabels);
}

    if ($bimba_unit) {
        $query->where('profiles.biMBA_unit', $bimba_unit);
    }

    if ($nama) {
        $query->where('imbalan_rekaps.nama', $nama);
    }

    /* ======================================================
     * 8. AMBIL DATA
     * ====================================================== */
    $rekaps = $showAll ? $query->get() : $query->paginate(50);

    /* ======================================================
     * 9. FORMAT MASA KERJA
     * ====================================================== */
    foreach ($rekaps as $r) {
        $formatted = '-';

        if (!empty($r->masa_kerja) && is_numeric($r->masa_kerja)) {
            $formatted = $this->formatMasaKerja((int) $r->masa_kerja);
        } elseif ($r->profile) {
            if (!empty($r->profile->masa_kerja)) {
                $formatted = $this->formatMasaKerja((int) $r->profile->masa_kerja);
            } elseif (!empty($r->profile->tgl_masuk)) {
                $formatted = $this->hitungMasaKerja($r->profile->tgl_masuk);
            }
        }

        $r->masa_kerja_formatted = $formatted;
    }

    if (! $showAll) {
        $rekaps->appends($request->all());
    }

    /* ======================================================
     * 10. RETURN VIEW
     * ====================================================== */
    return view('imbalan_rekap.index', compact(
        'rekaps',
        'periodeLabel',
        'start_bulan',
        'start_tahun',
        'end_bulan',
        'end_tahun',
        'bimba_unit',
        'nama',          // ✅
        'namaOptions',   // ✅ FIX ERROR
        'unitOptions',
        'startDate',
        'endDate',
        'showAll'
    ));
}






    private function hitungMasaKerja($tanggalMasuk)
    {
        if (!$tanggalMasuk)
            return null;
        try {
            $mulai = Carbon::parse($tanggalMasuk);
            $sekarang = Carbon::now();
            $tahun = $sekarang->diffInYears($mulai);
            $bulan = $sekarang->copy()->subYears($tahun)->diffInMonths($mulai);

            if ($tahun > 0 && $bulan > 0)
                return "$tahun tahun $bulan bulan";
            if ($tahun > 0)
                return "$tahun tahun";
            if ($bulan > 0)
                return "$bulan bulan";
            return "0 bulan";
        } catch (\Exception $e) {
            return null;
        }
    }

    public function truncate()
    {
        ImbalanRekap::truncate();
        return redirect()->route('imbalan_rekap.index')->with('success', 'Semua data ImbalanRekap dihapus.');
    }

    private function formatMasaKerja($bulan)
    {
        if ($bulan === null || $bulan < 0)
            return '-';
        $tahun = intdiv($bulan, 12);
        $sisaBulan = $bulan % 12;
        $parts = [];
        if ($tahun > 0)
            $parts[] = "$tahun tahun";
        if ($sisaBulan > 0)
            $parts[] = "$sisaBulan bulan";
        return $parts ? implode(' ', $parts) : '0 bulan';
    }

    public function refresh(Request $request)
{
    $start_bulan = $request->get('start_bulan', now()->format('m'));
    $start_tahun = $request->get('start_tahun', now()->format('Y'));

    try {
        $carbon = Carbon::createFromFormat('Y-m', "$start_tahun-$start_bulan");
        $periodeLabel = $carbon->locale('id')->translatedFormat('F Y');
    } catch (\Exception $e) {
        $periodeLabel = now()->locale('id')->translatedFormat('F Y');
    }

    DB::beginTransaction();

    try {

        // ❌ JANGAN DELETE DATA
        // ImbalanRekap::where('bulan', $periodeLabel)->delete();

        // regenerate (akan update jika sudah ada)
        $result = $this->createRekapsForPeriode($periodeLabel);

        DB::commit();

        return redirect()->route('imbalan_rekap.index', $request->query())
            ->with('success', "Refresh berhasil untuk periode {$periodeLabel}");

    } catch (\Throwable $e) {

        DB::rollBack();

        return redirect()->route('imbalan_rekap.index')
            ->with('error', 'Gagal refresh: ' . $e->getMessage());
    }
}
    public function updateInline(Request $request)
{
    $request->validate([
        'id' => 'required|exists:imbalan_rekaps,id',
    ]);

    $rekap = \App\Models\ImbalanRekap::findOrFail($request->id);

    // ======================================================
    // 1. Ambil SEMUA field yang dikirim (bisa > 1 field)
    // ======================================================
    $fields = $request->except(['id', '_token']);

    if (empty($fields)) {
        return response()->json([
            'success' => false,
            'message' => 'Tidak ada field yang dikirim'
        ], 422);
    }

    // ======================================================
    // 2. Set semua field ke model
    // ======================================================
    foreach ($fields as $field => $value) {
        // Normalisasi nilai kosong
        if ($value === '') {
            $value = null;
        }
        $rekap->$field = $value;
    }

    // ======================================================
    // 3. Logika khusus field tertentu
    // ======================================================

    // AT HARI → hitung tambahan transport
    if (array_key_exists('at_hari', $fields)) {
        $hari = (int) ($rekap->at_hari ?? 0);
        $rekap->tambahan_transport = $hari * 24000;
    }

    // ======================================================
    // 4. Hitung ulang TOTAL IMBALAN
    // ======================================================
    $rekap->total_imbalan =
        ($rekap->imbalan_pokok ?? 0) +
        ($rekap->imbalan_lainnya ?? 0) +
        ($rekap->insentif_mentor ?? 0) +
        ($rekap->tambahan_transport ?? 0) +
        ($rekap->kekurangan ?? 0);

    // ======================================================
    // 5. Hitung ulang YANG DIBAYARKAN (FINAL)
    // ======================================================
    $rekap->yang_dibayarkan =
        $rekap->total_imbalan +
        ($rekap->jumlah_bagi_hasil ?? 0) -
        ($rekap->kelebihan ?? 0) -
        ($rekap->cicilan ?? 0);

    // ======================================================
    // 6. Bersihkan keterangan cicilan jika cicilan = 0
    // ======================================================
    if (
        array_key_exists('cicilan', $fields) ||
        array_key_exists('keterangan_cicilan', $fields)
    ) {
        if (($rekap->cicilan ?? 0) == 0) {
            $rekap->keterangan_cicilan = null;

            if ($rekap->catatan) {
                $parts = explode(' | ', $rekap->catatan);

                $parts = array_filter($parts, function ($part) {
                    $part = strtolower(trim($part));
                    return !str_contains($part, 'cicilan cash advance')
                        && !str_contains($part, 'angsuran ke');
                });

                $rekap->catatan = $parts
                    ? implode(' | ', $parts)
                    : null;
            }
        }
    }

    // ======================================================
    // 7. Simpan perubahan ke imbalan_rekaps
    // ======================================================
    $rekap->save();

    // ======================================================
// 8. UPDATE STATUS CICILAN – VERSI AMAN & SESUAI DATABASE
// ======================================================
if (array_key_exists('installment_id', $fields)) {
    $newInstallmentId = $rekap->installment_id;

    // Ambil nama relawan
    $namaRelawan = trim($rekap->nama ?? $rekap->profile?->nama ?? '');

    if ($namaRelawan) {
        // 1. Reset semua cicilan relawan ini yang sebelumnya lunas via potong gaji
        \App\Models\CashAdvanceInstallment::whereHas('cashAdvance', function ($q) use ($namaRelawan) {
            $q->whereRaw('TRIM(UPPER(nama)) = ?', [strtoupper($namaRelawan)]);
        })
        ->where('keterangan', 'like', '%Dipotong via Imbalan Relawan%')
        ->update([
            'status'         => 'belum',   // <-- SESUAIKAN DENGAN NILAI ASLI
            'sudah_dibayar'  => 0,
            'tanggal_bayar'  => null,
            'keterangan'     => null,
        ]);
    }

    // 2. Jika ada cicilan baru dipilih → tandai lunas
    if ($newInstallmentId) {
        $installment = \App\Models\CashAdvanceInstallment::find($newInstallmentId);

        if ($installment) {
            $periode = \Carbon\Carbon::now()->locale('id')->translatedFormat('F Y');
            if (request()->has('start_bulan') && request()->has('start_tahun')) {
                $periode = \Carbon\Carbon::createFromFormat('m-Y', request('start_bulan') . '-' . request('start_tahun'))
                    ->locale('id')
                    ->translatedFormat('F Y');
            }

            $installment->update([
                'status'         => 'lunas',   // <-- SESUAIKAN DENGAN NILAI ASLI
                'sudah_dibayar'  => 1,
                'tanggal_bayar'  => \Carbon\Carbon::now(),
                'keterangan'     => 'Dipotong via Imbalan Relawan periode ' . $periode,
            ]);
        }
    }
}
    // ======================================================
    // 9. Response untuk frontend (real-time update)
    // ======================================================
    return response()->json([
        'success'             => true,
        'message'             => 'Data berhasil diperbarui',
        'total_imbalan'       => $rekap->total_imbalan,
        'yang_dibayarkan'     => $rekap->yang_dibayarkan,
        'tambahan_transport'  => $rekap->tambahan_transport ?? 0,
        'at_hari'             => $rekap->at_hari ?? '',
        'cicilan'             => $rekap->cicilan ?? 0,
        'keterangan_cicilan'  => $rekap->keterangan_cicilan ?? '',
        'catatan'             => $rekap->catatan ?? '',
    ]);
}




    public function slip(Request $request, $id)
{
    $rekap = ImbalanRekap::findOrFail($id);
    $profile = Profile::where('nama', $rekap->nama)->first();

    // === MASA KERJA ===
    $masaKerja = '-';
    if ($profile?->tgl_masuk) {
        $masaKerja = $this->hitungMasaKerja($profile->tgl_masuk);
    } elseif ($rekap->masa_kerja && is_numeric($rekap->masa_kerja)) {
        $masaKerja = $this->formatMasaKerja((int) $rekap->masa_kerja);
    }

    // === PERIODE (12 bulan terakhir) ===
    $now = Carbon::now()->startOfMonth();
    $months = [];
    for ($i = 0; $i < 12; $i++) {
        $m = $now->copy()->subMonths($i);
        $months[] = [
            'value' => $m->format('Y-m'),
            'label' => $m->locale('id')->translatedFormat('F Y'),
        ];
    }

    // === TENTUKAN PERIODE YANG DITAMPILKAN ===
    $defaultPeriode = $rekap->bulan
        ? Carbon::createFromFormat('F Y', $rekap->bulan, 'id')->format('Y-m')
        : Carbon::now()->format('Y-m');

    $selectedPeriode = $request->query('periode') ?? $defaultPeriode;

    $periodeLabel = Carbon::createFromFormat('Y-m', $selectedPeriode)
        ->locale('id')
        ->translatedFormat('F Y');

    // === AMBIL POTONGAN ===
    $potongan = PotonganTunjangan::where('nama', $rekap->nama)
        ->where('bulan', $selectedPeriode)
        ->first();

    $totalPotongan = ($potongan->sakit ?? 0) +
        ($potongan->izin ?? 0) +
        ($potongan->alpa ?? 0) +
        ($potongan->tidak_aktif ?? 0) +
        ($potongan->cash_advance_nominal ?? 0) +
        ($potongan->lainnya ?? 0);

    // === HITUNG TOTAL PENDAPATAN ===
    $imbalanPokok = $rekap->imbalan_pokok ?? 0;
    $imbalanLainnya = $rekap->imbalan_lainnya ?? 0;
    $insentifMentor = $rekap->insentif_mentor ?? 0;
    $tambahanTransport = $rekap->tambahan_transport ?? 0;

    $totalPendapatan = $imbalanPokok + $imbalanLainnya + $insentifMentor + $tambahanTransport;
    $yangDibayarkan = $totalPendapatan - $totalPotongan;

    // === TANGGAL MASUK ===
    $tglMasuk = $profile?->tgl_masuk
        ? Carbon::parse($profile->tgl_masuk)->locale('id')->translatedFormat('d F Y')
        : '-';

    // === CEK APAKAH USER ADALAH ADMIN ===
    $isAdmin = auth()->check() && (
        auth()->user()->role === 'admin' || 
        auth()->user()->is_admin || 
        auth()->user()->hasRole('admin') // sesuaikan dengan sistem role kamu
    );

    // === AMBIL SEMUA UNIT (hanya jika admin) ===
    $units = $isAdmin ? \App\Models\Unit::orderBy('biMBA_unit')->get() : collect();

    // === AMBIL SEMUA REKAP UNTUK DROPDOWN NAMA (tambah unit_id agar bisa filter) ===
    $allRekaps = ImbalanRekap::orderBy('nama')->get(['id', 'nama', 'unit_id']);

    return view('imbalan_rekap.slip', [
        'rekap'             => $rekap,
        'profile'           => $profile,
        'masaKerja'         => $masaKerja,
        'periode'           => $periodeLabel,
        'periodeValue'      => $selectedPeriode,
        'periodeOptions'    => $months,
        'tanggalMasuk'      => $tglMasuk,

        // UNIT & CABANG
        'unit'              => $rekap->biMBA_unit ?? $rekap->unit ?? 'biMBA AIUEO',
        'no_cabang'         => $rekap->no_cabang ?? '-',

        // REKENING
        'noRekening'        => $profile?->no_rekening ?? '-',
        'bank'              => $profile?->bank ?? '-',
        'atasNama'          => $profile?->nama_rekening ?? $rekap->nama,

        // DATA HITUNGAN
        'potongan'          => $potongan,
        'totalPotongan'     => $totalPotongan,
        'yangDibayarkan'    => $yangDibayarkan,

        // DROPDOWN NAMA & FILTER
        'allRekaps'         => $allRekaps,

        // INI YANG WAJIB DITAMBAHKAN!
        'isAdmin'           => $isAdmin,
        'units'             => $units,
    ]);
}

    public function pdf(Request $request, $id)
{
    $rekap = ImbalanRekap::findOrFail($id);
    $profile = Profile::where('nama', $rekap->nama)->first();

    // Ambil periode dari query atau fallback ke bulan rekap
    $periodeValue = $request->query('periode');
    $bulan = $periodeValue
        ? Carbon::createFromFormat('Y-m', $periodeValue)->format('Y-m')
        : ($rekap->bulan ?? Carbon::now()->subMonth()->format('Y-m'));

    // Ambil potongan tunjangan (sudah bagus, tetap pakai)
    $potongan = null;
    if ($rekap->pendapatan_id) {
        $potongan = PotonganTunjangan::where('pendapatan_id', $rekap->pendapatan_id)
            ->where('bulan', $bulan)
            ->first();
    }
    if (!$potongan) {
        $potongan = PotonganTunjangan::where('nama', $rekap->nama)
            ->where('bulan', $bulan)
            ->first();
    }
    if (!$potongan && $profile?->nik) {
        $potongan = PotonganTunjangan::where('nik', $profile->nik)
            ->where('bulan', $bulan)
            ->first();
    }

    // Ambil adjustment untuk periode ini
    $totalKekuranganAdj = 0;
    $totalKelebihanAdj = 0;
    $keteranganKekuranganAdj = null;
    $keteranganKelebihanAdj = null;

    try {
        $carbonPeriode = Carbon::createFromFormat('Y-m', $bulan);
        $month = $carbonPeriode->month;
        $year = $carbonPeriode->year;

        $adjustments = Adjustment::whereRaw('TRIM(UPPER(nama)) = ?', [strtoupper(trim($rekap->nama))])
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        foreach ($adjustments as $adj) {
            $nominal = (float) $adj->nominal;
            $cleanType = strtolower(trim($adj->type ?? ''));

            if (str_contains($cleanType, 'tambah') || $cleanType === 'tambahan') {
                $totalKekuranganAdj += $nominal;
                $keteranganKekuranganAdj = $keteranganKekuranganAdj 
                    ? $keteranganKekuranganAdj . " | " . trim($adj->keterangan ?? '') 
                    : trim($adj->keterangan ?? '');
            } elseif (str_contains($cleanType, 'potong') || $cleanType === 'potongan') {
                $totalKelebihanAdj += $nominal;
                $keteranganKelebihanAdj = $keteranganKelebihanAdj 
                    ? $keteranganKelebihanAdj . " | " . trim($adj->keterangan ?? '') 
                    : trim($adj->keterangan ?? '');
            }
        }
    } catch (\Exception $e) {
        \Log::warning("Gagal ambil adjustment di PDF: " . $e->getMessage());
    }

    // Hitung total dengan adjustment
    $pokok      = $rekap->imbalan_pokok ?? 0;
    $lainnya    = $rekap->imbalan_lainnya ?? 0;
    $insentif   = $rekap->insentif_mentor ?? 0;
    $transport  = $rekap->tambahan_transport ?? 0;

    // Pendapatan: + kekurangan adjustment (tambahan dibayar)
    $totalPendapatan = $pokok + $lainnya + $insentif + $transport + $totalKekuranganAdj;

    // Potongan tetap
    $totalPotonganTetap = 0;
    if ($potongan) {
        $totalPotonganTetap += ($potongan->sakit ?? 0)
                             + ($potongan->izin ?? 0)
                             + ($potongan->alpa ?? 0)
                             + ($potongan->tidak_aktif ?? 0)
                             + ($potongan->cash_advance_nominal ?? 0)
                             + ($potongan->lainnya ?? 0);
    }

    $cicilanNilai = $rekap->cicilan ?? 0;
    $cicilanKeterangan = $rekap->keterangan_cicilan ?? 'Cicilan Cash Advance';

    // Potongan total: tetap + cicilan + kelebihan adjustment (potongan)
    $totalPotongan = $totalPotonganTetap + $cicilanNilai + $totalKelebihanAdj;

    $yangDibayarkan = $totalPendapatan - $totalPotongan;

    // Unit & cabang
    $unitName = $rekap->bimba_unit ?? $rekap->biMBA_unit ?? $profile?->bimba_unit ?? $profile?->unit ?? '-';
    $noCabang = $rekap->no_cabang ?? $profile?->no_cabang ?? null;
    $unitDisplay = ($noCabang && $unitName !== '-') ? $noCabang . ' - ' . strtoupper($unitName) : strtoupper($unitName);

    $periodeLabel = $periodeValue
        ? Carbon::createFromFormat('Y-m', $periodeValue)->locale('id')->translatedFormat('F Y')
        : ($rekap->bulan ?? Carbon::now()->subMonth()->locale('id')->translatedFormat('F Y'));

    Carbon::setLocale('id');

    $tanggalMasukFormatted = $profile?->tgl_masuk
        ? Carbon::parse($profile->tgl_masuk)->translatedFormat('d F Y')
        : '-';

    $data = [
        'rekap'                   => $rekap,
        'profile'                 => $profile,
        'masaKerja'               => $this->formatMasaKerja($profile?->masa_kerja ?? $rekap->masa_kerja ?? 0),
        'periode'                 => $periodeLabel,
        'periodeValue'            => $periodeValue,
        'tanggalSekarang'         => Carbon::now()->translatedFormat('d F Y'),
        'tanggalMasukFormatted'   => $tanggalMasukFormatted,
        'biMBA_unit'              => $unitDisplay,
        'no_cabang'               => $noCabang ?? '-',
        'noRekening'              => $profile?->no_rekening ?? '-',
        'bank'                    => $profile?->bank ?? '-',
        'atasNama'                => $profile?->nama_rekening ?? $rekap->nama ?? '-',
        'potongan'                => $potongan,
        'cicilanNilai'            => $cicilanNilai,
        'cicilanKeterangan'       => $cicilanKeterangan,
        'totalPendapatan'         => $totalPendapatan,
        'totalPotongan'           => $totalPotongan,
        'yangDibayarkan'          => $yangDibayarkan,
        'totalKekuranganAdj'      => $totalKekuranganAdj,
        'totalKelebihanAdj'       => $totalKelebihanAdj,
        'keteranganKekuranganAdj' => $keteranganKekuranganAdj,
        'keteranganKelebihanAdj'  => $keteranganKelebihanAdj,
        'isPdf'                   => true,
    ];

    $pdf = Pdf::loadView('imbalan_rekap.slip_pdf', $data)->setPaper('a5', 'landscape');

    $fileName = 'slip-imbalan-' .
        preg_replace('/[^A-Za-z0-9\-]/', '-', strtolower($rekap->nama ?? 'rekap')) .
        '-' . $periodeLabel . '.pdf';

    return $pdf->stream($fileName);
}


    public function generateMonth(Request $request)
{
    // Ambil periode dari query string (GET), sama seperti refresh
    $start_bulan = $request->get('start_bulan', now()->format('m'));
    $start_tahun = $request->get('start_tahun', now()->format('Y'));

    try {
        $carbon = Carbon::createFromFormat('Y-m', "$start_tahun-$start_bulan");
        $labelBulan = $carbon->locale('id')->translatedFormat('F Y');
    } catch (\Exception $e) {
        $labelBulan = now()->locale('id')->translatedFormat('F Y');
    }

    $result = $this->createRekapsForPeriode($labelBulan);

    return redirect()
        ->route('imbalan_rekap.index', $request->query())
        ->with('success', "Berhasil generate ulang {$result['created']} data untuk periode {$labelBulan}");
}

    /**
     * VERSI FINAL — waktu_bln PASTI dari durasi_kegiatan!
     */



    public function createRekapsForPeriode(string $labelBulan): array
{
    $created = 0;
    $updated = 0;
    $errors  = [];

    $labelBulan = trim($labelBulan);

    // Parse bulan dengan locale Indonesia
    try {
        $startOfMonth = Carbon::createFromFormat('F Y', $labelBulan, 'id')->startOfMonth();
    } catch (\Throwable $e) {
        try {
            $startOfMonth = Carbon::createFromFormat('M Y', $labelBulan, 'id')->startOfMonth();
        } catch (\Throwable $e) {
            $startOfMonth = Carbon::now()->startOfMonth();
        }
    }
    $endOfMonth = $startOfMonth->copy()->endOfMonth();

    $bulanFormatYm = $startOfMonth->format('Y-m');

    $profiles = Profile::orderBy('nama')->get();

    foreach ($profiles as $p) {
        $namaKaryawan = trim($p->nama ?? 'Unknown');

        try {
            $rekap = ImbalanRekap::firstOrNew([
                'nama'  => $p->nama,
                'bulan' => $labelBulan
            ]);

            $rekap->bulan = $labelBulan;
            $rekap->nama  = $p->nama;
            $isNew = !$rekap->exists;

            // ===================================================================
            // 1. BASIC INFO
            // ===================================================================
            $rawStatus = $p->status_karyawan ?? $p->status ?? $rekap->status;
            if (Schema::hasColumn('imbalan_rekaps', 'status')) {
                $rekap->status = $rawStatus;
            }
            $isMagang = !empty($rawStatus) && strcasecmp(trim($rawStatus), 'magang') === 0;

            if (Schema::hasColumn('imbalan_rekaps', 'departemen')) {
                $rekap->departemen = $p->departemen ?? $rekap->departemen;
            }
            if (Schema::hasColumn('imbalan_rekaps', 'posisi')) {
                $rekap->posisi = $p->jabatan ?? $p->posisi ?? $rekap->posisi;
            }
            if (Schema::hasColumn('imbalan_rekaps', 'biMBA_unit')) {
                $rekap->biMBA_unit = $p->biMBA_unit ?? $p->bimba_unit ?? $p->unit ?? $rekap->biMBA_unit;
            }
            if (Schema::hasColumn('imbalan_rekaps', 'no_cabang')) {
                $rekap->no_cabang = $p->no_cabang ?? $rekap->no_cabang;
            }
            if (Schema::hasColumn('imbalan_rekaps', 'unit')) {
                $rekap->unit = $rekap->biMBA_unit ?? $p->unit ?? $rekap->unit;
            }
            if (Schema::hasColumn('imbalan_rekaps', 'ktr')) {
                $rekap->ktr = $isMagang ? null : ($p->ktr ?? $p->ktr_tambahan ?? $rekap->ktr);
            }

            // ===================================================================
            // 2. MASA KERJA
            // ===================================================================
            if (Schema::hasColumn('imbalan_rekaps', 'masa_kerja')) {
                if (!empty($p->masa_kerja) && is_numeric($p->masa_kerja)) {
                    $rekap->masa_kerja = (int) $p->masa_kerja;
                } elseif (!empty($p->tgl_masuk)) {
                    try {
                        $tglMasuk = Carbon::parse($p->tgl_masuk)->startOfDay();
                        $now = Carbon::now()->startOfDay();
                        $years = $now->diffInYears($tglMasuk);
                        $months = $now->copy()->subYears($years)->diffInMonths($tglMasuk);
                        $rekap->masa_kerja = ($years * 12) + $months;
                    } catch (Throwable $e) {
                        $rekap->masa_kerja = null;
                    }
                }
            }

            // ===================================================================
            // 3. TARGET JAM & DURASI (untuk info saja, tidak memengaruhi pokok)
            // ===================================================================
            $finalWaktuMgg = 40;
            $finalWaktuBln = 160;

            if (!empty($p->rb) && preg_match('/RB0*(\d+)/i', trim($p->rb), $m)) {
                $jamMingguan = (int) $m[1];
                $durasi = DurasiKegiatan::where('waktu_mgg', $jamMingguan)->first();
                if ($durasi) {
                    $finalWaktuMgg = (int) $durasi->waktu_mgg;
                    $finalWaktuBln = (int) $durasi->waktu_bln;
                } else {
                    $map = [20 => 80, 25 => 100, 30 => 120, 35 => 140, 40 => 160];
                    $finalWaktuMgg = $jamMingguan;
                    $finalWaktuBln = $map[$jamMingguan] ?? 160;
                }
            } elseif (!empty($p->durasi_kegiatan_id)) {
                $durasi = DurasiKegiatan::find($p->durasi_kegiatan_id);
                if ($durasi) {
                    $finalWaktuMgg = (int) $durasi->waktu_mgg;
                    $finalWaktuBln = (int) $durasi->waktu_bln;
                }
            }

            // Durasi kerja untuk tampilan/info (tidak memengaruhi pokok)
            $durasiKerjaFinal = (float) $finalWaktuBln;

            // Pokok SELALU FULL
            $imbalanPokokPenuh = (float) ($p->imbalan_pokok_default ?? $p->rp ?? 900000);
            $imbalanPokokDibayar = $imbalanPokokPenuh; // <--- FULL, tidak dipotong absensi

            // Persentase ditetapkan 100% karena pokok full
            $persentase = 100.00;

            if ($isMagang) {
                $finalWaktuMgg = $finalWaktuBln = $durasiKerjaFinal = $persentase = $imbalanPokokPenuh = $imbalanPokokDibayar = 0;
            }

            // ===================================================================
            // 4. ABSENSI → HANYA UNTUK CATATAN, BUKAN UNTUK KURANGI POKOK
            // ===================================================================
            $jamPerHari = match ($finalWaktuMgg) {
                40 => 8.0, 35 => 7.0, 30 => 6.0, 25 => 5.0, 20 => 4.0, default => 8.0
            };

            $totalJamPC = 0.0;
            $hitungStatus = ['sakit' => 0, 'izin' => 0, 'alpa' => 0, 'tidak_aktif' => 0];

            $absensis = AbsensiVolunteer::select('tanggal', 'jam_keluar', 'offduty', 'status')
                ->where(fn($q) => $q->where('nik', $p->nik)->orWhere('nama_relawan', 'like', '%' . addcslashes($p->nama, '%_') . '%'))
                ->whereBetween('tanggal', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->get();

            foreach ($absensis as $a) {

    $status = strtoupper(trim($a->status ?? ''));

    if (str_contains($status, 'SAKIT')) {
        $hitungStatus['sakit']++;
        continue;
    }

    if (str_contains($status, 'IZIN')) {
        $hitungStatus['izin']++;
        continue;
    }

    if (str_contains($status, 'ALPA') || $status === 'TANPA KETERANGAN') {
        $hitungStatus['alpa']++;
        continue;
    }

    if (str_contains($status, 'TIDAK AKTIF') || str_contains($status, 'NON')) {
        $hitungStatus['tidak_aktif']++;
        continue;
    }

    // hanya hitung pulang cepat jika HADIR
    if (!empty($a->jam_keluar) && !empty($a->offduty)) {
        try {

            $jamKeluar = Carbon::createFromFormat('H:i', $a->jam_keluar);
            $offDuty   = Carbon::createFromFormat('H:i', $a->offduty);

            if ($jamKeluar && $offDuty && $jamKeluar->lessThan($offDuty)) {

                $menitPC = $jamKeluar->diffInMinutes($offDuty);

                if ($menitPC >= 10) {
                    $totalJamPC += round($menitPC / 60, 2);
                }

            }

        } catch (\Throwable $e) {}
    }
}

            // Hitung potongan absensi hanya untuk catatan (tidak memengaruhi pokok)
            $totalPotonganAbsensi = array_sum($hitungStatus) * $jamPerHari;

            // ===================================================================
            // 5. CATATAN ABSENSI (untuk info saja)
            // ===================================================================
            $catatanParts = [];

            if ($hitungStatus['sakit'] > 0)       $catatanParts[] = "Sakit: {$hitungStatus['sakit']} hari";
            if ($hitungStatus['izin'] > 0)        $catatanParts[] = "Izin: {$hitungStatus['izin']} hari";
            if ($hitungStatus['alpa'] > 0)        $catatanParts[] = "Alpa: {$hitungStatus['alpa']} hari";
            if ($hitungStatus['tidak_aktif'] > 0) $catatanParts[] = "Tidak Aktif: {$hitungStatus['tidak_aktif']} hari";
            if ($totalJamPC > 0)                  $catatanParts[] = "Pulang cepat: -{$totalJamPC} jam";

           // ===================================================================
// 6. ADJUSTMENT - Ambil keterangan langsung dari adjustment.keterangan
// ===================================================================
$adjustments = Adjustment::whereRaw('TRIM(UPPER(nama)) = ?', [strtoupper(trim($p->nama))])
    ->where('month', $startOfMonth->month)
    ->where('year', $startOfMonth->year)
    ->get();

// Reset semua field adjustment
$rekap->kekurangan              = 0;
$rekap->kelebihan               = 0;
$rekap->bulan_kekurangan        = 0;
$rekap->bulan_kelebihan         = 0;
$rekap->keterangan_kekurangan    = null;
$rekap->keterangan_kelebihan     = null;

$adjustmentNotes = [];

// Debugging
if ($adjustments->isNotEmpty()) {
    \Log::info("Adjustment ditemukan untuk {$p->nama} bulan {$startOfMonth->month}/{$startOfMonth->year}", [
        'count' => $adjustments->count(),
        'types' => $adjustments->pluck('type')->toArray(),
        'details' => $adjustments->map(fn($adj) => [
            'type' => $adj->type,
            'nominal' => $adj->nominal,
            'keterangan' => $adj->keterangan,
            'month' => $adj->month,
        ])->toArray(),
    ]);
} else {
    \Log::info("Tidak ada adjustment untuk {$p->nama} bulan {$startOfMonth->month}/{$startOfMonth->year}");
}

foreach ($adjustments as $adj) {
    $nominal    = (float) $adj->nominal;
    if ($nominal <= 0) continue;

    $rawType    = trim($adj->type ?? '');
    $cleanType  = strtolower($rawType);
    $adjKeterangan = trim($adj->keterangan ?? 'Tanpa keterangan'); // ← AMBIL LANGSUNG DARI SINI
    $monthName  = $adj->month_name ?? $startOfMonth->locale('id')->translatedFormat('F');
    $adjMonth   = (int) $adj->month;

    $displayNominal = number_format($nominal, 0, ',', '.');
    $note = "Rp {$displayNominal} ({$monthName} {$adj->year}) - {$adjKeterangan}";

    // LOGIKA DIBALIK SESUAI BISNIS KAMU
    if (
        str_contains($cleanType, 'tambah') ||
        str_contains($cleanType, 'lebih') ||
        str_contains($cleanType, 'bonus') ||
        $cleanType === 'tambahan' ||
        str_contains($cleanType, 'tmbhn') ||
        str_contains($cleanType, 'plus')
    ) {
        // "tambahan" → kekurangan
        $rekap->kekurangan += $nominal;
        $rekap->bulan_kekurangan = $adjMonth;
        
        // SIMPAN KETERANGAN LANGSUNG KE KOLOM KHUSUS
        $rekap->keterangan_kekurangan = $rekap->keterangan_kekurangan 
            ? $rekap->keterangan_kekurangan . " | " . $adjKeterangan 
            : $adjKeterangan;

        $adjustmentNotes[] = "Tambahan (potongan): {$note} [Bulan {$adjMonth}]";
    } 
    elseif (
        str_contains($cleanType, 'potong') ||
        str_contains($cleanType, 'kurang') ||
        str_contains($cleanType, 'minus') ||
        $cleanType === 'potongan' ||
        str_contains($cleanType, 'pengurangan') ||
        str_contains($cleanType, 'deduksi')
    ) {
        // "potongan" → kelebihan
        $rekap->kelebihan += $nominal;
        $rekap->bulan_kelebihan = $adjMonth;
        
        // SIMPAN KETERANGAN LANGSUNG KE KOLOM KHUSUS
        $rekap->keterangan_kelebihan = $rekap->keterangan_kelebihan 
            ? $rekap->keterangan_kelebihan . " | " . $adjKeterangan 
            : $adjKeterangan;

        $adjustmentNotes[] = "Potongan (bonus): {$note} [Bulan {$adjMonth}]";
    } 
    else {
        $adjustmentNotes[] = "Adjustment ({$rawType}): {$note} [Bulan {$adjMonth}] [TYPE TIDAK DIKENALI]";
        \Log::warning("Type adjustment tidak dikenali", [
            'nama'       => $p->nama,
            'type_raw'   => $rawType,
            'nominal'    => $nominal,
            'bulan_adj'  => $adjMonth,
        ]);
    }
}

// Gabung ke catatan umum (opsional, tetap simpan ringkasan)
if (!empty($adjustmentNotes)) {
    $rekap->catatan = $rekap->catatan 
        ? rtrim($rekap->catatan, ' | ') . " | " . implode(" | ", $adjustmentNotes)
        : implode(" | ", $adjustmentNotes);
}

// Log akhir untuk verifikasi
\Log::info("Nilai adjustment setelah proses untuk {$p->nama}", [
    'kekurangan'               => $rekap->kekurangan,
    'bulan_kekurangan'         => $rekap->bulan_kekurangan,
    'keterangan_kekurangan'     => $rekap->keterangan_kekurangan,
    'kelebihan'                => $rekap->kelebihan,
    'bulan_kelebihan'          => $rekap->bulan_kelebihan,
    'keterangan_kelebihan'      => $rekap->keterangan_kelebihan,
    'catatan_akhir'            => $rekap->catatan,
]);

            // ===================================================================
            // 7. CICILAN CASH ADVANCE → 100% MANUAL
            // ===================================================================
            $rekap->cicilan = $rekap->cicilan ?? 0;
            $rekap->keterangan_cicilan = $rekap->keterangan_cicilan ?? null;

            if (($rekap->cicilan ?? 0) == 0) {
                $rekap->keterangan_cicilan = null;
                $catatanParts = array_filter($catatanParts, function($part) {
                    $lower = strtolower(trim($part));
                    return !str_contains($lower, 'cicilan cash advance') && 
                           !str_contains($lower, 'angsuran ke');
                });
            }

            // ===================================================================
            // 8. ASSIGN FIELD LAIN
            // ===================================================================
            if (Schema::hasColumn('imbalan_rekaps', 'waktu_mgg')) $rekap->waktu_mgg = $p->rb ?? 'RB ' . $finalWaktuMgg;
            if (Schema::hasColumn('imbalan_rekaps', 'waktu_bln')) $rekap->waktu_bln = $finalWaktuBln . ' Jam';
            if (Schema::hasColumn('imbalan_rekaps', 'durasi_kerja')) $rekap->durasi_kerja = $durasiKerjaFinal;
            if (Schema::hasColumn('imbalan_rekaps', 'persen')) $rekap->persen = $persentase;
            if (Schema::hasColumn('imbalan_rekaps', 'kode_rb')) $rekap->kode_rb = $p->rb ?? 'RB' . $finalWaktuMgg;
            if (Schema::hasColumn('imbalan_rekaps', 'imbalan_pokok')) $rekap->imbalan_pokok = $imbalanPokokDibayar;

            // Field manual pertahankan nilai lama atau default
            $rekap->imbalan_lainnya     = $rekap->imbalan_lainnya     ?? $p->imbalan_lainnya_default ?? 0;
            $rekap->insentif_mentor     = $rekap->insentif_mentor     ?? $p->insentif_mentor ?? 0;
            $rekap->tambahan_transport  = $rekap->tambahan_transport  ?? $p->tambahan_transport ?? 0;
            $rekap->cicilan             = $rekap->cicilan             ?? $p->cicilan_default ?? 0;
            $rekap->jumlah_murid        = $rekap->jumlah_murid        ?? $p->jumlah_murid ?? 0;
            $rekap->jumlah_spp          = $rekap->jumlah_spp          ?? $p->jumlah_spp ?? 0;
            $rekap->kekurangan_spp      = $rekap->kekurangan_spp      ?? $p->kekurangan_spp ?? 0;
            $rekap->kelebihan_spp       = $rekap->kelebihan_spp       ?? $p->kelebihan_spp ?? 0;
            $rekap->jumlah_bagi_hasil   = $rekap->jumlah_bagi_hasil   ?? $p->jumlah_bagi_hasil ?? 0;

            // ===================================================================
            // 9. HITUNG TOTAL_IMBALAN & YANG_DIBAYARKAN
            // ===================================================================
            if (Schema::hasColumn('imbalan_rekaps', 'total_imbalan')) {
                $rekap->total_imbalan = 
                    ($rekap->imbalan_pokok ?? 0) +               // FULL
                    ($rekap->imbalan_lainnya ?? 0) +
                    ($rekap->insentif_mentor ?? 0) +
                    ($rekap->tambahan_transport ?? 0) +
                    ($rekap->kekurangan ?? 0);
            }

            if (Schema::hasColumn('imbalan_rekaps', 'yang_dibayarkan')) {
                // Ambil potongan tunjangan dari tabel potongan_tunjangans
                $potongan = PotonganTunjangan::where('nama', $rekap->nama)
                    ->where('bulan', $bulanFormatYm)
                    ->first();

                $potonganNominal = $potongan ? (float) ($potongan->total ?? 0) : 0;

                // Simpan nominal potongan untuk tampilan & perhitungan
                if (Schema::hasColumn('imbalan_rekaps', 'potongan_tunjangan_nominal')) {
                    $rekap->potongan_tunjangan_nominal = $potonganNominal;
                }

                $rekap->yang_dibayarkan = 
                    $rekap->total_imbalan +
                    ($rekap->jumlah_bagi_hasil ?? 0) -
                    ($rekap->kelebihan ?? 0) -
                    ($rekap->cicilan ?? 0) -
                    $potonganNominal;
            }

            // ===================================================================
            // 10. CATATAN FINAL
            // ===================================================================
            if (!empty($catatanParts)) {
                $catatanParts = array_unique($catatanParts);
                $rekap->catatan = implode(' | ', $catatanParts);
            } else {
                $rekap->catatan = null;
            }

            $rekap->save();

            $isNew ? $created++ : $updated++;

        } catch (Throwable $ex) {
            $errors[] = "Error {$namaKaryawan}: " . $ex->getMessage();
            \Log::error('Rekap Imbalan Gagal', [
                'nama'  => $namaKaryawan,
                'bulan' => $labelBulan,
                'error' => $ex->getMessage(),
                'file'  => $ex->getFile(),
                'line'  => $ex->getLine(),
            ]);
        }
    }

    return [
        'created' => $created,
        'updated' => $updated,
        'total'   => $created + $updated,
        'errors'  => $errors,
        'message' => "Rekap {$labelBulan} selesai. {$created} dibuat baru, {$updated} diperbarui."
    ];
}



    private function parseRbDurasi(?string $durasi): array
    {
        $result = ['rb' => null, 'hours' => null];
        if (empty($durasi) || !is_string($durasi))
            return $result;

        $s = trim(preg_replace('/[[:punct:]]+/', ' ', $durasi));
        $s = preg_replace('/\s+/', ' ', $s);

        if (preg_match('/\bR\s*B\s*0*([0-9]{1,4})\b/i', $s, $m)) {
            $result['rb'] = 'RB' . $m[1];
        } elseif (preg_match('/\bRB0*([0-9]{1,4})\b/i', $s, $m)) {
            $result['rb'] = 'RB' . $m[1];
        }

        preg_match_all('/\b([0-9]{1,5})\b/', $s, $allNums);
        if (!empty($allNums[1])) {
            $nums = array_map('intval', $allNums[1]);
            $result['hours'] = max($nums);
        }

        return $result;
    }

   public function indexSlip(Request $request)
{
    $user = auth()->user();

    /* ======================================================
     * PARAMETER URL
     * ====================================================== */
    $rekapId  = $request->get('rekap_id');
    $unitId   = $request->get('unit_id');
    $periode  = $request->get('periode');

    /* ======================================================
     * CEK ADMIN
     * ====================================================== */
    $isAdmin = $user && (
        $user->role === 'admin' ||
        ($user->is_admin ?? false)
    );

    /* ======================================================
     * AMBIL UNIT DARI USER LOGIN (NON ADMIN)
     * ====================================================== */
    $userUnit =
        $user->biMBA_unit
        ?? $user->bimba_unit
        ?? $user->unit_bimba
        ?? $user->unit
        ?? null;

    /* ======================================================
     * NORMALISASI PERIODE (Y-m)
     * ====================================================== */
    $normalizedPeriode = null;
    $bulanLabel = null;

    if ($periode) {
        try {
            $normalizedPeriode = Carbon::createFromFormat('Y-m', $periode)->format('Y-m');
        } catch (\Exception $e) {}
    }

    if (!$normalizedPeriode) {
        $latest = ImbalanRekap::latest()->first();
        if ($latest?->bulan) {
            try {
                $normalizedPeriode = Carbon::createFromFormat('F Y', $latest->bulan, 'id')->format('Y-m');
            } catch (\Exception $e) {}
        }
    }

    if ($normalizedPeriode) {
        $bulanLabel = Carbon::createFromFormat('Y-m', $normalizedPeriode)
            ->locale('id')
            ->translatedFormat('F Y');
    }

    /* ======================================================
     * RESOLVE UNIT FINAL (STRING)
     * ====================================================== */
    $displayUnit = null;

    // ADMIN → dari dropdown
    if ($isAdmin && $unitId) {
        $displayUnit = optional(\App\Models\Unit::find($unitId))->biMBA_unit;
    }

    // NON ADMIN → dari akun login
    if (!$isAdmin && $userUnit) {
        $displayUnit = $userUnit;
    }

    // FALLBACK → dari rekap
    if (!$displayUnit && $rekapId) {
        $rekapTmp = ImbalanRekap::find($rekapId);
        if ($rekapTmp) {
            $displayUnit = $rekapTmp->biMBA_unit;
        }
    }

    // LAST FALLBACK
    $displayUnit = $displayUnit ?: 'biMBA AIUEO';

    /* ======================================================
     * DROPDOWN UNIT (ADMIN)
     * ====================================================== */
    $units = $isAdmin
        ? \App\Models\Unit::orderBy('biMBA_unit')->get()
        : collect();

    /* ======================================================
     * DROPDOWN NAMA RELAWAN
     * ====================================================== */
    $allRekaps = ImbalanRekap::where('biMBA_unit', $displayUnit)
        ->orderBy('nama')
        ->get(['id', 'nama']);

    /* ======================================================
     * REKAP TERPILIH
     * ====================================================== */
    $rekap = $rekapId ? ImbalanRekap::find($rekapId) : null;

    /* ======================================================
     * DATA DETAIL
     * ====================================================== */
    $profile = $rekap ? Profile::where('nama', $rekap->nama)->first() : null;

    $potongan = $rekap
        ? PotonganTunjangan::where('nama', $rekap->nama)
            ->where('bulan', $normalizedPeriode)
            ->first()
        : null;

    $cicilan = collect();
    $totalCicilan = (int) ($rekap->cicilan ?? 0);

    if ($totalCicilan > 0) {
        $cicilan->push((object)[
            'keterangan' => $rekap->keterangan_cicilan ?? 'Cicilan Cash Advance',
            'jumlah'     => $totalCicilan,
        ]);
    }

    $masaKerja = '-';
    $tanggalMasuk = '-';

    if ($profile?->tgl_masuk) {
        $masaKerja = $this->hitungMasaKerja($profile->tgl_masuk);
        $tanggalMasuk = Carbon::parse($profile->tgl_masuk)->translatedFormat('d F Y');
    }

    // === AMBIL ADJUSTMENT UNTUK PERIODE INI ===
    $adjustments = collect();
    $totalKekuranganAdj = 0;
    $totalKelebihanAdj = 0;
    $keteranganKekuranganAdj = null;
    $keteranganKelebihanAdj = null;

    if ($rekap && $normalizedPeriode) {
        try {
            $carbonPeriode = Carbon::createFromFormat('Y-m', $normalizedPeriode);
            $month = $carbonPeriode->month;
            $year = $carbonPeriode->year;

            $adjustments = Adjustment::whereRaw('TRIM(UPPER(nama)) = ?', [strtoupper(trim($rekap->nama))])
                ->where('month', $month)
                ->where('year', $year)
                ->get();

            foreach ($adjustments as $adj) {
                $nominal = (float) $adj->nominal;
                $cleanType = strtolower(trim($adj->type ?? ''));

                if (str_contains($cleanType, 'tambah') || $cleanType === 'tambahan') {
                    $totalKekuranganAdj += $nominal;
                    $keteranganKekuranganAdj = $keteranganKekuranganAdj 
                        ? $keteranganKekuranganAdj . " | " . trim($adj->keterangan ?? '') 
                        : trim($adj->keterangan ?? '');
                } elseif (str_contains($cleanType, 'potong') || $cleanType === 'potongan') {
                    $totalKelebihanAdj += $nominal;
                    $keteranganKelebihanAdj = $keteranganKelebihanAdj 
                        ? $keteranganKelebihanAdj . " | " . trim($adj->keterangan ?? '') 
                        : trim($adj->keterangan ?? '');
                }
            }
        } catch (\Exception $e) {
            // Skip jika error parse periode
            \Log::warning("Gagal ambil adjustment di slip: " . $e->getMessage());
        }
    }

    // === HITUNG TOTAL PENDAPATAN & POTONGAN (SUDAH TERMASUK ADJUSTMENT) ===
    $imbalanPokok = $rekap->imbalan_pokok ?? 0;
    $imbalanLainnya = $rekap->imbalan_lainnya ?? 0;
    $insentifMentor = $rekap->insentif_mentor ?? 0;
    $tambahanTransport = $rekap->tambahan_transport ?? 0;

    $totalPendapatan = $imbalanPokok + $imbalanLainnya + $insentifMentor + $tambahanTransport + $totalKelebihanAdj;

    $totalPotonganTetap = $potongan ? (float) ($potongan->total ?? 0) : 0;
    $totalPotongan = $totalPotonganTetap + $totalCicilan + $totalKekuranganAdj;

    $yangDibayarkan = $totalPendapatan - $totalPotongan;

    /* ======================================================
     * RETURN VIEW
     * ====================================================== */
    return view('imbalan_rekap.slip_index', [
        'rekap'                   => $rekap,
        'profile'                 => $profile,
        'potongan'                => $potongan,
        'cicilan'                 => $cicilan,
        'totalCicilan'            => $totalCicilan,
        'allRekaps'               => $allRekaps,
        'periodeOptions'          => $this->getPeriodeOptions(),
        'periodeValue'            => $normalizedPeriode,
        'periode'                 => $bulanLabel,
        'masaKerja'               => $masaKerja,
        'tanggalMasuk'            => $tanggalMasuk,
        'noRekening'              => $profile?->no_rekening ?? '-',
        'bank'                    => $profile?->bank ?? '-',
        'atasNama'                => $profile?->nama_rekening ?? $rekap?->nama,
        'unit'                    => $displayUnit,
        'isAdmin'                 => $isAdmin,
        'units'                   => $units,

        // DATA ADJUSTMENT BARU
        'adjustments'             => $adjustments,
        'totalKekuranganAdj'      => $totalKekuranganAdj,
        'totalKelebihanAdj'       => $totalKelebihanAdj,
        'keteranganKekuranganAdj' => $keteranganKekuranganAdj,
        'keteranganKelebihanAdj'  => $keteranganKelebihanAdj,
        'totalPotongan'           => $totalPotongan,
        'totalPendapatan'         => $totalPendapatan,
        'yangDibayarkan'          => $yangDibayarkan,
    ]);
}

/* ======================================================
 * OPSI PERIODE (DROPDOWN BULAN)
 * ====================================================== */
private function getPeriodeOptions(): array
{
    $out = [];

    for ($i = 0; $i < 12; $i++) {
        $d = now()->subMonths($i);
        $out[] = [
            'value' => $d->format('Y-m'),
            'label' => $d->locale('id')->translatedFormat('F Y'),
        ];
    }

    return $out;
}

public function getRelawansByFilter(Request $request)
{
    $periode  = $request->query('periode');     // format Y-m
    $unit_id  = $request->query('unit_id');

    $query = ImbalanRekap::query()
        ->select('id', 'nama')
        ->distinct()
        ->orderBy('nama');

    // Filter periode (wajib)
    if ($periode) {
        // Karena kolom 'bulan' di tabel simpan format "Januari 2025", bukan Y-m
        // Kita perlu konversi dulu
        try {
            $carbonPeriode = Carbon::createFromFormat('Y-m', $periode);
            $bulanLabel = $carbonPeriode->locale('id')->translatedFormat('F Y');
            $query->where('bulan', $bulanLabel);
        } catch (\Exception $e) {
            // Jika format salah → return kosong atau fallback
            return response()->json([]);
        }
    } else {
        return response()->json([]);
    }

    // Filter unit (opsional, hanya jika admin & dipilih)
    if ($unit_id) {
        $query->where('biMBA_unit', function ($sub) use ($unit_id) {
            $sub->select('biMBA_unit')
                ->from('units')
                ->where('id', $unit_id)
                ->limit(1);
        });
        // Atau jika unit disimpan langsung sebagai string di imbalan_rekaps:
        // $query->where('biMBA_unit', $unitName);  // sesuaikan
    }

    $relawans = $query->get();

    return response()->json(
        $relawans->map(fn($r) => [
            'id'   => $r->id,
            'nama' => $r->nama,
        ])
    );
}
public function bayarPeriode(Request $request)
{

    $start_bulan = $request->start_bulan;
    $start_tahun = $request->start_tahun;

    $periode = Carbon::createFromFormat('Y-m', "$start_tahun-$start_bulan")
        ->locale('id')
        ->translatedFormat('F Y');

    $updated = ImbalanRekap::where('bulan', $periode)
        ->update([
            'status_pembayaran' => 'dibayar',
            'tanggal_dibayar' => now(),
            'dibayar_oleh' => auth()->user()->name
        ]);

    return back()->with(
        'success',
        "Berhasil membayar {$updated} relawan untuk periode {$periode}"
    );

}

public function bayarSingle(Request $request)
{
    $request->validate([
        'id' => 'required|exists:imbalan_rekaps,id'
    ]);

    $rekap = ImbalanRekap::findOrFail($request->id);

    if ($rekap->status_pembayaran === 'dibayar') {
        return response()->json([
            'success' => false,
            'message' => 'Sudah ditandai dibayar sebelumnya'
        ], 422);
    }

    $rekap->update([
        'status_pembayaran' => 'dibayar',
        'tanggal_dibayar'   => now(),
        'dibayar_oleh'      => auth()->user()?->name ?? 'System'
    ]);

    // Refresh model agar accessor bekerja (opsional, tapi aman)
    $rekap->refresh();

    $tanggalFormatted = $rekap->tanggal_dibayar 
        ? $rekap->tanggal_dibayar->format('d/m/Y H:i') 
        : now()->format('d/m/Y H:i');  // fallback jika entah kenapa null

    return response()->json([
        'success' => true,
        'message' => 'Berhasil ditandai sudah dibayar',
        'tanggal' => $tanggalFormatted
    ]);
}
}