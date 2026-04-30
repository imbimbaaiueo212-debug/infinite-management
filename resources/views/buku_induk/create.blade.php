@extends('layouts.app')

@section('title', 'Tambah Data Buku Induk')

@section('content')
<div class="card card-body">
    <h2 class="mb-4">Tambah Data Buku Induk</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('buku_induk.store') }}" method="POST">
        @csrf

        <div class="row">

            {{-- NIM – OTOMATIS TERISI --}}
            <div class="col-md-6 mb-3 fw-bold text-primary">
                <label class="form-label">NIM <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text" id="nim-display">
                        {{-- Tampilan NIM yang otomatis --}}
                        <span id="nim-preview">
                            @if (!$isAdmin && $autoNim)
                                {{ $autoNim }}
                            @else
                                ----- (akan terisi otomatis)
                            @endif
                        </span>
                    </span>
                    <input type="hidden" name="nim" id="nim" value="{{ old('nim', $autoNim ?? '') }}">
                    <input type="hidden" name="nim_suffix" id="nim_suffix" value="{{ old('nim_suffix', $autoNimSuffix ?? '') }}">
                </div>
                <small class="text-muted">
                    @if ($isAdmin)
                        Pilih unit → NIM otomatis terisi (urut terakhir + 1).
                    @else
                        NIM otomatis dari unit Anda (urut terakhir + 1).
                    @endif
                </small>
                @error('nim') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            {{-- UNIT biMBA & NO CABANG – Hanya untuk ADMIN --}}
            @if ($isAdmin)
                <div class="col-md-6 fw-bold">
                    <label for="bimba_unit">Unit biMBA <span class="text-danger">*</span></label>
                    <select name="bimba_unit" id="bimba_unit" class="form-control @error('bimba_unit') is-invalid @enderror" required>
                        <option value="">-- Pilih Unit --</option>
                        @foreach($units as $namaUnit => $cabang)
                            <option value="{{ $namaUnit }}" {{ old('bimba_unit') == $namaUnit ? 'selected' : '' }}>
                                {{ $namaUnit }}
                            </option>
                        @endforeach
                    </select>
                    @error('bimba_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

               <div class="col-md-6 fw-bold text-primary">
                    <label for="no_cabang">No. Cabang</label>
                    <input type="text" name="no_cabang" id="no_cabang"
                    class="form-control"
                    value=""
                    readonly>
                </div>
            @else
                {{-- Non-admin: hidden input saja --}}
                @if ($userUnit)
                    <input type="hidden" name="bimba_unit" value="{{ $userUnit }}">
                    <input type="hidden" name="no_cabang" value="{{ $userNoCabang }}">
                @else
                    <div class="col-12 mb-3">
                        <div class="alert alert-warning">
                            Unit biMBA Anda belum diatur. Hubungi admin.
                        </div>
                        <input type="hidden" name="bimba_unit" value="">
                        <input type="hidden" name="no_cabang" value="">
                    </div>
                @endif
            @endif

            {{-- Nama --}}
            <div class="col-md-6 mb-3 fw-bold">
                <label for="nama" class="form-label">Nama <span class="text-danger">*</span></label>
                <input type="text" name="nama" id="nama" class="form-control" value="{{ old('nama') }}" required>
                @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Tempat Lahir --}}
            <div class="col-md-6 mb-3 fw-bold">
                <label for="tmpt_lahir">Tempat Lahir</label>
                <input type="text" name="tmpt_lahir" id="tmpt_lahir" class="form-control" value="{{ old('tmpt_lahir') }}">
            </div>

            {{-- Tgl Lahir --}}
            <div class="col-md-6 mb-3 fw-bold">
    <label for="tgl_lahir">Tanggal Lahir <span class="text-danger">*</span></label>
    <input type="text" 
        name="tgl_lahir" 
        id="tgl_lahir" 
        class="form-control" 
        placeholder="Masukan Tanggal Lahir"
        value="{{ old('tgl_lahir') }}">
</div>

             {{-- Usia --}}
            <div class="col-md-6 mb-3">
                <label for="usia" class="text-primary fw-bold">Usia</label>
                <input type="number" name="usia" id="usia" class="form-control" readonly>
            </div>

            <div class="col-md-6 mb-3 fw-bold">
                <label for="tgl_daftar">Tanggal Daftar <span class="text-danger">*</span></label>
                <input type="text" 
                    name="tgl_daftar" 
                    id="tgl_daftar" 
                    class="form-control" 
                    placeholder="Masukan Tanggal Daftar"
                    value="{{ old('tgl_daftar') }}">
                <small class="text-muted">Bisa paste dari Excel</small>
            </div>

            {{-- Tgl Masuk --}}
            <div class="col-md-6 mb-3 fw-bold">
                <label for="tgl_masuk">Tanggal Aktif <span class="text-danger">*</span></label>
                <input type="text" name="tgl_masuk" id="tgl_masuk" class="form-control" placeholder="dd/mm/yyyy atau yyy-mm-dd" 
                value="{{ old('tgl_masuk') }}" required>
                <small class="text-muted">Contoh</small>
            </div>

            {{-- Lama Belajar --}}
            <div class="col-md-6 mb-3">
                <label for="lama_bljr" class="text-primary fw-bold">Lama Belajar</label>
                <input type="text" name="lama_bljr" id="lama_bljr" class="form-control" readonly>
            </div>

            {{-- Tahapan --}}
           <div class="col-md-6 mb-3 fw-bold">
                <label for="tahap">Tahapan <span class="text-danger">*</span></label>
                <select name="tahap" id="tahap" class="form-control w-100">
                    <option value="">-- Pilih --</option>
                    @foreach($tahapanOptions as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>

                <div class="col-md-6 mb-3">
                    <label for="tgl_tahapan" class="text-success fw-bold">Tanggal Tahapan</label>
                    <input type="date" name="tgl_tahapan" id="tgl_tahapan" class="form-control">
                </div>

            {{-- Sumber Informasi --}}
            <div class="col-md-6 mb-3 fw-bold">
    <label for="info">Sumber Informasi <span class="text-danger">*</span></label>
    <select name="info" id="info"
        class="form-control @error('info') is-invalid @enderror" required>
        <option value="">-- Pilih --</option>
        @foreach($infoOptions as $opt)
            <option value="{{ $opt }}"
                {{ old('info') == $opt ? 'selected' : '' }}>
                {{ $opt }}
            </option>
        @endforeach
    </select>
    @error('info')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="col-md-6 mb-3 fw-bold"
     id="keterangan_info_wrapper"
     style="display: none;">
    <label for="keterangan_info">Keterangan Info</label>
    <textarea name="keterangan_info"
        id="keterangan_info"
        class="form-control"
        rows="2">{{ old('keterangan_info') }}</textarea>
</div>
            <!-- Kelas -->
            <div class="col-md-6 mb-3 fw-bold">
    <label for="kelas">Kelas <span class="text-danger">*</span></label>

    <select name="kelas" id="kelas"
        class="form-control @error('kelas') is-invalid @enderror"
        required>
        <option value="">-- Pilih Kelas --</option>

        @foreach($kelasOptions as $k)
            <option value="{{ $k }}"
                {{ old('kelas') == $k ? 'selected' : '' }}>
                {{ $k }}
            </option>
        @endforeach
    </select>

    @error('kelas')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

            <!-- Gol -->
            <div class="col-md-6 mb-3 fw-bold">
                <label for="gol">Gol <span class="text-danger">*</span></label>
                <select name="gol" id="gol" class="form-control" required>
                    <option value="">-- Pilih Gol --</option>
                    @foreach($golOptions as $item)
                        <option value="{{ $item->kode }}">
                            {{ $item->kode }}
                        </option>
                    @endforeach
                </select>
                @error('gol') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- KD -->
            <div class="col-md-6 mb-3 fw-bold">
                <label for="kd">KD <span class="text-danger">*</span></label>
                <select name="kd" id="kd" class="form-control" required>
                    <option value="">-- Pilih KD --</option>
                    @foreach($kdOptions as $k)
                        <option value="{{ $k }}" {{ old('kd') == $k ? 'selected' : '' }}>{{ $k }}</option>
                    @endforeach
                </select>
                @error('kd') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- SPP -->
            <div class="col-md-6 mb-3 fw-bold">
                <label for="spp" class="text-primary fw-bold">SPP</label>
                <input type="text" id="spp" name="spp" class="form-control" readonly>
            </div>

                        <!-- Status -->
            <div class="col-md-6 mb-3">
                <label class="form-label text-primary fw-bold">Status</label>

                <div id="status-display"
                    class="form-control text-center fs-5 fw-bold bg-primary text-white">
                    Baru
                </div>

                <input type="hidden" name="status" id="status" value="Baru">
            </div>

            <!-- Petugas Trial -->
            <div class="col-md-6 mb-3 fw-bold">
                <label for="petugas_trial">Petugas Trial</label>
                <select name="petugas_trial" id="petugas_trial" class="form-control">
                    <option value="">-- Pilih --</option>
                    @foreach($profil as $p)
                        <option value="{{ $p->nama }}">{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Guru -->
            <div class="col-md-6 mb-3 fw-bold">
                <label for="guru">Guru <span class="text-danger">*</span></label>
                <select name="guru" id="guru" class="form-control" required>
                    <option value="">-- Pilih Guru --</option>
                    @foreach($profil as $g)
                        <option value="{{ $g->nama }}">{{ $g->nama }}</option>
                    @endforeach
                </select>
                @error('guru') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- Orangtua -->
            <div class="col-md-6 mb-3 fw-bold">
                <label for="orangtua">Orangtua</label>
                <input type="text" name="orangtua" id="orangtua" class="form-control" value="{{ old('orangtua') }}" required>
                @error('orangtua') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <!-- No Telp/HP -->
            <div class="col-md-6 mb-3 fw-bold">
                <label for="no_telp_hp">No Telp/HP <span class="text-danger">*</span></label>
                <input type="text" name="no_telp_hp" id="no_telp_hp" class="form-control" value="{{ old('no_telp_hp') }}">
            </div>

            <!-- Alamat Murid -->
            <div class="col-md-6 mb-3 fw-bold">
                <label for="alamat_murid">Alamat Murid<span class="text-danger">*</span></label>
                <textarea name="alamat_murid" id="alamat_murid" class="form-control" rows="1">{{ old('alamat_murid') }}</textarea>
            </div>

            <!-- Note -->
            <div class="col-md-6 mb-3 fw-bold">
                <label for="note">Note</label>
                <select name="note" id="note" class="form-control">
                    <option value="">-- Pilih Note --</option>
                    @foreach($noteOptions as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-3 fw-bold">
                <label for="no_cab_merge">No Cab Merge</label>
                <input type="text" name="no_cab_merge" id="no_cab_merge" class="form-control" value="{{ old('no_cab_merge') }}">
            </div>

            <div class="col-md-6 mb-3 fw-bold">
                <label for="no_pembayaran_murid">No Pembayaran Murid</label>
                <input type="text" name="no_pembayaran_murid" id="no_pembayaran_murid" class="form-control" value="{{ old('no_pembayaran_murid') }}">
            </div>

            <div class="col-md-6 mb-3 fw-bold">
                <label for="jenis_kbm">Jenis KBM <span class="text-danger">*</span></label>
                <select name="jenis_kbm" id="jenis_kbm" class="form-control">
                    <option value="">-- Pilih --</option>
                    @foreach($jenisKbmOptions as $jk)
                        <option value="{{ $jk }}">{{ $jk }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-3 fw-bold">
                <label for="level">Level <span class="text-danger">*</span></label>
                <select name="level" id="level" class="form-control">
                    <option value="">-- Pilih --</option>
                    @foreach($levelOptions as $l)
                        <option value="{{ $l }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label for="tgl_level" class="text-success fw-bold">Tanggal Level</label>
                <input type="date" name="tgl_level" id="tgl_level" class="form-control" value="{{ old('tgl_level') }}">
            </div>

            <div class="col-md-6 mb-3 fw-bold">
                <label for="keterangan_level">Keterangan Level</label>
                <textarea name="keterangan_level" id="keterangan_level" class="form-control" rows="1">{{ old('keterangan_level') }}</textarea>
            </div>

            <!-- Separator -->

            <div id="duafa-bnf-section" style="display: none;">

                    <div class="col-12"><hr class="my-4"></div>
                    <h4 class="col-12 mb-3 fw-bold">🗓️ MASA AKTIF (DHUAFA & BNF)</h4>

                    <div class="row g-3">

                        {{-- PERIODE --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Periode</label>

                            @php
                                $selectedPeriode = old('periode', $bukuInduk->periode ?? '');
                                preg_match('/\d+/', $selectedPeriode, $match);
                                $selectedNumber = $match[0] ?? '';
                            @endphp

                            <select name="periode" id="periode" class="form-control text-danger">
                                <option value="">-- Pilih --</option>
                                @for ($i = 1; $i <= 10; $i++)
                                    <option value="Ke-{{ $i }}" {{ $selectedNumber == $i ? 'selected' : '' }}>
                                        Ke-{{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        {{-- TGL MULAI --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Tanggal Mulai</label>
                            <input type="date"
                                name="tgl_mulai"
                                id="tgl_mulai"
                                class="form-control"
                                value="{{ old('tgl_mulai') }}">
                        </div>

                        {{-- TGL AKHIR --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Tanggal Akhir</label>
                            <input type="date"
                                name="tgl_akhir"
                                id="tgl_akhir"
                                class="form-control"
                                value="{{ old('tgl_akhir') }}">
                        </div>

                        {{-- JUMLAH --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold text-success">Jumlah Beasiswa</label>
                            <input type="number"
                                name="jumlah_beasiswa"
                                id="jumlah_beasiswa"
                                class="form-control"
                                value="{{ old('jumlah_beasiswa') }}">
                        </div>

                    </div>

                    {{-- CHECKBOX --}}
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input"
                                    type="checkbox"
                                    name="alert"
                                    id="alert"
                                    value="aktif"
                                    {{ old('alert') === 'aktif' ? 'checked' : '' }}>

                                <label class="form-check-label text-info" for="alert">
                                    Beasiswa Aktif
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <hr class="my-4">
                    </div>

                </div>

                <div id="paket72Section" style="display: none;">
    <div class="col-12 mb-3">
        <h4 class="fw-bold">⏱️ Masa Aktif Paket 72</h4>
    </div>

    <div class="row">

        <div class="col-md-4 mb-3">
            <label for="tgl_bayar" class="form-label fw-bold">Tanggal Bayar</label>
            <input type="date" name="tgl_bayar" id="tgl_bayar" class="form-control">
        </div>

        <div class="col-md-4 mb-3">
            <label for="tgl_selesai" class="form-label fw-bold text-success">Tanggal Selesai</label>
            <input type="date" name="tgl_selesai" id="tgl_selesai" class="form-control">
        </div>

        <div class="col-md-4 mb-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="alert2" id="alert2" value="aktif">
                <label class="form-check-label fw-bold text-info">
                    Paket 72 Aktif
                </label>
            </div>
        </div>

    </div>
</div>

            <h4 class="col-12 mb-3">📚 Supply Modul</h4>

            <div class="col-md-6 mb-3 fw-bold">
                <label for="asal_modul">Asal Modul <span class="text-danger">*</span></label>
                <select name="asal_modul" id="asal_modul" class="form-control">
                    <option value="">-- Pilih --</option>
                    @foreach($asalModulOptions as $am)
                        <option value="{{ $am }}">{{ $am }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-3 fw-bold">
                <label for="keterangan_optional">Keterangan Optional</label>
                <input type="text" name="keterangan_optional" id="keterangan_optional" class="form-control" value="{{ old('keterangan_optional') }}">
            </div>

            <div class="col-12"><hr class="my-4"></div>

            <h4 class="col-12 mb-3">⏰ Jadwal biMBA</h4>

            <div class="col-md-2 mb-3">
                <label class="form-label fw-bold">
                    Kode Jadwal <span class="text-danger">*</span>
                </label>

                <select name="kode_jadwal" id="kode_jadwal" class="form-control" required>
                    <option value="">-- Pilih Kode Jadwal --</option>
                    @foreach($kodeJadwalOptions as $kode)
                        <option value="{{ $kode }}" {{ old('kode_jadwal') == $kode ? 'selected' : '' }}>
                            {{ $kode }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold text-primary">Hari & Jam</label>

                <div id="jadwal_preview" class="form-control-plaintext border p-2 bg-light text-muted">
                    Pilih kode jadwal dulu
                </div>
            </div>

            <div class="col-12"><hr class="my-4"></div>

            <h4 class="col-12 mb-3">📝 SURAT GARANSI BCA 372 BEBAS</h4>

            <div class="col-md-6 mb-3 fw-bold">
                <label for="tgl_surat_garansi">Tanggal Diberikan Surat</label>
                <input type="date" name="tgl_surat_garansi" id="tgl_surat_garansi" class="form-control" value="{{ old('tgl_surat_garansi')}}">
            </div>

            <!-- Note Garansi -->
            <div class="col-md-6 mb-3 fw-bold">
                <label for="note_garansi">Note Garansi</label>
                <select name="note_garansi" id="note_garansi" class="form-control">
                    <option value="">-- Pilih --</option>
                    @foreach($noteGaransiOptions as $ng)
                        <option value="{{ $ng }}">{{ $ng }}</option>
                    @endforeach
                </select>
            </div>    
                <!-- Submit -->
            <div class="col-12 mt-5">
                <button type="submit" class="btn btn-success btn-lg">Simpan Data</button>
                <a href="{{ route('buku_induk.index') }}" class="btn btn-secondary btn-lg">Kembali</a>
            </div>
        </div>
    </form>
</div>

<style>
        .select2-container .select2-selection--single {
            height: calc(1.5em + .75rem + 2px);
            padding-top: .375rem;
        }
</style>

<!-- JavaScript -->
<script>
    window.unitsData = {!! $unitsJson !!};
    window.guruByUnit = {!! json_encode($guruByUnit ?? []) !!};

    document.addEventListener('DOMContentLoaded', function () {

        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
        let currentUnit = '{{ $userUnit ?? '' }}';

        // Elements
        const unitSelect = document.getElementById('bimba_unit');
        const guruSelect = document.getElementById('guru');

        const nimPreview = document.getElementById('nim-preview');
        const nimInput = document.getElementById('nim');
        const nimSuffix = document.getElementById('nim_suffix');
        const noCabangInput = document.getElementById('no_cabang');

        // ==================== UPDATE NIM ====================
        function updateNim(unitValue) {
            if (!unitValue) {
                if (nimPreview) nimPreview.textContent = '-----';
                if (nimInput) nimInput.value = '';
                return;
            }

            fetch(`/buku-induk/next-suffix?bimba_unit=${encodeURIComponent(unitValue)}`)
                .then(res => res.json())
                .then(data => {
                    const nextSuffix = data.next_suffix || '0001';
                    const padded = String(nextSuffix).padStart(4, '0');
                    const prefix = window.unitsData?.[unitValue] || '-----';
                    const fullNim = prefix + padded;

                    if (nimPreview) nimPreview.textContent = fullNim;
                    if (nimInput) nimInput.value = fullNim;
                    if (nimSuffix) nimSuffix.value = padded;
                })
                .catch(() => {
                    if (nimPreview) nimPreview.textContent = 'Error load NIM';
                });
        }

        // ==================== UPDATE NO CABANG ====================
        function updateNoCabang(unitValue) {
            if (!noCabangInput) return;
            noCabangInput.value = window.unitsData?.[unitValue] || '';
        }

        // ==================== FILTER GURU BY UNIT ====================
        function filterGuruByUnit(unitValue) {
            if (!guruSelect) return;

            const allowedGuru = window.guruByUnit?.[unitValue] || [];

            // Hapus semua option kecuali placeholder
            while (guruSelect.options.length > 1) {
                guruSelect.remove(1);
            }

            if (allowedGuru.length > 0) {
                allowedGuru.forEach(guruNama => {
                    const option = new Option(guruNama, guruNama);
                    guruSelect.appendChild(option);
                });
            } else {
                // Fallback: tampilkan semua guru
                @foreach($profil as $g)
                    const option{{ $loop->index }} = new Option('{{ addslashes($g->nama) }}', '{{ addslashes($g->nama) }}');
                    guruSelect.appendChild(option{{ $loop->index }});
                @endforeach
            }

            guruSelect.value = '';
        }

        // ==================== HANDLE UNIT CHANGE ====================
        function handleUnitChange(unitValue) {
            currentUnit = unitValue;

            updateNim(unitValue);
            updateNoCabang(unitValue);
            filterGuruByUnit(unitValue);
        }

        // ==================== EVENT LISTENER UNIT (ADMIN ONLY) ====================
        if (isAdmin && unitSelect) {
            unitSelect.addEventListener('change', function () {
                handleUnitChange(this.value);
            });

            // Initial load
            if (unitSelect.value) {
                handleUnitChange(unitSelect.value);
            }
        }

        // ==================== USIA ====================
        document.getElementById('tgl_lahir')?.addEventListener('change', function () {
            if (!this.value) return;

            const tgl = new Date(this.value);
            const today = new Date();

            let age = today.getFullYear() - tgl.getFullYear();
            const m = today.getMonth() - tgl.getMonth();

            if (m < 0 || (m === 0 && today.getDate() < tgl.getDate())) age--;

            document.getElementById('usia').value = age > 0 ? age : '';
        });

        // ==================== LAMA BELAJAR ====================
const tglInput = document.getElementById('tgl_masuk');
const lamaInput = document.getElementById('lama_bljr');

// ==================== HANDLE PASTE DARI EXCEL ====================
tglInput.addEventListener('paste', function (e) {
    e.preventDefault();

    let pasted = (e.clipboardData || window.clipboardData).getData('text').trim();

    // Format Excel umum: 01/12/2024 atau 01-12-2024
    let match = pasted.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);

    if (match) {
        let day = match[1].padStart(2, '0');
        let month = match[2].padStart(2, '0');
        let year = match[3];

        let formatted = `${year}-${month}-${day}`;

        this.value = formatted;
        hitungLama();
    }
});

// ==================== HITUNG LAMA BELAJAR ====================
function hitungLama() {
    if (!tglInput.value) return;

    const tgl = new Date(tglInput.value);
    const today = new Date();

    let totalBulan =
        (today.getFullYear() - tgl.getFullYear()) * 12 +
        (today.getMonth() - tgl.getMonth());

    if (today.getDate() < tgl.getDate()) totalBulan--;

    if (totalBulan < 0) {
        lamaInput.value = '';
        return;
    }

    let tahun = Math.floor(totalBulan / 12);
    let bulan = totalBulan % 12;

    let hasil = '';

    if (tahun > 0) {
        hasil += tahun + ' tahun ';
    }

    if (bulan > 0) {
        hasil += bulan + ' bulan';
    }

    if (hasil === '') {
        hasil = '0 bulan';
    }

    lamaInput.value = hasil.trim();
}

        // ==================== SPP ====================
        const sppMapping = @json($sppMapping);
        const golSelect = document.getElementById('gol');
        const kdSelect = document.getElementById('kd');
        const sppInput = document.getElementById('spp');

        function updateSPP() {
            const gol = golSelect?.value;
            const kd = kdSelect?.value;

            if (gol && kd && sppMapping[gol]?.[kd] !== undefined) {
                sppInput.value = 'Rp. ' + new Intl.NumberFormat('id-ID').format(sppMapping[gol][kd]);
            } else {
                sppInput.value = '';
            }
        }

        golSelect?.addEventListener('change', updateSPP);
        kdSelect?.addEventListener('change', updateSPP);
        updateSPP();
        //===========TAHAPAN==================

            $('#tahap').select2({
        width: 'resolve',
        placeholder: '-- Pilih --',
        allowClear: true
    });

    // ==================== PASTE SUPPORT SELECT2 ====================
    $(document).on('paste', '.select2-search__field', function (e) {
        let pasted = (e.originalEvent.clipboardData || window.clipboardData)
            .getData('text')
            .trim()
            .toLowerCase();

        let select = $('#tahap');

        let found = false;

        select.find('option').each(function () {
            if ($(this).text().toLowerCase() === pasted) {
                select.val($(this).val()).trigger('change');
                found = true;
            }
        });

        if (!found) {
            select.val(null).trigger('change'); // kosong kalau tidak cocok
        }
    });
    const tahapSelect = $('#tahap');
    const tglWrapper = $('#tgl_tahapan').closest('.col-md-6');

    // ==================== INITIAL HIDE ====================
    if (!tahapSelect.val()) {
        tglWrapper.hide();
    }

    // ==================== ON CHANGE ====================
    tahapSelect.on('change', function () {
        let val = $(this).val();

        if (val === 'Persiapan' || val === 'Lanjutan') {
            tglWrapper.show();
        } else {
            $('#tgl_tahapan').val('');
            tglWrapper.hide();
        }
    });

        // ==================== INFO LAINNYA ====================
$(document).ready(function () {

    // =========================
    // INIT SELECT2
    // =========================
    $('#info').select2({
        width: '100%',
        placeholder: '-- Pilih --',
        allowClear: true
    });
    $('#kelas').select2({
        width: '100%',
        placeholder: '-- Pilih Kelas --',
        allowClear: true,
        minimumResultsForSearch: 0
    });
    $('#gol').select2({
        width: '100%',
        placeholder: '-- Pilih Gol --',
        allowClear: true,
        minimumResultsForSearch: 0
    });
    $('#kd').select2({
        width: '100%',
        placeholder: '-- Pilih KD --',
        allowClear: true,
        minimumResultsForSearch: 0
    });
    $('#petugas_trial').select2({
        width: '100%',
        placeholder: '-- Pilih Guru Trial --',
        allowClear: true,
        minimumResultsForSearch: 0
    });
    $('#guru').select2({
        width: '100%',
        placeholder: '-- Pilih Guru --',
        allowClear: true,
        minimumResultsForSearch: 0
    });
        $('#note').select2({
        width: '100%',
        placeholder: '-- Pilih Note --',
        allowClear: true,
        minimumResultsForSearch: 0
    });


    // =========================
    // TOGGLE KETERANGAN
    // =========================
    function toggleKeterangan() {
        let val = $('#info').val();

        if (val && val.toLowerCase() === 'lainnya') {
            $('#keterangan_info_wrapper').slideDown(150);
        } else {
            $('#keterangan_info_wrapper').slideUp(150);
            $('#keterangan_info').val('');
        }
    }

    // WAJIB pakai ini (Select2 event)
    $('#info').on('change.select2', toggleKeterangan);

    // Jalankan saat load (edit mode / old value)
    toggleKeterangan();

    // =========================
    // SUPPORT PASTE DARI EXCEL
    // =========================
    $(document).on('paste', '.select2-search__field', function (e) {

        let pasted = (e.originalEvent.clipboardData || window.clipboardData)
            .getData('text')
            .trim()
            .toLowerCase();

        // Ambil select yang aktif
        let selectId = $(this)
            .closest('.select2-container')
            .prev('select')
            .attr('id');

        // hanya untuk #info
        if (selectId !== 'info') return;

        let select = $('#info');
        let found = false;

        select.find('option').each(function () {
            if ($(this).text().toLowerCase() === pasted) {
                select.val($(this).val()).trigger('change.select2');
                found = true;
            }
        });

        // kalau tidak cocok → kosong
        if (!found) {
            select.val(null).trigger('change.select2');
        }
    });

});

        // ==================== LEVEL ====================
        const level = document.getElementById('level');
        const tglLevel = document.getElementById('tgl_level');

        level?.addEventListener('change', function () {
            if (this.value && !tglLevel.value) {
                tglLevel.value = new Date().toISOString().split('T')[0];
            }
        });

        // ==================== JADWAL ====================
        const kodeJadwal = document.getElementById('kode_jadwal');
        const preview = document.getElementById('jadwal_preview');

        const jadwalMap = {
            108: { shift: 'SRJ', hari: 'Senin | Rabu | Jumat', jam: '08:00' },
            109: { shift: 'SRJ', hari: 'Senin | Rabu | Jumat', jam: '09:00' },
            110: { shift: 'SRJ', hari: 'Senin | Rabu | Jumat', jam: '10:00' },
            111: { shift: 'SRJ', hari: 'Senin | Rabu | Jumat', jam: '11:00' },
            112: { shift: 'SRJ', hari: 'Senin | Rabu | Jumat', jam: '12:00' },
            113: { shift: 'SRJ', hari: 'Senin | Rabu | Jumat', jam: '13:00' },
            114: { shift: 'SRJ', hari: 'Senin | Rabu | Jumat', jam: '14:00' },
            115: { shift: 'SRJ', hari: 'Senin | Rabu | Jumat', jam: '15:00' },
            116: { shift: 'SRJ', hari: 'Senin | Rabu | Jumat', jam: '16:00' },

            208: { shift: 'SKS', hari: 'Selasa | Kamis | Sabtu', jam: '08:00' },
            209: { shift: 'SKS', hari: 'Selasa | Kamis | Sabtu', jam: '09:00' },
            210: { shift: 'SKS', hari: 'Selasa | Kamis | Sabtu', jam: '10:00' },
            211: { shift: 'SKS', hari: 'Selasa | Kamis | Sabtu', jam: '11:00' },

            308: { shift: 'S6', hari: 'Senin - Sabtu', jam: '08:00' },
            309: { shift: 'S6', hari: 'Senin - Sabtu', jam: '09:00' },
            310: { shift: 'S6', hari: 'Senin - Sabtu', jam: '10:00' },
            311: { shift: 'S6', hari: 'Senin - Sabtu', jam: '11:00' },
        };

        function updateJadwal() {
            const val = kodeJadwal?.value;

            if (jadwalMap[val]) {
                const j = jadwalMap[val];
                preview.innerHTML = `
                    <strong>${j.shift}</strong><br>
                    <span>${j.hari}</span> - 
                    <span class="text-primary fw-bold">${j.jam}</span>
                `;
            } else {
                preview.innerHTML = 'Belum ada jadwal';
            }
        }

        kodeJadwal?.addEventListener('change', updateJadwal);
        updateJadwal();

    });

    // ==================== FUNGSI DI LUAR DOMContentLoaded ====================

    // Toggle Duafa BNF
    function toggleDuafaBNF() {
        const gol = (document.getElementById('gol')?.value || '').toUpperCase().trim();
        const triggerGol = ['D', 'S3B1', 'S3B2', 'S3B3'];
        const section = document.getElementById('duafa-bnf-section');

        if (!section) return;

        if (triggerGol.includes(gol)) {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
            document.getElementById('periode').value = '';
            document.getElementById('tgl_mulai').value = '';
            document.getElementById('tgl_akhir').value = '';
            document.getElementById('jumlah_beasiswa').value = '';
            document.getElementById('alert').checked = false;
        }
    }

    document.getElementById('gol')?.addEventListener('change', toggleDuafaBNF);
    document.addEventListener('DOMContentLoaded', toggleDuafaBNF);

    // ==================== AUTO FILL BEASISWA (S3B1, S3B2, dll) ====================
    document.addEventListener('DOMContentLoaded', function () {
        const gol = document.getElementById('gol');
        const periode = document.getElementById('periode');
        const tglMulai = document.getElementById('tgl_mulai');
        const tglAkhir = document.getElementById('tgl_akhir');
        const jumlahBeasiswa = document.getElementById('jumlah_beasiswa');

        const allowedGol = ['S3B1', 'S3B2', 'S3B3', 'D'];

        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }

        function addMonths(date, m) {
            let d = new Date(date);
            d.setMonth(d.getMonth() + m);
            return d;
        }

        gol?.addEventListener('change', function () {
            if (!allowedGol.includes(this.value)) return;

            // Periode auto
            let current = periode.value;
            if (!current) {
                periode.value = 'Ke-1';
            } else {
                let match = current.match(/\d+/);
                let number = match ? parseInt(match[0]) : 0;
                if (number < 10) {
                    periode.value = `Ke-${number + 1}`;
                }
            }

            // Tanggal mulai = hari ini
            let today = new Date();
            tglMulai.value = formatDate(today);

            // Tanggal akhir = +6 bulan
            let end = addMonths(today, 6);
            tglAkhir.value = formatDate(end);

            // Jumlah beasiswa
            const beasiswaMapping = {
                'S3B1': 100000,
                'S3B2': 200000,
                'S3B3': 50000,
                'D': 300000
            };

            let nominal = beasiswaMapping[this.value] || 0;
            if (nominal > 0) {
                jumlahBeasiswa.value = nominal * 6;
            }
        });
    });

    // ==================== PAKET 72 ====================
    const gol = document.getElementById('gol');
    const paket72Section = document.getElementById('paket72Section');
    const tglBayar = document.getElementById('tgl_bayar');
    const tglSelesai = document.getElementById('tgl_selesai');
    const alert2 = document.getElementById('alert2');

    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    function addMonths(date, m) {
        let d = new Date(date);
        d.setMonth(d.getMonth() + m);
        return d;
    }

    function handlePaket72() {
        if (!gol) return;

        if (gol.value === 'P72') {
            paket72Section.style.display = 'block';

            let today = new Date();

            if (tglBayar && !tglBayar.value) {
                tglBayar.value = formatDate(today);
            }

            let end6 = addMonths(today, 6);
            if (tglSelesai && !tglSelesai.value) {
                tglSelesai.value = formatDate(end6);
            }

            alert2.checked = true;
        } else {
            paket72Section.style.display = 'none';
            tglBayar.value = '';
            tglSelesai.value = '';
            alert2.checked = false;
        }
    }

    gol?.addEventListener('change', handlePaket72);
    handlePaket72(); // untuk edit mode

    // ==================== FORMAT TANGGAL (tgl_lahir) ====================
    document.getElementById('tgl_lahir')?.addEventListener('blur', function () {
        let val = this.value.trim();
        let match = val.match(/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/);

        if (match) {
            let dd = match[1];
            let mm = match[2];
            let yyyy = match[3];
            this.value = `${yyyy}-${mm}-${dd}`;
        }
    });

    // ==================== HITUNG USIA LENGKAP (tgl_lahir) ====================
    document.addEventListener('DOMContentLoaded', function () {
        const tglLahir = document.getElementById('tgl_lahir');
        const usia = document.getElementById('usia');

        function hitungUsia(tanggal) {
            let today = new Date();
            let birth = new Date(tanggal);
            let umur = today.getFullYear() - birth.getFullYear();
            let m = today.getMonth() - birth.getMonth();

            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                umur--;
            }
            return umur;
        }

        function convertDanHitung() {
            let val = tglLahir.value.trim();
            let match = val.match(/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/);

            if (match) {
                val = `${match[3]}-${match[2]}-${match[1]}`;
                tglLahir.value = val;
            }

            if (val) {
                let umur = hitungUsia(val);
                usia.value = isNaN(umur) ? '' : umur;
            } else {
                usia.value = '';
            }
        }

        tglLahir?.addEventListener('change', convertDanHitung);
        tglLahir?.addEventListener('blur', convertDanHitung);
    });

    // ==================== FORMAT TANGGAL DAFTAR ====================
    document.addEventListener('DOMContentLoaded', function () {
        const tglDaftar = document.getElementById('tgl_daftar');

        function convertTanggal(input) {
            let val = input.value.trim();
            let match = val.match(/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/);

            if (match) {
                input.value = `${match[3]}-${match[2]}-${match[1]}`;
            }
        }

        tglDaftar?.addEventListener('blur', function () {
            convertTanggal(this);
        });

        tglDaftar?.addEventListener('change', function () {
            convertTanggal(this);
        });
    });

</script>
@endsection