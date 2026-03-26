@extends('layouts.app')

@section('title', 'Absensi Relawan')

@section('content')
    <main>
        <div class="container-fluid px-4">
            <h2 class="mt-4 mb-4 fw-bold">Absensi Relawan</h2>

            <div class="card shadow-sm mb-4">
                <div class="card-body">

                    <!-- Header + Tombol Absen Masuk -->
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <h5 class="mb-0 fw-semibold">Daftar Absensi Relawan</h5>
                        <button type="button" class="btn btn-primary" 
                                data-bs-toggle="modal" data-bs-target="#absenMasukModal">
                            <i class="fa-solid fa-plus me-2"></i> Absen Masuk
                        </button>
                    </div>

                    <!-- Notifikasi -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error') || session('danger') || session('warning'))
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            {{ session('error') ?? session('danger') ?? session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Import & Export -->
                    <div class="card shadow-sm mb-4 border-0 bg-light">
                        <div class="card-body py-3">
                            <h6 class="card-title mb-3">Import / Export Data Absensi</h6>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <form action="{{ route('relawan.import') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                                            <button type="submit" class="btn btn-primary">Import Excel</button>
                                        </div>
                                        <small class="text-muted d-block mt-1">File akan difilter sesuai unit Anda</small>
                                    </form>
                                </div>

                                <div class="col-md-6 text-md-end">
                                    <a href="{{ route('relawan.export') . '?' . http_build_query($filters ?? []) }}"
                                       class="btn btn-success btn-sm">
                                        <i class="bi bi-download me-1"></i> Export ke Excel
                                    </a>
                                    <small class="text-muted d-block mt-1">Ekspor sesuai filter saat ini</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter -->
                    <form method="GET" action="{{ route('relawan.index') }}" id="filterForm" class="mb-4">
                        <div class="row g-3 align-items-end">

                            @if (auth()->check() && (auth()->user()->is_admin ?? false))
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label fw-bold small">Unit BIMBA</label>
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

                            <div class="col-md-3 col-lg-{{ auth()->check() && (auth()->user()->is_admin ?? false) ? '2' : '3' }}">
                                <label class="form-label fw-bold small">Tgl Mulai</label>
                                <input type="date" name="date_from" class="form-control form-control-sm"
                                       value="{{ $filters['date_from'] ?? '' }}">
                            </div>

                            <div class="col-md-3 col-lg-{{ auth()->check() && (auth()->user()->is_admin ?? false) ? '2' : '3' }}">
                                <label class="form-label fw-bold small">Tgl Akhir</label>
                                <input type="date" name="date_to" class="form-control form-control-sm"
                                       value="{{ $filters['date_to'] ?? '' }}">
                            </div>

                            <div class="col-md-{{ auth()->check() && (auth()->user()->is_admin ?? false) ? '4' : '5' }} col-lg-{{ auth()->check() && (auth()->user()->is_admin ?? false) ? '4' : '5' }}">
                                <label class="form-label fw-bold small">Nama Relawan (NIK)</label>
                                <select name="nik" id="nikFilter" class="form-select form-select-sm">
                                    <option value="">-- Pilih Nama Relawan --</option>
                                    @foreach($relawanOptions as $r)
                                        <option value="{{ $r->nik }}" {{ ($filters['nik'] ?? '') == $r->nik ? 'selected' : '' }}>
                                            {{ $r->nik }} | {{ $r->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3 col-lg-2">
                                <label class="form-label fw-bold small">Status Kehadiran</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">-- Semua Status --</option>
                                    @foreach(['Hadir', 'Izin', 'Sakit', 'Alpa', 'DT', 'PC', 'Tidak Aktif', 'Cuti', 'Minggu', 'Libur Nasional'] as $s)
                                        <option value="{{ $s }}" {{ ($filters['status'] ?? '') == $s ? 'selected' : '' }}>
                                            {{ $s }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-auto d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                <a href="{{ route('relawan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                            </div>

                        </div>
                    </form>

                    <!-- Tabel -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>NIK</th>
                                    <th>Nama Relawan</th>
                                    <th>Posisi</th>

                                    @if (auth()->check() && (auth()->user()->is_admin ?? false))
                                        <th>Unit</th>
                                        <th>No Cabang</th>
                                    @endif

                                    <th>Tanggal</th>
                                    <th>On Duty</th>
                                    <th>Off Duty</th>
                                    <th>Jam Masuk</th>
                                    <th>Jam Keluar</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th>Alasan</th>
                                    <th>Lembur</th>
                                    <th>Absen Pulang</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($absensi as $data)
                                    @php
                                        $redDates = ['2025-01-01', '2025-01-27', '2025-01-29', '2025-03-01', '2025-03-28', '2025-03-29', '2025-03-31', '2025-04-01', '2025-04-02', '2025-04-03', '2025-04-04', '2025-04-07', '2025-05-01', '2025-05-12', '2025-05-13', '2025-05-29', '2025-05-30', '2025-06-06', '2025-06-27', '2025-09-05', '2025-12-26'];
                                        $isRed = in_array($data->tanggal, $redDates);

                                        $lembur = $data->jam_lembur ?? 0;
                                        $jam = floor($lembur / 60);
                                        $menit = $lembur % 60;
                                    @endphp
                                    <tr class="{{ $isRed ? 'table-danger' : '' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $data->nik ?? '-' }}</td>
                                        <td><strong>{{ $data->nama_relawan }}</strong></td>
                                        <td>{{ $data->posisi ?? '-' }}</td>

                                        @if (auth()->check() && (auth()->user()->is_admin ?? false))
                                            <td>{{ $data->bimba_unit ?? '-' }}</td>
                                            <td>{{ $data->no_cabang ?? '-' }}</td>
                                        @endif

                                        <td class="{{ $isRed ? 'text-danger fw-bold' : '' }}">
                                            {{ \Carbon\Carbon::parse($data->tanggal)->format('d M Y') }}
                                        </td>
                                        <td class="text-center">
                                            @if($data->onduty)
                                                <span class="badge bg-success">{{ \Carbon\Carbon::parse($data->onduty)->format('H:i') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($data->offduty)
                                                <span class="badge bg-danger">{{ \Carbon\Carbon::parse($data->offduty)->format('H:i') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $data->jam_masuk ? \Carbon\Carbon::parse($data->jam_masuk)->format('H:i') : '-' }}</td>
                                        <td>{{ $data->jam_keluar ? \Carbon\Carbon::parse($data->jam_keluar)->format('H:i') : '-' }}</td>
                                        <td>
                                            @if(auth()->user()->is_admin ?? false)
                                                <form action="{{ route('relawan.updateStatus', $data->id) }}" method="POST" class="d-inline">
                                                    @csrf @method('PATCH')
                                                    <select name="status" onchange="this.form.submit()" class="form-select form-select-sm">
                                                        @foreach(['Hadir', 'Izin', 'Sakit', 'Alpa', 'Libur Nasional', 'Minggu', 'DT', 'PC', 'SKD', 'TSKD', 'TS', 'FF', 'Tidak Aktif'] as $s)
                                                            <option value="{{ $s }}" {{ $data->status == $s ? 'selected' : '' }}>{{ $s }}</option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            @else
                                                <span class="badge {{ in_array($data->status, ['Minggu', 'Libur Nasional']) ? 'bg-danger' : ($data->status == 'Hadir' ? 'bg-success' : 'bg-secondary') }}">
                                                    {{ $data->status }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $data->keterangan ?? '-' }}</td>
                                        <td class="{{ $data->alasan ? 'text-danger fw-bold' : 'text-muted' }}">
                                            {{ $data->alasan ?? '-' }}
                                        </td>
                                        <td>{{ $jam }}j {{ $menit }}m</td>
                                        <td class="text-center">
                                            @if(in_array($data->status, ['Hadir', 'DT', 'PC']))
                                                @if(is_null($data->jam_keluar))
                                                    <button type="button" class="btn btn-warning btn-sm" onclick="absenPulang({{ $data->id }})">
                                                        Belum Absen Pulang
                                                    </button>
                                                @else
                                                    <span class="badge bg-success">Sudah</span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">Selesai ({{ $data->status }})</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('relawan.edit', $data->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                            @if(auth()->user()->is_admin ?? false)
                                                <form action="{{ route('relawan.destroy', $data->id) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')">Hapus</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ auth()->check() && (auth()->user()->is_admin ?? false) ? '17' : '15' }}" 
                                            class="text-center py-5 text-muted fst-italic">
                                            Belum ada data absensi relawan
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 d-flex justify-content-center">
                        {{ $absensi->links('pagination::bootstrap-5') }}
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- Modal Absen Masuk (tetap sama seperti sebelumnya) -->
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

                        <!-- Alasan (wajib jika bukan Hadir) -->
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
                $('#nikFilter, #unitFilter').select2({
                    width: '100%',
                    placeholder: function() { return $(this).data('placeholder') || '-- Pilih --'; },
                    allowClear: true
                });

                window.absenPulang = function(id) {
                    if (!confirm('Yakin absen pulang sekarang?')) return;
                    fetch(`/relawan/${id}/pulang`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.error || 'Gagal absen pulang');
                        }
                    })
                    .catch(() => alert('Error koneksi'));
                };

                const checkbox = document.getElementById('absenAllCheckbox');
                if (checkbox) {
                    checkbox.addEventListener('change', function () {
                        document.getElementById('pegawaiSelectBox').style.display = this.checked ? 'none' : 'block';
                    });
                }
            });

            // Toggle Alasan (wajib jika bukan Hadir)
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
                toggleAlasan(); // Jalankan pertama kali
            });
        </script>
    @endpush
@endsection