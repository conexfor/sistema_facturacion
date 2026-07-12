<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $warehouses = Warehouse::query()
            ->with('branch:id,nombre')
            ->withCount('stocks')
            ->when($request->input('search'), fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")
                ->orWhere('codigo', 'like', "%{$s}%"))
            ->orderByDesc('is_main')
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Inventory/Warehouses/Index', [
            'warehouses' => $warehouses,
            'branches' => Branch::orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->rules(null));
        Warehouse::create($request->only(['codigo', 'nombre', 'direccion', 'branch_id', 'is_main', 'is_active']));

        return back()->with('success', 'Almacén creado correctamente.');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $request->validate($this->rules($warehouse->id));
        $warehouse->update($request->only(['codigo', 'nombre', 'direccion', 'branch_id', 'is_main', 'is_active']));

        return back()->with('success', 'Almacén actualizado correctamente.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $hasMovements = InventoryMovement::where('warehouse_id', $warehouse->id)->exists();
        if ($hasMovements) {
            return back()->with(
                'error',
                'No se puede eliminar: el almacén tiene movimientos de inventario. Desactívalo en su lugar.',
            );
        }

        $warehouse->delete();

        return back()->with('success', 'Almacén eliminado.');
    }

    private function rules(?int $id): array
    {
        $companyId = request()->user()->company_id;

        return [
            'codigo' => [
                'required', 'string', 'max:20',
                Rule::unique('warehouses', 'codigo')->where('company_id', $companyId)->ignore($id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'is_main' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
