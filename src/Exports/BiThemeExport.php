<?php

namespace iEducar\Packages\Bis\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BiThemeExport implements FromArray, WithHeadings
{
    use Exportable;

    public function __construct(
        private array $data,
        private array $headings = []
    ) {
        if (empty($this->headings) && !empty($this->data[0])) {
            $this->headings = array_keys($this->data[0]);
        }
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
