<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $customer = User::updateOrCreate(['email' => 'itzel@example.com'], [
            'name' => 'Itzel',
            'last_name' => 'Judith',
            'password' => Hash::make('password'),
            'phone' => '1234567890',
            'address' => 'Apizaco, Tlaxcala',
            'role' => 'customer',
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'Ig1613822@gmail.com'], [
            'name' => 'Administrador',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $categories = collect(['Faldas', 'Blusas', 'Camisetas', 'Sudaderas', 'Pantalones', 'Accesorios'])
            ->mapWithKeys(fn (string $name) => [$name => Category::updateOrCreate(['name' => $name])]);

        $faldas = $categories['Faldas'];
        $blusas = $categories['Blusas'];
        $camisetas = $categories['Camisetas'];
        $sudaderas = $categories['Sudaderas'];
        $pantalones = $categories['Pantalones'];
        $accesorios = $categories['Accesorios'];

        $products = collect([
            [$camisetas->id, 'Camisa Oversize Blanca', 'Camisa unisex estilo oversize en tela suave, ideal para outfits callejeros.', 299, 'uploads/1765570943_adidas.jpg', 12, ['M', 'XL'], 'Adidas'],
            [$sudaderas->id, 'Sudadera Negra', 'Sudadera negra con diseño minimalista, cómoda y ligera.', 450, 'uploads/1765572693_sudadera_negra.jpg', 8, ['S', 'M', 'L', 'XL'], null],
            [$pantalones->id, 'Pantalón Cargo Arena', 'Pantalón cargo color arena con múltiples bolsas y ajuste en tobillos.', 520, 'uploads/1765572773_pantalonn.jpg', 6, ['S', 'M', 'L'], null],
            [$pantalones->id, 'Jeans Azul', 'Jeans clásico azul, corte baggy, tela stretch.', 399, 'uploads/1765572872_jeans.jpg', 9, ['S', 'M', 'L', 'XL'], null],
            [$accesorios->id, 'Gorra Negra Street', 'Gorra negra estilo urbano, diseño bordado frontal.', 180, 'uploads/1765574395_GORRA.jpg', 15, ['Unitalla'], null],
            [$camisetas->id, 'Playera Gráfica Anime', 'Playera de algodón con estampado inspirado en estética anime.', 250, 'uploads/1765574444_anime.jpg', 10, ['XS', 'S', 'M', 'L'], null],
            [$blusas->id, 'Top Deportivo Rosa', 'Top ligero y transpirable para entrenamiento o uso casual.', 220, 'uploads/1765574461_top.jpg', 7, ['S', 'M', 'L'], null],
            [$pantalones->id, 'Shorts Deportivos Unisex', 'Short deportivo cómodo con tela fresca.', 210, 'uploads/1765574545_shorts.jpg', 11, ['S', 'M', 'L', 'XL'], null],
            [$accesorios->id, 'Mochila Urbana Negra', 'Mochila resistente con bolsillos múltiples y estilo urbano.', 550, 'uploads/1765574568_mochila.jpg', 4, ['Unitalla'], null],
            [$sudaderas->id, 'Chamarra Rompevientos Azul', 'Rompevientos ligero resistente al agua, ideal para clima fresco.', 480, 'uploads/1765574586_rompevientos.jpg', 5, ['M', 'L', 'XL'], null],
            [$pantalones->id, 'Joggers Grises', 'Joggers unisex color gris, cómodos y combinables.', 330, 'uploads/1765574663_joggers .jpg', 8, ['S', 'M', 'L'], null],
            [$blusas->id, 'Blusa Casual Beige', 'Blusa suave con corte relajado y color beige clásico.', 260, 'uploads/1765574685_camisa.jpg', 9, ['S', 'M', 'L'], null],
            [$accesorios->id, 'Cinturón Negro Clásico', 'Cinturón de piel sintética color negro, hebilla metálica.', 150, 'uploads/1765574706_cinturon.jpg', 20, ['Unitalla'], null],
            [$sudaderas->id, 'Hoodie Rosa Pastel', 'Hoodie unisex color rosa pastel con bolsas frontales.', 470, 'uploads/1765574761_hoddie.jpg', 7, ['S', 'M', 'L', 'XL', 'XXL'], null],
            [$faldas->id, 'Falda Plisada Negra', 'Falda juvenil plisada color negro, tela ligera y cómoda.', 320, 'uploads/1765574781_falda.jpg', 6, ['XS', 'S', 'M'], null],
        ])->map(fn (array $row) => Product::updateOrCreate(
            ['name' => $row[1]],
            [
                'category_id' => $row[0],
                'description' => $row[2],
                'price' => $row[3],
                'image_path' => $row[4],
                'is_available' => true,
                'stock' => $row[5],
                'sizes' => $row[6],
                'brand' => $row[7],
            ],
        ));

        $order = Order::updateOrCreate(['order_number' => 'SH202606040001'], [
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'shipping_address' => $customer->address,
            'status' => 'Pendiente',
            'subtotal' => 470,
            'shipping' => 99,
            'total' => 569,
        ]);

        $order->items()->updateOrCreate([
            'product_id' => $products->firstWhere('name', 'Hoodie Rosa Pastel')->id,
            'size' => 'M',
        ], [
            'quantity' => 1,
            'unit_price' => 470,
            'subtotal' => 470,
        ]);
    }
}
