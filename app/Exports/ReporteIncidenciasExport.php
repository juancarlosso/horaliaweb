<?php

namespace App\Exports;

use App\Models\Empresa;
use App\Services\ReporteIncidenciasService;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use XMLWriter;
use ZipArchive;

class ReporteIncidenciasExport
{
    private const HEADINGS = ['Fecha', 'Empleado', 'Código', 'Tipo de incidencia', 'Detalle'];
    private const TYPE_STYLES = [
        'retardos' => 4,
        'posibles_faltas' => 5,
        'salidas_pendientes' => 6,
        'vacaciones' => 7,
        'vacaciones_trabajadas' => 8,
        'festivos_trabajados' => 9,
    ];

    public function generate(array $rows, Empresa $company, string $from, string $through, array $types): string
    {
        if (!class_exists(ZipArchive::class) || !class_exists(XMLWriter::class)) {
            throw new RuntimeException('La exportación de Excel no está disponible en este servidor.');
        }

        $xlsxPath = tempnam(sys_get_temp_dir(), 'horalia-incidencias-');
        $sheetPath = tempnam(sys_get_temp_dir(), 'horalia-inc-sheet-');
        $logoTempPath = null;
        if ($xlsxPath === false || $sheetPath === false) {
            throw new RuntimeException('No se pudo preparar el archivo de Excel.');
        }

        try {
            $logo = $this->logoImage($company->logo);
            if ($logo !== null) {
                $logoTempPath = tempnam(sys_get_temp_dir(), 'horalia-inc-logo-');
                if ($logoTempPath === false || file_put_contents($logoTempPath, $logo['bytes']) === false) {
                    throw new RuntimeException('No se pudo preparar el logotipo para Excel.');
                }
            }

            $this->writeSheet($rows, $company, $from, $through, $types, $sheetPath, $logo !== null);
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
                $zip->addFile($logoTempPath, 'xl/media/image1.'.$logo['extension']);
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

    private function writeSheet(array $rows, Empresa $company, string $from, string $through, array $types, string $path, bool $hasLogo): void
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
        $xml->startElement('dimension');
        $xml->writeAttribute('ref', 'A1:E'.max(10, count($rows) + 10));
        $xml->endElement();
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

        $companyName = $company->nombre;
        $offset = $hasLogo ? 1 : 0;
        $metadata = [
            1 => ['REPORTE DE INCIDENCIAS', 1],
            2 => [$companyName, 2],
            6 => ['Periodo: '.$from.' al '.$through, 2],
            7 => ['Emitido: '.now()->format('d/m/Y H:i'), 2],
            8 => ['Tipos seleccionados: '.implode(', ', array_map(fn ($type) => ReporteIncidenciasService::LABELS[$type], $types)), 2],
        ];
        foreach ($this->fiscalDetailLines($company) as $index => $line) {
            $metadata[$index + 3] = [$line, 2];
        }
        ksort($metadata);
        foreach ($metadata as $rowNumber => [$text, $style]) {
            $cells = array_merge(array_fill(0, $offset, ['', 0]), [[$text, $style]]);
            $this->writeRow($xml, $rowNumber, $cells);
        }
        $this->writeRow($xml, 10, array_map(fn ($heading) => [$heading, 3], self::HEADINGS));

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
        $startColumn = $hasLogo ? 'B' : 'A';
        foreach (array_keys($metadata) as $row) {
            $xml->startElement('mergeCell');
            $xml->writeAttribute('ref', $startColumn.$row.':E'.$row);
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
            $xml->writeAttribute('r', $this->columnName($index + 1).$rowNumber);
            if ($style > 0) $xml->writeAttribute('s', (string) $style);
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
            $name = chr(65 + $remainder).$name;
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
            ? $company->regimen_fiscal.(isset($regimenes[$company->regimen_fiscal]['descripcion']) ? ' - '.$regimenes[$company->regimen_fiscal]['descripcion'] : '')
            : null;
        $identity = array_filter([
            $company->rfc ? 'RFC: '.$company->rfc : null,
            $regimen ? 'Régimen fiscal: '.$regimen : null,
        ]);
        if ($identity) $lines[] = implode(' | ', $identity);
        $street = trim(implode(' ', array_filter([
            $company->domicilio_calle,
            $company->domicilio_numero_exterior ? 'No. '.$company->domicilio_numero_exterior : null,
            $company->domicilio_numero_interior ? 'Int. '.$company->domicilio_numero_interior : null,
        ])));
        if ($street !== '') $lines[] = $street;
        $neighborhood = $company->domicilio_colonia ? 'Colonia: '.$company->domicilio_colonia : null;
        $locality = array_filter([
            $company->domicilio_codigo_postal ? 'C.P. '.$company->domicilio_codigo_postal : null,
            $company->domicilio_municipio,
            $company->domicilio_ciudad,
            $company->domicilio_estado,
        ]);
        if ($neighborhood || $locality) $lines[] = implode(' | ', array_filter([$neighborhood, implode(', ', $locality)]));
        return array_slice($lines, 0, 3);
    }

    private function contentTypesXml(?string $imageExtension = null): string
    {
        $image = $imageExtension ? '<Default Extension="'.$imageExtension.'" ContentType="image/'.($imageExtension === 'jpeg' ? 'jpeg' : 'png').'"/>' : '';
        $drawing = $imageExtension ? '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>' : '';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'.$image.'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'.$drawing.'</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets><sheet name="Incidencias" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="4"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FF1B2348"/><name val="Calibri"/></font><font><i/><sz val="10"/><color rgb="FF667085"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="9"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF536DF5"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF59E0B"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEF4444"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF06B6D4"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF8B5CF6"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF10B981"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF4F6EF7"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="11"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="3" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="4" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="5" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="6" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="7" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="3" fillId="8" borderId="0" xfId="0" applyFill="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function drawingRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>';
    }

    private function drawingImageRelationshipsXml(string $extension): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image1.'.$extension.'"/></Relationships>';
    }

    private function drawingXml(int $imageWidth, int $imageHeight): string
    {
        $boxWidth = 900_000;
        $boxHeight = 650_000;
        $scale = min($boxWidth / $imageWidth, $boxHeight / $imageHeight);
        $width = (int) round($imageWidth * $scale);
        $height = (int) round($imageHeight * $scale);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><xdr:oneCellAnchor><xdr:from><xdr:col>0</xdr:col><xdr:colOff>50000</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>50000</xdr:rowOff></xdr:from><xdr:ext cx="'.$width.'" cy="'.$height.'"/><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="2" name="Logotipo de la empresa"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$width.'" cy="'.$height.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>';
    }
}
