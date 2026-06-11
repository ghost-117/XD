<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    private const ADMIN_EMAIL = 'Ig1613822@gmail.com';

    public function index(): JsonResponse
    {
        $this->authorizeAdmin(request());

        $orders = Order::with(['items.product:id,name,image_path', 'user:id,name,email'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $orders]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer.name' => ['required', 'string', 'max:150'],
            'customer.email' => ['required', 'email', 'max:150'],
            'customer.phone' => ['nullable', 'string', 'max:20'],
            'customer.address' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.size' => ['nullable', 'string', 'max:10'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $order = DB::transaction(function () use ($data): Order {
            $customer = $data['customer'];
            $user = User::firstOrCreate(
                ['email' => $customer['email']],
                [
                    'name' => $customer['name'],
                    'password' => Hash::make(str()->random(16)),
                    'phone' => $customer['phone'] ?? null,
                    'address' => $customer['address'] ?? null,
                    'role' => 'customer',
                ],
            );

            $subtotal = 0;
            $items = collect($data['items'])->map(function (array $item) use (&$subtotal): array {
                $product = Product::where('is_available', true)->findOrFail($item['product_id']);
                $lineSubtotal = (float) $product->price * $item['quantity'];
                $subtotal += $lineSubtotal;

                return [
                    'product_id' => $product->id,
                    'size' => $item['size'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'subtotal' => $lineSubtotal,
                ];
            });

            $shipping = $subtotal >= 500 ? 0 : 99;

            $order = Order::create([
                'order_number' => 'SH'.now()->format('YmdHis').random_int(100, 999),
                'user_id' => $user->id,
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'] ?? null,
                'shipping_address' => $customer['address'] ?? null,
                'status' => 'Pendiente',
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'total' => $subtotal + $shipping,
            ]);

            $order->items()->createMany($items->all());

            return $order->load('items.product:id,name,image_path');
        });

        return response()->json(['data' => $order], 201);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'status' => ['required', Rule::in(['Pendiente', 'En Proceso', 'Entregado'])],
        ]);

        $order->update($data);

        return response()->json(['data' => $order]);
    }

    public function summary(): JsonResponse
    {
        $this->authorizeAdmin(request());

        return response()->json([
            'data' => [
                'pending' => Order::where('status', 'Pendiente')->count(),
                'processing' => Order::where('status', 'En Proceso')->count(),
                'delivered' => Order::where('status', 'Entregado')->count(),
                'sales' => (float) Order::where('status', 'Entregado')->sum('total'),
                'products' => Product::count(),
            ],
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            strtolower($request->header('X-User-Email', '')) === strtolower(self::ADMIN_EMAIL),
            Response::HTTP_FORBIDDEN,
            'Solo el administrador puede usar el panel.',
        );
    }
}
