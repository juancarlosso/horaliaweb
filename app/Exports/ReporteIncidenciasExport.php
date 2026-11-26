<?php

namespace App\Exports;

use App\Models\Empresa;
use App\Services\ReporteIncidenciasService;
use App\Support\DatosFiscalesReporte;
use Carbon\CarbonImmutable;
use RuntimeException;
use XMLWriter;

class ReporteIncidenciasExport extends ReporteExcelExport
{
    private const HEADINGS = ['Fecha', 'Empleado', 'Código', 'Tipo de incidencia', 'Detalle'];

    private const TYPE_STYLES = [
        'retardos' => 4,
        'posibles_faltas' => 5,
        'salidas_pendientes' => 6,
        'vacaciones' => 7,
        'vacaciones_trabajadas' => 8,
        'festivos_trabajados' => 9,
        'incapacidades' => 7,
        'incapacidades_trabajadas' => 8,
    ];

    public function generate(iterable $rows, Empresa $company, string $from, string $through, array $types): string
    {
        return $this->generateWorkbook(
            $company,
            'Incidencias',
            fn (string $path, bool $hasLogo) => $this->writeSheet($rows, $company, $from, $through, $types, $path, $hasLogo),
            fallbackLogoPath: public_path('assets/logos/logoLN.png'),
        );
    }

    protected function logoBox(): array
    {
        return ['column' => 4, 'left' => 3_650_000, 'top' => 100_000, 'width' => 1_500_000, 'height' => 1_350_000, 'alignRight' => true];
    }

    private function writeSheet(iterable $rows, Empresa $company, string $from, string $through, array $types, string $path, bool $hasLogo): void
    {
        $xml = new XMLWriter;
        if (! $xml->openUri($path)) {
            throw new RuntimeException('No se pudo escribir el detalle del reporte.');
        }
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xml->writeAttribute('xmlns:xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', '10');
        $xml->writeAttribute('topLeftCell', 'A11');
        $xml->writeAttribute('activePane', 'bottomLeft');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('sheetFormatPr');
        $xml->writeAttribute('defaultRowHeight', '20');
        $xml->endElement();
        $xml->startElement('cols');
        foreach ([15, 30, 18, 25, 80] as $index => $width) {
            $xml->startElement('col');
            $xml->writeAttribute('min', (string) ($index + 1));
            $xml->writeAttribute('max', (string) ($index + 1));
            $xml->writeAttribute('width', (string) $width);
            $xml->writeAttribute('customWidth', '1');
            $xml->endElement();
        }
        $xml->endElement();
        $xml->startElement('sheetData');

        $startDate = CarbonImmutable::parse($from)->format('d/m/Y');
        $endDate = CarbonImmutable::parse($through)->format('d/m/Y');
        $metadata = [
            1 => ['REPORTE DE INCIDENCIAS', 1],
            2 => [$company->nombre ?: $company->razon_social, 2],
            6 => ['Periodo: '.$startDate.' al '.$endDate, 2],
            7 => ['Emitido: '.now()->format('d/m/Y H:i'), 2],
            8 => ['Tipos seleccionados: '.implode(', ', array_map(fn ($type) => ReporteIncidenciasService::LABELS[$type], $types)), 2],
        ];
        foreach (DatosFiscalesReporte::lineas($company) as $index => $line) {
            $metadata[$index + 3] = [$line, 2];
        }
        ksort($metadata);
        foreach ($metadata as $rowNumber => [$text, $style]) {
            $height = $rowNumber === 1 ? 25 : (mb_strlen($text) > 85 ? 32 : null);
            $this->writeRow($xml, $rowNumber, [[$text, $style]], $height);
        }
        $this->writeRow($xml, 10, array_map(fn ($heading) => [$heading, 3], self::HEADINGS), 30);

        $rowNumber = 11;
        foreach ($rows as $row) {
            $values = [
                date('d/m/Y', strtotime($row['fecha'])),
                $row['empleado'],
                $row['codigo'] ?: '-',
                ReporteIncidenciasService::LABELS[$row['tipo']],
                $row['detalle'],
            ];
            $styles = [0, 0, 0, self::TYPE_STYLES[$row['tipo']] ?? 0, 10];
            $this->writeRow($xml, $rowNumber++, array_map(fn ($value, $style) => [$value, $style], $values, $styles));
        }

        $xml->endElement();
        $xml->startElement('autoFilter');
        $xml->writeAttribute('ref', 'A10:E'.max(10, $rowNumber - 1));
        $xml->endElement();
        $xml->startElement('mergeCells');
        $xml->writeAttribute('count', (string) count($metadata));
        foreach (array_keys($metadata) as $row) {
            $xml->startElement('mergeCell');
            $endColumn = $hasLogo && $row < 8 ? 'D' : 'E';
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
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="4"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FF1B2348"/><name val="Calibri"/></font><font><i/><sz val="10"/><color rgb="FF667085"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="9"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF536DF5"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF59E0B"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEF4444"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF06B6D4"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF8B5CF6"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF10B981"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF4F6EF7"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="11"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="3" fillId="3" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="4" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="5" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="6" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="7" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="8" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
