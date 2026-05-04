@extends('layouts.app')

@section('title', 'Tambah Data Penerimaan')

@section('content')
<div class="container">
    <h4 class="mb-3">Tambah Data Penerimaan</h4>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('penerimaan.store') }}" method="POST" id="form-penerimaan" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">NIM <span class="text-danger">*</span></label>
                <select name="nim" id="nimSelect" class="form-select" required>
                    <option value="" disabled selected>-- Pilih NIM --</option>
                    @foreach ($murids as $murid)
                        @php 
                            $nimWithZero = str_pad($murid->nim, 9, '0', STR_PAD_LEFT);
                        @endphp
                        <option value="{{ $nimWithZero }}"
                                data-nama="{{ $murid->nama ?? '' }}"
                                data-kelas="{{ $murid->kelas ?? '' }}"
                                data-gol="{{ $murid->gol ?? '' }}"
                                data-kd="{{ $murid->kd ?? '' }}"
                                data-status="{{ $murid->status ?? 'Aktif' }}"
                                data-guru="{{ $murid->guru ?? '' }}"
                                data-spp="{{ $murid->spp ?? 0 }}"
                                data-bimba_unit="{{ $murid->bimba_unit ?? '' }}"
                                data-no_cabang="{{ $murid->no_cabang ?? '' }}">
                            {{ $nimWithZero }} - {{ $murid->nama ?? 'Nama tidak ada' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label">Nama Murid</label>
                <input type="text" id="namaMuridInput" name="nama_murid" class="form-control" readonly required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Kelas</label>
                <input type="text" id="kelasInput" name="kelas" class="form-control" readonly>
            </div>

            <div class="col-md-1">
                <label class="form-label">Gol</label>
                <input type="text" id="golInput" name="gol" class="form-control" readonly>
            </div>

            <div class="col-md-1">
                <label class="form-label">KD</label>
                <input type="text" id="kdInput" name="kd" class="form-control" readonly>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <input type="text" id="statusInput" name="status" class="form-control" readonly>
            </div>

            <div class="col-md-4">
                <label class="form-label">Guru</label>
                <input type="text" id="guruInput" name="guru" class="form-control" readonly>
            </div>

            <div class="col-md-4">
                <label class="form-label">Nilai SPP / Bulan</label>
                <input type="text" id="nilai_spp" class="form-control text-end fw-bold" readonly>
            </div>
        </div>

        {{-- BIMBA UNIT & NO CABANG – HANYA TAMPIL JIKA ADMIN --}}
        @if (auth()->user()->isAdminUser())
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label">Bimba Unit</label>
                    <input type="text" id="bimbaUnitInput" name="bimba_unit" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">No Cabang</label>
                    <input type="text" id="noCabangInput" name="no_cabang" class="form-control">
                </div>
            </div>
        @else
            {{-- Non-admin: simpan otomatis via hidden (tidak muncul di tampilan) --}}
            <input type="hidden" id="bimbaUnitInput" name="bimba_unit">
            <input type="hidden" id="noCabangInput" name="no_cabang">
        @endif

        <hr class="my-4">

        <h5 class="text-primary">Informasi Pembayaran</h5>
        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <label class="form-label fw-bold">Preview Nomor Kwitansi</label>
                <div class="bg-light p-3 rounded border text-center mb-2">
                    <div class="fs-4 fw-bold" id="kwitansi-preview">Pilih NIM terlebih dahulu</div>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="manual_kwitansi_toggle" name="manual_kwitansi">
                    <label class="form-check-label" for="manual_kwitansi_toggle">
                        Gunakan nomor kwitansi manual
                    </label>
                </div>

                <div id="manual_kwitansi_input" style="display:none;">
                    <input type="text" name="kwitansi_base_manual" id="kwitansi_base_manual" 
                           class="form-control" placeholder="Contoh: KW20260114 atau KW-ABC-001" value="">
                    <small class="text-muted d-block mt-1">
                        Akan otomatis ditambah -01, -02, dst
                    </small>
                </div>

                <small class="text-muted d-block mt-2">
                    Format otomatis: KW[3digitNIM][YY][MM][DD][NN]  
                    Contoh: KW00126011401
                </small>
            </div>

            <div class="col-md-3">
                <label class="form-label">Via Pembayaran</label>
                <select name="via" id="via" class="form-select" required>
                    <option value="" disabled selected>-- Pilih --</option>
                    <option value="cash">Cash</option>
                    <option value="transfer">Transfer</option>
                    <option value="edc">EDC / VA</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Tanggal Bayar</label>
                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
        </div>

        <div class="row g-3 mt-3">
            <div class="col-md-6">
                <label class="form-label">Tanggal Penyerahan Produk (opsional)</label>
                <input type="date" name="tanggal_penyerahan" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">Bukti Transfer (opsional)</label>
                <input type="file" name="bukti_transfer" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <small class="text-muted">Maks. 5 MB</small>
            </div>
        </div>

        <hr class="my-4">

        <h5 class="text-primary">Pembayaran SPP</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label text-danger fw-bold">
                    Jumlah Bulan <span class="text-danger">*</span>
                </label>
                <select name="spp" id="spp_dropdown" class="form-select fs-5 fw-bold text-end" required>
                    <option value="">-- Pilih jumlah bulan --</option>
                </select>
                <small id="spp_info" class="text-muted">Harga per bulan muncul setelah pilih murid</small>
            </div>

            <div class="col-md-8">
                <label class="form-label text-success fw-bold">
                    Untuk Bulan Tahun <span class="text-danger">*</span>
                </label>
                <div id="bulan-container">
                    <div class="d-flex gap-2 mb-2 align-items-center bulan-row">
                        <select name="bulan_bayar[]" class="form-select" style="width:180px;" required>
                            <option value="">Pilih Bulan</option>
                            @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="tahun_bayar[]" class="form-control" style="width:100px;" value="{{ date('Y') }}" min="2020" required>
                        <button type="button" class="btn btn-success btn-sm btn-add-remove">Tambah</button>
                    </div>
                </div>

                <button type="button" id="tambah-bulan-lagi" class="btn btn-link text-primary p-0 mt-1">
                    + Tambah bulan lain
                </button>

                <div id="info-bulan" class="mt-3 p-3 bg-light rounded small"></div>
            </div>
        </div>

        <hr class="my-4">

        <h5>Biaya Lain-lain</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Daftar</label>
                <input type="text" name="daftar" class="form-control biaya-lain text-end" value="0">
            </div>

            <!--- Kaos Pendek --->
            <div class="col-md-3">
                <label class="form-label">Kaos Pendek</label>
                <select name="kaos_pendek_kode" id="kaos_pendek_select" class="form-select mb-1">
                    <option value="">-- Pilih Ukuran Kaos Pendek --</option>
                    @foreach($kaosPendekList as $kaos)  {{-- Pastikan $kaosPendekList dikirim dari controller --}}
                        <option value="{{ $kaos['kode'] }}" 
                                data-harga="{{ $kaos['harga'] }}"
                                data-nama="{{ $kaos['nama'] }}">
                            {{ $kaos['nama'] }} - Rp {{ number_format($kaos['harga'], 0) }}
                        </option>
                    @endforeach
                </select>
                <input type="number" id="kaos_pendek_qty" class="form-control text-center" value="0" min="0" style="width:80px; display:inline-block;">
                <small class="text-muted">× Harga</small>
                <input type="hidden" name="kaos_pendek" id="kaos_pendek_hidden" value="0">
                <div id="ukuran-pendek-container" class="mt-2"></div>
            </div>
            <!--- End --->

            <!--- Kaos Panjang --->
            <div class="col-md-3">
                <label class="form-label">Kaos Panjang (Lengan Panjang)</label>
                <select name="kaos_panjang_kode" id="kaos_panjang_select" class="form-select mb-1">
                    <option value="">-- Pilih Ukuran Kaos Panjang --</option>
                    @foreach($kaosPanjangList as $kaos)
                        <option value="{{ $kaos['kode'] }}" 
                                data-harga="{{ $kaos['harga'] }}"
                                data-nama="{{ $kaos['nama'] }}">
                            {{ $kaos['nama'] }} - Rp {{ number_format($kaos['harga'], 0) }}
                        </option>
                    @endforeach
                </select>
                <input type="number" id="kaos_panjang_qty" class="form-control text-center" value="0" min="0" style="width:80px; display:inline-block;">
                <small class="text-muted">× Harga</small>
                <input type="hidden" name="kaos_panjang" id="kaos_panjang_hidden" value="0">
                <div id="ukuran-panjang-container" class="mt-2"></div>
            </div>
            <!--- End --->

            <div class="col-md-3">
                <label class="form-label">KPK</label>
                <input type="text" name="kpk" class="form-control biaya-lain text-end" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tas</label>
                <input type="text" name="tas" class="form-control biaya-lain text-end" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sertifikat</label>
                <input type="text" name="sertifikat" class="form-control biaya-lain text-end" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">STPB</label>
                <input type="text" name="stpb" class="form-control biaya-lain text-end" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">Event</label>
                <input type="text" name="event" class="form-control biaya-lain text-end" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">Lain-lain</label>
                <input type="text" name="lain_lain" class="form-control biaya-lain text-end" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">RBAS</label>
                <input type="text" name="RBAS" class="form-control biaya-lain text-end" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">BCABS01</label>
                <input type="text" name="BCABS01" class="form-control biaya-lain text-end" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">BCABS02</label>
                <input type="text" name="BCABS02" class="form-control biaya-lain text-end" value="0">
            </div>

            <div class="col-md-4 mt-3">
                <label class="form-label">Voucher (bisa multi)</label>
                <select id="voucher" name="voucher[]" class="form-select" multiple>
                    <option value="">-- Tidak pakai voucher --</option>
                    @foreach ($vouchers as $v)
                        <option value="{{ $v->no_voucher ?? $v->id }}" data-nominal="50000">
                            {{ $v->no_voucher ?? 'VCHR-'.$v->id }} - Rp 50.000 (sisa: {{ $v->jumlah_voucher }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mt-3">
                <label class="form-label fw-bold">TOTAL</label>
                <input type="text" id="total" class="form-control bg-warning text-end fs-5 fw-bold" readonly value="0">
            </div>
        </div>

        <div class="mt-5 text-end">
            <button type="submit" class="btn btn-success btn-lg px-5">Simpan Pembayaran</button>
            <a href="{{ route('penerimaan.index') }}" class="btn btn-outline-secondary btn-lg px-5 ms-3">Kembali</a>
        </div>
    </form>
</div>

<<!-- Dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    let sppPerBulan = 0;
    let currentNimLast3 = '';
    let currentTanggal = '{{ date('Y-m-d') }}';
    let isManualMode = false;

    // ────────────────────────────────────────────────
    // Fungsi bantu
    // ────────────────────────────────────────────────
    function formatRupiah(angka) {
        if (!angka) return '0';
        return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function unformatRupiah(str) {
        return parseInt((str || '0').replace(/\./g, '')) || 0;
    }

    function generateKwitansiBase() {
        if (!currentNimLast3) return null;
        const tgl = new Date(currentTanggal);
        if (isNaN(tgl.getTime())) return null;

        const yy = String(tgl.getFullYear()).slice(-2).padStart(2, '0');
        const mm = String(tgl.getMonth() + 1).padStart(2, '0');
        const dd = String(tgl.getDate()).padStart(2, '0');

        return `KW${currentNimLast3}${yy}${mm}${dd}`;
    }

    // ================================================
    // UPDATE KWITANSI PREVIEW (DENGAN KAOS PENDEK & PANJANG)
    // ================================================
    function updateKwitansiPreview() {
        const jumlahBulan = $('.bulan-row').length;
        
        const totalPendek  = unformatRupiah($('#kaos_pendek_hidden').val());
        const totalPanjang = unformatRupiah($('#kaos_panjang_hidden').val());
        
        // Hitung apakah ada biaya lain + kaos
        const adaBiayaLain = $('.biaya-lain').toArray().some(el => unformatRupiah($(el).val()) > 0);

        // Setiap jenis kaos yang dibeli dihitung sebagai item terpisah
        let totalItem = jumlahBulan;
        if (adaBiayaLain) totalItem++;
        if (totalPendek > 0) totalItem++;
        if (totalPanjang > 0) totalItem++;

        let teks = '';

        if (isManualMode) {
            const manualBase = $('#kwitansi_base_manual').val().trim();
            
            if (!manualBase) {
                teks = 'Masukkan base kwitansi manual';
                $('#kwitansi-preview').removeClass('text-primary').addClass('text-muted');
            } else {
                if (totalItem === 0) {
                    teks = manualBase + ' (belum ada item)';
                } else if (totalItem === 1) {
                    teks = manualBase;
                } else {
                    const prefix = manualBase.endsWith('-') ? '' : '-';
                    teks = manualBase + prefix + '01 s/d ' + manualBase + prefix + String(totalItem).padStart(2, '0');
                }
                $('#kwitansi-preview').removeClass('text-muted').addClass('text-primary');
            }
        } else {
            const base = generateKwitansiBase();
            if (!base) {
                teks = 'Pilih NIM terlebih dahulu';
                $('#kwitansi-preview').removeClass('text-primary').addClass('text-muted');
            } else {
                if (totalItem === 0) {
                    teks = base + ' (belum ada item)';
                } else if (totalItem === 1) {
                    teks = base + '01';
                } else {
                    teks = base + '01 s/d ' + base + String(totalItem).padStart(2, '0');
                }
                $('#kwitansi-preview').removeClass('text-muted').addClass('text-primary');
            }
        }

        $('#kwitansi-preview').text(teks);
    }

    // ================================================
    // KWITANSI MANUAL
    // ================================================
    $('#manual_kwitansi_toggle').on('change', function() {
        isManualMode = this.checked;
        
        if (isManualMode) {
            $('#manual_kwitansi_input').slideDown(200);
            $('#kwitansi_base_manual').focus();
        } else {
            $('#manual_kwitansi_input').slideUp(200);
            $('#kwitansi_base_manual').val('');
        }
        updateKwitansiPreview();
    });

    $('#kwitansi_base_manual').on('input', updateKwitansiPreview);

    // ================================================
    // SELECT2
    // ================================================
    $('#voucher, #via').select2({
        placeholder: "-- Pilih --",
        allowClear: true,
        width: '100%',
        multiple: true
    });

    // ================================================
    // HANDLE KAOS PENDEK & PANJANG (DENGAN QUANTITY)
    // ================================================
    function hitungKaos() {
        // Kaos Pendek
        const pendekHarga = parseFloat($('#kaos_pendek_select').find(':selected').data('harga')) || 0;
        const pendekQty   = parseInt($('#kaos_pendek_qty').val()) || 0;
        const totalPendek = pendekHarga * pendekQty;
        $('#kaos_pendek_hidden').val(totalPendek);

        if (pendekQty > 0 && pendekHarga > 0) {
            $('#ukuran-pendek-container').html(`
                <div class="alert alert-info py-2 small">
                    ${pendekQty} Kaos Pendek × Rp ${formatRupiah(pendekHarga)} = 
                    <strong>Rp ${formatRupiah(totalPendek)}</strong>
                </div>
            `);
        } else {
            $('#ukuran-pendek-container').html('');
        }

        // Kaos Panjang
        const panjangHarga = parseFloat($('#kaos_panjang_select').find(':selected').data('harga')) || 0;
        const panjangQty   = parseInt($('#kaos_panjang_qty').val()) || 0;
        const totalPanjang = panjangHarga * panjangQty;
        $('#kaos_panjang_hidden').val(totalPanjang);

        if (panjangQty > 0 && panjangHarga > 0) {
            $('#ukuran-panjang-container').html(`
                <div class="alert alert-info py-2 small">
                    ${panjangQty} Kaos Panjang × Rp ${formatRupiah(panjangHarga)} = 
                    <strong>Rp ${formatRupiah(totalPanjang)}</strong>
                </div>
            `);
        } else {
            $('#ukuran-panjang-container').html('');
        }

        hitungTotal();
        updateKwitansiPreview();
    }

    // Event Listener Kaos
    $('#kaos_pendek_select, #kaos_panjang_select').on('change', hitungKaos);
    $('#kaos_pendek_qty, #kaos_panjang_qty').on('input', function() {
        let qty = parseInt(this.value) || 0;
        if (qty < 0) this.value = 0;
        hitungKaos();
    });

    // ================================================
    // FUNGSI LOAD VOUCHER
    // ================================================
    function loadVouchersByNim(nim) {
        const voucherSelect = $('#voucher');
        voucherSelect.empty();
        voucherSelect.append('<option value="">-- Tidak pakai voucher --</option>');

        if (!nim) {
            voucherSelect.trigger('change');
            return;
        }

        $.ajax({
            url: '{{ route("penerimaan.vouchers.by.nim") }}',
            type: 'GET',
            data: { nim: nim },
            success: function(data) {
                if (data.length === 0) {
                    voucherSelect.append('<option value="" disabled>Tidak ada voucher tersedia untuk murid ini</option>');
                } else {
                    $.each(data, function(i, v) {
                        voucherSelect.append(
                            `<option value="${v.no_voucher}" data-nominal="50000">
                                ${v.no_voucher} - Rp 50.000 (sisa: ${v.jumlah_voucher})
                             </option>`
                        );
                    });
                }
                voucherSelect.trigger('change');
            },
            error: function() {
                console.error('Gagal memuat voucher');
                voucherSelect.append('<option value="" disabled>Gagal memuat daftar voucher</option>');
                voucherSelect.trigger('change');
            }
        });
    }

    // Event Handler NIM
    $('#nimSelect').select2({
        placeholder: "-- Pilih NIM --",
        width: '100%',
        allowClear: true
    }).on('select2:select', function(e) {
        const opt = $(this).find(':selected');
        currentNimLast3 = String(opt.val()).slice(-3).padStart(3, '0');

        $('#namaMuridInput').val(opt.data('nama'));
        $('#kelasInput').val(opt.data('kelas'));
        $('#golInput').val(opt.data('gol'));
        $('#kdInput').val(opt.data('kd'));
        $('#statusInput').val(opt.data('status'));
        $('#guruInput').val(opt.data('guru'));
        $('#nilai_spp').val(opt.data('spp') > 0 ? 'Rp ' + formatRupiah(opt.data('spp')) : '0');

        const unit = opt.data('bimba_unit') || '';
        const cabang = opt.data('no_cabang') || '';
        $('#bimbaUnitInput').val(unit);
        $('#noCabangInput').val(cabang);

        sppPerBulan = parseInt(opt.data('spp')) || 0;
        updateSppDropdown(sppPerBulan);
        resetBulanRows();
        loadVouchersByNim(opt.val());
        updateKwitansiPreview();
    }).on('select2:clear', function() {
        currentNimLast3 = '';
        $('#namaMuridInput, #kelasInput, #golInput, #kdInput, #statusInput, #guruInput, #nilai_spp, #bimbaUnitInput, #noCabangInput').val('');
        sppPerBulan = 0;
        updateSppDropdown(0);
        resetBulanRows();
        loadVouchersByNim(null);
        updateKwitansiPreview();
    });

    // Fungsi lain (SPP, Bulan, Total, dll)
    function updateSppDropdown(harga) {
        const el = $('#spp_dropdown');
        el.empty().append('<option value="">-- Pilih jumlah bulan --</option>');
        if (harga <= 0) {
            $('#spp_info').text('Tidak ada data SPP');
            el.prop('disabled', true);
            return;
        }
        $('#spp_info').text(`Rp ${formatRupiah(harga)} / bulan`);
        for (let i = 1; i <= 12; i++) {
            const total = harga * i;
            el.append(`<option value="${total}">${i} bulan - Rp ${formatRupiah(total)}</option>`);
        }
        el.prop('disabled', false);
    }

    function resetBulanRows() {
        $('#bulan-container').html(`
            <div class="d-flex gap-2 mb-2 align-items-center bulan-row">
                <select name="bulan_bayar[]" class="form-select" style="width:180px;" required>
                    <option value="">Pilih Bulan</option>
                    ${['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'].map(b => `<option value="${b}">${b}</option>`).join('')}
                </select>
                <input type="number" name="tahun_bayar[]" class="form-control" style="width:100px;" value="${new Date().getFullYear()}" min="2020" required>
                <button type="button" class="btn btn-success btn-sm btn-add-remove">Tambah</button>
            </div>
        `);
        updateInfoBulan();
    }

    function updateInfoBulan() {
        const count = $('.bulan-row').length;
        const totalHarus = count * sppPerBulan;
        const dibayar = parseInt($('#spp_dropdown').val()) || 0;
        const selisih = dibayar - totalHarus;

        let html = `<strong>${count} bulan</strong> × Rp ${formatRupiah(sppPerBulan)} = Rp ${formatRupiah(totalHarus)}<br>`;

        if (dibayar === 0) html += '<span class="text-muted">Belum memilih jumlah bulan</span>';
        else if (selisih > 0) html += `<span class="text-success fw-bold">Kelebihan Rp ${formatRupiah(selisih)} → deposit</span>`;
        else if (selisih < 0) html += `<span class="text-danger fw-bold">Kurang Rp ${formatRupiah(-selisih)}</span>`;
        else html += '<span class="text-info fw-bold">Sesuai tagihan</span>';

        const voucherCount = $('#voucher').val()?.length || 0;
        if (voucherCount > count) {
            html += `<br><span class="text-danger">Voucher melebihi (${voucherCount}/${count})</span>`;
        } else if (voucherCount > 0) {
            html += `<br><span class="text-success">${voucherCount} voucher dipilih</span>`;
        }

        $('#info-bulan').html(html);
        hitungTotal();
        updateKwitansiPreview();
    }

    function hitungTotal() {
        let sum = parseInt($('#spp_dropdown').val()) || 0;

        $('.biaya-lain').each(function() { 
            sum += unformatRupiah($(this).val()); 
        });

        // Kaos Pendek + Panjang
        sum += unformatRupiah($('#kaos_pendek_hidden').val());
        sum += unformatRupiah($('#kaos_panjang_hidden').val());

        let potongan = 0;
        $('#voucher :selected').each(function() { 
            potongan += parseInt($(this).data('nominal') || 0); 
        });
        sum -= potongan;

        $('#total').val(formatRupiah(sum));
    }

    // Event tambah/hapus baris bulan
    $(document).on('click', '.btn-add-remove', function() {
        if ($(this).hasClass('btn-success')) {
            const clone = $(this).closest('.bulan-row').clone(true, true);
            clone.find('select, input[type=number]').val('');
            clone.find('input[type=number]').val(new Date().getFullYear());
            clone.find('.btn-add-remove').removeClass('btn-success').addClass('btn-danger').text('Hapus');
            $('#bulan-container').append(clone);
        } else if ($('.bulan-row').length > 1) {
            $(this).closest('.bulan-row').remove();
        }
        updateInfoBulan();
    });

    $('#tambah-bulan-lagi').click(() => $('.btn-add-remove.btn-success').first()?.click());

    $('#spp_dropdown').on('change', updateInfoBulan);

    $('.biaya-lain').on('input', function() {
        let val = this.value.replace(/\D/g, '');
        this.value = formatRupiah(val);
        hitungTotal();
        updateKwitansiPreview();
    });

    $('#voucher').on('change', function() {
        const max = $('.bulan-row').length;
        const selected = $(this).val()?.length || 0;

        if (selected > max) {
            alert(`Maksimal ${max} voucher untuk ${max} bulan SPP`);
            $(this).val($(this).data('prev') || []).trigger('change');
        } else {
            $(this).data('prev', $(this).val());
            hitungTotal();
            updateInfoBulan();
        }
    }).data('prev', []);

    // ────────────────────────────────────────────────
    // Inisialisasi Awal
    // ────────────────────────────────────────────────
    updateKwitansiPreview();
    updateInfoBulan();
    hitungTotal();

    // Restore jika ada error validasi
    @if(old('nim'))
        setTimeout(() => {
            $('#nimSelect').val('{{ old('nim') }}').trigger('change');
        }, 300);
    @endif

    // Restore Kaos jika ada error validasi
    @if(old('kaos_pendek_kode'))
        setTimeout(() => {
            $('#kaos_pendek_select').val('{{ old('kaos_pendek_kode') }}').trigger('change');
        }, 400);
    @endif

    @if(old('kaos_panjang_kode'))
        setTimeout(() => {
            $('#kaos_panjang_select').val('{{ old('kaos_panjang_kode') }}').trigger('change');
        }, 500);
    @endif
});
</script>

<style>
    .select2-container .select2-selection--single {
        height: calc(1.5em + 0.75rem + 2px) !important;
        padding-top: 0.375rem !important;
        padding-bottom: 0.375rem !important;
    }
    
</style>
@endsection