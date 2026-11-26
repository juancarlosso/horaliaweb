<?php

namespace App\Exports;

use App\Models\Asistencia;
use App\Models\Empresa;
use App\Support\DatosFiscalesReporte;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use XMLWriter;

class AsistenciaReporteExport extends ReporteExcelExport
{
    private const HEADINGS = [
        'Empresa', 'Personal', 'Fecha', 'Modalidad', 'Hr. entrada', 'Entrada', 'Retardo (min)', 'Rango entrada', 'Distancia entrada (m)',
        'Hr. salida', 'Salida', 'Salida temprana (min)', 'Rango salida', 'Distancia salida (m)', 'Latitud entrada',
        'Longitud entrada', 'Latitud salida', 'Longitud salida', 'Tiempo de pausas', 'Tiempo productivo',
    ];

    public function generate(Builder $query, Empresa $company, string $startDate, string $endDate): string
    {
        return $this->generateFromRecords(
            $company, $startDate, $endDate,
            $this->attendanceRows($query),
            (clone $query)->reorder()->lazyById(300, 'asistencias.id', 'id'),
        );
    }

    public function generateFromRecords(Empresa $company, string $startDate, string $endDate, iterable $attendance, iterable $pauses): string
    {
        return $this->generateWorkbook(
            $company,
            'Asistencias',
            fn (string $path, bool $hasLogo) => $this->writeSheet($attendance, $company, $startDate, $endDate, $path, $hasLogo),
            fallbackLogoPath: public_path('assets/logos/logoLN.png'),
            additionalSheets: ['Pausas' => fn (string $path) => $this->writePauseSheet($pauses, $path)],
        );
    }

    protected function logoBox(): array
    {
        return ['column' => 19, 'left' => 45_000, 'top' => 90_000, 'width' => 1_240_000, 'height' => 1_180_000, 'alignRight' => true];
    }

    private function writeSheet(iterable $records, Empresa $company, string $startDate, string $endDate, string $path, bool $hasLogo): void
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
        foreach ([28, 30, 14, 16, 12, 12, 16, 18, 20, 12, 12, 22, 18, 20, 18, 18, 18, 18, 20, 20] as $index => $width) {
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
        $fiscalLines = DatosFiscalesReporte::lineas($company);
        $headerRows = [1 => ['REPORTE DE ASISTENCIA', 1], 2 => [$companyName, 2]];
        foreach ($fiscalLines as $index => $line) {
            $headerRows[$index + 3] = [$line, 2];
        }
        $periodRow = count($fiscalLines) + 3;
        $headerRows[$periodRow] = ["Periodo: {$startDate} al {$endDate}", 2];
        ksort($headerRows);
        foreach ($headerRows as $row => [$text, $style]) {
            $this->writeRow($xml, $row, [[$text, $style]], $row === 1 ? 25 : null);
        }
        $headingCells = array_map(fn ($heading) => [$heading, 3], self::HEADINGS);
        $this->writeRow($xml, 7, $headingCells);

        $rowNumber = 8;
        foreach ($records as $record) {
            $schedule = $record->personal?->horarios?->firstWhere('dia', $record->fecha?->dayOfWeekIso);
            $values = [
                $companyName,
                $record->personal?->nombre ?? '',
                $record->fecha?->format('d/m/Y') ?? '',
                ($record->modalidad ?? 'PRESENCIAL') === 'HOME_OFFICE' ? 'Home Office' : 'Presencial',
                $schedule?->entrada ? substr($schedule->entrada, 0, 5) : '',
                $record->llegada?->format('H:i') ?? '',
                (int) ($record->minutos_tarde ?? 0),
                $record->rango_entrada ?? '',
                $record->distancia_entrada_metros,
                $schedule?->salida ? substr($schedule->salida, 0, 5) : '',
                $record->salida?->format('H:i') ?? '',
                (int) ($record->minutos_salida_temprano ?? 0),
                $record->rango_salida ?? '',
                $record->distancia_salida_metros,
                $record->latitud,
                $record->longitud,
                $record->latitud_salida,
                $record->longitud_salida,
                Asistencia::formatoDuracion($record->tiempoPausasMinutos()),
                Asistencia::formatoDuracion($record->tiempoProductivoMinutos()),
            ];
            $this->writeRow($xml, $rowNumber++, array_map(fn ($value) => [$value, 0], $values));
        }

        $xml->endElement();
        $xml->startElement('autoFilter');
        $xml->writeAttribute('ref', 'A7:T'.max(7, $rowNumber - 1));
        $xml->endElement();
        $xml->startElement('mergeCells');
        $xml->writeAttribute('count', (string) count($headerRows));
        $endColumn = $hasLogo ? 'S' : 'T';
        foreach (array_keys($headerRows) as $row) {
            $range = 'A'.$row.':'.$endColumn.$row;
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

    private function attendanceRows(Builder $query): iterable
    {
        $lastRecord = null;
        do {
            $chunkQuery = clone $query;
            if ($lastRecord !== null) {
                $lastDate = $lastRecord->getRawOriginal('fecha');
                $lastId = $lastRecord->id;
                $chunkQuery->where(function ($cursor) use ($lastDate, $lastId): void {
                    $cursor->where('asistencias.fecha', '<', $lastDate)
                        ->orWhere(function ($sameDate) use ($lastDate, $lastId): void {
                            $sameDate->where('asistencias.fecha', '=', $lastDate)
                                ->where('asistencias.id', '<', $lastId);
                        });
                });
            }
            $records = $chunkQuery->reorder('asistencias.fecha', 'desc')
                ->orderByDesc('asistencias.id')
                ->limit(500)
                ->get();
            if ($records->isEmpty()) {
                break;
            }

            yield from $records;
            $lastRecord = $records->last();
        } while ($records->count() === 500);
    }

    private function writePauseSheet(iterable $records, string $path): void
    {
        $xml = new XMLWriter;
        if (! $xml->openUri($path)) {
            throw new RuntimeException('No se pudo escribir el detalle de pausas.');
        }
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('worksheet');
        $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', '1');
        $xml->writeAttribute('topLeftCell', 'A2');
        $xml->writeAttribute('activePane', 'bottomLeft');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('sheetData');
        $headings = ['Personal', 'Fecha / jornada', 'Tipo', 'Inicio', 'Fin', 'Duración', 'Estado'];
        $this->writeRow($xml, 1, array_map(fn ($heading) => [$heading, 3], $headings));
        $rowNumber = 2;
        foreach ($records as $record) {
            foreach ($record->pausas->sortByDesc('inicio')->sortByDesc('id') as $pause) {
                $type = config('constantes.tiposPausa.'.$pause->tipo);
                $minutes = $pause->fin_utc && $pause->inicio_utc ? $pause->inicio_utc->diffInMinutes($pause->fin_utc) : null;
                $values = [
                    $record->personal?->nombre ?? '',
                    $record->fecha?->format('d/m/Y') ?? '',
                    $type['nombre'] ?? strtoupper($pause->tipo),
                    $pause->inicio?->format('d/m/Y H:i') ?? '',
                    $pause->fin?->format('d/m/Y H:i') ?? '',
                    $pause->fin ? Asistencia::formatoDuracion($minutes) : '',
                    $pause->fin ? 'Finalizada' : 'En curso',
                ];
                $this->writeRow($xml, $rowNumber++, array_map(fn ($value) => [$value, 0], $values));
            }
        }
        $xml->endElement();
        $xml->startElement('autoFilter');
        $xml->writeAttribute('ref', 'A1:G'.max(1, $rowNumber - 1));
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
    }

    protected function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="4"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FF1B2348"/><name val="Calibri"/></font><font><i/><sz val="10"/><color rgb="FF667085"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF536DF5"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="2" borderId="0" xfId="0" applyFill="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
