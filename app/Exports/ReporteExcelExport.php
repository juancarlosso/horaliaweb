<?php

namespace App\Exports;

use App\Models\Empresa;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use XMLWriter;
use ZipArchive;

abstract class ReporteExcelExport
{
    protected function generateWorkbook(?Empresa $company, string $sheetName, callable $writeSheet, ?string $fallbackLogoPath = null, array $additionalSheets = []): string
    {
        if (! class_exists(ZipArchive::class) || ! class_exists(XMLWriter::class)) {
            throw new RuntimeException('La exportación de Excel no está disponible en este servidor.');
        }

        $xlsxPath = tempnam(sys_get_temp_dir(), 'horalia-reporte-');
        $sheetPath = tempnam(sys_get_temp_dir(), 'horalia-reporte-sheet-');
        $logoTempPath = null;
        $extraPaths = [];
        try {
            if ($xlsxPath === false || $sheetPath === false) {
                throw new RuntimeException('No se pudo preparar el archivo de Excel.');
            }

            $logo = $this->logoImage($company?->logo);
            if ($logo === null && $fallbackLogoPath !== null) {
                $logo = $this->decodeLogo(@file_get_contents($fallbackLogoPath) ?: null);
            }
            if ($logo !== null) {
                $logoTempPath = tempnam(sys_get_temp_dir(), 'horalia-reporte-logo-');
                if ($logoTempPath === false || file_put_contents($logoTempPath, $logo['bytes']) === false) {
                    throw new RuntimeException('No se pudo preparar el logotipo para Excel.');
                }
            }

            $writeSheet($sheetPath, $logo !== null);
            foreach ($additionalSheets as $name => $writeAdditionalSheet) {
                $path = tempnam(sys_get_temp_dir(), 'horalia-reporte-sheet-');
                if ($path === false) {
                    throw new RuntimeException('No se pudo preparar una hoja de Excel.');
                }

                $extraPaths[$name] = $path;
                $writeAdditionalSheet($path);
            }
            $sheetNames = [$sheetName, ...array_keys($extraPaths)];

            $zip = new ZipArchive;
            if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo crear el archivo de Excel.');
            }

            $zip->addFromString('[Content_Types].xml', $this->contentTypesXml(count($sheetNames), $logo['extension'] ?? null));
            $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
            $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetNames));
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml(count($sheetNames)));
            $zip->addFromString('xl/styles.xml', $this->stylesXml());
            foreach ([$sheetPath, ...array_values($extraPaths)] as $index => $path) {
                $zip->addFile($path, 'xl/worksheets/sheet'.($index + 1).'.xml');
            }
            if ($logoTempPath !== null && $logo !== null) {
                $zip->addFile($logoTempPath, 'xl/media/image1.'.$logo['extension']);
                $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $this->drawingRelationshipsXml());
                $zip->addFromString('xl/drawings/drawing1.xml', $this->drawingXml($logo['width'], $logo['height']));
                $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $this->drawingImageRelationshipsXml($logo['extension']));
            }
            if (! $zip->close()) {
                throw new RuntimeException('No se pudo guardar el archivo de Excel.');
            }

            return $xlsxPath;
        } catch (\Throwable $exception) {
            if ($xlsxPath !== false) {
                @unlink($xlsxPath);
            }
            throw $exception;
        } finally {
            if ($sheetPath !== false) {
                @unlink($sheetPath);
            }
            foreach ($extraPaths as $path) {
                @unlink($path);
            }
            if ($logoTempPath) {
                @unlink($logoTempPath);
            }
        }
    }

    protected function writeRow(XMLWriter $xml, int $rowNumber, array $cells, ?int $height = null): void
    {
        $xml->startElement('row');
        $xml->writeAttribute('r', (string) $rowNumber);
        if ($height !== null) {
            $xml->writeAttribute('ht', (string) $height);
            $xml->writeAttribute('customHeight', '1');
        }
        foreach ($cells as $index => [$value, $style]) {
            $xml->startElement('c');
            $xml->writeAttribute('r', $this->columnName($index + 1).$rowNumber);
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
            $name = chr(65 + $remainder).$name;
            $number = intdiv($number - 1, 26);
        }

        return $name;
    }

    private function logoImage(?string $path): ?array
    {
        if (! $path) {
            return null;
        }
        try {
            $disk = str_starts_with($path, 'empresas/logos/') ? 'public' : 'wasabi';

            return $this->decodeLogo(Storage::disk($disk)->get($path));
        } catch (\Throwable) {
            return null;
        }
    }

    private function decodeLogo(?string $bytes): ?array
    {
        if (! $bytes) {
            return null;
        }

        $image = @getimagesizefromstring($bytes);
        $mime = $image['mime'] ?? null;
        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            return null;
        }

        if (function_exists('imagecreatefromstring')) {
            $resource = @imagecreatefromstring($bytes);
            if (! $resource) {
                return null;
            }

            if ($mime === 'image/webp') {
                ob_start();
                $converted = imagepng($resource);
                $bytes = (string) ob_get_clean();
                $mime = $converted ? 'image/png' : null;
            }
            imagedestroy($resource);
        } elseif (match ($mime) {
            'image/png' => ! str_ends_with($bytes, "\x00\x00\x00\x00IEND\xae\x42\x60\x82"),
            'image/jpeg' => ! str_ends_with($bytes, "\xff\xd9"),
            default => true,
        }) {
            return null;
        }

        return in_array($mime, ['image/png', 'image/jpeg'], true)
            ? ['bytes' => $bytes, 'extension' => $mime === 'image/png' ? 'png' : 'jpeg', 'width' => (int) $image[0], 'height' => (int) $image[1]]
            : null;
    }

    private function contentTypesXml(int $sheetCount, ?string $imageExtension = null): string
    {
        $image = $imageExtension ? '<Default Extension="'.$imageExtension.'" ContentType="image/'.($imageExtension === 'jpeg' ? 'jpeg' : 'png').'"/>' : '';
        $drawing = $imageExtension ? '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>' : '';

        $sheets = '';
        for ($id = 1; $id <= $sheetCount; $id++) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet'.$id.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'.$image.'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.$sheets.'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'.$drawing.'</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbookXml(array $sheetNames): string
    {
        $sheets = '';
        foreach ($sheetNames as $index => $name) {
            $id = $index + 1;
            $escaped = htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $sheets .= '<sheet name="'.$escaped.'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets>'.$sheets.'</sheets></workbook>';
    }

    private function workbookRelationshipsXml(int $sheetCount): string
    {
        $relationships = '';
        for ($id = 1; $id <= $sheetCount; $id++) {
            $relationships .= '<Relationship Id="rId'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$id.'.xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relationships.'<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    abstract protected function stylesXml(): string;

    private function drawingRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/></Relationships>';
    }

    private function drawingImageRelationshipsXml(string $extension): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/image1.'.$extension.'"/></Relationships>';
    }

    protected function logoBox(): array
    {
        return ['column' => 0, 'left' => 50_000, 'top' => 50_000, 'width' => 900_000, 'height' => 650_000];
    }

    private function drawingXml(int $imageWidth, int $imageHeight): string
    {
        $box = $this->logoBox();
        $scale = min($box['width'] / $imageWidth, $box['height'] / $imageHeight);
        $width = (int) round($imageWidth * $scale);
        $height = (int) round($imageHeight * $scale);
        $left = $box['left'] + (($box['alignRight'] ?? false) ? $box['width'] - $width : 0);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><xdr:oneCellAnchor><xdr:from><xdr:col>'.$box['column'].'</xdr:col><xdr:colOff>'.$left.'</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>'.$box['top'].'</xdr:rowOff></xdr:from><xdr:ext cx="'.$width.'" cy="'.$height.'"/><xdr:pic><xdr:nvPicPr><xdr:cNvPr id="2" name="Logotipo"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr><xdr:blipFill><a:blip r:embed="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill><xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$width.'" cy="'.$height.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr></xdr:pic><xdr:clientData/></xdr:oneCellAnchor></xdr:wsDr>';
    }
}
