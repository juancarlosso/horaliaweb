<?php

namespace App\Support;

use App\Models\Empresa;

class DatosFiscalesReporte
{
    public static function lineas(Empresa $company): array
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
        if ($identity) {
            $lines[] = implode(' | ', $identity);
        }
        $street = trim(implode(' ', array_filter([
            $company->domicilio_calle,
            $company->domicilio_numero_exterior ? 'No. '.$company->domicilio_numero_exterior : null,
            $company->domicilio_numero_interior ? 'Int. '.$company->domicilio_numero_interior : null,
        ])));
        if ($street !== '') {
            $lines[] = $street;
        }
        $neighborhood = $company->domicilio_colonia ? 'Colonia: '.$company->domicilio_colonia : null;
        $locality = array_filter([
            $company->domicilio_codigo_postal ? 'C.P. '.$company->domicilio_codigo_postal : null,
            $company->domicilio_municipio,
            $company->domicilio_ciudad,
            $company->domicilio_estado,
        ]);
        if ($neighborhood || $locality) {
            $lines[] = implode(' | ', array_filter([$neighborhood, implode(', ', $locality)]));
        }

        return array_slice($lines, 0, 3);
    }
}
