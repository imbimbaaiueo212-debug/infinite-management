@extends('layouts.app')

@section('title', 'Absensi Relawan')

@section('content')
    <main>
        <div class="container-fluid px-4">
            <h2 class="mt-4 mb-4 fw-bold">Absensi Relawan</h2>

            <div class="card shadow-sm mb-4">
                <div class="card-body">

                    <!-- Header + Tombol Tambah Manual -->
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <h5 class="mb-0 fw-semibold">Daftar Absensi Relawan</h5>
                        <!--<a href="{{ route('absensi-relawan.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Tambah Absensi Manual
                        </a>-->
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Import & Export -->
                    <div class="card shadow-sm mb-4 border-0 bg-light">
                        <div class="card-body py-3">
                            <h6 class="card-title mb-3 fw-semibold">Import / Export Data Absensi</h6>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <form action="{{ route('absensi-relawan.import.store') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                                            <button type="submit" class="btn btn-success">
                                                <i class="bi bi-upload me-1"></i> Import Data
                                            </button>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Format: xlsx, xls, csv. Data akan di-normalisasi otomatis.
                                        </small>
                                    </form>
                                </div>

                                <div class="col-md-6 text-md-end">
                                    <a href="{{ route('absensi-relawan.export') . '?' . http_build_query($filters ?? []) }}"
                                       class="btn btn-primary btn-sm">
                                        <i class="bi bi-download me-1"></i> Export ke Excel
                                    </a>
                                    <small class="text-muted d-block mt-1">
                                        Ekspor sesuai filter saat ini
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter -->
                    <form method="GET" action="{{ route('absensi-relawan.index') }}" id="filterForm" class="mb-4">
                        <div class="row g-3 align-items-end">

                            @if (auth()->check() && (auth()->user()->is_admin ?? false))
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label fw-bold small">Unit biMBA</label>
                                    <select name="bimba_unit" id="unitFilter" class="form-select form-select-sm">
                                        <option value="">-- Semua Unit --</option>
                                        @foreach($unitOptions as $u)
                                            <option value="{{ $u }}" {{ ($filters['bimba_unit'] ?? '') == $u ? 'selected' : '' }}>
                                                {{ $u }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="col-md-{{ auth()->check() && (auth()->user()->is_admin ?? false) ? '4' : '5' }} col-lg-4">
                                <label class="form-label fw-bold small">NIK | Nama Relawan</label>
                                <select name="nik" id="nikFilter" class="form-select form-select-sm">
                                    <option value="">-- Cari NIK atau Nama --</option>
                                    @foreach($muridOptions as $m)
                                        <option value="{{ $m->nim }}" {{ ($filters['nik'] ?? '') == $m->nim ? 'selected' : '' }}>
                                            {{ $m->nim }} • {{ $m->nama_murid }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 col-lg-2">
                                <label class="form-label fw-bold small">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">-- Semua Status --</option>
                                    @foreach(['Hadir','Sakit','Izin','Alpa','DT','PC','Cuti','Tidak Aktif'] as $st)
                                        <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>
                                            {{ $st }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 col-lg-2">
                                <label class="form-label fw-bold small">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control form-control-sm"
                                       value="{{ $filters['tanggal'] ?? '' }}">
                            </div>

                            <div class="col-md-auto d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                <a href="{{ route('absensi-relawan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                            </div>

                        </div>
                    </form>

                    <!-- Tabel -->
                    @if($absensi->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th>NIK</th>
                                        <th>Nama Relawan</th>
                                        <th>Posisi</th>

                                        @if (auth()->check() && (auth()->user()->is_admin ?? false))
                                            <th>Unit</th>
                                            <th>Cabang</th>
                                        @endif

                                        <th>Jabatan</th>
                                        <th>Status</th>
                                        <th>Departemen</th>
                                        <th>Tanggal</th>
                                        <th>Absensi</th>
                                        <th>Status Sistem</th>
                                        <th>Keterangan</th>
                                        <th>Alasan</th>
                                        <th>Lembur</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($absensi as $index => $item)
                                        @php
                                            $isManual = property_exists($item, 'id') && $item->id !== null;
                                        @endphp
                                        <tr class="{{ 
                                            $item->status == 'Hadir' ? 'table-success' : 
                                            ($item->status == 'Alpa' ? 'table-danger' : '') 
                                        }}">
                                            <td class="text-center fw-bold">{{ $loop->iteration }}</td>
                                            <td><span class="badge bg-secondary">{{ $item->nik }}</span></td>
                                            <td class="fw-bold">{{ $item->nama_relawaan }}</td>
                                            <td>{{ $item->posisi ?? '-' }}</td>

                                            @if (auth()->check() && (auth()->user()->is_admin ?? false))
                                                <td class="text-primary fw-bold">{{ $item->bimba_unit ?? '-' }}</td>
                                                <td class="text-center"><span class="badge bg-info text-dark">{{ $item->no_cabang ?? '-' }}</span></td>
                                            @endif

                                            <td>{{ $item->posisi ?? '-' }}</td>
                                            <td>
                                                <span class="badge {{ $item->status_relawaan == 'Aktif' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $item->status_relawaan ?? 'Aktif' }}
                                                </span>
                                            </td>
                                            <td>{{ $item->departemen ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                                            <td>{{ $item->absensi ?? '-' }}</td>
                                            <td class="text-center">
                                                @switch($item->status)
                                                    @case('Hadir')    <span class="badge bg-success">Hadir</span> @break
                                                    @case('Sakit')    <span class="badge bg-info">Sakit</span> @break
                                                    @case('Izin')     <span class="badge bg-warning text-dark">Izin</span> @break
                                                    @case('Alpa')     <span class="badge bg-danger">Alpa</span> @break
                                                    @case('DT')       <span class="badge bg-warning text-dark">DT</span> @break
                                                    @case('PC')       <span class="badge bg-purple text-white">PC</span> @break
                                                    @case('Cuti')     <span class="badge bg-primary">Cuti</span> @break
                                                    @default          <span class="badge bg-secondary">{{ $item->status }}</span>
                                                @endswitch
                                            </td>
                                            <td><small class="text-muted">{{ $item->keterangan ?? '-' }}</small></td>
                                            <td class="{{ $item->alasan ? 'text-danger fw-bold' : 'text-muted' }}">
                                                {{ $item->alasan ?? '-' }}
                                            </td>
                                            <td class="text-center">{{ floor(($item->jam_lembur ?? 0) / 60) }}j {{ ($item->jam_lembur ?? 0) % 60 }}m</td>
                                            <td class="text-center text-nowrap">
                                                @if($isManual && (auth()->user()->is_admin ?? false))
                                                    <a href="{{ route('absensi-relawan.edit', $item->id) }}"
                                                       class="btn btn-warning btn-sm me-1" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <form action="{{ route('absensi-relawan.destroy', $item->id) }}"
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Yakin ingin menghapus absensi ini? Data akan hilang permanen.');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">
                                                        {{ $isManual ? 'Terbatas (admin only)' : 'Otomatis' }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
                            <div class="text-muted small">
                                Menampilkan {{ $absensi->firstItem() }}–{{ $absensi->lastItem() }} dari {{ $absensi->total() }} data
                            </div>
                            {{ $absensi->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5 my-5 text-muted">
                            <h5>Belum ada data absensi untuk periode ini</h5>
                            <small class="d-block mt-2">Coba ubah filter atau tambahkan data manual</small>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </main>

    <!-- Modal Absen Masuk (tetap sama) -->
    <div class="modal fade" id="absenMasukModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('relawan.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Absen Masuk Relawan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if(auth()->user()->is_admin ?? false)
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="absen_all" id="absenAllCheckbox">
                                <label class="form-check-label" for="absenAllCheckbox">
                                    Absen Semua Relawan (Admin Only)
                                </label>
                            </div>
                        @endif

                        <div class="mb-3" id="pegawaiSelectBox">
                            <label class="form-label">Pilih Relawan</label>
                            <select name="nik" class="form-control" required>
                                <option value="" disabled {{ old('nik') ? '' : 'selected' }}>-- Pilih Nama Relawan --</option>
                                @foreach($relawanOptions as $item)
                                    <option value="{{ $item->nik }}" {{ old('nik') == $item->nik ? 'selected' : '' }}>
                                        {{ $item->nama }} ({{ $item->bimba_unit }}{{ $item->no_cabang ? ' - ' . $item->no_cabang : '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Tanggal Absen</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                        </div>

                        <div class="mb-3">
                            <label>Kehadiran</label>
                            <select name="kehadiran" class="form-control" required>
                                <option value="">-- Pilih Kehadiran --</option>
                                @foreach([
                                    'Hadir', 'Datang Terlambat', 'Pulang Cepat', 'Sakit Dengan Keterangan Dokter',
                                    'Sakit Tanpa Keterangan Dokter', 'Izin Dengan Form di ACC', 'Izin Tanpa Form di ACC',
                                    'Tidak Mengisi', 'Tidak Masuk Tanpa Form', 'Cuti', 'Tidak Aktif',
                                    'Hari Minggu', 'Libur Nasional'
                                ] as $status)
                                    <option value="{{ $status }}" {{ old('kehadiran') == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Alasan -->
                        <div class="mb-3" id="alasanContainer" style="display: none;">
                            <label class="form-label">Alasan <span class="text-danger">*</span></label>
                            <textarea name="alasan" class="form-control" rows="3" placeholder="Jelaskan alasan secara singkat dan jelas (wajib untuk status selain Hadir)"></textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Absen</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function () {
                $('#nikFilter').select2({
                    width: '100%',
                    placeholder: '-- Cari NIK atau Nama --',
                    allowClear: true,
                    minimumInputLength: 2
                });

                $('#unitFilter').select2({
                    width: '100%',
                    placeholder: '-- Semua Unit --',
                    allowClear: true
                });
            });

            // Toggle Alasan
            document.addEventListener('DOMContentLoaded', function () {
                const selectKehadiran = document.querySelector('select[name="kehadiran"]');
                const alasanContainer = document.getElementById('alasanContainer');
                const textareaAlasan  = alasanContainer?.querySelector('textarea[name="alasan"]');

                if (!selectKehadiran || !alasanContainer || !textareaAlasan) return;

                function toggleAlasan() {
                    const value = selectKehadiran.value.trim();
                    const perluAlasan = value && value !== 'Hadir';

                    alasanContainer.style.display = perluAlasan ? 'block' : 'none';

                    if (perluAlasan) {
                        textareaAlasan.setAttribute('required', 'required');
                    } else {
                        textareaAlasan.removeAttribute('required');
                    }
                }

                selectKehadiran.addEventListener('change', toggleAlasan);
                toggleAlasan();
            });
        </script>
    @endpush
@endsection