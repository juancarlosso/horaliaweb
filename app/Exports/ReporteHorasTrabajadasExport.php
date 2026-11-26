<?php

namespace App\Exports;

use App\Models\ReporteHorasTrabajadas;
use App\Models\ReporteHorasTrabajadasFila;
use App\Support\DatosFiscalesReporte;
use RuntimeException;
use XMLWriter;

class ReporteHorasTrabajadasExport extends ReporteExcelExport
{
    private const DISPLAY_NOTE = 'Las horas trabajadas corresponden a la columna “Tiempo productivo” del reporte General.';

    public function generate(ReporteHorasTrabajadas $reporte): string
    {
        return $this->generateWorkbook(
            $reporte->empresa,
            'Horas trabajadas',
            fn (string $path, bool $hasLogo) => $this->writeSheet($reporte, $path, $hasLogo),
            fallbackLogoPath: public_path('assets/logos/logoLN.png'),
        );
    }

    protected function logoBox(): array
    {
        return ['column' => 9, 'left' => 90_000, 'top' => 100_000, 'width' => 1_500_000, 'height' => 1_350_000, 'alignRight' => true];
    }

    private function writeSheet(ReporteHorasTrabajadas $reporte, string $path, bool $hasLogo): void
    {
        $xml = new XMLWriter;
        if (! $xml->openUri($path)) {
            throw new RuntimeException('No se pudo escribir el resumen de horas trabajadas.');
        }

        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', '9');
        $xml->writeAttribute('topLeftCell', 'A10');
        $xml->writeAttribute('activePane', 'bottomLeft');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();

        $xml->startElement('sheetFormatPr');
        $xml->writeAttribute('defaultRowHeight', '18');
        $xml->endElement();
        $xml->startElement('cols');
        foreach ([38, 22, 16, 23, 25, 16, 16, 17, 17, 25] as $index => $width) {
            $xml->startElement('col');
            $xml->writeAttribute('min', (string) ($index + 1));
            $xml->writeAttribute('max', (string) ($index + 1));
            $xml->writeAttribute('width', (string) $width);
            $xml->writeAttribute('customWidth', '1');
            $xml->endElement();
        }
        $xml->endElement();

        $xml->startElement('sheetData');
        $company = $reporte->empresa;
        $from = $reporte->desde->format('d/m/Y');
        $through = $reporte->hasta->format('d/m/Y');
        $metadata = [
            1 => ['HORAS TRABAJADAS', 1],
            2 => [$company->nombre ?: $company->razon_social, 2],
            6 => ["Periodo: {$from} al {$through}", 2],
            7 => [self::DISPLAY_NOTE, 2],
        ];
        foreach (DatosFiscalesReporte::lineas($company) as $index => $line) {
            $metadata[$index + 3] = [$line, 2];
        }
        ksort($metadata);
        foreach ($metadata as $row => $cell) {
            $this->writeRow($xml, $row, [$cell], $row === 1 ? 25 : null);
        }

        $headings = [
            'Personal', 'Semana (lunes a domingo)', 'Días registrados', 'Días con tiempo indeterminado',
            ...array_values(ReporteHorasTrabajadasFila::COLUMNAS_DURACION),
        ];
        $this->writeRow($xml, 9, array_map(fn ($heading) => [$heading, 3], $headings), 34);

        $rowNumber = 10;
        $durationStyles = ['minutos_trabajados' => 5, 'minutos_pausas' => 6];
        foreach ($reporte->filas()->lazyById(500) as $row) {
            $cells = [
                [$row->personal, 0],
                [$row->semana->format('d/m/Y'), 0],
                [(int) $row->dias_registrados, 0],
                [(int) $row->dias_indeterminados, 0],
            ];
            foreach (array_keys(ReporteHorasTrabajadasFila::COLUMNAS_DURACION) as $column) {
                $cells[] = [$row->{$column} / 1440, $durationStyles[$column] ?? 4];
            }
            $this->writeRow($xml, $rowNumber++, $cells);
        }

        $xml->endElement();
        $xml->startElement('autoFilter');
        $xml->writeAttribute('ref', 'A9:J'.($rowNumber - 1));
        $xml->endElement();

        $xml->startElement('mergeCells');
        $xml->writeAttribute('count', (string) count($metadata));
        $endColumn = $hasLogo ? 'I' : 'J';
        foreach (array_keys($metadata) as $row) {
            $xml->startElement('mergeCell');
            $xml->writeAttribute('ref', 'A'.$row.':'.$endColumn.$row);
            $xml->endElement();
        }
        $xml->endElement();
        if ($hasLogo) {
            $xml->startElement('drawing');
            $xml->writeAttribute('r:id', 'rId1');
            $xml->endElement();
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
    <numFmts count="1"><numFmt numFmtId="164" formatCode="[h]:mm"/></numFmts>
    <fonts count="5">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="16"/><color rgb="FF1B2348"/><name val="Calibri"/></font>
        <font><i/><sz val="10"/><color rgb="FF667085"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><color rgb="FF1B2348"/><name val="Calibri"/></font>
    </fonts>
    <fills count="5">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FF536DF5"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFD1FAE5"/><bgColor indexed="64"/></patternFill></fill>
        <fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/><bgColor indexed="64"/></patternFill></fill>
    </fills>
    <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="7">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFill="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center" wrapText="1"/>
        </xf>
        <xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>
        <xf numFmtId="164" fontId="4" fillId="3" borderId="0" xfId="0" applyNumberFormat="1" applyFill="1"/>
        <xf numFmtId="164" fontId="4" fillId="4" borderId="0" xfId="0" applyNumberFormat="1" applyFill="1"/>
    </cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML;
    }
}
