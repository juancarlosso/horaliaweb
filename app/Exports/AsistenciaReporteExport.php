<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use ZipArchive;
use XMLWriter;

class AsistenciaReporteExport
{
    private const HEADINGS = [
        'Empresa', 'Personal', 'Fecha', 'Entrada', 'Rango entrada', 'Retardo (min)',
        'Salida', 'Rango salida', 'Salida temprana (min)', 'Latitud entrada',
        'Longitud entrada', 'Latitud salida', 'Longitud salida', 'Foto entrada', 'Foto salida',
    ];

    public function generate(Builder $query, string $companyName, string $startDate, string $endDate): string
    {
        if (!class_exists(ZipArchive::class) || !class_exists(XMLWriter::class)) {
            throw new RuntimeException('La exportación de Excel no está disponible en este servidor.');
        }

        $xlsxPath = tempnam(sys_get_temp_dir(), 'horalia-asistencia-');
        $sheetPath = tempnam(sys_get_temp_dir(), 'horalia-sheet-');
        if ($xlsxPath === false || $sheetPath === false) {
            throw new RuntimeException('No se pudo preparar el archivo de Excel.');
        }

        try {
            $this->writeSheet($query, $companyName, $startDate, $endDate, $sheetPath);
            $zip = new ZipArchive();
            if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo crear el archivo de Excel.');
            }

            $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
            $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
            $zip->addFromString('xl/workbook.xml', $this->workbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
            $zip->addFromString('xl/styles.xml', $this->stylesXml());
            $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
            $zip->close();

            return $xlsxPath;
        } catch (\Throwable $exception) {
            @unlink($xlsxPath);
            throw $exception;
        } finally {
            @unlink($sheetPath);
        }
    }

    private function writeSheet(Builder $query, string $companyName, string $startDate, string $endDate, string $path): void
    {
        $xml = new XMLWriter();
        if (!$xml->openUri($path)) {
            throw new RuntimeException('No se pudo escribir el detalle del reporte.');
        }
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $lastDataRow = max(3, (clone $query)->count() + 3);
        $xml->startElement('dimension');
        $xml->writeAttribute('ref', 'A1:O' . $lastDataRow);
        $xml->endElement();
        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', '3');
        $xml->writeAttribute('topLeftCell', 'A4');
        $xml->writeAttribute('activePane', 'bottomLeft');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('sheetFormatPr');
        $xml->writeAttribute('defaultRowHeight', '18');
        $xml->endElement();
        $xml->startElement('cols');
        foreach ([28, 30, 14, 12, 18, 16, 12, 18, 22, 18, 18, 18, 18, 42, 42] as $index => $width) {
            $xml->startElement('col');
            $xml->writeAttribute('min', (string) ($index + 1));
            $xml->writeAttribute('max', (string) ($index + 1));
            $xml->writeAttribute('width', (string) $width);
            $xml->writeAttribute('customWidth', '1');
            $xml->endElement();
        }
        $xml->endElement();
        $xml->startElement('sheetData');

        $this->writeRow($xml, 1, [['REPORTE DE ASISTENCIA', 1]]);
        $this->writeRow($xml, 2, [["{$companyName} | Periodo: {$startDate} al {$endDate}", 2]]);
        $headingCells = array_map(fn ($heading) => [$heading, 3], self::HEADINGS);
        $this->writeRow($xml, 3, $headingCells);

        $rowNumber = 4;
        $query->orderByDesc('id')->chunk(500, function ($records) use ($xml, &$rowNumber, $companyName): void {
            foreach ($records as $record) {
                $values = [
                    $companyName,
                    $record->personal?->nombre ?? '',
                    $record->fecha?->format('d/m/Y') ?? '',
                    $record->llegada?->format('H:i') ?? '',
                    $record->rango_entrada ?? '',
                    (int) ($record->minutos_tarde ?? 0),
                    $record->salida?->format('H:i') ?? '',
                    $record->rango_salida ?? '',
                    (int) ($record->minutos_salida_temprano ?? 0),
                    $record->latitud,
                    $record->longitud,
                    $record->latitud_salida,
                    $record->longitud_salida,
                    $record->fotoEntradaUrl() ?? '',
                    $record->fotoSalidaUrl() ?? '',
                ];
                $this->writeRow($xml, $rowNumber++, array_map(fn ($value) => [$value, 0], $values));
            }
        });

        $xml->endElement();
        $xml->startElement('autoFilter');
        $xml->writeAttribute('ref', 'A3:O' . max(3, $rowNumber - 1));
        $xml->endElement();
        $xml->startElement('mergeCells');
        $xml->writeAttribute('count', '2');
        foreach (['A1:O1', 'A2:O2'] as $range) {
            $xml->startElement('mergeCell');
            $xml->writeAttribute('ref', $range);
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
    }

    private function writeRow(XMLWriter $xml, int $rowNumber, array $cells): void
    {
        $xml->startElement('row');
        $xml->writeAttribute('r', (string) $rowNumber);
        foreach ($cells as $index => [$value, $style]) {
            $xml->startElement('c');
            $xml->writeAttribute('r', $this->columnName($index + 1) . $rowNumber);
            if ($style > 0) {
                $xml->writeAttribute('s', (string) $style);
            }
            if (is_int($value) || is_float($value)) {
                $xml->writeElement('v', (string) $value);
            } elseif ($value !== null && $value !== '') {
                $xml->writeAttribute('t', 'inlineStr');
                $xml->startElement('is');
                $xml->startElement('t');
                $xml->writeAttribute('xml:space', 'preserve');
                $xml->text((string) $value);
                $xml->endElement();
                $xml->endElement();
            }
            $xml->endElement();
        }
        $xml->endElement();
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $name = chr(65 + $remainder) . $name;
            $number = intdiv($number - 1, 26);
        }

        return $name;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets><sheet name="Asistencias" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="4"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FF1B2348"/><name val="Calibri"/></font><font><i/><sz val="10"/><color rgb="FF667085"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF536DF5"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFill="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
