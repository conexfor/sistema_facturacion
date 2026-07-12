<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = Category::query()
            ->withCount('products')
            ->when($request->input('search'), fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Inventory/Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->rules(null));
        Category::create($request->only(['nombre', 'descripcion', 'is_active']));

        return back()->with('success', 'Categoría creada correctamente.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $request->validate($this->rules($category->id));
        $category->update($request->only(['nombre', 'descripcion', 'is_active']));

        return back()->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'Categoría eliminada.');
    }

    private function rules(?int $id): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'nombre')
                    ->where('company_id', request()->user()->company_id)
                    ->ignore($id),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
