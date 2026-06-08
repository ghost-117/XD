<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

const products = ref([])
const orders = ref([])
const summary = ref(null)
const cart = ref([])
const view = ref('shop')
const search = ref('')
const loading = ref(false)
const message = ref('')
const user = ref(JSON.parse(localStorage.getItem('shopUser') || 'null'))

const checkout = reactive({ name: '', email: '', phone: '', address: '' })
const auth = reactive({ name: '', email: '', password: '' })
const productForm = reactive({
  name: '',
  description: '',
  price: 0,
  image_path: '',
  stock: 0,
  sizes: 'S,M,L,XL',
  brand: '',
})

const subtotal = computed(() => cart.value.reduce((sum, item) => sum + Number(item.price) * item.quantity, 0))
const shipping = computed(() => (subtotal.value >= 500 || subtotal.value === 0 ? 0 : 99))
const total = computed(() => subtotal.value + shipping.value)
const cartCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0))

async function api(path, options = {}) {
  const response = await fetch(`/api${path}`, {
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...options.headers },
    ...options,
  })
  const payload = await response.json().catch(() => ({}))
  if (!response.ok) {
    const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null
    throw new Error(firstError || payload.message || 'No se pudo completar la operación.')
  }
  return payload
}

async function run(task) {
  try {
    message.value = ''
    await task()
  } catch (error) {
    message.value = error.message
  }
}

async function loadProducts() {
  loading.value = true
  try {
    const query = search.value ? `?search=${encodeURIComponent(search.value)}` : ''
    const payload = await api(`/products${query}`)
    products.value = payload.data
  } finally {
    loading.value = false
  }
}

async function loadAdmin() {
  const [ordersPayload, summaryPayload] = await Promise.all([api('/admin/orders'), api('/admin/summary')])
  orders.value = ordersPayload.data
  summary.value = summaryPayload.data
}

function imageUrl(path) {
  if (!path) return 'https://images.unsplash.com/photo-1523381294911-8d3cead13475?w=800'
  if (path.startsWith('http')) return path
  return `http://127.0.0.1:8001/${path}`
}

function addToCart(product) {
  const size = product.sizes?.[0] || 'Unitalla'
  const key = `${product.id}-${size}`
  const existing = cart.value.find((item) => item.key === key)
  if (existing) existing.quantity += 1
  else cart.value.push({ key, product_id: product.id, name: product.name, price: Number(product.price), image_path: product.image_path, size, quantity: 1 })
  message.value = `${product.name} agregado al carrito.`
}

function changeQuantity(key, delta) {
  const item = cart.value.find((entry) => entry.key === key)
  if (item) item.quantity = Math.max(1, Math.min(99, item.quantity + delta))
}

function removeFromCart(key) {
  cart.value = cart.value.filter((item) => item.key !== key)
}

async function placeOrder() {
  if (!cart.value.length) return
  const payload = await api('/orders', {
    method: 'POST',
    body: JSON.stringify({
      customer: checkout,
      items: cart.value.map((item) => ({ product_id: item.product_id, size: item.size, quantity: item.quantity })),
    }),
  })
  cart.value = []
  message.value = `Pedido ${payload.data.order_number} creado correctamente.`
  view.value = 'shop'
}

async function login() {
  const payload = await api('/login', { method: 'POST', body: JSON.stringify({ email: auth.email, password: auth.password }) })
  user.value = payload.user
  localStorage.setItem('shopUser', JSON.stringify(payload.user))
  message.value = `Sesión iniciada como ${payload.user.name}.`
  view.value = 'shop'
}

async function register() {
  const payload = await api('/register', { method: 'POST', body: JSON.stringify(auth) })
  user.value = payload.user
  localStorage.setItem('shopUser', JSON.stringify(payload.user))
  message.value = `Cuenta creada para ${payload.user.name}.`
  view.value = 'shop'
}

function logout() {
  user.value = null
  localStorage.removeItem('shopUser')
}

async function createProduct() {
  await api('/admin/products', {
    method: 'POST',
    body: JSON.stringify({
      ...productForm,
      price: Number(productForm.price),
      stock: Number(productForm.stock),
      sizes: productForm.sizes.split(',').map((size) => size.trim()).filter(Boolean),
      is_available: true,
    }),
  })
  Object.assign(productForm, { name: '', description: '', price: 0, image_path: '', stock: 0, sizes: 'S,M,L,XL', brand: '' })
  message.value = 'Producto creado.'
  await loadProducts()
  await loadAdmin()
}

async function updateOrder(order, status) {
  await api(`/admin/orders/${order.id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) })
  await loadAdmin()
}

async function openAdmin() {
  view.value = 'admin'
  await run(loadAdmin)
}

onMounted(() => run(loadProducts))
</script>

<template>
  <div class="app-shell">
    <header class="topbar">
      <button class="brand" type="button" @click="view = 'shop'">
        <span>SHOP HOLY</span>
        <small>Luxury Urban Fashion</small>
      </button>
      <nav class="nav">
        <button type="button" @click="view = 'shop'">Tienda</button>
        <button type="button" @click="view = 'cart'">Carrito ({{ cartCount }})</button>
        <button type="button" @click="openAdmin">Admin</button>
        <button v-if="!user" type="button" @click="view = 'auth'">Entrar</button>
        <button v-else type="button" @click="logout">{{ user.name }}</button>
      </nav>
    </header>

    <p v-if="message" class="notice">{{ message }}</p>

    <main v-if="view === 'shop'" class="shop-page">
      <section class="hero">
        <div>
          <p class="eyebrow">Colección activa</p>
          <h1>Ropa urbana.</h1>
        </div>
        <form class="search" @submit.prevent="run(loadProducts)">
          <input v-model="search" placeholder="Buscar productos" />
          <button type="submit">Buscar</button>
        </form>
      </section>

      <section class="product-grid" aria-label="Catálogo">
        <article v-for="product in products" :key="product.id" class="product-card">
          <img :src="imageUrl(product.image_path)" :alt="product.name" />
          <div class="product-body">
            <p class="category">{{ product.category?.name || 'General' }}</p>
            <h2>{{ product.name }}</h2>
            <p>{{ product.description }}</p>
            <div class="meta">
              <strong>${{ Number(product.price).toFixed(2) }}</strong>
              <span>Stock {{ product.stock }}</span>
            </div>
            <div class="sizes">
              <span v-for="size in product.sizes || ['Unitalla']" :key="size">{{ size }}</span>
            </div>
            <button type="button" @click="addToCart(product)">Agregar</button>
          </div>
        </article>
      </section>
      <p v-if="loading" class="empty">Cargando catálogo...</p>
    </main>

    <main v-else-if="view === 'cart'" class="cart-page">
      <section class="panel">
        <h1>Carrito</h1>
        <div v-if="cart.length" class="cart-list">
          <article v-for="item in cart" :key="item.key" class="cart-item">
            <img :src="imageUrl(item.image_path)" :alt="item.name" />
            <div>
              <h2>{{ item.name }}</h2>
              <p>Talla {{ item.size }} · ${{ item.price.toFixed(2) }}</p>
            </div>
            <div class="qty">
              <button type="button" @click="changeQuantity(item.key, -1)">-</button>
              <span>{{ item.quantity }}</span>
              <button type="button" @click="changeQuantity(item.key, 1)">+</button>
            </div>
            <button class="ghost danger" type="button" @click="removeFromCart(item.key)">Quitar</button>
          </article>
        </div>
        <p v-else class="empty">Tu carrito está vacío.</p>
      </section>

      <form class="panel checkout" @submit.prevent="run(placeOrder)">
        <h2>Finalizar compra</h2>
        <input v-model="checkout.name" required placeholder="Nombre completo" />
        <input v-model="checkout.email" required type="email" placeholder="Correo" />
        <input v-model="checkout.phone" required placeholder="Teléfono" />
        <textarea v-model="checkout.address" required placeholder="Dirección"></textarea>
        <div class="totals">
          <span>Subtotal</span><strong>${{ subtotal.toFixed(2) }}</strong>
          <span>Envío</span><strong>${{ shipping.toFixed(2) }}</strong>
          <span>Total</span><strong>${{ total.toFixed(2) }}</strong>
        </div>
        <button type="submit" :disabled="!cart.length">Crear pedido</button>
      </form>
    </main>

    <main v-else-if="view === 'auth'" class="auth-page">
      <form class="panel" @submit.prevent="run(login)">
        <h1>Iniciar sesión</h1>
        <input v-model="auth.email" type="email" required placeholder="Correo" />
        <input v-model="auth.password" type="password" required placeholder="Contraseña" />
        <button type="submit">Entrar</button>
        <p class="hint">Cliente demo: itzel@example.com / password</p>
        <p class="hint">Admin demo: admin@shopholy.test / admin1234</p>
      </form>

      <form class="panel" @submit.prevent="run(register)">
        <h2>Crear cuenta</h2>
        <input v-model="auth.name" required placeholder="Nombre" />
        <input v-model="auth.email" type="email" required placeholder="Correo" />
        <input v-model="auth.password" type="password" required placeholder="Contraseña" />
        <button type="submit">Registrarme</button>
      </form>
    </main>

    <main v-else class="admin-page">
      <section class="stats" v-if="summary">
        <article><span>Pendientes</span><strong>{{ summary.pending }}</strong></article>
        <article><span>En proceso</span><strong>{{ summary.processing }}</strong></article>
        <article><span>Entregados</span><strong>{{ summary.delivered }}</strong></article>
        <article><span>Ventas</span><strong>${{ summary.sales.toFixed(2) }}</strong></article>
      </section>

      <section class="admin-grid">
        <form class="panel" @submit.prevent="run(createProduct)">
          <h1>Nuevo producto</h1>
          <input v-model="productForm.name" required placeholder="Nombre" />
          <textarea v-model="productForm.description" placeholder="Descripción"></textarea>
          <input v-model="productForm.price" required type="number" min="0" step="0.01" placeholder="Precio" />
          <input v-model="productForm.stock" required type="number" min="0" placeholder="Stock" />
          <input v-model="productForm.sizes" placeholder="Tallas separadas por coma" />
          <input v-model="productForm.image_path" placeholder="Ruta de imagen o URL" />
          <button type="submit">Guardar</button>
        </form>

        <section class="panel">
          <h1>Pedidos</h1>
          <article v-for="order in orders" :key="order.id" class="order-row">
            <div>
              <strong>{{ order.order_number }}</strong>
              <p>{{ order.customer_name }} · ${{ Number(order.total).toFixed(2) }}</p>
            </div>
            <select :value="order.status" @change="run(() => updateOrder(order, $event.target.value))">
              <option>Pendiente</option>
              <option>En Proceso</option>
              <option>Entregado</option>
            </select>
          </article>
        </section>
      </section>
    </main>
  </div>
</template>
