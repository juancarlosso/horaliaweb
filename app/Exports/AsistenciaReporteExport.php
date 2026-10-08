<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Empresa;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;
use XMLWriter;

class AsistenciaReporteExport
{
    private const HEADINGS = [
        'Empresa', 'Personal', 'Fecha', 'Hr. entrada', 'Entrada', 'Retardo (min)', 'Rango entrada',
        'Hr. salida', 'Salida', 'Salida temprana (min)', 'Rango salida', 'Latitud entrada',
        'Longitud entrada', 'Latitud salida', 'Longitud salida',
    ];

    public function generate(Builder $query, Empresa $company, string $startDate, string $endDate): string
    {
        if (!class_exists(ZipArchive::class) || !class_exists(XMLWriter::class)) {
            throw new RuntimeException('La exportación de Excel no está disponible en este servidor.');
        }

        $xlsxPath = tempnam(sys_get_temp_dir(), 'horalia-asistencia-');
        $sheetPath = tempnam(sys_get_temp_dir(), 'horalia-sheet-');
        $logoTempPath = null;
        if ($xlsxPath === false || $sheetPath === false) {
            throw new RuntimeException('No se pudo preparar el archivo de Excel.');
        }

        try {
            $logo = $this->logoImage($company->logo);
            if ($logo !== null) {
                $logoTempPath = tempnam(sys_get_temp_dir(), 'horalia-logo-');
                if ($logoTempPath === false || file_put_contents($logoTempPath, $logo['bytes']) === false) {
                    throw new RuntimeException('No se pudo preparar el logotipo para Excel.');
                }
            }
            $this->writeSheet($query, $company, $startDate, $endDate, $sheetPath, $logo !== null);
            $zip = new ZipArchive();
            if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo crear el archivo de Excel.');
            }

            $zip->addFromString('[Content_Types].xml', $this->contentTypesXml($logo['extension'] ?? null));
            $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
            $zip->addFromString('xl/workbook.xml', $this->workbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
            $zip->addFromString('xl/styles.xml', $this->stylesXml());
            $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
            if ($logoTempPath !== null && $logo !== null) {
                $zip->addFile($logoTempPath, 'xl/media/image1.' . $logo['extension']);
                $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $this->drawingRelationshipsXml());
                $zip->addFromString('xl/drawings/drawing1.xml', $this->drawingXml($logo['width'], $logo['height']));
                $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $this->drawingImageRelationshipsXml($logo['extension']));
            }
            $zip->close();

            return $xlsxPath;
        } catch (\Throwable $exception) {
            @unlink($xlsxPath);
            throw $exception;
        } finally {
            @unlink($sheetPath);
            if ($logoTempPath) @unlink($logoTempPath);
        }
    }

    private function writeSheet(Builder $query, Empresa $company, string $startDate, string $endDate, string $path, bool $hasLogo): void
    {
        $xml = new XMLWriter();
        if (!$xml->openUri($path)) {
            throw new RuntimeException('No se pudo escribir el detalle del reporte.');
        }
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xml->writeAttribute('xmlns:xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
        $lastDataRow = max(7, (clone $query)->count() + 7);
        $xml->startElement('dimension');
        $xml->writeAttribute('ref', 'A1:O' . $lastDataRow);
        $xml->endElement();
        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', '7');
        $xml->writeAttribute('topLeftCell', 'A8');
        $xml->writeAttribute('activePane', 'bottomLeft');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('sheetFormatPr');
        $xml->writeAttribute('defaultRowHeight', '18');
        $xml->endElement();
        $xml->startElement('cols');
        foreach ([28, 30, 14, 12, 12, 16, 18, 12, 12, 22, 18, 18, 18, 18, 18] as $index => $width) {
            $xml->startElement('col');
            $xml->writeAttribute('min', (string) ($index + 1));
            $xml->writeAttribute('max', (string) ($index + 1));
            $xml->writeAttribute('width', (string) $width);
            $xml->writeAttribute('customWidth', '1');
            $xml->endElement();
        }
        $xml->endElement();
        $xml->startElement('sheetData');

        $companyName = $company->razon_social;
        $headerOffset = $hasLogo ? 3 : 0;
        $fiscalLines = $this->fiscalDetailLines($company);
        $headerRows = [1 => ['REPORTE DE ASISTENCIA', 1], 2 => [$companyName, 2]];
        foreach ($fiscalLines as $index => $line) {
            $headerRows[$index + 3] = [$line, 2];
        }
        $periodRow = count($fiscalLines) + 3;
        $headerRows[$periodRow] = ["Periodo: {$startDate} al {$endDate}", 2];
        ksort($headerRows);
        foreach ($headerRows as $row => [$text, $style]) {
            $this->writeRow($xml, $row, array_merge(array_fill(0, $headerOffset, ['', 0]), [[$text, $style]]));
        }
        $headingCells = array_map(fn ($heading) => [$heading, 3], self::HEADINGS);
        $this->writeRow($xml, 7, $headingCells);

        $rowNumber = 8;
        $query->orderByDesc('id')->chunk(500, function ($records) use ($xml, &$rowNumber, $companyName): void {
            foreach ($records as $record) {
                $schedule = $record->personal?->horarios?->firstWhere('dia', $record->fecha?->dayOfWeekIso);
                $values = [
                    $companyName,
                    $record->personal?->nombre ?? '',
                    $record->fecha?->format('d/m/Y') ?? '',
                    $schedule?->entrada ? substr($schedule->entrada, 0, 5) : '',
                    $record->llegada?->format('H:i') ?? '',
                    (int) ($record->minutos_tarde ?? 0),
                    $record->rango_entrada ?? '',
                    $schedule?->salida ? substr($schedule->salida, 0, 5) : '',
                    $record->salida?->format('H:i') ?? '',
                    (int) ($record->minutos_salida_temprano ?? 0),
                    $record->rango_salida ?? '',
                    $record->latitud,
                    $record->longitud,
                    $record->latitud_salida,
                    $record->longitud_salida,
                ];
                $this->writeRow($xml, $rowNumber++, array_map(fn ($value) => [$value, 0], $values));
            }
        });

        $xml->endElement();
        $xml->startElement('autoFilter');
        $xml->writeAttribute('ref', 'A7:O' . max(7, $rowNumber - 1));
        $xml->endElement();
        $xml->startElement('mergeCells');
        $xml->writeAttribute('count', (string) count($headerRows));
        $startColumn = $hasLogo ? 'D' : 'A';
        foreach (array_keys($headerRows) as $row) {
            $range = $startColumn . $row . ':O' . $row;
            $xml->startElement('mergeCell');
            $xml->writeAttribute('ref', $range);
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

    private function logoImage(?string $path): ?array
    {
        if (!$path) return null;

        try {
            $disk = str_starts_with($path, 'empresas/logos/') ? 'public' : 'wasabi';
            $bytes = Storage::disk($disk)->get($path);
            $image = @getimagesizefromstring($bytes);
            $mime = $image['mime'] ?? null;

            if ($mime === 'image/webp' && function_exists('imagecreatefromstring')) {
                $resource = @imagecreatefromstring($bytes);
                if ($resource) {
                    ob_start();
                    imagepng($resource);
                    $bytes = (string) ob_get_clean();
                    imagedestroy($resource);
                    $mime = 'image/png';
                }
            }

            return in_array($mime, ['image/png', 'image/jpeg'], true)
                ? ['bytes' => $bytes, 'extension' => $mime === 'image/png' ? 'png' : 'jpeg', 'width' => (int) $image[0], 'height' => (int) $image[1]]
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function fiscalDetailLines(Empresa $company): array
    {
        $lines = [];
        $regimenes = config('constantes.regimenes_fiscales', []);
        $regimen = $company->regimen_fiscal
            ? $company->regimen_fiscal . (isset($regimenes[$company->regimen_fiscal]['descripcion']) ? ' - ' . $regimenes[$company->regimen_fiscal]['descripcion'] : '')
            : null;
        $identity = array_filter([
            $company->rfc ? 'RFC: ' . $company->rfc : null,
            $regimen ? 'Régimen fiscal: ' . $regimen : null,
        ]);
        if ($identity) $lines[] = implode(' | ', $identity);

        $street = trim(implode(' ', array_filter([
            $company->domicilio_calle,
            $company->domicilio_numero_exterior ? 'No. ' . $company->domicilio_numero_exterior : null,
            $company->domicilio_numero_interior ? 'Int. ' . $company->domicilio_numero_interior : null,
        ])));
        if ($street !== '') $lines[] = $street;

        $neighborhood = $company->domicilio_colonia ? 'Colonia: ' . $company->domicilio_colonia : null;
        $locality = array_filter([
            $company->domicilio_codigo_postal ? 'C.P. ' . $company->domicilio_codigo_postal : null,
            $company->domicilio_municipio,
            $company->domicilio_ciudad,
            $company->domicilio_estado,
        ]);
        if ($neighborhood || $locality) {
            $lines[] = implode(' | ', array_filter([$neighborhood, implode(', ', $locality)]));
        }

        return array_slice($lines, 0, 3);
    }

    private function contentTypesXml(?string $imageExtension = null): string
    {
        $imageType = $imageExtension ? '<Default Extension="' . $imageExtension . '" ContentType="image/' . ($imageExtension === 'jpeg' ? 'jpeg' : 'png') . '"/>' : '';
        $drawing = $imageExtension ? '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>' . $imageType . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $drawing . '</Types>';
    }

    private function drawingRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>';
    }

    private function drawingImageRelationshipsXml(string $extension): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image1.' . $extension . '"/></Relationships>';
    }

    private function drawingXml(int $imageWidth, int $imageHeight): string
    {
        $boxWidth = 2_800_000;
        $boxHeight = 800_000;
        $scale = min($boxWidth / $imageWidth, $boxHeight / $imageHeight);
        $width = (int) round($imageWidth * $scale);
        $height = (int) round($imageHeight * $scale);
        $offsetX = (int) round((4_800_000 - $width) / 2);
        $offsetY = 0;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><xdr:oneCellAnchor><xdr:from><xdr:col>0</xdr:col><xdr:colOff>' . $offsetX . '</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>' . $offsetY . '</xdr:rowOff></xdr:from><xdr:ext cx="' . $width . '" cy="' . $height . '"/><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="2" name="Logotipo de la empresa"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $width . '" cy="' . $height . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>';
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
