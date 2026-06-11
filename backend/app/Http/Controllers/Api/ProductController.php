<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private const ADMIN_EMAIL = 'Ig1613822@gmail.com';

    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with('category:id,name')
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';

                $query->where(function ($query) use ($search, $operator): void {
                    $query->where('name', $operator, "%{$search}%")
                        ->orWhere('description', $operator, "%{$search}%");
                });
            })
            ->when($request->boolean('available', true), fn ($query) => $query->where('is_available', true))
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $products]);
    }

    public function categories(): JsonResponse
    {
        collect(['Faldas', 'Blusas', 'Pantalones', 'Camisetas', 'Sudaderas', 'Accesorios'])
            ->each(fn (string $name) => Category::firstOrCreate(['name' => $name]));

        $faldas = Category::where('name', 'Faldas')->first();
        $blusas = Category::where('name', 'Blusas')->first();

        Product::where('name', 'like', '%Falda%')->update(['category_id' => $faldas?->id]);
        Product::where(function ($query): void {
            $query->where('name', 'like', '%Blusa%')
                ->orWhere('name', 'like', '%Top%');
        })->update(['category_id' => $blusas?->id]);

        return response()->json([
            'data' => Category::query()
                ->select(['id', 'name', 'description'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $product = Product::create($this->validatedData($request));

        return response()->json(['data' => $product->load('category:id,name')], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorizeAdmin($request);

        $product->update($this->validatedData($request));

        return response()->json(['data' => $product->load('category:id,name')]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorizeAdmin(request());

        $product->update(['is_available' => false]);

        return response()->json(['data' => $product]);
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:4096'],
            'is_available' => ['boolean'],
            'stock' => ['required', 'integer', 'min:0'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['string', 'max:10'],
            'brand' => ['nullable', 'string', 'max:100'],
        ]);

        unset($data['image']);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->extension();
            $file->move(public_path('uploads'), $filename);
            $data['image_path'] = 'uploads/'.$filename;
        }

        return $data;
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            strtolower($request->header('X-User-Email', '')) === strtolower(self::ADMIN_EMAIL),
            Response::HTTP_FORBIDDEN,
            'Solo el administrador puede gestionar productos.',
        );
    }
}
