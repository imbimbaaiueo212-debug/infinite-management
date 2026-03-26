@extends('layouts.app')

@section('title', 'Data Relawan')

@section('content')
<div class="min-h-screen from-cyan-50 to-blue-50 native-container native-scroll flex flex-col">

    <!-- App Bar Header (Mobile) -->
    <div class="d-block d-lg-none app-bar">
        <div class="px-6 py-7">
            <h1 class="text-4xl font-black mb-2 text-center">Data Relawan</h1>
            <p class="text-cyan-100 text-lg opacity-90">Kelola relawan biMBA-AIUEO</p>
        </div>
    </div>

    <!-- FILTER - DIPINDAH KE ATAS (tepat di bawah navbar mobile) -->
    <div class="d-block d-lg-none filter-wrapper">
        <div class="bg-white rounded-4 shadow-sm border p-4">
            <form method="GET" action="{{ route('profiles.index') }}" id="filterForm">
                <div class="space-y-6">
                    <div>
                        <label class="block text-xl font-bold text-gray-800 mb-3">Nama / NIK Relawan</label>
                        <select name="search" id="searchProfile" class="w-full">
                            <option value="">-- Pilih Relawan --</option>
                            @foreach($profileOptions as $p)
                                <option value="{{ $p->id }}" {{ request('search') == $p->id ? 'selected' : '' }}>
                                    {{ $p->nik }} | {{ $p->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Unit biMBA – HANYA UNTUK ADMIN -->
                    @if (auth()->check() && (auth()->user()->is_admin ?? false))
                        <div>
                            <label class="block text-xl font-bold text-gray-800 mb-3">Unit biMBA</label>
                            <select name="unit" id="unitFilter" class="w-full">
                                <option value="">-- Semua Unit --</option>
                                @foreach($unitOptions as $unit)
                                    <option value="{{ $unit }}" {{ request('unit') == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-5">
                        <button type="submit" class="btn btn-primary btn-native font-bold">Terapkan Filter</button>
                        @if(request()->hasAny(['search', 'unit']))
                            <a href="{{ route('profiles.index') }}" class="btn btn-outline-secondary btn-native text-center font-bold">Reset</a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tombol Tambah & Alert (tetap di bawah filter) -->
    <div class="d-block d-lg-none px-4 py-4">
        <a href="{{ route('profiles.create') }}"
           class="btn btn-primary btn-native w-full shadow-2xl hover:shadow-3xl transition-all block text-center mb-5">
            <i class="fas fa-user-plus text-3xl me-4"></i>
            <span class="font-bold text-xl">Tambah Relawan Baru</span>
        </a>

        @if(session('success'))
            <div class="bg-green-100 border-2 border-green-400 text-green-800 px-8 py-6 rounded-3xl text-center font-bold text-xl shadow-xl mb-5">
                {{ session('success') }}
            </div>
        @endif
    </div>

    <!-- DESKTOP: TABEL LENGKAP -->
    <div class="d-none d-lg-block container-fluid px-4 py-4">
        <h2 class="mt-2 mb-4 fw-bold text-center">Data Relawan</h2>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0 fw-semibold">Filter & Pencarian</h5>
                    <a href="{{ route('profiles.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Tambah Relawan Baru
                    </a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- FILTER DESKTOP -->
                <form method="GET" action="{{ route('profiles.index') }}" id="filterFormDesktop" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <!-- Filter Unit biMBA – HANYA UNTUK ADMIN -->
                        @if (auth()->check() && (auth()->user()->is_admin ?? false))
                            <div class="col-lg-3">
                                <label class="form-label fw-bold small">Unit biMBA</label>
                                <select name="unit" id="unitFilterDesktop" class="form-select form-select-sm">
                                    <option value="">-- Semua Unit --</option>
                                    @foreach($unitOptions as $unit)
                                        <option value="{{ $unit }}" {{ request('unit') == $unit ? 'selected' : '' }}>
                                            {{ $unit }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-lg-{{ auth()->check() && (auth()->user()->is_admin ?? false) ? '5' : '8' }}">
                            <label class="form-label fw-bold small">Nama / NIK Relawan</label>
                            <select name="search" id="searchProfileDesktop" class="form-select form-select-sm">
                                <option value="">-- Cari atau pilih relawan --</option>
                                @foreach($profileOptions as $p)
                                    <option value="{{ $p->id }}" {{ request('search') == $p->id ? 'selected' : '' }}>
                                        {{ $p->nik }} | {{ $p->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-{{ auth()->check() && (auth()->user()->is_admin ?? false) ? '4' : '4' }} d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Terapkan Filter</button>
                            @if(request()->hasAny(['search', 'unit']))
                                <a href="{{ route('profiles.index') }}" class="btn btn-outline-secondary btn-sm flex-grow-1">Reset</a>
                            @endif
                        </div>
                    </div>
                </form>

                <!-- TABEL DESKTOP -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm card-body">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" class="text-center align-middle">No</th>
                                @php
                                    $jumlahTanggalKolom = 1;
                                    if($adaMagang) $jumlahTanggalKolom++;
                                    if($adaNonAktif) $jumlahTanggalKolom++;
                                    if($adaResign) $jumlahTanggalKolom++;
                                @endphp

                                <th colspan="{{ 8 + $jumlahTanggalKolom }}" class="text-center">DATA RELAWAN</th>
                                <th colspan="3" class="text-center">JUMLAH MURID</th>
                                <th colspan="2" class="text-center">PENYESUAIAN RB MTV</th>
                                <th colspan="5" class="text-center">PERHITUNGAN RB & KTR</th>
                                <th colspan="3" class="text-center">MUTASI JABATAN</th>
                                <th colspan="4" class="text-center">DATA PRIBADI</th>
                                <th colspan="3" class="text-center">DATA BANK</th>
                                <th colspan="3" class="text-center">DATA MAGANG</th>
                                <th colspan="8" class="text-center">PENDATAAN SERAGAM</th>
                                <th rowspan="2" class="text-center align-middle">Aksi</th>
                            </tr>
                            <!-- baris header kedua -->
                            <tr>
                                <th class="text-center">NIK</th>
                                <th class="text-center">Nama</th>
                                <th class="text-center">Jabatan</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Departemen</th>

                                <!-- Kolom UNIT & CABANG – HANYA UNTUK ADMIN -->
                                @if (auth()->check() && (auth()->user()->is_admin ?? false))
                                    <th class="text-center" style="min-width:160px">Unit biMBA</th>
                                    <th class="text-center">No Cabang</th>
                                @endif

                                <th class="text-center">Tgl Masuk</th>
                                @if($adaMagang)<th class="text-center">Tgl Magang</th>@endif
                                @if($adaNonAktif)<th class="text-center">Tgl Non Aktif</th>@endif
                                @if($adaResign)<th class="text-center">Tgl Resign</th>@endif
                                <th class="text-center">Masa Kerja</th>

                                <th class="text-center">MBA</th>
                                <th class="text-center">ENG</th>
                                <th class="text-center">TTL</th>

                                <th class="text-center">J. Murid (JDWL)</th>
                                <th class="text-center">Jumlah Rombim</th>

                                <th class="text-center">RB</th>
                                <th class="text-center">RB !</th>
                                <th class="text-center">KTR</th>
                                <th class="text-center">KTR !</th>
                                <th class="text-center">RP</th>

                                <th class="text-center">Jenis Mutasi</th>
                                <th class="text-center">Tgl Mutasi</th>
                                <th class="text-center">Masa Kerja Jabatan</th>

                                <th class="text-center">Tgl Lahir</th>
                                <th class="text-center">Usia</th>
                                <th class="text-center">No Telp</th>
                                <th class="text-center">Email</th>

                                <th class="text-center">No Rekening</th>
                                <th class="text-center">Bank</th>
                                <th class="text-center">Atas Nama</th>

                                <th class="text-center">Mentor</th>
                                <th class="text-center">Periode</th>
                                <th class="text-center">Tgl Selesai</th>

                                <th>Kaos K-H</th>
                                <th>Kaos M-K-B</th>
                                <th>Kemeja K-H</th>
                                <th>Blazer Merah</th>
                                <th>Blazer Biru</th>

                                <th class="text-center">Status</th>
                                <th class="text-center">Keterangan</th>
                                <th class="text-center">Tgl Ambil</th>
                            </tr>
                        </thead>
                        <tbody> 
                            @foreach ($profiles as $profile)
                                @php
                                    $formatDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d-m-Y') : '-';
                                    $tglMasuk = $formatDate($profile->tgl_masuk);
                                    $tglLahir = $formatDate($profile->tgl_lahir);
                                    $tglMutasi = $formatDate($profile->tgl_mutasi_jabatan);
                                    $tglSelesaiMagang = $formatDate($profile->tgl_selesai_magang);
                                    $tglAmbilSeragam = $formatDate($profile->tgl_ambil_seragam);

                                    $masaKerja = $profile->masa_kerja !== null
                                        ? intdiv($profile->masa_kerja, 12) . ' tahun ' . ($profile->masa_kerja % 12) . ' bulan'
                                        : '-';
                                @endphp

                                <tr data-id="{{ $profile->id }}">
                                    <td class="text-center table-wrap">{{ $loop->iteration }}</td>
                                    <td class="text-center table-wrap">{{ $profile->nik }}</td>
                                    <td class="text-start text-center">{{ $profile->nama }}</td>
                                    <td class="text-center table-wrap">{{ $profile->jabatan }}</td>
                                    <td class="status-karyawan text-center">{{ $profile->status_karyawan ?? '-' }}</td>
                                    <td class="text-center table-wrap">{{ $profile->departemen ?? '-' }}</td>

                                    <!-- Kolom UNIT & CABANG – HANYA UNTUK ADMIN -->
                                    @if (auth()->check() && (auth()->user()->is_admin ?? false))
                                        <td class="fw-bold text-primary">{{ $profile->bimba_unit ?? '-' }}</td>
                                        <td class="text-center table-wrap">{{ $profile->no_cabang ?? '-' }}</td>
                                    @endif

                                    <td class="text-center table-wrap">{{ $tglMasuk }}</td>

                                    @if($adaMagang)
                                        <td class="text-center table-wrap">{{ $formatDate($profile->tgl_magang) }}</td>
                                    @endif

                                    @if($adaNonAktif)
                                        <td class="text-center table-wrap">{{ $formatDate($profile->tgl_non_aktif) }}</td>
                                    @endif

                                    @if($adaResign)
                                        <td class="text-center table-wrap">{{ $formatDate($profile->tgl_resign) }}</td>
                                    @endif

                                    <td class="text-center table-wrap">{{ $masaKerja }}</td>
                                    <td class="text-center table-wrap">{{ $profile->jumlah_murid_mba ?? 0 }}</td>
                                    <td class="text-center table-wrap">{{ $profile->jumlah_murid_eng ?? 0 }}</td>
                                    <td class="text-center table-wrap">{{ $profile->total_murid ?? 0 }}</td>

                                    <td class="text-center table-wrap">
                                        {{ $profile->jumlah_murid_jadwal ? $profile->jumlah_murid_jadwal . ($profile->rb ? ' (' . $profile->rb . ')' : '') : '-' }}
                                    </td>
                                    <td class="kolom-rombim text-center">{{ $profile->jumlah_rombim ?? '-' }}</td>

                                    <!-- RB -->
                                    <td class="text-center table-wrap">
                                        @if(auth()->user()->is_admin ?? false)
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <select class="inline-rb-select form-select form-select-sm" data-id="{{ $profile->id }}">
                                                    <option value="">RB..</option>
                                                    @foreach($rbOptions as $rb)
                                                        <option value="{{ $rb }}" {{ $profile->rb == $rb ? 'selected' : '' }}>{{ $rb }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="saving-rb">Saving...</span>
                                            </div>
                                        @else
                                            {{ $profile->rb ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="kolom-rb-calc text-primary fw-bold">{{ $profile->rb_tambahan ?? '-' }}</td>

                                    <!-- KTR -->
                                    <td class="text-center table-wrap">
                                        @if(auth()->user()->is_admin ?? false)
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                <select class="inline-ktr-select form-select form-select-sm" data-id="{{ $profile->id }}">
                                                    <option value="" {{ empty($profile->ktr_tambahan) ? 'selected' : '' }}>Otomatis</option>
                                                    <option value="KTR Tambahan" {{ $profile->ktr_tambahan && $profile->ktr_tambahan !== $profile->ktr ? 'selected' : '' }}>KTR Tambahan</option>
                                                    @foreach($ktrOptions as $ktr)
                                                        <option value="{{ $ktr }}" {{ $profile->ktr_tambahan == $ktr ? 'selected' : '' }}>{{ $ktr }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="saving-ktr">Saving...</span>
                                            </div>
                                        @else
                                            {{ $profile->ktr_tambahan ?? $profile->ktr ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="kolom-ktr-tambahan text-primary fw-bold text-center">{{ $profile->ktr_tambahan ?? $profile->ktr ?? '-' }}</td>

                                    <td class="kolom-rp fw-bold text-primary text-center">
                                        {{ $profile->rp ? number_format($profile->rp, 0, ',', '.') : '-' }}
                                    </td>

                                    <!-- MUTASI -->
                                    <td class="text-center table-wrap">{{ $profile->jenis_mutasi ?? '-' }}</td>
                                    <td class="text-center table-wrap">{{ $tglMutasi }}</td>
                                    <td class="text-center table-wrap">{{ $profile->masa_kerja_jabatan ?? '-' }}</td>

                                    <!-- PRIBADI -->
                                    <td class="text-center table-wrap">{{ $tglLahir }}</td>
                                    <td class="text-center table-wrap">{{ $profile->usia ?? '-' }}</td>
                                    <td class="text-center table-wrap">{{ $profile->no_telp ?? '-' }}</td>
                                    <td class="text-center table-wrap">{{ $profile->email ?? '-' }}</td>

                                    <!-- BANK -->
                                    <td class="text-center table-wrap">{{ $profile->no_rekening ?? '-' }}</td>
                                    <td class="text-center table-wrap">{{ $profile->bank ?? '-' }}</td>
                                    <td class="text-center table-wrap">{{ $profile->atas_nama ?? '-' }}</td>

                                    <!-- MAGANG -->
                                    <td class="text-center table-wrap">
                                        @if(auth()->user()->is_admin ?? false)
                                            <select class="inline-edit form-select form-select-sm" data-id="{{ $profile->id }}" data-field="mentor_magang">
                                                <option value="">- Mentor -</option>
                                                @foreach($mentors as $m)
                                                    <option value="{{ $m->nama }}" {{ $profile->mentor_magang == $m->nama ? 'selected' : '' }}>{{ $m->nama }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            {{ $profile->mentor_magang ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="text-center table-wrap">
                                        @if(auth()->user()->is_admin ?? false)
                                            <select class="inline-edit form-select form-select-sm" data-id="{{ $profile->id }}" data-field="periode">
                                                <option value="">- Periode -</option>
                                                @for($i=1; $i<=12; $i++)
                                                    <option value="Ke - {{ $i }}" {{ $profile->periode == "Ke - $i" ? 'selected' : '' }}>Ke - {{ $i }}</option>
                                                @endfor
                                            </select>
                                        @else
                                            {{ $profile->periode ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="text-center table-wrap">
                                        @if(auth()->user()->is_admin ?? false)
                                            <input type="date" class="inline-edit form-control form-control-sm" data-id="{{ $profile->id }}" data-field="tgl_selesai_magang" value="{{ $profile->tgl_selesai_magang?->format('Y-m-d') }}">
                                            <small class="d-block text-muted mt-1">{{ $tglSelesaiMagang }}</small>
                                        @else
                                            {{ $tglSelesaiMagang }}
                                        @endif
                                    </td>

                                    <!-- SERAGAM -->
                                    <td><input type="text" class="form-control form-control-sm text-center seragam-direct {{ auth()->user()->is_admin ?? false ? '' : 'border-0 bg-transparent' }}" value="{{ $profile->kaos_kuning_hitam ?? '' }}" data-field="kaos_kuning_hitam" {{ auth()->user()->is_admin ?? false ? '' : 'readonly' }}></td>
                                    <td><input type="text" class="form-control form-control-sm text-center seragam-direct {{ auth()->user()->is_admin ?? false ? '' : 'border-0 bg-transparent' }}" value="{{ $profile->kaos_merah_kuning_biru ?? '' }}" data-field="kaos_merah_kuning_biru" {{ auth()->user()->is_admin ?? false ? '' : 'readonly' }}></td>
                                    <td><input type="text" class="form-control form-control-sm text-center seragam-direct {{ auth()->user()->is_admin ?? false ? '' : 'border-0 bg-transparent' }}" value="{{ $profile->kemeja_kuning_hitam ?? '' }}" data-field="kemeja_kuning_hitam" {{ auth()->user()->is_admin ?? false ? '' : 'readonly' }}></td>
                                    <td><input type="text" class="form-control form-control-sm text-center seragam-direct text-danger fw-bold {{ auth()->user()->is_admin ?? false ? '' : 'border-0 bg-transparent' }}" value="{{ $profile->blazer_merah ?? '' }}" data-field="blazer_merah" {{ auth()->user()->is_admin ?? false ? '' : 'readonly' }}></td>
                                    <td><input type="text" class="form-control form-control-sm text-center seragam-direct text-primary fw-bold {{ auth()->user()->is_admin ?? false ? '' : 'border-0 bg-transparent' }}" value="{{ $profile->blazer_biru ?? '' }}" data-field="blazer_biru" {{ auth()->user()->is_admin ?? false ? '' : 'readonly' }}></td>

                                    <td>
                                        @if(auth()->user()->is_admin ?? false)
                                            <select class="inline-edit form-select form-select-sm" data-id="{{ $profile->id }}" data-field="status_lain">
                                                <option value="">-</option>
                                                <option value="Sudah diterima" {{ $profile->status_lain == 'Sudah diterima' ? 'selected' : '' }}>Sudah</option>
                                                <option value="Belum diterima" {{ $profile->status_lain == 'Belum diterima' ? 'selected' : '' }}>Belum</option>
                                            </select>
                                        @else
                                            {{ $profile->status_lain ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(auth()->user()->is_admin ?? false)
                                            <textarea class="inline-edit form-control form-control-sm" data-id="{{ $profile->id }}" data-field="keterangan" rows="2">{{ $profile->keterangan }}</textarea>
                                        @else
                                            {{ $profile->keterangan ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(auth()->user()->is_admin ?? false)
                                            <input type="date" class="inline-edit form-control form-control-sm" data-id="{{ $profile->id }}" data-field="tgl_ambil_seragam" value="{{ $profile->tgl_ambil_seragam?->format('Y-m-d') }}">
                                            <small class="d-block text-muted mt-1">{{ $tglAmbilSeragam }}</small>
                                        @else
                                            {{ $tglAmbilSeragam }}
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Edit selalu ada untuk semua user -->
                                            <a href="{{ route('profiles.edit', $profile->id) }}" 
                                               class="btn btn-warning">Edit</a>

                                            <!-- Hapus HANYA untuk admin -->
                                            @if(auth()->user()->is_admin ?? false)
                                                <form action="{{ route('profiles.destroy', $profile->id) }}" method="POST" 
                                                      class="d-inline" onsubmit="return confirm('Yakin hapus {{ addslashes($profile->nama) }}?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">Hapus</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MOBILE: CARD VIEW (HANYA MUNCUL DI MOBILE) -->
    <div class="d-block d-lg-none w-100 max-w-md mx-auto border-t border-gray-200 shadow-sm my-6 px-3">
        <div class="mobile-list-container py-3">

            @forelse($profiles as $profile)
                @php
                    $formatDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '-';
                    $masaKerja = $profile->masa_kerja !== null
                        ? intdiv($profile->masa_kerja, 12).' th '.($profile->masa_kerja % 12).' bln'
                        : '-';
                @endphp

                <div class="relawan-card-item mb-4 pb-4 border-bottom border-gray-200" data-id="{{ $profile->id }}">

                    <!-- HEADER -->
                    <div class="card-header pb-2 border-bottom border-gray-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <div class="fw-bold" style="font-size: 1rem">{{ $profile->nama }}</div>
                                <div class="small text-muted">
                                    {{ $profile->nik }} • {{ $profile->jabatan }}
                                </div>
                            </div>
                            <span class="badge bg-secondary bg-opacity-25 px-3 py-1 rounded-pill small fw-bold">
                                #{{ $loop->iteration }}
                            </span>
                        </div>

                        <!-- UNIT & CABANG – HANYA UNTUK ADMIN di mobile -->
                        @if (auth()->check() && (auth()->user()->is_admin ?? false))
                            <div class="small text-muted">
                                <strong>Unit:</strong> {{ $profile->bimba_unit ?? '-' }} 
                                <span class="ms-2">({{ $profile->no_cabang ?? '-' }})</span>
                            </div>
                        @endif
                    </div>

                    <!-- BODY -->
                    <div class="py-3 d-flex flex-column gap-4">

                        <!-- Informasi Utama -->
                        <div class="mobile-section">
                            <div class="mobile-title fw-semibold mb-2">Informasi Utama</div>
                            <div class="mobile-grid-2 row row-cols-2 g-2 small">
                                <div class="mobile-inline">
                                    <span class="mobile-label text-muted">Status</span>
                                    <div class="mobile-value fw-medium">{{ $profile->status_karyawan ?? '-' }}</div>
                                </div>
                                <div class="mobile-inline">
                                    <span class="mobile-label text-muted">Departemen</span>
                                    <div class="mobile-value fw-medium">{{ $profile->departemen ?? '-' }}</div>
                                </div>
                                <div class="mobile-inline">
                                    <span class="mobile-label text-muted">Tgl Masuk</span>
                                    <div class="mobile-value fw-medium">{{ $formatDate($profile->tgl_masuk) }}</div>
                                </div>
                                <div class="mobile-inline">
                                    <span class="mobile-label text-muted">Masa Kerja</span>
                                    <div class="mobile-value fw-medium">{{ $masaKerja }}</div>
                                </div>
                                <div class="mobile-inline">
                                    <span class="mobile-label text-muted">Total Murid</span>
                                    <div class="mobile-value text-success fw-bold">{{ $profile->total_murid ?? 0 }}</div>
                                </div>
                                <div class="mobile-inline">
                                    <span class="mobile-label text-muted">RP</span>
                                    <div class="mobile-value text-success fw-bold">
                                        {{ $profile->rp ? number_format($profile->rp, 0, ',', '.') : '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RB & KTR -->
                        <div class="mobile-section pt-3 border-top border-gray-100">
                            <div class="mobile-title fw-semibold mb-2">RB & KTR</div>

                            <div class="d-flex flex-column gap-2">
                                <!-- RB -->
                                <div class="mobile-inline d-flex align-items-center gap-2">
                                    <span class="mobile-label text-muted w-16">RB</span>

                                    @if(auth()->user()->is_admin ?? false)
                                        <select class="inline-rb-select form-select form-select-sm rounded-pill flex-grow-1"
                                                data-id="{{ $profile->id }}"
                                                style="max-width: 130px">
                                            <option value="">RB...</option>
                                            @foreach($rbOptions as $rb)
                                                <option value="{{ $rb }}" {{ $profile->rb == $rb ? 'selected' : '' }}>
                                                    {{ $rb }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="saving-rb text-primary ms-2 fst-italic">Saving...</small>
                                    @else
                                        <span class="mobile-value fw-medium">{{ $profile->rb ?? '-' }}</span>
                                    @endif

                                    <span class="mobile-value text-primary fw-bold kolom-rb-calc ms-auto">
                                        {{ $profile->rb_tambahan ?? '' }}
                                    </span>
                                </div>

                                <!-- KTR -->
                                <div class="mobile-inline d-flex align-items-center gap-2">
                                    <span class="mobile-label text-muted w-16">KTR</span>

                                    @if(auth()->user()->is_admin ?? false)
                                        <select class="inline-ktr-select form-select form-select-sm rounded-pill flex-grow-1"
                                                data-id="{{ $profile->id }}"
                                                style="max-width: 160px">
                                            <option value="">KTR...</option>
                                            <option value="KTR Tambahan" {{ $profile->ktr == 'KTR Tambahan' ? 'selected' : '' }}>
                                                KTR Tambahan
                                            </option>
                                            @foreach($ktrOptions as $ktr)
                                                <option value="{{ $ktr }}"
                                                    {{ ($profile->ktr == $ktr || $profile->ktr_tambahan == $ktr) ? 'selected' : '' }}>
                                                    {{ $ktr }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="saving-ktr text-primary ms-2 fst-italic">Saving...</small>
                                    @else
                                        <span class="mobile-value fw-medium">
                                            {{ $profile->ktr_tambahan ?? $profile->ktr ?? '-' }}
                                        </span>
                                    @endif

                                    <span class="mobile-value text-info fw-bold kolom-ktr-tambahan ms-auto">
                                        {{ $profile->ktr_tambahan ?? '' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Seragam -->
                        <div class="mobile-section pt-3 border-top border-gray-100">
                            <div class="mobile-title fw-semibold mb-2">Pendataan Seragam</div>

                            <div class="seragam-grid row row-cols-3 g-2 mb-3">
                                @php
                                    $seragam = [
                                        'Kaos K-H'     => 'kaos_kuning_hitam',
                                        'Kaos M-K-B'   => 'kaos_merah_kuning_biru',
                                        'Kemeja K-H'   => 'kemeja_kuning_hitam',
                                        'Blazer Merah' => 'blazer_merah',
                                        'Blazer Biru'  => 'blazer_biru',
                                    ];
                                @endphp
                                @foreach($seragam as $label => $field)
                                    <div class="seragam-item text-center">
                                        <small class="d-block text-muted mb-1">{{ $label }}</small>
                                        <input type="text"
                                               class="form-control seragam-direct text-center fw-bold py-1"
                                               value="{{ $profile->$field ?? '' }}"
                                               data-field="{{ $field }}"
                                               {{ auth()->user()->is_admin ?? false ? '' : 'readonly bg-light' }}>
                                    </div>
                                @endforeach
                            </div>

                            <div>
                                <small class="mobile-label d-block mb-1 text-muted">Keterangan</small>
                                @if(auth()->user()->is_admin ?? false)
                                    <textarea class="inline-edit form-control rounded-3 small" rows="2"
                                              data-id="{{ $profile->id }}" 
                                              data-field="keterangan">{{ $profile->keterangan ?? '' }}</textarea>
                                @else
                                    <div class="p-2 bg-light rounded-3 small text-muted">
                                        {{ $profile->keterangan ?? '-' }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Aksi -->
                        <div class="text-center mt-4">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('profiles.edit', $profile->id) }}" 
                                   class="btn btn-warning">Edit</a>

                                @if(auth()->user()->is_admin ?? false)
                                    <form action="{{ route('profiles.destroy', $profile->id) }}" method="POST" 
                                          class="d-inline" onsubmit="return confirm('Yakin hapus {{ addslashes($profile->nama) }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            @empty
                <div class="text-center py-5 my-4">
                    <p class="text-muted fw-medium fs-5">Belum ada data relawan</p>
                </div>
            @endforelse

        </div>
    </div>


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Inisialisasi Select2 untuk filter
    $('#searchProfile, #unitFilter, #searchProfileDesktop, #unitFilterDesktop').select2({
        width: '100%',
        placeholder: "Pilih...",
        allowClear: true
    });

    // Auto submit filter saat berubah
    $('#searchProfile, #unitFilter, #searchProfileDesktop, #unitFilterDesktop').on('change', function () {
        $(this).closest('form').submit();
    });

    // Seragam direct input
    $('.seragam-direct').each(function() {
        if ($(this).prop('readonly')) return;

        let timer;
        const input = this;
        const send = () => {
            const id = $(input).closest('[data-id]').data('id');
            const field = input.dataset.field;
            let ukuran = input.value.trim().toUpperCase();

            if (ukuran && !['S','M','L','XL','XXL'].includes(ukuran)) {
                input.value = input.dataset.last || '';
                return;
            }

            input.dataset.last = ukuran;

            fetch(`/profiles/${id}/seragam-kolom`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ field, ukuran: ukuran || null })
            })
            .then(r => r.json())
            .then(() => {
                $(input).css('border-color', ukuran ? '#28a745' : '#ced4da');
            })
            .catch(() => {
                $(input).css('border-color', '#dc3545');
            });
        };

        $(input).on('blur', () => {
            clearTimeout(timer);
            timer = setTimeout(send, 300);
        }).on('keypress', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(input).blur();
            }
        });
    });

    // Inline edit umum (select, date, textarea)
    $('.inline-edit').on('change blur', function(e) {
        const $this = $(this);
        const id = $this.data('id');
        const field = $this.data('field');
        let value = $this.val();

        if ($this.is('textarea') || $this.is('input[type="date"]')) {
            clearTimeout($this.data('timer'));
            $this.data('timer', setTimeout(() => sendUpdate(id, field, value, $this), 500));
        } else {
            sendUpdate(id, field, value, $this);
        }

        function sendUpdate(id, field, value, $element) {
            $element.prop('disabled', true).addClass('opacity-50');

            fetch(`/profiles/${id}/inline-update-field`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ field, value: value === '' ? null : value })
            })
            .then(r => {
                if (!r.ok) throw new Error('Gagal menyimpan');
                return r.json();
            })
            .then(data => {
                $element.prop('disabled', false).removeClass('opacity-50');

                if (data.status_karyawan) {
                    $(`td.status-karyawan[data-id="${id}"]`).text(data.status_karyawan);
                    if (data.status_karyawan === 'Aktif') {
                        $(`td.status-karyawan[data-id="${id}"]`).html('<span class="badge bg-success">Aktif</span>');
                    } else if (data.status_karyawan === 'Magang') {
                        $(`td.status-karyawan[data-id="${id}"]`).html('<span class="badge bg-warning text-dark">Magang</span>');
                    }
                }

                if (data.masa_kerja_text) {
                    $(`td:has(#masa_kerja)`).text(data.masa_kerja_text);
                }

                if (data.formatted_date) {
                    $element.next('small').text(data.formatted_date);
                }

                $element.css('border-color', '#28a745');
                setTimeout(() => $element.css('border-color', ''), 1500);
            })
            .catch(err => {
                console.error(err);
                $element.prop('disabled', false).removeClass('opacity-50');
                $element.css('border-color', '#dc3545');
                alert('Gagal menyimpan perubahan. Coba lagi.');
            });
        }
    });

    // RB Dropdown
    $('.inline-rb-select').on('change', function () {
        const $this = $(this);
        const id = $this.data('id');
        const value = $this.val() || null;
        const $saving = $this.parent().find('.saving-rb');

        $saving.show();

        fetch(`/profiles/${id}/inline-update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ rb: value })
        })
        .then(r => r.json())
        .then(data => {
            $saving.hide();
            if (data.status === 'ok') {
                const p = data.profile;
                $this.val(p.rb ?? '');

                const $row = $this.closest('tr');
                $row.find('.kolom-rb-calc').text(p.rb_tambahan ?? '-');
                $row.find('.kolom-ktr-tambahan').text(p.ktr_tambahan ?? '-');
                $row.find('.kolom-rp').text(p.rp ? new Intl.NumberFormat('id-ID').format(p.rp) : '-');
                $row.find('.kolom-rombim').text(p.jumlah_rombim ?? '-');

                const $ktrSelect = $row.find('.inline-ktr-select');
                if ($ktrSelect.length && p.ktr) $ktrSelect.val(p.ktr);

                const $jdwl = $row.find('td:nth-child(14)');
                if ($jdwl.length && p.jumlah_murid_jadwal !== undefined) {
                    $jdwl.text(p.jumlah_murid_jadwal 
                        ? `${p.jumlah_murid_jadwal}${p.rb ? ' (' + p.rb + ')' : ''}` 
                        : '-');
                }
            }
        })
        .catch(() => {
            $saving.hide();
            alert('Gagal menyimpan RB.');
        });
    });

    // KTR Dropdown
    $('.inline-ktr-select').on('change', function () {
        const $this = $(this);
        const id = $this.data('id');
        const value = $this.val() || null;
        const $saving = $this.parent().find('.saving-ktr');

        $saving.show();

        fetch(`/profiles/${id}/inline-update-ktr`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ktr: value })
        })
        .then(r => r.json())
        .then(data => {
            $saving.hide();
            if (data.status === 'ok') {
                const p = data.profile;
                $this.val(p.ktr ?? p.ktr_tambahan ?? '');

                const $row = $this.closest('tr');
                $row.find('.kolom-rb-calc').text(p.rb_tambahan ?? '-');
                $row.find('.kolom-ktr-tambahan').text(p.ktr_tambahan ?? '-');
                $row.find('.kolom-rp').html(
                    p.rp 
                        ? `<span style="color:#198754">Rp ${new Intl.NumberFormat('id-ID').format(p.rp)}</span>`
                        : '-'
                );

                $row.find('td:nth-child(17)').text(p.ktr ?? p.ktr_tambahan ?? '-');

                $row.find('.kolom-rombim').text(p.jumlah_rombim ?? '-');
                const $jdwl = $row.find('td:nth-child(14)');
                if ($jdwl.length && p.jumlah_murid_jadwal !== undefined) {
                    $jdwl.text(p.jumlah_murid_jadwal 
                        ? `${p.jumlah_murid_jadwal}${p.rb ? ' (' + p.rb + ')' : ''}` 
                        : '-');
                }
            } else {
                alert('Gagal update KTR: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error(err);
            $saving.hide();
            alert('Gagal menyimpan KTR. Cek koneksi.');
        });
    });
});
</script>
@endpush
@endsection