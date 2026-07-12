<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function index(Request $request): Response
    {
        $cid = $request->user()->company_id;

        $branches = Branch::query()
            ->where('company_id', $cid)
            ->withCount(['users', 'warehouses'])
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%");
            }))
            ->orderByDesc('is_main')
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Settings/Branches/Index', [
            'branches' => $branches,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $data['company_id'] = $request->user()->company_id;

        $branch = Branch::create($data);
        $this->syncMain($branch, $request);

        return back()->with('success', 'Sucursal creada correctamente.');
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->validated($request, $branch->id));
        $this->syncMain($branch, $request);

        return back()->with('success', 'Sucursal actualizada correctamente.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->is_main) {
            return back()->with('error', 'No se puede eliminar la sucursal principal.');
        }
        if ($branch->users()->exists() || $branch->warehouses()->exists()) {
            return back()->with('error', 'No se puede eliminar: la sucursal tiene usuarios o almacenes asociados.');
        }

        $branch->delete();

        return back()->with('success', 'Sucursal eliminada.');
    }

    private function validated(Request $request, ?int $id): array
    {
        $cid = $request->user()->company_id;

        return $request->validate([
            'codigo' => [
                'required', 'string', 'max:4',
                Rule::unique('branches', 'codigo')->where('company_id', $cid)->ignore($id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ubigeo' => ['nullable', 'string', 'size:6'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'is_main' => ['boolean'],
            'is_active' => ['boolean'],
        ]);
    }

    /** Asegura que solo exista una sucursal principal. */
    private function syncMain(Branch $branch, Request $request): void
    {
        if ($request->boolean('is_main')) {
            Branch::where('company_id', $branch->company_id)
                ->where('id', '!=', $branch->id)
                ->update(['is_main' => false]);
        }
    }
}
