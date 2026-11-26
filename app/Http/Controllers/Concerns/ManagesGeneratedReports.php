<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ReporteGenerado;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait ManagesGeneratedReports
{
    use ManagesCompanyCatalogs;

    protected function reportHistory(Request $request, string $type): LengthAwarePaginator
    {
        $requiresFullControl = $this->requiresFullControl($type);
        $companyIds = $requiresFullControl && ! $this->hasReportManagementProfile($request)
            ? []
            : $this->companyIdsFor($request, $requiresFullControl);

        return ReporteGenerado::query()->with('empresa')->where('user_id', $request->user()->id)
            ->where('tipo', $type)->whereIn('empresa_id', $companyIds)
            ->latest()->orderByDesc('id')->paginate(15, pageName: 'historial_page')
            ->withQueryString()->fragment('historial-reportes');
    }

    protected function authorizeReportCompany(Request $request, string $type, int $companyId): void
    {
        $requiresFullControl = $this->requiresFullControl($type);
        abort_if($requiresFullControl && ! $this->hasReportManagementProfile($request), 403);
        abort_unless(in_array($companyId, $this->companyIdsFor($request, $requiresFullControl), true), 403);
    }

    protected function authorizeReportOwner(Request $request, ReporteGenerado $reporte): void
    {
        abort_unless((int) $reporte->user_id === (int) $request->user()->id, 404);
        $this->authorizeReportCompany($request, $reporte->tipo, (int) $reporte->empresa_id);
    }

    protected function hasReportManagementProfile(Request $request): bool
    {
        return in_array((int) $request->user()->profile, [1, 2], true);
    }

    private function requiresFullControl(string $type): bool
    {
        return in_array($type, [ReporteGenerado::HORAS, ReporteGenerado::INCIDENCIAS], true);
    }
}
