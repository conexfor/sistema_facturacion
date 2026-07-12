<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CashRegisterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->rules(null));
        CashRegister::create($request->only(['nombre', 'is_active']));

        return back()->with('success', 'Caja registrada.');
    }

    public function update(Request $request, CashRegister $cashRegister): RedirectResponse
    {
        $request->validate($this->rules($cashRegister->id));
        $cashRegister->update($request->only(['nombre', 'is_active']));

        return back()->with('success', 'Caja actualizada.');
    }

    public function destroy(CashRegister $cashRegister): RedirectResponse
    {
        if ($cashRegister->sessions()->exists()) {
            return back()->with('error', 'No se puede eliminar: la caja tiene sesiones registradas. Desactívala.');
        }

        $cashRegister->delete();

        return back()->with('success', 'Caja eliminada.');
    }

    private function rules(?int $id): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('cash_registers', 'nombre')
                    ->where('company_id', request()->user()->company_id)
                    ->ignore($id),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
