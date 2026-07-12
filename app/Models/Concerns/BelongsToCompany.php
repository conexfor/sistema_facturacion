<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Aísla los registros por empresa (multiempresa). Aplica un global scope
 * que filtra por la empresa del usuario autenticado y asigna company_id
 * automáticamente al crear. En contexto sin autenticación (consola, seeders)
 * no filtra; ahí el company_id debe asignarse explícitamente.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (Auth::hasUser() && Auth::user()->company_id) {
                $builder->where(
                    $builder->getModel()->getTable() . '.company_id',
                    Auth::user()->company_id,
                );
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id) && Auth::hasUser()) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
