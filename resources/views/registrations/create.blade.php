@extends('layouts.app')

@section('title', 'Tambah Pendaftaran')

@section('content')
<div class="container">
    <h2 class="mb-3">Tambah Pendaftaran</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $students           = $students           ?? collect();
        $selectedStudentId  = $selectedStudentId  ?? old('student_id');
        $prefilledNim       = $prefilledNim       ?? '';
        $prefilledNama      = $prefilledNama      ?? '';
        $prefilledUnit      = $prefilledUnit      ?? '';
        $prefilledCabang    = $prefilledCabang    ?? '';
        $prefilledTglLahir  = $prefilledTglLahir  ?? '';
        $prefilledTglMasuk  = $prefilledTglMasuk  ?? '';
        $tahapanOptions     = $tahapanOptions     ?? [];
        $kelasOptions       = $kelasOptions       ?? [];
        $hargaSaptataruna   = $hargaSaptataruna   ?? collect();
        $kdOptions          = $kdOptions          ?? [];
        $sppMapping         = $sppMapping         ?? [];
        $guruOptions        = $guruOptions        ?? [];
    @endphp

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('registrations.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- PILIH MURID --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Murid</label>

                    @if ($students->isEmpty())
                        <div class="alert alert-warning mb-2">
                            Belum ada data murid. Set status trial ke <b>LANJUT</b> dulu.
                        </div>
                    @endif

                    <select name="student_id" class="form-select" required
                            id="studentSelect" {{ $students->isEmpty() ? 'disabled' : '' }}>
                        <option value="">-- Pilih Murid --</option>
                        @foreach ($students as $s)
                            <option value="{{ $s->id }}"
                                data-unit="{{ $s->bimba_unit ?? '' }}"
                                data-cabang="{{ $s->no_cabang ?? '' }}"
                                data-nim="{{ $s->nim ?? '' }}"
                                data-nama="{{ $s->nama ?? '' }}"
                                data-tgllahir="{{ $s->tgl_lahir ?? $s->muridTrial->tgl_lahir ?? '' }}"
                                data-orangtua="{{ $s->orangtua ?? $s->muridTrial->orangtua ?? '' }}"
                                data-alamat="{{ $s->alamat ?? $s->muridTrial->alamat ?? '' }}"
                                data-info="{{ $s->muridTrial->info ?? '' }}"
                                {{ (int) $selectedStudentId === (int) $s->id ? 'selected' : '' }}>
                                {{ $s->nim ?: '—' }} — {{ $s->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- UNIT & CABANG (OTOMATIS DARI MURID) --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-primary">Unit biMBA</label>
                        <input type="text"
                               id="displayUnit"
                               class="form-control bg-light"
                               value="{{ old('bimba_unit', $prefilledUnit) }}"
                               readonly>
                        <input type="hidden"
                               name="bimba_unit"
                               value="{{ old('bimba_unit', $prefilledUnit) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-primary">No Cabang</label>
                        <input type="text"
                               id="displayCabang"
                               class="form-control bg-light"
                               value="{{ old('no_cabang', $prefilledCabang) }}"
                               readonly>
                        <input type="hidden"
                               name="no_cabang"
                               value="{{ old('no_cabang', $prefilledCabang) }}">
                    </div>
                </div>

                {{-- STATUS & TANGGAL DAFTAR --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Status</label>
                        @php
                            $st = old('status', 'pending');
                            $isAdmin = auth()->check() &&
                                       (auth()->user()->role === 'admin' || (auth()->user()->is_admin ?? false));
                        @endphp
                        <select name="status" class="form-select" required>
                            <option value="pending"  {{ $st === 'pending'  ? 'selected' : '' }}>Pending</option>
                            <option value="verified" {{ $st === 'verified' ? 'selected' : '' }}>Verified</option>
                            @if ($isAdmin)
                                <option value="accepted" {{ $st === 'accepted' ? 'selected' : '' }}>Accepted</option>
                            @else
                                <option value="accepted" disabled>Accepted (admin only)</option>
                            @endif
                            <option value="rejected" {{ $st === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tanggal Daftar</label>
                        <input type="date"
                            name="tanggal_daftar"
                            class="form-control"
                            value="{{ old('tanggal_daftar', 
                                $selectedStudent?->muridTrial?->tgl_mulai?->format('Y-m-d') 
                                ?? now()->format('Y-m-d')
                            ) }}">
                        @error('tanggal_daftar')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- IDENTITAS MURID (NIM, NAMA, TGL LAHIR, TGL MASUK) --}}
                <hr class="my-4">
                <h5 class="mb-3">Identitas Murid</h5>

                <div class="row mb-3">
                    
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tanggal Penerimaan</label>
                        <input type="date"
                            name="tanggal_penerimaan"
                            class="form-control"
                            value="{{ old('tanggal_penerimaan', now()->format('Y-m-d')) }}">
                        @error('tanggal_penerimaan')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="text-primary">Biaya Daftar</h5>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Biaya Daftar</label>

                    <select name="daftar_kode" id="daftar_select" class="form-select mb-1">
                        <option value="">-- Pilih Biaya Daftar --</option>
                        @foreach($daftarList as $item)
                            <option value="{{ $item['kode'] }}" 
                                    data-harga-duafa="{{ $item['harga_duafa'] }}"
                                    data-harga-promo="{{ $item['harga_promo'] }}"
                                    data-harga-daftar="{{ $item['harga_daftar'] }}"
                                    data-harga-spesial="{{ $item['harga_spesial'] }}"
                                    data-harga-umum1="{{ $item['harga_umum1'] }}"
                                    data-harga-umum2="{{ $item['harga_umum2'] }}">
                                {{ $item['nama'] }}
                            </option>
                        @endforeach
                    </select>

                    <select name="daftar_tipe_harga" id="daftar_tipe_harga" class="form-select form-select-sm mb-2">
                        <option value="">-- Pilih Biaya Daftar --</option>
                        <option value="harga_daftar">Daftar Ulang</option>
                        <option value="harga_duafa">Dhuafa</option>
                        <option value="harga_promo">Promo Khusus</option>
                        <option value="harga_spesial">Spesial</option>
                        <option value="harga_umum1">Umum 1</option>
                        <option value="harga_umum2">Promo Gratis</option>
                    </select>

                    <div class="d-flex align-items-center gap-2 d-none">
                        <input type="number"
                            id="daftar_qty"
                            class="form-control text-center"
                            value="0"
                            min="0"
                            style="width:90px;">

                        <small class="text-muted">× Harga</small>
                    </div>

                    <input type="hidden" name="daftar" id="daftar_hidden" value="0">

                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-primary">TOTAL BIAYA PENDAFTARAN</label>
                        <input type="text"
                            id="total_daftar"
                            class="form-control text-end bg-success fw-bold text-white"
                            readonly
                            value="0">
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <h5>Biaya Lain-lain</h5>

            <div class="row g-3">

               <!-- KAOS PENDEK -->
                <div class="col-md-4">
                    <label class="form-label">Kaos Pendek</label>
                    <div id="kaos-pendek-container">
                        <div class="kaos-pendek-row d-flex gap-2 mb-2 align-items-end">
                            <select name="kaos_pendek_kode[]" class="form-select kaos-pendek-select" style="flex: 1;">
                                <option value="">-- Pilih Ukuran --</option>
                                @foreach($kaosPendekList as $kaos)
                                    <option value="{{ $kaos['kode'] }}" data-harga="{{ $kaos['harga'] }}">
                                        {{ $kaos['kode'] }} - Rp {{ number_format($kaos['harga'], 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="kaos_pendek_qty[]" class="form-control kaos-pendek-qty" 
                                value="0" min="0" style="width: 80px;">
                            <button type="button" class="btn btn-success btn-sm btn-add-kaos-pendek">+ Tambah</button>
                        </div>
                    </div>
                    <input type="hidden" name="kaos_pendek" id="kaos_pendek_hidden" value="0">
                    <div id="ukuran-pendek-info" class="mt-2 small text-muted"></div>
                </div>

                <!-- KAOS PANJANG (sama) -->
                <div class="col-md-4">
                    <label class="form-label">Kaos Panjang (Lengan Panjang)</label>
                    <div id="kaos-panjang-container">
                        <div class="kaos-panjang-row d-flex gap-2 mb-2 align-items-end">
                            <select name="kaos_panjang_kode[]" class="form-select kaos-panjang-select" style="flex: 1;">
                                <option value="">-- Pilih Ukuran --</option>
                                @foreach($kaosPanjangList as $kaos)
                                    <option value="{{ $kaos['kode'] }}" data-harga="{{ $kaos['harga'] }}">
                                        {{ $kaos['kode'] }} - Rp {{ number_format($kaos['harga'], 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="kaos_panjang_qty[]" class="form-control kaos-panjang-qty" 
                                value="0" min="0" style="width: 80px;">
                            <button type="button" class="btn btn-success btn-sm btn-add-kaos-panjang">+ Tambah</button>
                        </div>
                    </div>
                    <input type="hidden" name="kaos_panjang" id="kaos_panjang_hidden" value="0">
                    <div id="ukuran-panjang-info" class="mt-2 small text-muted"></div>
                </div>
            <!--- End --->

           <div class="col-md-3">
                <label class="form-label">KPK</label>
                <select name="kpk_kode" id="kpk_select" class="form-select">
                    <option value="">-- Pilih KPK --</option>
                    @foreach($kpkList as $kpk)
                        <option value="{{ $kpk['kode'] }}" 
                                data-harga="{{ $kpk['harga'] }}">
                            {{ $kpk['kode'] }} - Rp {{ number_format($kpk['harga'], 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="kpk" id="kpk_hidden" value="0">
                <div id="kpk-info" class="mt-1 small"></div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Tas</label>
                <select name="tas_kode" id="tas_select" class="form-select">
                    <option value="">-- Pilih Tas --</option>
                    @foreach($tasList as $tas)
                        <option value="{{ $tas['kode'] }}" 
                                data-harga="{{ $tas['harga'] }}">
                            {{ $tas['kode'] }} - Rp {{ number_format($tas['harga'], 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="tas" id="tas_hidden" value="0">
                <div id="tas-info" class="mt-1 small"></div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Sertifikat</label>
                <select name="sertifikat_kode" id="sertifikat_select" class="form-select">
                    <option value="">-- Pilih --</option>
                    @foreach($sertifikatList as $sertifikat)
                        <option value="{{ $sertifikat['kode'] }}" 
                                data-harga="{{ $sertifikat['harga'] }}">
                            {{ $sertifikat['kode'] }} - Rp {{ number_format($sertifikat['harga'], 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="sertifikat" id="sertifikat_hidden" value="0">
                <div id="sertifikat-info" class="mt-1 small"></div>
            </div>

            <div class="col-md-3">
                <label class="form-label">STPB</label>
                <select name="stpb_kode" id="stpb_select" class="form-select">
                    <option value="">-- Pilih --</option>
                    @foreach($stpbList as $stpb)
                        <option value="{{ $stpb['kode'] }}" 
                                data-harga="{{ $stpb['harga'] }}">
                            {{ $stpb['kode'] }} - Rp {{ number_format($stpb['harga'], 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="stpb" id="stpb_hidden" value="0">
                <div id="stpb-info" class="mt-1 small"></div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Event</label>
                <input type="text" name="event" class="form-control biaya-lain text-end" value="">
            </div>
            <div class="col-md-3">
                <label class="form-label">Lain-lain</label>
                <input type="text" name="lain_lain" class="form-control biaya-lain text-end" value="0">
            </div>

            <div class="col-md-3">
                <label class="form-label">RBAS</label>
                <select name="rbas_kode" id="rbas_select" class="form-select">
                    <option value="">-- Pilih RBAS --</option>
                    @foreach($rbasList as $rbas)
                        <option value="{{ $rbas['kode'] }}" 
                                data-harga="{{ $rbas['harga'] }}">
                            {{ $rbas['kode'] }} - Rp {{ number_format($rbas['harga'], 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="RBAS" id="rbas_hidden" value="0">
                <div id="rbas-info" class="mt-1 small"></div>
            </div>

            <div class="col-md-3">
                <label class="form-label">BCABS01</label>
                <select name="bcabs01_kode" id="bcabs01_select" class="form-select">
                    <option value="">-- Pilih BCABS01 --</option>
                    @foreach($bcabs01List as $item)
                        <option value="{{ $item['kode'] }}" 
                                data-harga="{{ $item['harga'] }}">
                            {{ $item['kode'] }} - Rp {{ number_format($item['harga'], 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="BCABS01" id="bcabs01_hidden" value="0">
                <div id="bcabs01-info" class="mt-1 small"></div>
            </div>

            <div class="col-md-3">
                <label class="form-label">BCABS02</label>
                <select name="bcabs02_kode" id="bcabs02_select" class="form-select">
                    <option value="">-- Pilih BCABS02 --</option>
                    @foreach($bcabs02List as $item)
                        <option value="{{ $item['kode'] }}" 
                                data-harga="{{ $item['harga'] }}">
                            {{ $item['kode'] }} - Rp {{ number_format($item['harga'], 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="BCABS02" id="bcabs02_hidden" value="0">
                <div id="bcabs02-info" class="mt-1 small"></div>
            </div>            

            <div class="col-md-6">
                <label class="form-label fw-bold text-primary">TOTAL LAIN-LAIN</label>
                <input type="text"
                    id="total_lain"
                    class="form-control text-end bg-success fw-bold text-white"
                    readonly
                    value="0">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold text-danger">GRAND TOTAL</label>
                <input type="text"
                    id="grand_total"
                    class="form-control bg-warning text-end fs-4 fw-bold"
                    readonly
                    value="0">
            </div>
        </div>

                {{-- DATA BUKU INDUK --}}
                <hr class="my-4">
                <h5 class="mb-3">Data Buku Induk</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tahapan</label>
                        <select class="form-control" name="bi[tahap]">
                            <option value="">-- Pilih Tahapan --</option>
                            @foreach ($tahapanOptions as $t)
                                <option value="{{ $t }}" {{ old('bi.tahap') === $t ? 'selected' : '' }}>
                                    {{ $t }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kelas</label>
                        <select class="form-control" name="bi[kelas]">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($kelasOptions as $k)
                                <option value="{{ $k }}" {{ old('bi.kelas') === $k ? 'selected' : '' }}>
                                    {{ $k }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label text-primary fw-bold">Guru Pengajar</label>
                        <select class="form-control" name="bi[guru]">
                            <option value="">-- Pilih Guru --</option>
                            @foreach ($guruOptions as $guru)
                                <option value="{{ $guru }}" {{ old('bi.guru') === $guru ? 'selected' : '' }}>
                                    {{ $guru }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Gol</label>
                        <select class="form-control" name="bi[gol]" id="bi_gol">
                            <option value="">-- Pilih Gol --</option>
                            @foreach ($hargaSaptataruna->unique('kode') as $row)
                                @if ($row->kode)
                                    <option value="{{ $row->kode }}" {{ old('bi.gol') === $row->kode ? 'selected' : '' }}>
                                        {{ $row->kode }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">KD</label>
                        <select class="form-control" name="bi[kd]" id="bi_kd">
                            <option value="">-- Pilih KD --</option>
                            @foreach ($kdOptions as $kd)
                                <option value="{{ $kd }}" {{ old('bi.kd') === $kd ? 'selected' : '' }}>
                                    {{ $kd }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">SPP</label>
                        <input type="text"
                               class="form-control"
                               id="bi_spp_display"
                               readonly
                               placeholder="Otomatis terisi">
                        <input type="hidden" name="bi[spp]" id="bi_spp" value="{{ old('bi.spp') }}">
                    </div>
                </div>
                <div class="row mb-4">

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tempat Lahir</label>
                        <input type="text"
                            class="form-control"
                            name="bi[tmpt_lahir]"
                            value="{{ old('bi.tmpt_lahir', $prefilledTmptLahir) }}"
                            placeholder="Contoh: Bandung">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tanggal Lahir</label>
                        <input type="date"
                name="bi[tanggal_lahir]"
                class="form-control"
                value="{{ old('bi.tanggal_lahir', $prefilledTglLahir) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nama Orang Tua</label>
                        <input type="text"
                name="bi[orangtua]"
                class="form-control"
                value="{{ old('bi.orangtua', $prefilledOrangtua) }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Info / Sumber Informasi</label>
                        <input type="text"
                name="bi[info]"
                class="form-control"
                value="{{ old('bi.info', $prefilledInfo) }}">
                    </div>

                </div>

                {{-- ATTACHMENT --}}
                <div class="mb-3">
                    <label class="form-label">Upload Dokumen (PDF/JPG/PNG) - opsional</label>
                    <input type="file"
                           name="attachment"
                           class="form-control"
                           accept=".pdf,.jpg,.jpeg,.png,.webp">
                    <small class="text-muted">Maks 3MB</small>
                </div>

                {{-- TOMBOL AKSI --}}
                <div class="d-flex gap-2 mt-4">
                    <button type="submit"
                            class="btn btn-primary"
                            {{ $students->isEmpty() ? 'disabled' : '' }}>
                        Simpan Pendaftaran
                    </button>
                    <a href="{{ route('registrations.index') }}" class="btn btn-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const select      = document.getElementById('studentSelect');
    const unit        = document.getElementById('displayUnit');
    const cabang      = document.getElementById('displayCabang');
    const nim         = document.getElementById('displayNim');
    const nama        = document.getElementById('displayNama');
    const hiddenNim   = document.querySelector('input[name="bi[nim]"]');
    const hiddenNama  = document.querySelector('input[name="bi[nama]"]');
    const hiddenUnit  = document.querySelector('input[name="bimba_unit"]');
    const hiddenCabang= document.querySelector('input[name="no_cabang"]');

    function updateInfo() {
        const option = select.options[select.selectedIndex];

        if (option && option.value) {
            const vUnit   = option.dataset.unit   || '';
            const vCabang = option.dataset.cabang || '';
            const vNim    = option.dataset.nim    || '';
            const vNama   = option.dataset.nama   || '';

            unit.value   = vUnit;
            cabang.value = vCabang;
            nim.value    = vNim;
            nama.value   = vNama;

            hiddenUnit.value   = vUnit;
            hiddenCabang.value = vCabang;
            hiddenNim.value    = vNim;
            hiddenNama.value   = vNama;
        }
    }

    // Jalankan saat halaman dimuat (misal dari Trial langsung ke sini)
    if (select) {
        updateInfo();
        select.addEventListener('change', updateInfo);
    }

    // === SPP OTOMATIS ===
    const mapping = @json($sppMapping);
    const gol     = document.getElementById('bi_gol');
    const kd      = document.getElementById('bi_kd');
    const spp     = document.getElementById('bi_spp');
    const disp    = document.getElementById('bi_spp_display');

    function updateSPP() {
        const g = gol?.value || '';
        const k = kd?.value  || '';
        const val = mapping[g]?.[k] ?? '';

        if (spp)  spp.value  = val || '';
        if (disp) disp.value = val
            ? new Intl.NumberFormat('id-ID').format(val)
            : '';
    }

    gol?.addEventListener('change', updateSPP);
    kd?.addEventListener('change', updateSPP);
    updateSPP();
});
// ==================== BIAYA DAFTAR ====================

const daftarSelect      = document.getElementById('daftar_select');
const daftarTipeHarga   = document.getElementById('daftar_tipe_harga');
const daftarQty         = document.getElementById('daftar_qty');
const daftarHidden      = document.getElementById('daftar_hidden');
const totalDaftar       = document.getElementById('total_daftar');

// FORMAT RUPIAH
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID').format(
        parseInt(angka || 0)
    );
}

// AMBIL HARGA DARI DATA ATTRIBUTE
function getHargaDaftar(selectedOption, tipe) {

    if (!selectedOption) return 0;

    switch (tipe) {

        case 'harga_duafa':
            return parseInt(selectedOption.dataset.hargaDuafa || 0);

        case 'harga_promo':
            return parseInt(selectedOption.dataset.hargaPromo || 0);

        case 'harga_daftar':
            return parseInt(selectedOption.dataset.hargaDaftar || 0);

        case 'harga_spesial':
            return parseInt(selectedOption.dataset.hargaSpesial || 0);

        case 'harga_umum1':
            return parseInt(selectedOption.dataset.hargaUmum1 || 0);

        case 'harga_umum2':
            return parseInt(selectedOption.dataset.hargaUmum2 || 0);

        default:
            return 0;
    }
}

// UPDATE TOTAL BIAYA DAFTAR
function updateBiayaDaftar() {

    // VALIDASI ELEMENT
    if (!daftarSelect || !daftarTipeHarga || !daftarHidden || !totalDaftar) {
        return;
    }

    const selectedOption = daftarSelect.options[daftarSelect.selectedIndex];

    // RESET JIKA BELUM PILIH
    if (!selectedOption || !selectedOption.value) {

        if (daftarQty) {
            daftarQty.value = 0;
        }

        daftarHidden.value = 0;
        totalDaftar.value  = '0';

        return;
    }

    const tipe  = daftarTipeHarga.value;

    // AMBIL HARGA
    let harga = getHargaDaftar(selectedOption, tipe);

    // QTY DEFAULT = 1
    let qty = 1;

    if (daftarQty) {

        qty = parseInt(daftarQty.value) || 0;

        // AUTO SET 1 JIKA ADA HARGA
        if (harga > 0 && qty <= 0) {

            qty = 1;
            daftarQty.value = 1;
        }

        // RESET JIKA HARGA 0
        if (harga <= 0) {

            qty = 0;
            daftarQty.value = 0;
        }
    }

    // HITUNG TOTAL
    const total = harga * qty;

    // SIMPAN KE INPUT HIDDEN
    daftarHidden.value = total;

    // TAMPILKAN FORMAT
    totalDaftar.value = formatRupiah(total);
}

// EVENT
daftarSelect?.addEventListener('change', updateBiayaDaftar);
daftarTipeHarga?.addEventListener('change', updateBiayaDaftar);
daftarQty?.addEventListener('input', updateBiayaDaftar);

// LOAD AWAL
updateBiayaDaftar();
// ============================
// FORMAT
// ============================

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID').format(
        parseInt(angka || 0)
    );
}

// ============================
// SIMPLE SELECT HARGA
// ============================

function setupSingleBiaya(config) {

    const select  = document.getElementById(config.selectId);
    const hidden  = document.getElementById(config.hiddenId);
    const info    = document.getElementById(config.infoId);

    if (!select || !hidden) return;

    function update() {

        const option = select.options[select.selectedIndex];

        if (!option || !option.value) {

            hidden.value = 0;

            if (info) {
                info.innerHTML = '';
            }

            hitungGrandTotal();
            return;
        }

        const harga = parseInt(option.dataset.harga || 0);

        hidden.value = harga;

        if (info) {

            info.innerHTML = `
                <div class="alert alert-info py-1 px-2 mb-0">
                    Rp ${formatRupiah(harga)}
                </div>
            `;
        }

        hitungGrandTotal();
    }

    select.addEventListener('change', update);

    update();
}

function setupKaos(config) {
    const container = document.getElementById(config.containerId);
    const hidden    = document.getElementById(config.hiddenId);
    const infoBox   = document.getElementById(config.infoId);

    if (!container || !hidden) return;

    function hitungTotal() {
        let total = 0;
        let infoHtml = '';

        container.querySelectorAll(config.rowClass).forEach(row => {
            const select = row.querySelector('select');
            const qtyInput = row.querySelector('input[type="number"]');

            if (!select || !qtyInput) return;

            const harga = parseFloat(select.selectedOptions[0]?.dataset.harga || 0);
            let qty = parseInt(qtyInput.value || 0);

            // OTOMATIS SET QTY = 1 saat memilih ukuran
            if (harga > 0 && qty === 0) {
                qty = 1;
                qtyInput.value = 1;
            } else if (harga === 0) {
                qty = 0;
                qtyInput.value = 0;
            }

            if (harga > 0 && qty > 0) {
                const subtotal = harga * qty;
                total += subtotal;

                infoHtml += `
                    <div class="text-success small">
                        ${select.value} × ${qty} = Rp ${formatRupiah(subtotal)}
                    </div>`;
            }
        });

        hidden.value = Math.round(total);
        if (infoBox) infoBox.innerHTML = infoHtml || '<small class="text-muted">Belum ada pilihan</small>';
        
        hitungGrandTotal();
    }

    // Event listeners
    container.addEventListener('change', hitungTotal);
    container.addEventListener('input', hitungTotal);

    // Tambah / Hapus baris
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-add-kaos-pendek') || 
            e.target.classList.contains('btn-add-kaos-panjang')) {
            
            const firstRow = container.querySelector(config.rowClass);
            if (!firstRow) return;

            const clone = firstRow.cloneNode(true);
            clone.querySelector('select').value = '';
            clone.querySelector('input[type="number"]').value = 0;

            const btn = clone.querySelector('button');
            btn.classList.remove('btn-success');
            btn.classList.add('btn-danger');
            btn.textContent = 'Hapus';
            btn.classList.add('btn-remove-kaos');

            container.appendChild(clone);
            hitungTotal();
        }

        if (e.target.classList.contains('btn-remove-kaos')) {
            e.target.closest(config.rowClass).remove();
            hitungTotal();
        }
    });

    hitungTotal(); // initial
}

// ============================
// GRAND TOTAL
// ============================

function hitungGrandTotal() {

    const daftar = parseInt(
        document.getElementById('daftar_hidden')?.value || 0
    );

    const lain = [

        'kaos_pendek_hidden',
        'kaos_panjang_hidden',
        'kpk_hidden',
        'tas_hidden',
        'sertifikat_hidden',
        'stpb_hidden',
        'rbas_hidden',
        'bcabs01_hidden',
        'bcabs02_hidden'

    ].reduce((sum, id) => {

        return sum + parseInt(
            document.getElementById(id)?.value || 0
        );

    }, 0);

    const eventVal = parseInt(
        document.querySelector('input[name="event"]')?.value || 0
    );

    const lainVal = parseInt(
        document.querySelector('input[name="lain_lain"]')?.value || 0
    );

    const totalLain = lain + eventVal + lainVal;

    document.getElementById('total_lain').value =
        formatRupiah(totalLain);

    document.getElementById('grand_total').value =
        formatRupiah(daftar + totalLain);
}

// ============================
// INIT
// ============================

setupSingleBiaya({
    selectId : 'kpk_select',
    hiddenId : 'kpk_hidden',
    infoId   : 'kpk-info'
});

setupSingleBiaya({
    selectId : 'tas_select',
    hiddenId : 'tas_hidden',
    infoId   : 'tas-info'
});

setupSingleBiaya({
    selectId : 'sertifikat_select',
    hiddenId : 'sertifikat_hidden',
    infoId   : 'sertifikat-info'
});

setupSingleBiaya({
    selectId : 'stpb_select',
    hiddenId : 'stpb_hidden',
    infoId   : 'stpb-info'
});

setupSingleBiaya({
    selectId : 'rbas_select',
    hiddenId : 'rbas_hidden',
    infoId   : 'rbas-info'
});

setupSingleBiaya({
    selectId : 'bcabs01_select',
    hiddenId : 'bcabs01_hidden',
    infoId   : 'bcabs01-info'
});

setupSingleBiaya({
    selectId : 'bcabs02_select',
    hiddenId : 'bcabs02_hidden',
    infoId   : 'bcabs02-info'
});

// ============================
// KAOS PENDEK
// ============================

// KAOS PENDEK & PANJANG
setupKaos({
    containerId : 'kaos-pendek-container',
    hiddenId    : 'kaos_pendek_hidden',
    infoId      : 'ukuran-pendek-info',
    rowClass    : '.kaos-pendek-row'
});

setupKaos({
    containerId : 'kaos-panjang-container',
    hiddenId    : 'kaos_panjang_hidden',
    infoId      : 'ukuran-panjang-info',
    rowClass    : '.kaos-panjang-row'
});

// ============================
// INPUT EVENT / LAIN
// ============================

document.querySelectorAll('.biaya-lain').forEach(el => {

    el.addEventListener('input', hitungGrandTotal);
});

// ============================
// LOAD
// ============================

hitungGrandTotal();

</script>
@endsection
