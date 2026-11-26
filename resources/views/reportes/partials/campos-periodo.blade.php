<div class="col-12 col-lg">
    <label for="empresa_id" class="form-label">Empresa <span class="text-danger">*</span></label>
    <select id="empresa_id" name="empresa_id" class="hr-input select2 w-100" required data-placeholder="Selecciona una empresa">
        @if($companies->count() !== 1)
            <option value="">Selecciona una empresa</option>
        @endif
        @foreach($companies as $company)
            <option value="{{ $company->id }}" @selected((string) old('empresa_id', $selectedCompanyId) === (string) $company->id)>{{ $company->nombre ?: $company->razon_social }}</option>
        @endforeach
    </select>
</div>
<div class="col-12 col-sm-6 col-lg-auto">
    <label for="desde" class="form-label">Desde <span class="text-danger">*</span></label>
    <input class="hr-input w-100" id="desde" name="desde" type="date" max="{{ $latestDate->toDateString() }}" value="{{ old('desde', $startDate ?? $latestDate->startOfMonth()->toDateString()) }}" required>
</div>
<div class="col-12 col-sm-6 col-lg-auto">
    <label for="hasta" class="form-label">Hasta <span class="text-danger">*</span></label>
    <input class="hr-input w-100" id="hasta" name="hasta" type="date" max="{{ $latestDate->toDateString() }}" value="{{ old('hasta', $endDate ?? $latestDate->toDateString()) }}" required>
</div>
