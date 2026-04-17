<?php

namespace App\Exports;

use App\Models\BukuInduk;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Contracts\Database\Query\Builder;

class BukuIndukExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = BukuInduk::query()->with('jadwal');

        // Filter sesuai halaman index
        if (!empty($this->filters['murid'])) {
            $query->where('nim', $this->filters['murid']);
        }
        if (!empty($this->filters['unit'])) {
            $query->where('bimba_unit', $this->filters['unit']);
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('nim', 'asc');
    }

    /**
     * Header ← TAMBAH 'tgl_daftar' setelah 'nim'
     */
    public function headings(): array
    {
        return [
            'nim',
            'tgl_daftar',          // ← NEW: POSISI 2
            'nama',
            'unit',
            'no cabang',
            'kelas',
            'gol',
            'kd',
            'spp',
            'tgl_masuk',
            'status',
            'tmpt_lahir',
            'tgl_lahir',
            'usia',
            'lama_bljr',
            'tahap',
            'tgl_keluar',
            'kategori_keluar',
            'alasan',
            'guru',
            'orangtua',
            'no_telp_hp',
            'alamat_murid',
            'petugas_trial',
            'note',
            'periode',
            'tgl_mulai',
            'tgl_akhir',
            'tgl_bayar',
            'tgl_selesai',
            'level',
            'jenis_kbm',
            'kode_jadwal',
            'hari_jam',
            'no_cab_merge',
            'no_pembayaran_murid',
            'note_garansi',
            'alert',
            'alert2',
            'asal_modul',
            'keterangan_optional',
            'status_pindah',
            'tanggal_pindah',
            'ke_bimba_intervio',
            'keterangan',
        ];
    }

    /**
     * Mapping data ← TAMBAH tgl_daftar
     */
    public function map($item): array
    {
        // Bersihkan SPP jadi angka murni
        $sppClean = $item->spp ? (int) str_replace(['.', 'Rp', ' '], '', $item->spp) : null;

        return [
            $item->nim ?? '',
            $item->tgl_daftar ?? '',           // ← NEW: POSISI 2
            $item->nama ?? '',
            $item->bimba_unit ?? '',
            $item->no_cabang ?? '',
            $item->kelas ?? '',
            $item->gol ?? '',
            $item->kd ?? '',
            $sppClean,
            $item->tgl_masuk ?? '',
            $item->status ?? '',
            $item->tmpt_lahir ?? '',
            $item->tgl_lahir ?? '',
            $item->usia ?? '',
            $item->lama_bljr ?? '',
            $item->tahap ?? '',
            $item->tgl_keluar ?? '',
            $item->kategori_keluar ?? '',
            $item->alasan ?? '',
            $item->guru ?? '',
            $item->orangtua ?? '',
            $item->no_telp_hp ?? '',
            $item->alamat_murid ?? '',
            $item->petugas_trial ?? '',
            $item->note ?? '',
            $item->periode ?? '',
            $item->tgl_mulai ?? '',
            $item->tgl_akhir ?? '',
            $item->tgl_bayar ?? '',
            $item->tgl_selesai ?? '',
            $item->level ?? '',
            $item->jenis_kbm ?? '',
            $item->kode_jadwal ?? '',
            $item->hari_jam ?? '',
            $item->no_cab_merge ?? '',
            $item->no_pembayaran_murid ?? '',
            $item->note_garansi ?? '',
            $item->alert ?? '',
            $item->alert2 ?? '',
            $item->asal_modul ?? '',
            $item->keterangan_optional ?? '',
            $item->status_pindah ?? '',
            $item->tanggal_pindah ?? '',
            $item->ke_bimba_intervio ?? '',
            $item->keterangan ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header style
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => 'FF000000'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD9EAD3'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],

            // Format tanggal ← TAMBAH kolom B (tgl_daftar)
            'B' => [  // ← NEW: tgl_daftar
                'numberFormat' => [
                    'formatCode' => 'dd-mm-yyyy',
                ],
            ],
            'J' => [  // tgl_masuk
                'numberFormat' => [
                    'formatCode' => 'dd-mm-yyyy',
                ],
            ],
            'L' => [  // tgl_lahir
                'numberFormat' => [
                    'formatCode' => 'dd-mm-yyyy',
                ],
            ],
            'P' => [  // tgl_keluar
                'numberFormat' => [
                    'formatCode' => 'dd-mm-yyyy',
                ],
            ],
            'U' => [  // tgl_mulai
                'numberFormat' => [
                    'formatCode' => 'dd-mm-yyyy',
                ],
            ],
            'V' => [  // tgl_akhir
                'numberFormat' => [
                    'formatCode' => 'dd-mm-yyyy',
                ],
            ],
            'W' => [  // tgl_bayar
                'numberFormat' => [
                    'formatCode' => 'dd-mm-yyyy',
                ],
            ],
            'X' => [  // tgl_selesai
                'numberFormat' => [
                    'formatCode' => 'dd-mm-yyyy',
                ],
            ],
            'AG' => [ // tanggal_pindah
                'numberFormat' => [
                    'formatCode' => 'dd-mm-yyyy',
                ],
            ],
        ];
    }
}