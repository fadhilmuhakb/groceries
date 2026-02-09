<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ProductStockExport implements FromCollection, WithHeadings, WithColumnFormatting, WithMapping
{
    private Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function map($row): array
    {
        // prepend space to force Excel to keep as text
        return [
            ' ' . (string)$row['Kode'],
            $row['Produk'],
            $row['Stok sistem'],
        ];
    }

    public function headings(): array
    {
        return ['Kode', 'Produk', 'Stok sistem'];
    }

    public function columnFormats(): array
    {
        // Kolom kode dipaksa menjadi teks agar tidak jadi scientific notation
        return [
            'A' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
