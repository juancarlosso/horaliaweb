<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait ManagesCompanyCatalogs
{
    protected function companiesFor(Request $request, bool $manageableOnly = false): Collection
    {
        $user = $request->user();

        if ((int) $user->profile === 1) {
            return Empresa::query()->orderBy('razon_social')->get();
        }

        $query = $user->empresas()->orderBy('razon_social');
        if ($manageableOnly) {
            $query->wherePivot('control_total', true);
        }

        return $query->get();
    }

    protected function companyIdsFor(Request $request, bool $manageableOnly = false): array
    {
        return $this->companiesFor($request, $manageableOnly)->modelKeys();
    }

    protected function selectedCompanyForFilter(Request $request, Collection $companies): ?int
    {
        $requestedId = $request->integer('empresa_id');

        return $requestedId ?: $this->defaultCompanyId($companies);
    }

    protected function defaultCompanyId(Collection $companies): ?int
    {
        return $companies->count() === 1 ? (int) $companies->first()->getKey() : null;
    }

    protected function selectedCompanyForForm(Request $request, Collection $companies): ?int
    {
        $requestedId = $request->integer('empresa_id');
        if ($requestedId && $companies->contains('id', $requestedId)) {
            return $requestedId;
        }

        return $this->defaultCompanyId($companies);
    }

    protected function authorizeManageableCompany(Request $request, int $companyId): void
    {
        abort_unless(in_array($companyId, $this->companyIdsFor($request, true), true), 403);
    }
}
