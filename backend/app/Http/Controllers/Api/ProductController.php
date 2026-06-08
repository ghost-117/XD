<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with('category:id,name')
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            })
            ->when($request->boolean('available', true), fn ($query) => $query->where('is_available', true))
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $products]);
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::create($this->validatedData($request));

        return response()->json(['data' => $product->load('category:id,name')], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $product->update($this->validatedData($request));

        return response()->json(['data' => $product->load('category:id,name')]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->update(['is_available' => false]);

        return response()->json(['data' => $product]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_available' => ['boolean'],
            'stock' => ['integer', 'min:0'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['string', 'max:10'],
            'brand' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
