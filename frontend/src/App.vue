<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { fallbackCatalog } from './fallbackCatalog'

const emptyProductForm = () => ({
  id: null,
  name: '',
  description: '',
  price: '',
  image_path: '',
  image: null,
  stock: '',
  sizes: 'S,M,L,XL',
  brand: '',
})

const products = ref([])
const orders = ref([])
const summary = ref(null)
const cart = ref(JSON.parse(localStorage.getItem('shopCart') || '[]'))
const view = ref('shop')
const search = ref('')
const loading = ref(false)
const message = ref('')
const messageType = ref('info')
const errors = ref({})
const user = ref(JSON.parse(localStorage.getItem('shopUser') || 'null'))
const productForm = reactive(emptyProductForm())
const checkout = reactive({ name: '', email: '', phone: '', address: '' })
const loginForm = reactive({ email: '', password: '' })
const registerForm = reactive({ name: '', email: '', password: '', phone: '', address: '' })

const isEditingProduct = computed(() => Boolean(productForm.id))
const subtotal = computed(() => cart.value.reduce((sum, item) => sum + Number(item.price) * item.quantity, 0))
const shipping = computed(() => (subtotal.value >= 500 || subtotal.value === 0 ? 0 : 99))
const total = computed(() => subtotal.value + shipping.value)
const cartCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0))

function setMessage(text, type = 'info') {
  message.value = text
  messageType.value = type
}

function clearErrors() {
  errors.value = {}
}

function fieldError(field, scope = '') {
  return errors.value[scope ? `${scope}.${field}` : field]?.[0] || errors.value[field]?.[0] || ''
}

function validEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(email).trim())
}

function requireLogin(target = 'cart') {
  if (user.value) return true
  view.value = 'auth'
  setMessage('Inicia sesion para comprar o agregar prendas al carrito.', 'warning')
  localStorage.setItem('shopNextView', target)
  return false
}

function persistCart() {
  localStorage.setItem('shopCart', JSON.stringify(cart.value))
}

async function api(path, options = {}) {
  const headers = options.body instanceof FormData
    ? { Accept: 'application/json', ...options.headers }
    : { 'Content-Type': 'application/json', Accept: 'application/json', ...options.headers }

  const response = await fetch(`/api${path}`, { ...options, headers })
  const payload = await response.json().catch(() => ({}))

  if (!response.ok) {
    if (payload.errors) {
      errors.value = options.errorScope
        ? Object.fromEntries(Object.entries(payload.errors).map(([key, value]) => [`${options.errorScope}.${key}`, value]))
        : payload.errors
    }
    const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null
    throw new Error(firstError || payload.message || 'No se pudo completar la operacion.')
  }

  return payload
}

async function run(task) {
  try {
    clearErrors()
    message.value = ''
    await task()
  } catch (error) {
    setMessage(error.message, 'error')
  }
}

async function loadProducts() {
  loading.value = true
  try {
    const query = search.value ? `?search=${encodeURIComponent(search.value)}` : ''
    const payload = await api(`/products${query}`)
    products.value = payload.data
  } catch (error) {
    const term = search.value.trim().toLowerCase()
    products.value = fallbackCatalog.filter((product) => {
      if (!term) return true
      return `${product.name} ${product.description} ${product.category?.name || ''} ${product.brand || ''}`.toLowerCase().includes(term)
    })
    setMessage('Backend apagado: se cargo el catalogo local para que las prendas aparezcan.', 'warning')
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
  return `/${path}`
}

function addToCart(product) {
  if (!requireLogin('cart')) return

  const size = product.sizes?.[0] || 'Unitalla'
  const key = `${product.id}-${size}`
  const existing = cart.value.find((item) => item.key === key)

  if (existing) existing.quantity += 1
  else {
    cart.value.push({
      key,
      product_id: product.id,
      name: product.name,
      price: Number(product.price),
      image_path: product.image_path,
      size,
      quantity: 1,
    })
  }

  persistCart()
  setMessage(`${product.name} agregado al carrito.`, 'success')
}

function goToCart() {
  if (requireLogin('cart')) view.value = 'cart'
}

function changeQuantity(key, delta) {
  const item = cart.value.find((entry) => entry.key === key)
  if (!item) return
  item.quantity = Math.max(1, Math.min(99, item.quantity + delta))
  persistCart()
}

function removeFromCart(key) {
  cart.value = cart.value.filter((item) => item.key !== key)
  persistCart()
}

function validateCheckout() {
  const nextErrors = {}
  if (!checkout.name.trim()) nextErrors['customer.name'] = ['El nombre es obligatorio.']
  if (!validEmail(checkout.email)) nextErrors['customer.email'] = ['Ingresa un correo valido con @ y dominio.']
  if (!checkout.phone.trim()) nextErrors['customer.phone'] = ['El telefono es obligatorio.']
  if (!checkout.address.trim()) nextErrors['customer.address'] = ['La direccion es obligatoria.']
  errors.value = nextErrors
  return Object.keys(nextErrors).length === 0
}

async function placeOrder() {
  if (!requireLogin('cart')) return
  if (!cart.value.length) throw new Error('Agrega al menos una prenda al carrito.')
  if (!validateCheckout()) throw new Error('Revisa los campos obligatorios de compra.')

  const payload = await api('/orders', {
    method: 'POST',
    body: JSON.stringify({
      customer: checkout,
      items: cart.value.map((item) => ({ product_id: item.product_id, size: item.size, quantity: item.quantity })),
    }),
  })

  cart.value = []
  persistCart()
  Object.assign(checkout, { name: '', email: '', phone: '', address: '' })
  setMessage(`Pedido ${payload.data.order_number} creado correctamente.`, 'success')
  view.value = 'shop'
}

function validateLogin() {
  const nextErrors = {}
  if (!validEmail(loginForm.email)) nextErrors.email = ['Ingresa un correo valido con @ y dominio.']
  if (!loginForm.password) nextErrors.password = ['La contrasena es obligatoria.']
  errors.value = Object.fromEntries(Object.entries(nextErrors).map(([key, value]) => [`login.${key}`, value]))
  return Object.keys(nextErrors).length === 0
}

async function login() {
  if (!validateLogin()) throw new Error('Revisa los datos de inicio de sesion.')

  const payload = await api('/login', { method: 'POST', body: JSON.stringify(loginForm), errorScope: 'login' })
  user.value = payload.user
  localStorage.setItem('shopUser', JSON.stringify(payload.user))
  Object.assign(checkout, {
    name: payload.user.name || '',
    email: payload.user.email || '',
    phone: payload.user.phone || '',
    address: payload.user.address || '',
  })
  setMessage(`Sesion iniciada como ${payload.user.name}.`, 'success')
  view.value = localStorage.getItem('shopNextView') || 'shop'
  localStorage.removeItem('shopNextView')
}

function validateRegister() {
  const nextErrors = {}
  if (!registerForm.name.trim()) nextErrors.name = ['El nombre es obligatorio.']
  if (!validEmail(registerForm.email)) nextErrors.email = ['Ingresa un correo valido con @ y dominio.']
  if (registerForm.password.length < 6) nextErrors.password = ['La contrasena debe tener al menos 6 caracteres.']
  if (!registerForm.phone.trim()) nextErrors.phone = ['El telefono es obligatorio.']
  if (!registerForm.address.trim()) nextErrors.address = ['La direccion es obligatoria.']
  errors.value = Object.fromEntries(Object.entries(nextErrors).map(([key, value]) => [`register.${key}`, value]))
  return Object.keys(nextErrors).length === 0
}

async function register() {
  if (!validateRegister()) throw new Error('Revisa los campos obligatorios del registro.')

  const payload = await api('/register', { method: 'POST', body: JSON.stringify(registerForm), errorScope: 'register' })
  user.value = payload.user
  localStorage.setItem('shopUser', JSON.stringify(payload.user))
  Object.assign(checkout, {
    name: payload.user.name || '',
    email: payload.user.email || '',
    phone: payload.user.phone || '',
    address: payload.user.address || '',
  })
  Object.assign(registerForm, { name: '', email: '', password: '', phone: '', address: '' })
  setMessage(`Cuenta creada para ${payload.user.name}.`, 'success')
  view.value = localStorage.getItem('shopNextView') || 'shop'
  localStorage.removeItem('shopNextView')
}

function logout() {
  user.value = null
  cart.value = []
  localStorage.removeItem('shopUser')
  persistCart()
  setMessage('Sesion cerrada.', 'info')
  view.value = 'shop'
}

function validateProductForm() {
  const nextErrors = {}
  if (!productForm.name.trim()) nextErrors.name = ['El nombre del producto es obligatorio.']
  if (!productForm.description.trim()) nextErrors.description = ['La descripcion es obligatoria.']
  if (!productForm.price || Number(productForm.price) <= 0) nextErrors.price = ['El precio debe ser mayor a cero.']
  if (productForm.stock === '' || Number(productForm.stock) < 0) nextErrors.stock = ['El stock es obligatorio y no puede ser negativo.']
  if (!productForm.sizes.split(',').map((size) => size.trim()).filter(Boolean).length) nextErrors.sizes = ['Agrega al menos una talla.']
  if (productForm.image && !['image/png', 'image/jpeg'].includes(productForm.image.type)) {
    nextErrors.image = ['Solo se aceptan imagenes PNG o JPG.']
  }
  errors.value = nextErrors
  return Object.keys(nextErrors).length === 0
}

function buildProductPayload() {
  const formData = new FormData()
  const sizes = productForm.sizes.split(',').map((size) => size.trim()).filter(Boolean)
  formData.append('name', productForm.name.trim())
  formData.append('description', productForm.description.trim())
  formData.append('price', String(Number(productForm.price)))
  formData.append('stock', String(Number(productForm.stock)))
  sizes.forEach((size) => formData.append('sizes[]', size))
  formData.append('brand', productForm.brand.trim())
  formData.append('is_available', '1')
  if (productForm.image_path.trim()) formData.append('image_path', productForm.image_path.trim())
  if (productForm.image) formData.append('image', productForm.image)
  return formData
}

async function saveProduct() {
  if (!validateProductForm()) throw new Error('Revisa los campos obligatorios del producto.')

  const payload = buildProductPayload()
  const path = isEditingProduct.value ? `/admin/products/${productForm.id}` : '/admin/products'
  if (isEditingProduct.value) payload.append('_method', 'PUT')

  await api(path, { method: 'POST', body: payload })
  setMessage(isEditingProduct.value ? 'Producto actualizado correctamente.' : 'Producto registrado correctamente.', 'success')
  resetProductForm()
  await loadProducts()
  await loadAdmin()
}

function editProduct(product) {
  Object.assign(productForm, {
    id: product.id,
    name: product.name || '',
    description: product.description || '',
    price: product.price || '',
    image_path: product.image_path || '',
    image: null,
    stock: product.stock ?? '',
    sizes: (product.sizes || []).join(','),
    brand: product.brand || '',
  })
  view.value = 'admin'
  setMessage(`Editando ${product.name}.`, 'info')
}

async function deleteProduct(product) {
  const confirmed = window.confirm(`Eliminar definitivamente "${product.name}" del catalogo visible?`)
  if (!confirmed) return
  await api(`/admin/products/${product.id}`, { method: 'DELETE' })
  cart.value = cart.value.filter((item) => item.product_id !== product.id)
  persistCart()
  setMessage('Producto eliminado del catalogo.', 'success')
  await loadProducts()
  await loadAdmin()
}

function resetProductForm() {
  Object.assign(productForm, emptyProductForm())
  const fileInput = document.querySelector('#product-image')
  if (fileInput) fileInput.value = ''
}

function onProductImage(event) {
  const file = event.target.files?.[0] || null
  if (file && !['image/png', 'image/jpeg'].includes(file.type)) {
    productForm.image = null
    event.target.value = ''
    errors.value = { ...errors.value, image: ['Solo se aceptan imagenes PNG o JPG.'] }
    setMessage('La imagen debe ser PNG o JPG.', 'error')
    return
  }
  productForm.image = file
}

async function updateOrder(order, status) {
  await api(`/admin/orders/${order.id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) })
  await loadAdmin()
}

async function openAdmin() {
  view.value = 'admin'
  await run(loadAdmin)
}

onMounted(() => {
  if (user.value) {
    Object.assign(checkout, {
      name: user.value.name || '',
      email: user.value.email || '',
      phone: user.value.phone || '',
      address: user.value.address || '',
    })
  }
  run(loadProducts)
})
</script>

<template>
  <div class="app-shell">
    <header class="topbar">
      <button class="brand" type="button" @click="view = 'shop'">
        <span>SHOP HOLY</span>
        <small>Luxury Urban Fashion</small>
      </button>
      <nav class="nav">
        <button type="button" @click="view = 'shop'">Catalogo</button>
        <button type="button" @click="goToCart">Carrito ({{ cartCount }})</button>
        <button type="button" @click="openAdmin">Admin</button>
        <button v-if="!user" type="button" @click="view = 'auth'">Entrar</button>
        <button v-else type="button" @click="logout">{{ user.name }}</button>
      </nav>
    </header>

    <p v-if="message" class="notice" :class="messageType">{{ message }}</p>

    <main v-if="view === 'shop'" class="shop-page">
      <section class="hero">
        <div>
          <p class="eyebrow">Catalogo activo</p>
          <h1>Prendas urbanas listas para comprar.</h1>
        </div>
        <form class="search" @submit.prevent="run(loadProducts)">
          <input v-model.trim="search" placeholder="Buscar productos" />
          <button type="submit">Buscar</button>
        </form>
      </section>

      <section class="product-grid" aria-label="Catalogo">
        <article v-for="product in products" :key="product.id" class="product-card">
          <img :src="imageUrl(product.image_path)" :alt="product.name" />
          <div class="product-body">
            <p class="category">{{ product.category?.name || product.brand || 'General' }}</p>
            <h2>{{ product.name }}</h2>
            <p>{{ product.description }}</p>
            <div class="meta">
              <strong>${{ Number(product.price).toFixed(2) }}</strong>
              <span>Stock {{ product.stock }}</span>
            </div>
            <div class="sizes">
              <span v-for="size in product.sizes || ['Unitalla']" :key="size">{{ size }}</span>
            </div>
            <div class="card-actions">
              <button type="button" @click="addToCart(product)">Agregar al carrito</button>
              <button class="ghost" type="button" @click="editProduct(product)">Editar</button>
              <button class="ghost danger" type="button" @click="run(() => deleteProduct(product))">Borrar</button>
            </div>
          </div>
        </article>
      </section>
      <p v-if="loading" class="empty">Cargando catalogo...</p>
      <p v-else-if="!products.length" class="empty">No hay productos disponibles.</p>
    </main>

    <main v-else-if="view === 'cart'" class="cart-page">
      <section class="panel">
        <h1>Carrito</h1>
        <div v-if="cart.length" class="cart-list">
          <article v-for="item in cart" :key="item.key" class="cart-item">
            <img :src="imageUrl(item.image_path)" :alt="item.name" />
            <div>
              <h2>{{ item.name }}</h2>
              <p>Talla {{ item.size }} - ${{ item.price.toFixed(2) }}</p>
            </div>
            <div class="qty">
              <button type="button" @click="changeQuantity(item.key, -1)">-</button>
              <span>{{ item.quantity }}</span>
              <button type="button" @click="changeQuantity(item.key, 1)">+</button>
            </div>
            <button class="ghost danger" type="button" @click="removeFromCart(item.key)">Quitar</button>
          </article>
        </div>
        <p v-else class="empty">Tu carrito esta vacio.</p>
      </section>

      <form class="panel checkout" novalidate @submit.prevent="run(placeOrder)">
        <h2>Finalizar compra</h2>
        <label>Nombre completo<input v-model.trim="checkout.name" required placeholder="Nombre completo" /></label>
        <p v-if="fieldError('customer.name')" class="field-error">{{ fieldError('customer.name') }}</p>
        <label>Correo<input v-model.trim="checkout.email" required type="email" placeholder="correo@dominio.com" /></label>
        <p v-if="fieldError('customer.email')" class="field-error">{{ fieldError('customer.email') }}</p>
        <label>Telefono<input v-model.trim="checkout.phone" required placeholder="Telefono" /></label>
        <p v-if="fieldError('customer.phone')" class="field-error">{{ fieldError('customer.phone') }}</p>
        <label>Direccion<textarea v-model.trim="checkout.address" required placeholder="Direccion"></textarea></label>
        <p v-if="fieldError('customer.address')" class="field-error">{{ fieldError('customer.address') }}</p>
        <div class="totals">
          <span>Subtotal</span><strong>${{ subtotal.toFixed(2) }}</strong>
          <span>Envio</span><strong>${{ shipping.toFixed(2) }}</strong>
          <span>Total</span><strong>${{ total.toFixed(2) }}</strong>
        </div>
        <button type="submit" :disabled="!cart.length">Comprar</button>
      </form>
    </main>

    <main v-else-if="view === 'auth'" class="auth-page">
      <form class="panel" novalidate @submit.prevent="run(login)">
        <h1>Iniciar sesion</h1>
        <label>Correo<input v-model.trim="loginForm.email" type="email" required placeholder="correo@dominio.com" /></label>
        <p v-if="fieldError('email', 'login')" class="field-error">{{ fieldError('email', 'login') }}</p>
        <label>Contrasena<input v-model="loginForm.password" type="password" required placeholder="Contrasena" /></label>
        <p v-if="fieldError('password', 'login')" class="field-error">{{ fieldError('password', 'login') }}</p>
        <button type="submit">Entrar</button>
        <p class="hint">Cliente demo: itzel@example.com / password</p>
        <p class="hint">Admin demo: admin@shopholy.test / admin1234</p>
      </form>

      <form class="panel" novalidate @submit.prevent="run(register)">
        <h2>Crear cuenta</h2>
        <label>Nombre<input v-model.trim="registerForm.name" required placeholder="Nombre" /></label>
        <p v-if="fieldError('name', 'register')" class="field-error">{{ fieldError('name', 'register') }}</p>
        <label>Correo<input v-model.trim="registerForm.email" type="email" required placeholder="correo@dominio.com" /></label>
        <p v-if="fieldError('email', 'register')" class="field-error">{{ fieldError('email', 'register') }}</p>
        <label>Contrasena<input v-model="registerForm.password" type="password" required placeholder="Minimo 6 caracteres" /></label>
        <p v-if="fieldError('password', 'register')" class="field-error">{{ fieldError('password', 'register') }}</p>
        <label>Telefono<input v-model.trim="registerForm.phone" required placeholder="Telefono" /></label>
        <p v-if="fieldError('phone', 'register')" class="field-error">{{ fieldError('phone', 'register') }}</p>
        <label>Direccion<textarea v-model.trim="registerForm.address" required placeholder="Direccion"></textarea></label>
        <p v-if="fieldError('address', 'register')" class="field-error">{{ fieldError('address', 'register') }}</p>
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
        <form class="panel" novalidate @submit.prevent="run(saveProduct)">
          <h1>{{ isEditingProduct ? 'Editar producto' : 'Nuevo producto' }}</h1>
          <label>Nombre<input v-model.trim="productForm.name" required placeholder="Nombre" /></label>
          <p v-if="fieldError('name')" class="field-error">{{ fieldError('name') }}</p>
          <label>Descripcion<textarea v-model.trim="productForm.description" required placeholder="Descripcion"></textarea></label>
          <p v-if="fieldError('description')" class="field-error">{{ fieldError('description') }}</p>
          <label>Precio<input v-model="productForm.price" required type="number" min="0.01" step="0.01" placeholder="Precio" /></label>
          <p v-if="fieldError('price')" class="field-error">{{ fieldError('price') }}</p>
          <label>Stock<input v-model="productForm.stock" required type="number" min="0" step="1" placeholder="Stock" /></label>
          <p v-if="fieldError('stock')" class="field-error">{{ fieldError('stock') }}</p>
          <label>Tallas<input v-model.trim="productForm.sizes" required placeholder="S,M,L,XL" /></label>
          <p v-if="fieldError('sizes')" class="field-error">{{ fieldError('sizes') }}</p>
          <label>Marca<input v-model.trim="productForm.brand" placeholder="Marca" /></label>
          <label>URL o ruta de imagen<input v-model.trim="productForm.image_path" placeholder="uploads/imagen.jpg o URL" /></label>
          <label>Adjuntar imagen PNG/JPG<input id="product-image" type="file" accept="image/png,image/jpeg" @change="onProductImage" /></label>
          <p v-if="fieldError('image')" class="field-error">{{ fieldError('image') }}</p>
          <div class="form-actions">
            <button type="submit">{{ isEditingProduct ? 'Actualizar' : 'Guardar' }}</button>
            <button v-if="isEditingProduct" class="ghost" type="button" @click="resetProductForm">Cancelar</button>
          </div>
        </form>

        <section class="panel">
          <h1>Pedidos</h1>
          <article v-for="order in orders" :key="order.id" class="order-row">
            <div>
              <strong>{{ order.order_number }}</strong>
              <p>{{ order.customer_name }} - ${{ Number(order.total).toFixed(2) }}</p>
            </div>
            <select :value="order.status" @change="run(() => updateOrder(order, $event.target.value))">
              <option>Pendiente</option>
              <option>En Proceso</option>
              <option>Entregado</option>
            </select>
          </article>
          <p v-if="!orders.length" class="empty">No hay pedidos registrados.</p>
        </section>
      </section>
    </main>
  </div>
</template>
