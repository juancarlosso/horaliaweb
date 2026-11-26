<?php

namespace App\Exports;

use App\Models\ReporteGenerado;
use App\Services\ReporteAsistenciasSnapshot;
use App\Support\DatosFiscalesReporte;
use RuntimeException;
use XMLWriter;

class ReporteDepartamentoExport extends ReporteExcelExport
{
    public function generate(ReporteGenerado $reporte, ReporteAsistenciasSnapshot $snapshot): string
    {
        $company = $reporte->empresa;
        $period = $reporte->desde->format('d/m/Y').' al '.$reporte->hasta->format('d/m/Y');
        $metadata = ['REPORTE POR DEPARTAMENTO', $company->nombre ?: $company->razon_social, ...DatosFiscalesReporte::lineas($company), 'Periodo: '.$period];
        $indicators = ReporteAsistenciasSnapshot::INDICADORES;

        return $this->generateWorkbook(
            $company,
            'Resumen',
            fn ($path, $hasLogo) => $this->writeSheet(
                $path, $metadata, ['Departamento', ...array_values($indicators)], [30, 15, 19, 22, 18, 22],
                $this->summaryRows($reporte), $hasLogo,
            ),
            fallbackLogoPath: public_path('assets/logos/logoLN.png'),
            additionalSheets: [
                'Detalle' => fn ($path) => $this->writeSheet($path, [],
                    ['Departamento', 'Personal', 'Fecha', 'Entrada', 'Retardo (min)', 'Rango entrada', 'Salida',
                        'Salida temprana (min)', 'Rango salida', 'Fuera de rango', 'Sin salida'],
                    [28, 32, 16, 13, 18, 20, 13, 23, 20, 18, 15], $this->detailRows($reporte, $snapshot)),
            ],
        );
    }

    protected function logoBox(): array
    {
        return ['column' => 5, 'left' => 65_000, 'top' => 90_000, 'width' => 1_350_000, 'height' => 1_180_000, 'alignRight' => true];
    }

    private function summaryRows(ReporteGenerado $reporte): iterable
    {
        foreach ($reporte->resumen['departamentos'] as $department) {
            yield [$department['nombre'], ...array_map(fn ($key) => $department[$key], array_keys(ReporteAsistenciasSnapshot::INDICADORES))];
        }
        yield ['Total', ...array_map(fn ($key) => $reporte->resumen['totales'][$key], array_keys(ReporteAsistenciasSnapshot::INDICADORES))];
    }

    private function detailRows(ReporteGenerado $reporte, ReporteAsistenciasSnapshot $snapshot): iterable
    {
        foreach ($reporte->resultado()->orderByDesc('fecha')->orderByDesc('origen_id')->lazy(300) as $fila) {
            $record = $snapshot->restaurar($fila->datos);
            yield [
                $record->personal?->departamento?->nombre ?? 'Sin departamento', $record->personal?->nombre,
                $record->fecha->format('d/m/Y'), $record->llegada?->format('H:i'), (int) $record->minutos_tarde,
                $record->rango_entrada, $record->salida?->format('H:i'), (int) $record->minutos_salida_temprano,
                $record->rango_salida, $fila->datos['indicadores']['out_of_range'] ? 'Sí' : 'No',
                $fila->datos['indicadores']['without_exit'] ? 'Sí' : 'No',
            ];
        }
    }

    private function writeSheet(string $path, array $metadata, array $headings, array $widths, iterable $rows, bool $hasLogo = false): void
    {
        $xml = new XMLWriter;
        if (! $xml->openUri($path)) {
            throw new RuntimeException('No se pudo escribir la hoja de departamentos.');
        }

        $header = $metadata ? max(count($metadata) + 2, $hasLogo ? 8 : 1) : 1;
        $end = chr(64 + count($headings));
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xml->writeRaw('<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$header.'" topLeftCell="A'.($header + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>');
        $xml->startElement('cols');
        foreach ($widths as $index => $width) {
            $column = $index + 1;
            $xml->writeRaw('<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>');
        }
        $xml->endElement();
        $xml->startElement('sheetData');
        foreach ($metadata as $index => $value) {
            $cell = [$value, $index === 0 ? 1 : 0];
            $this->writeRow($xml, $index + 1, [$cell], $index === 0 ? 25 : null);
        }
        $this->writeRow($xml, $header, array_map(fn ($heading) => [$heading, 2], $headings), 22);
        $rowNumber = $header + 1;
        foreach ($rows as $row) {
            $this->writeRow($xml, $rowNumber++, array_map(fn ($value) => [$value, 0], $row));
        }
        $xml->endElement();
        $xml->writeRaw('<autoFilter ref="A'.$header.':'.$end.($rowNumber - 1).'"/>');
        if ($metadata) {
            $xml->startElement('mergeCells');
            $metadataEnd = $hasLogo ? 'E' : $end;
            foreach (array_keys($metadata) as $index) {
                $row = $index + 1;
                $xml->writeRaw('<mergeCell ref="A'.$row.':'.$metadataEnd.$row.'"/>');
            }
            $xml->endElement();
        }
        if ($hasLogo) {
            $xml->writeRaw('<drawing r:id="rId1"/>');
        }
        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
    }

    protected function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="3">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="16"/><color rgb="FF1B2348"/><name val="Calibri"/></font>
        <font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF536DF5"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="3">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFill="1"/>
    </cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML;
    }
}
