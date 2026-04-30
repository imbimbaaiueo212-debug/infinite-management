<?php

namespace App\Exports;

use App\Models\BukuInduk;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Contracts\Database\Query\Builder;

class BukuIndukExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = BukuInduk::query();

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
     * 🔥 HEADER 2 BARIS (SUDAH FIX & SEIMBANG)
     */
    public function headings(): array
    {
        return [
            // ===== GROUP =====
            [
                'BUKU INDUK MURID biMBA AIUEO','','','','','','','','','','',

                'MASA AKTIF (DHUAFA & BNF)','','',

                'PAKET 72','','','',

                'SUPPLY MODUL','',

                'JADWAL','',

                'PINDAH','','',

                'LAINNYA','','','','','','','','','','','','',''
            ],

            // ===== DETAIL =====
            [
                'NIM','TGL DAFTAR','NAMA','UNIT','NO CABANG','KELAS','GOL','KD','SPP','TGL MASUK','STATUS',

                'PERIODE','TGL MULAI','TGL AKHIR',

                'TGL BAYAR','TGL SELESAI','ALERT','ALERT 2',

                'ASAL MODUL','KETERANGAN',

                'KODE JADWAL','HARI/JAM',

                'STATUS PINDAH','TGL PINDAH','KE INTERVIO',

                'GURU','ORANGTUA','NO HP','ALAMAT','LEVEL','JENIS KBM',
                'NOTE','NOTE GARANSI','NO VA','NO CAB MERGE'
            ]
        ];
    }

    /**
     * 🔥 DATA
     */
    public function map($item): array
    {
        $sppClean = $item->spp ? (int) str_replace(['.', 'Rp', ' '], '', $item->spp) : null;

        return [
            $item->nim,
            $item->tgl_daftar,
            $item->nama,
            $item->bimba_unit,
            $item->no_cabang,
            $item->kelas,
            $item->gol,
            $item->kd,
            $sppClean,
            $item->tgl_masuk,
            $item->status,

            $item->periode,
            $item->tgl_mulai,
            $item->tgl_akhir,

            $item->tgl_bayar,
            $item->tgl_selesai,
            $item->alert,
            $item->alert2,

            $item->asal_modul,
            $item->keterangan_optional,

            $item->kode_jadwal,
            $item->hari_jam,

            $item->status_pindah,
            $item->tanggal_pindah,
            $item->ke_bimba_intervio,

            $item->guru,
            $item->orangtua,
            $item->no_telp_hp,
            $item->alamat_murid,
            $item->level,
            $item->jenis_kbm,
            $item->note,
            $item->note_garansi,
            $item->no_pembayaran_murid,
            $item->no_cab_merge,
        ];
    }

    /**
     * 🔥 STYLE + MERGE + RAPIIIN
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // ===== MERGE HEADER =====
                $sheet->mergeCells('A1:K1');   // Buku Induk

                $sheet->mergeCells('L1:N1');   // Masa Aktif ✅ FIX

                $sheet->mergeCells('O1:R1');   // Paket 72

                $sheet->mergeCells('S1:T1');   // Supply

                $sheet->mergeCells('U1:V1');   // Jadwal

                $sheet->mergeCells('W1:Y1');   // Pindah

                $sheet->mergeCells('Z1:AJ1');  // Lainnya

                // ===== STYLE HEADER =====
                $sheet->getStyle('A1:AJ2')->getFont()->setBold(true)->setSize(11);

                $sheet->getStyle('A1:AJ2')->getAlignment()
                    ->setHorizontal('center')
                    ->setVertical('center');

                // Background header
                $sheet->getStyle('A1:AJ1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9EAD3');

                // Border header
                $sheet->getStyle('A1:AJ2')->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Freeze
                $sheet->freezePane('A3');

                // Auto width
                foreach (range('A', 'AJ') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Tinggi row
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(22);
            }
        ];
    }
}