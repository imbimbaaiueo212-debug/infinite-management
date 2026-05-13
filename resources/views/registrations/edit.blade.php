@extends('layouts.app')

@section('title', 'Edit Pendaftaran')

@section('content')
<div class="container">
    <h2 class="mb-3">Edit Pendaftaran</h2>

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
        $useOld = $errors->any();
    @endphp

    <div class="card">
        <div class="card-body">
            <form method="POST" 
                  action="{{ route('registrations.update', $registration->id) }}" 
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- MURID --}}
                <div class="mb-3">
                    <label class="form-label fw-bold">Murid</label>
                    <select name="student_id" class="form-select" required>
                        @foreach ($students as $s)
                            <option value="{{ $s->id }}"
                                {{ ($useOld ? old('student_id', $registration->student_id) : $registration->student_id) == $s->id ? 'selected' : '' }}>
                                {{ $s->nim ?? '-' }} — {{ $s->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- NIM & NAMA LENGKAP (Readonly + Hidden) - Sesuai Create --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">NIM</label>
                        <input type="text" 
                               class="form-control bg-light" 
                               value="{{ $useOld ? old('bi.nim', $registration->nim ?? $biPrefill['nim'] ?? '') : ($registration->nim ?? $biPrefill['nim'] ?? '') }}"
                               readonly>
                        <input type="hidden" 
                               name="bi[nim]" 
                               value="{{ $useOld ? old('bi.nim', $registration->nim ?? $biPrefill['nim'] ?? '') : ($registration->nim ?? $biPrefill['nim'] ?? '') }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" 
                               class="form-control bg-light" 
                               value="{{ $useOld ? old('bi.nama', $registration->nama ?? $biPrefill['nama'] ?? '') : ($registration->nama ?? $biPrefill['nama'] ?? '') }}"
                               readonly>
                        <input type="hidden" 
                               name="bi[nama]" 
                               value="{{ $useOld ? old('bi.nama', $registration->nama ?? $biPrefill['nama'] ?? '') : ($registration->nama ?? $biPrefill['nama'] ?? '') }}">
                    </div>
                </div>

                {{-- STATUS & TANGGAL DAFTAR --}}
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="pending"  
                                {{ ($useOld ? old('status') : $registration->status) === 'pending' ? 'selected' : '' }}>
                                Pending
                            </option>
                            <option value="verified" 
                                {{ ($useOld ? old('status') : $registration->status) === 'verified' ? 'selected' : '' }}>
                                Verified
                            </option>
                            
                            @if ($isAdmin)
                                <option value="accepted" 
                                    {{ ($useOld ? old('status') : $registration->status) === 'accepted' ? 'selected' : '' }}>
                                    Accepted
                                </option>
                            @else
                                @if ($registration->status === 'accepted')
                                    <option value="accepted" selected>Accepted (Hanya Admin)</option>
                                @endif
                            @endif

                            <option value="rejected" 
                                {{ ($useOld ? old('status') : $registration->status) === 'rejected' ? 'selected' : '' }}>
                                Rejected
                            </option>
                        </select>
                        @if (!$isAdmin)
                            <small class="text-muted">Hanya Admin yang dapat mengubah status menjadi <strong>Accepted</strong>.</small>
                        @endif
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Tanggal Daftar</label>
                        <input type="date" name="tanggal_daftar" class="form-control"
                               value="{{ $useOld ? old('tanggal_daftar') : optional($registration->tanggal_daftar)->format('Y-m-d') }}">
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">Data Buku Induk</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tahapan</label>
                        <select name="bi[tahap]" class="form-control">
                            <option value="">-- Pilih Tahapan --</option>
                            @foreach ($tahapanOptions as $t)
                                <option value="{{ $t }}" {{ ($useOld ? old('bi.tahap') : $biPrefill['tahap']) === $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kelas</label>
                        <select name="bi[kelas]" class="form-control">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($kelasOptions as $k)
                                <option value="{{ $k }}" {{ ($useOld ? old('bi.kelas') : $biPrefill['kelas']) === $k ? 'selected' : '' }}>{{ $k }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Gol</label>
                        <select name="bi[gol]" id="bi_gol" class="form-control">
                            <option value="">-- Pilih Gol --</option>
                            @foreach ($hargaSaptataruna->unique('kode') as $row)
                                @if ($row->kode)
                                    <option value="{{ $row->kode }}" 
                                        {{ ($useOld ? old('bi.gol') : $biPrefill['gol']) === $row->kode ? 'selected' : '' }}>
                                        {{ $row->kode }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">KD</label>
                        <select name="bi[kd]" id="bi_kd" class="form-control">
                            <option value="">-- Pilih KD --</option>
                            @foreach ($kdOptions as $kd)
                                <option value="{{ $kd }}" 
                                    {{ ($useOld ? old('bi.kd') : $biPrefill['kd']) === $kd ? 'selected' : '' }}>
                                    {{ $kd }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">SPP (Rp)</label>
                        <input type="text" id="bi_spp_display" class="form-control"
                               value="{{ $useOld ? old('bi.spp') : $biPrefill['spp'] }}">
                        <input type="hidden" name="bi[spp]" id="bi_spp" 
                               value="{{ $useOld ? old('bi.spp') : $biPrefill['spp'] }}">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Guru</label>
                        <select name="bi[guru]" class="form-control">
                            <option value="">-- Pilih Guru --</option>
                            @foreach ($guruOptions as $g)
                                <option value="{{ $g }}" 
                                    {{ ($useOld ? old('bi.guru') : $biPrefill['guru']) == $g ? 'selected' : '' }}>
                                    {{ $g }}
                                </option>
                            @endforeach
                            @php
                                $currentGuru = $useOld ? old('bi.guru') : $biPrefill['guru'];
                            @endphp
                            @if ($currentGuru && !in_array($currentGuru, $guruOptions))
                                <option value="{{ $currentGuru }}" selected>
                                    {{ $currentGuru }} (Data Lama)
                                </option>
                            @endif
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kode Jadwal</label>
                        <select name="bi[kode_jadwal]" class="form-control">
                            <option value="">-- Pilih Kode Jadwal --</option>
                            @foreach ($kodeJadwalOptions as $kj)
                                <option value="{{ $kj }}" {{ ($useOld ? old('bi.kode_jadwal') : $biPrefill['kode_jadwal']) === $kj ? 'selected' : '' }}>
                                    {{ $kj }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">Penerimaan / Kwitansi</h5>

                <div class="row g-3">
                    <!-- Penerimaan fields tetap sama seperti sebelumnya -->
                    <div class="col-md-4">
                        <label class="form-label">No. Kwitansi</label>
                        <input type="text" name="kwitansi" class="form-control"
                               value="{{ $useOld ? old('kwitansi') : $registration->kwitansi }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Via</label>
                        <select name="via" class="form-select">
                            <option value="">-- Pilih Via --</option>
                            <option value="Cash"     {{ ($useOld ? old('via') : $registration->via) === 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Transfer" {{ ($useOld ? old('via') : $registration->via) === 'Transfer' ? 'selected' : '' }}>Transfer</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tanggal Penerimaan</label>
                        <input type="date" name="tanggal_penerimaan" class="form-control"
                               value="{{ $useOld ? old('tanggal_penerimaan') : optional($registration->tanggal_penerimaan)->format('Y-m-d') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Daftar (Rp)</label>
                        <input type="text" name="daftar" class="form-control money-format"
                               value="{{ $useOld ? old('daftar') : $registration->daftar }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Kaos (Rp)</label>
                        <input type="text" name="kaos" class="form-control money-format"
                               value="{{ $useOld ? old('kaos') : $registration->kaos }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">KPK (Rp)</label>
                        <input type="text" name="kpk" class="form-control money-format"
                               value="{{ $useOld ? old('kpk') : $registration->kpk }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tas (Rp)</label>
                        <input type="text" name="tas" class="form-control money-format"
                               value="{{ $useOld ? old('tas') : $registration->tas }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Sertifikat (Rp)</label>
                        <input type="text" name="sertifikat" class="form-control money-format"
                               value="{{ $useOld ? old('sertifikat') : $registration->sertifikat }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">STPB (Rp)</label>
                        <input type="text" name="stpb" class="form-control money-format"
                               value="{{ $useOld ? old('stpb') : $registration->stpb }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Lain-lain (Rp)</label>
                        <input type="text" name="lain_lain" class="form-control money-format"
                               value="{{ $useOld ? old('lain_lain') : $registration->lain_lain }}">
                    </div>
                </div>

                {{-- Attachment --}}
                <div class="mb-4 mt-4">
                    <label class="form-label">Upload Dokumen Baru (Opsional)</label>
                    @if ($registration->attachment_path)
                        <div class="mb-2">
                            <a href="{{ asset('storage/' . $registration->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                Lihat File Saat Ini
                            </a>
                        </div>
                    @endif
                    <input type="file" name="attachment" class="form-control" accept=".pdf,image/*">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="{{ route('registrations.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Script SPP Auto + Money Format --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapping = @json($sppMapping);
    const gol = document.getElementById('bi_gol');
    const kd  = document.getElementById('bi_kd');
    const sppHidden = document.getElementById('bi_spp');
    const sppDisplay = document.getElementById('bi_spp_display');

    function updateSPP() {
        const g = gol.value;
        const k = kd.value;
        if (mapping[g] && mapping[g][k] !== undefined) {
            const val = parseInt(mapping[g][k]);
            sppHidden.value = val;
            sppDisplay.value = new Intl.NumberFormat('id-ID').format(val);
        }
    }

    gol.addEventListener('change', updateSPP);
    kd.addEventListener('change', updateSPP);

    document.querySelectorAll('.money-format').forEach(el => {
        el.addEventListener('input', () => {
            let val = el.value.replace(/\D/g, '');
            if (val) el.value = new Intl.NumberFormat('id-ID').format(parseInt(val));
        });
    });

    if (!sppDisplay.value) updateSPP();
});
</script>
@endsection