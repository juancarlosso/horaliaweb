<?php

namespace App\Exports;

use App\Models\Personal;
use Carbon\CarbonInterface;
use RuntimeException;
use XMLWriter;

class PersonalExport extends ReporteExcelExport
{
    private const HEADINGS = [
        'Fecha de alta', 'Código', 'PIN', 'Nombre', 'Sexo', 'Email', 'Teléfono', 'Departamento',
        'Puesto', 'Estatus', 'Fecha de Baja', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo',
    ];

    private const EXCEL_UNIX_EPOCH = 25569;

    public function generate(iterable $employees): string
    {
        return $this->generateWorkbook(null, 'Personal', fn (string $path) => $this->writeSheet($path, $employees));
    }

    private function writeSheet(string $path, iterable $employees): void
    {
        $xml = new XMLWriter;
        if (! $xml->openUri($path)) {
            throw new RuntimeException('No se pudo escribir el listado de personal.');
        }

        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeRaw('<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>');

        $xml->startElement('cols');
        foreach ([16, 16, 11, 34, 16, 36, 19, 27, 25, 15, 17, 21, 21, 21, 21, 21, 21, 21] as $index => $width) {
            $column = $index + 1;
            $xml->writeRaw('<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>');
        }
        $xml->endElement();

        $xml->startElement('sheetData');
        $this->writeRow($xml, 1, array_map(fn ($heading) => [$heading, 1], self::HEADINGS), 25);
        $row = 2;
        foreach ($employees as $employee) {
            $this->writeRow($xml, $row++, $this->employeeCells($employee));
        }
        $xml->endElement();
        $xml->writeRaw('<autoFilter ref="A1:R'.($row - 1).'"/>');
        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
    }

    private function employeeCells(Personal $employee): array
    {
        $cells = [
            [$this->excelDate($employee->created_at), 2],
            [(string) $employee->codigo, 3],
            [(string) $employee->pin, 3],
            [$employee->nombre, 0],
            [ucfirst($employee->sexo ?? ''), 0],
            [$employee->email, 0],
            [(string) $employee->telefono, 3],
            [$employee->departamento?->nombre, 0],
            [$employee->puesto?->nombre, 0],
            [$employee->activo ? 'Activo' : 'Inactivo', 0],
            [$this->excelDate($employee->fecha_baja), 2],
        ];

        $schedule = $employee->horarios->keyBy('dia');
        for ($day = 1; $day <= 7; $day++) {
            $shift = $schedule->get($day);
            if (! $shift?->entrada || ! $shift->salida) {
                $cells[] = ['', 0];

                continue;
            }

            $modality = $shift->modalidad === 'HOME_OFFICE' ? 'HO' : 'PR';
            $cells[] = [substr($shift->entrada, 0, 5).' - '.substr($shift->salida, 0, 5).' '.$modality, 0];
        }

        return $cells;
    }

    private function excelDate(?CarbonInterface $date): ?int
    {
        return $date ? self::EXCEL_UNIX_EPOCH + intdiv(strtotime($date->toDateString().' UTC'), 86400) : null;
    }

    protected function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts>
    <fonts count="2">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF536DF5"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="4">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFill="1" applyAlignment="1"><alignment vertical="center"/></xf>
        <xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
        <xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
    </cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML;
    }
}
