<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { fallbackCatalog } from './fallbackCatalog'

const API_ORIGIN = normalizeApiOrigin(import.meta.env.VITE_API_URL)
const DEV_ASSET_ORIGIN = 'http://127.0.0.1:8001'
const ADMIN_EMAIL = 'Ig1613822@gmail.com'
const ADMIN_PASSWORD = '12345678'
const DEFAULT_CATEGORIES = [
  { id: 1, name: 'Faldas' },
  { id: 2, name: 'Blusas' },
  { id: 3, name: 'Pantalones' },
  { id: 4, name: 'Camisetas' },
  { id: 5, name: 'Sudaderas' },
  { id: 6, name: 'Accesorios' },
]
const SIZE_OPTIONS = [
  { label: 'Ropa XS-S-M-L', value: 'XS,S,M,L' },
  { label: 'Ropa S-M-L-XL', value: 'S,M,L,XL' },
  { label: 'Ropa M-L-XL', value: 'M,L,XL' },
  { label: 'Extendida S-XXL', value: 'S,M,L,XL,XXL' },
  { label: 'Unitalla', value: 'Unitalla' },
]
const PAYMENT_METHODS = ['Tarjeta de credito/debito', 'Transferencia bancaria', 'Pago contra entrega']
const BANK_TRANSFER = {
  account: '6495613497912',
  owner: 'SHOPHOLY',
  bank: 'Banco Nacional Demo',
}
const MEXICO_STATES = [
  'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas', 'Chihuahua',
  'Ciudad de Mexico', 'Coahuila', 'Colima', 'Durango', 'Estado de Mexico', 'Guanajuato',
  'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacan', 'Morelos', 'Nayarit', 'Nuevo Leon', 'Oaxaca',
  'Puebla', 'Queretaro', 'Quintana Roo', 'San Luis Potosi', 'Sinaloa', 'Sonora', 'Tabasco',
  'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatan', 'Zacatecas',
]
const STATE_MUNICIPALITIES = {
  Aguascalientes: ['Aguascalientes', 'Calvillo', 'Jesus Maria', 'Rincon de Romos'],
  'Baja California': ['Mexicali', 'Tijuana', 'Ensenada', 'Tecate', 'Playas de Rosarito'],
  'Baja California Sur': ['La Paz', 'Los Cabos', 'Comondu', 'Loreto', 'Mulege'],
  Campeche: ['Campeche', 'Carmen', 'Champoton', 'Escarcega'],
  Chiapas: ['Tuxtla Gutierrez', 'Tapachula', 'San Cristobal de las Casas', 'Comitan'],
  Chihuahua: ['Chihuahua', 'Juarez', 'Delicias', 'Cuauhtemoc', 'Parral'],
  'Ciudad de Mexico': ['Alvaro Obregon', 'Benito Juarez', 'Coyoacan', 'Cuauhtemoc', 'Miguel Hidalgo', 'Tlalpan'],
  Coahuila: ['Saltillo', 'Torreon', 'Monclova', 'Piedras Negras', 'Acuña'],
  Colima: ['Colima', 'Manzanillo', 'Tecoman', 'Villa de Alvarez'],
  Durango: ['Durango', 'Gomez Palacio', 'Lerdo', 'Santiago Papasquiaro'],
  'Estado de Mexico': ['Toluca', 'Ecatepec', 'Naucalpan', 'Nezahualcoyotl', 'Metepec', 'Tlalnepantla'],
  Guanajuato: ['Leon', 'Irapuato', 'Celaya', 'Guanajuato', 'Salamanca'],
  Guerrero: ['Acapulco', 'Chilpancingo', 'Iguala', 'Taxco', 'Zihuatanejo'],
  Hidalgo: ['Pachuca', 'Tulancingo', 'Tula de Allende', 'Huejutla'],
  Jalisco: ['Guadalajara', 'Zapopan', 'Tlaquepaque', 'Tonalá', 'Puerto Vallarta'],
  Michoacan: ['Morelia', 'Uruapan', 'Zamora', 'Lazaro Cardenas'],
  Morelos: ['Cuernavaca', 'Jiutepec', 'Cuautla', 'Temixco'],
  Nayarit: ['Tepic', 'Bahia de Banderas', 'Compostela', 'Santiago Ixcuintla'],
  'Nuevo Leon': ['Monterrey', 'Guadalupe', 'San Nicolas', 'San Pedro Garza Garcia', 'Apodaca'],
  Oaxaca: ['Oaxaca de Juarez', 'Salina Cruz', 'Juchitan', 'Tuxtepec'],
  Puebla: ['Puebla', 'Tehuacan', 'San Andres Cholula', 'Atlixco'],
  Queretaro: ['Queretaro', 'San Juan del Rio', 'El Marques', 'Corregidora'],
  'Quintana Roo': ['Benito Juarez', 'Solidaridad', 'Othon P. Blanco', 'Tulum'],
  'San Luis Potosi': ['San Luis Potosi', 'Soledad de Graciano Sanchez', 'Ciudad Valles', 'Matehuala'],
  Sinaloa: ['Culiacan', 'Mazatlan', 'Ahome', 'Guasave'],
  Sonora: ['Hermosillo', 'Cajeme', 'Nogales', 'San Luis Rio Colorado'],
  Tabasco: ['Centro', 'Cardenas', 'Comalcalco', 'Macuspana'],
  Tamaulipas: ['Victoria', 'Tampico', 'Reynosa', 'Matamoros', 'Nuevo Laredo'],
  Tlaxcala: [
    'Acuamanala de Miguel Hidalgo', 'Amaxac de Guerrero', 'Apetatitlan de Antonio Carvajal', 'Apizaco',
    'Atlangatepec', 'Benito Juarez', 'Calpulalpan', 'Chiautempan', 'Contla de Juan Cuamatzi',
    'Cuapiaxtla', 'Cuaxomulco', 'El Carmen Tequexquitla', 'Emiliano Zapata', 'Espanita',
    'Huamantla', 'Hueyotlipan', 'Ixtacuixtla de Mariano Matamoros', 'Ixtenco', 'La Magdalena Tlaltelulco',
    'Lazaro Cardenas', 'Mazatecochco de Jose Maria Morelos', 'Munoz de Domingo Arenas', 'Nanacamilpa de Mariano Arista',
    'Nativitas', 'Panotla', 'Papalotla de Xicohtencatl', 'San Damian Texoloc', 'San Francisco Tetlanohcan',
    'San Jeronimo Zacualpan', 'San Jose Teacalco', 'San Juan Huactzinco', 'San Lorenzo Axocomanitla',
    'San Lucas Tecopilco', 'San Pablo del Monte', 'Sanctórum de Lazaro Cardenas', 'Santa Ana Nopalucan',
    'Santa Apolonia Teacalco', 'Santa Catarina Ayometla', 'Santa Cruz Quilehtla', 'Santa Cruz Tlaxcala',
    'Santa Isabel Xiloxoxtla', 'Tenancingo', 'Teolocholco', 'Tepetitla de Lardizabal', 'Tepeyanco',
    'Terrenate', 'Tetla de la Solidaridad', 'Tetlatlahuca', 'Tlaxcala', 'Tlaxco', 'Tocatlan',
    'Totolac', 'Tzompantepec', 'Xaloztoc', 'Xaltocan', 'Xicohtzinco', 'Yauhquemehcan',
    'Zacatelco', 'Zitlaltepec de Trinidad Sanchez Santos',
  ],
  Veracruz: ['Xalapa', 'Veracruz', 'Boca del Rio', 'Coatzacoalcos', 'Cordoba', 'Orizaba'],
  Yucatan: ['Merida', 'Valladolid', 'Tizimin', 'Progreso'],
  Zacatecas: ['Zacatecas', 'Guadalupe', 'Fresnillo', 'Jerez'],
}
const DEFAULT_NEIGHBORHOODS = ['Centro', 'Norte', 'Sur', 'Oriente', 'Poniente', 'Residencial', 'Industrial']
const NEIGHBORHOODS_BY_MUNICIPALITY = {
  Apizaco: ['Centro', 'San Martin de Porres', 'Loma Florida', 'Santa Rosa', 'Fovissste'],
  Tlaxcala: ['Centro', 'Ocotlan', 'La Loma Xicohtencatl', 'San Gabriel Cuauhtla', 'Adolfo Lopez Mateos'],
  Chiautempan: ['Centro', 'Santa Ana', 'El Alto', 'Tepetlapa', 'Xaxala'],
  Huamantla: ['Centro', 'San Sebastian', 'San Lucas', 'Nuevos Horizontes', 'Benito Juarez'],
  Zacatelco: ['Centro', 'Exquitla', 'Guardia', 'Manantiales', 'Xochicalco'],
  Puebla: ['Centro Historico', 'La Paz', 'Angelopolis', 'San Manuel', 'Amalucan'],
  Queretaro: ['Centro Sur', 'Juriquilla', 'El Refugio', 'Mileno III', 'Centro'],
  Guadalajara: ['Americana', 'Centro', 'Providencia', 'Chapalita', 'Oblatos'],
  Monterrey: ['Centro', 'Cumbres', 'Mitras', 'Contry', 'San Jeronimo'],
  'Ciudad de Mexico': ['Roma Norte', 'Condesa', 'Del Valle', 'Centro', 'Napoles'],
}
const POSTAL_CODE_MAP = {
  90000: { state: 'Tlaxcala', municipality: 'Tlaxcala', neighborhood: 'Centro' },
  90100: { state: 'Tlaxcala', municipality: 'Tlaxcala', neighborhood: 'Ocotlan' },
  90300: { state: 'Tlaxcala', municipality: 'Apizaco', neighborhood: 'Centro' },
  90800: { state: 'Tlaxcala', municipality: 'Chiautempan', neighborhood: 'Santa Ana' },
  90500: { state: 'Tlaxcala', municipality: 'Huamantla', neighborhood: 'Centro' },
  90740: { state: 'Tlaxcala', municipality: 'Zacatelco', neighborhood: 'Centro' },
  72000: { state: 'Puebla', municipality: 'Puebla', neighborhood: 'Centro Historico' },
  76000: { state: 'Queretaro', municipality: 'Queretaro', neighborhood: 'Centro' },
  44100: { state: 'Jalisco', municipality: 'Guadalajara', neighborhood: 'Centro' },
  64000: { state: 'Nuevo Leon', municipality: 'Monterrey', neighborhood: 'Centro' },
}

const emptyProductForm = () => ({
  id: null,
  category_id: '',
  name: '',
  description: '',
  price: '',
  image_path: '',
  image: null,
  stock: '',
  sizes: 'S,M,L,XL',
  brand: '',
})

const savedSettings = JSON.parse(localStorage.getItem('shopAdminSettings') || '{}')
const products = ref([])
const categories = ref(DEFAULT_CATEGORIES)
const orders = ref([])
const summary = ref(null)
const cart = ref(JSON.parse(localStorage.getItem('shopCart') || '[]'))
const view = ref('shop')
const authMode = ref('login')
const adminTab = ref('products')
const selectedCategory = ref('Todos')
const search = ref('')
const loading = ref(false)
const message = ref('')
const messageType = ref('info')
const errors = ref({})
const showLoginPassword = ref(false)
const showRegisterPassword = ref(false)
const apiOnline = ref(true)
const user = ref(JSON.parse(localStorage.getItem('shopUser') || 'null'))
const productForm = reactive(emptyProductForm())
const checkout = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
  country: 'Mexico',
  state: '',
  municipality: '',
  neighborhood: '',
  postal_code: '',
  payment_method: PAYMENT_METHODS[0],
  card_name: '',
  card_number: '',
  card_expiry: '',
  card_cvv: '',
})
const loginForm = reactive({ email: '', password: '' })
const registerForm = reactive({ name: '', email: '', password: '', phone: '', address: '', terms: false })
const confirmDialog = reactive({ open: false, title: '', message: '', confirmText: '', action: null })
const alertDialog = reactive({ open: false, title: '', message: '', type: 'info' })
const adminSettings = reactive({
  displayName: savedSettings.displayName || 'Administrador SHOP HOLY',
  theme: savedSettings.theme || 'dark',
  photo: savedSettings.photo || '',
})

const isAdmin = computed(() => user.value?.role === 'admin' && user.value?.email?.toLowerCase() === ADMIN_EMAIL.toLowerCase())
const isEditingProduct = computed(() => Boolean(productForm.id))
const selectedImagePreview = computed(() => productForm.image ? URL.createObjectURL(productForm.image) : '')
const adminThemeClass = computed(() => adminSettings.theme === 'light' ? 'admin-light' : 'admin-dark')
const visibleProducts = computed(() => {
  const term = search.value.trim().toLowerCase()
  return products.value.filter((product) => {
    const categoryName = product.category?.name || product.brand || 'General'
    const matchesCategory = selectedCategory.value === 'Todos' || categoryName === selectedCategory.value
    const matchesSearch = !term || `${product.name} ${product.description} ${categoryName} ${product.brand || ''}`.toLowerCase().includes(term)
    return matchesCategory && matchesSearch
  })
})
const categoryNames = computed(() => ['Todos', ...new Set(products.value.map((product) => product.category?.name || product.brand || 'General'))])
const municipalityOptions = computed(() => STATE_MUNICIPALITIES[checkout.state] || [])
const neighborhoodOptions = computed(() => NEIGHBORHOODS_BY_MUNICIPALITY[checkout.municipality] || DEFAULT_NEIGHBORHOODS)
const groupedProducts = computed(() => {
  return visibleProducts.value.reduce((groups, product) => {
    const categoryName = product.category?.name || product.brand || 'General'
    groups[categoryName] = groups[categoryName] || []
    groups[categoryName].push(product)
    return groups
  }, {})
})
const subtotal = computed(() => cart.value.reduce((sum, item) => sum + Number(item.price) * item.quantity, 0))
const shipping = computed(() => (subtotal.value >= 900 || subtotal.value === 0 ? 0 : 99))
const total = computed(() => subtotal.value + shipping.value)
const cartCount = computed(() => cart.value.reduce((sum, item) => sum + item.quantity, 0))
const passwordStrength = computed(() => getPasswordStrength(registerForm.password))

function setMessage(text, type = 'info', showModal = ['error', 'warning'].includes(type)) {
  message.value = text
  messageType.value = type
  if (showModal) {
    Object.assign(alertDialog, {
      open: true,
      title: type === 'success' ? 'Operacion completada' : type === 'warning' ? 'Atencion' : 'No se pudo completar',
      message: text,
      type,
    })
  }
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

function normalizeApiOrigin(url = '') {
  return String(url).trim().replace(/\/+$/, '').replace(/\/api$/i, '')
}

function apiEndpoint(path) {
  return API_ORIGIN ? `${API_ORIGIN}/api${path}` : `/api${path}`
}

function assetEndpoint(path) {
  const cleanPath = String(path).replace(/^\/+/, '')
  const origin = API_ORIGIN || (import.meta.env.DEV ? DEV_ASSET_ORIGIN : '')
  return origin ? `${origin}/${cleanPath}` : `/${cleanPath}`
}

function getPasswordStrength(password) {
  const value = String(password)
  let score = 0
  const feedback = []
  const commonPasswords = ['12345678', 'password', 'contrasena', 'qwerty123', 'admin123', 'shopholy']

  if (value.length >= 8) score += 25
  else feedback.push('minimo 8 caracteres')

  if (/[A-Z]/.test(value)) score += 25
  else feedback.push('una mayuscula')

  if (/\d/.test(value)) score += 25
  else feedback.push('un numero')

  if (/[^A-Za-z0-9]/.test(value)) score += 15
  if (value.length >= 12) score += 10

  const normalized = value.toLowerCase()
  if (commonPasswords.includes(normalized) || /(.)\1{3,}/.test(value) || /^(\d+|[a-z]+)$/i.test(value)) {
    score = Math.min(score, 35)
  }

  const percent = Math.min(score, 100)
  const label = percent >= 80 ? 'Segura' : percent >= 60 ? 'Aceptable' : percent >= 40 ? 'Debil' : 'Muy vulnerable'
  return {
    percent,
    label,
    className: percent >= 80 ? 'strong' : percent >= 60 ? 'ok' : percent >= 40 ? 'weak' : 'danger',
    valid: percent >= 60 && value.length >= 8 && /[A-Z]/.test(value) && /\d/.test(value),
    feedback: feedback.length ? `Agrega ${feedback.join(', ')}.` : 'Usa una clave dificil de adivinar.',
  }
}

function closeAlert() {
  Object.assign(alertDialog, { open: false, title: '', message: '', type: 'info' })
}

function persistCart() {
  localStorage.setItem('shopCart', JSON.stringify(cart.value))
}

function persistAdminSettings() {
  localStorage.setItem('shopAdminSettings', JSON.stringify(adminSettings))
  setMessage('Configuracion del admin guardada.', 'success')
}

function onPostalCodeInput() {
  const match = POSTAL_CODE_MAP[checkout.postal_code.trim()]
  if (!match) return
  checkout.state = match.state
  checkout.municipality = match.municipality
  checkout.neighborhood = match.neighborhood
}

function onStateChange() {
  checkout.municipality = ''
  checkout.neighborhood = ''
}

function onMunicipalityChange() {
  checkout.neighborhood = ''
}

function askConfirmation({ title, message, confirmText, action }) {
  Object.assign(confirmDialog, {
    open: true,
    title,
    message,
    confirmText,
    action,
  })
}

async function confirmAction() {
  const action = confirmDialog.action
  Object.assign(confirmDialog, { open: false, title: '', message: '', confirmText: '', action: null })
  if (action) await run(action)
}

function cancelConfirmation() {
  Object.assign(confirmDialog, { open: false, title: '', message: '', confirmText: '', action: null })
}

function publicView(nextView = 'shop') {
  view.value = nextView
  if (nextView !== 'admin') resetProductForm()
}

function requireLogin(target = 'cart') {
  if (user.value) return true
  view.value = 'auth'
  authMode.value = 'login'
  setMessage('Inicia sesion para comprar o agregar prendas al carrito.', 'warning')
  localStorage.setItem('shopNextView', target)
  return false
}

function requireAdmin() {
  if (isAdmin.value) return true
  view.value = 'auth'
  authMode.value = 'login'
  setMessage('Solo el administrador puede entrar al panel.', 'warning')
  localStorage.setItem('shopNextView', 'admin')
  return false
}

function adminHeaders() {
  return isAdmin.value ? { 'X-User-Email': user.value.email } : {}
}

async function api(path, options = {}) {
  const controller = new AbortController()
  const timeout = setTimeout(() => controller.abort(), options.timeout || 8000)
  const headers = options.body instanceof FormData
    ? { Accept: 'application/json', ...adminHeaders(), ...options.headers }
    : { 'Content-Type': 'application/json', Accept: 'application/json', ...adminHeaders(), ...options.headers }

  try {
    const response = await fetch(apiEndpoint(path), { ...options, headers, signal: controller.signal })
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
  } finally {
    clearTimeout(timeout)
  }
}

async function run(task) {
  try {
    clearErrors()
    message.value = ''
    await task()
  } catch (error) {
    setMessage(error.name === 'AbortError' ? 'El servidor tardo demasiado en responder.' : error.message, 'error')
  }
}

function fallbackProducts() {
  return fallbackCatalog
}

async function loadCategories() {
  try {
    const payload = await api('/categories', { timeout: 4000 })
    categories.value = payload.data.length ? payload.data : DEFAULT_CATEGORIES
  } catch {
    categories.value = DEFAULT_CATEGORIES
  }
}

async function loadProducts() {
  loading.value = true
  try {
    const payload = await api('/products', { timeout: 5000 })
    products.value = payload.data
    apiOnline.value = true
  } catch {
    apiOnline.value = false
    products.value = fallbackProducts()
    setMessage('Catalogo local activo mientras el backend no responde.', 'warning')
  } finally {
    loading.value = false
  }
}

async function loadAdmin() {
  if (!requireAdmin()) return
  const [ordersPayload, summaryPayload] = await Promise.all([api('/admin/orders'), api('/admin/summary')])
  orders.value = ordersPayload.data
  summary.value = summaryPayload.data
  apiOnline.value = true
}

function imageUrl(path) {
  if (!path) return 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900'
  if (path.startsWith('http') || path.startsWith('blob:') || path.startsWith('data:')) return path
  return apiOnline.value ? assetEndpoint(path) : `/${String(path).replace(/^\/+/, '')}`
}

function onImageError(event, path) {
  if (!path || path.startsWith('http') || path.startsWith('blob:') || event.target.dataset.fallback === '1') return
  event.target.dataset.fallback = '1'
  event.target.src = `/${path}`
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
  const item = cart.value.find((entry) => entry.key === key)
  if (!item) return
  askConfirmation({
    title: 'Quitar prenda del carrito',
    message: `Quieres quitar "${item.name}" de tu carrito?`,
    confirmText: 'Quitar prenda',
    action: async () => {
      cart.value = cart.value.filter((entry) => entry.key !== key)
      persistCart()
      setMessage('Prenda retirada del carrito.', 'success')
    },
  })
}

function validateCheckout() {
  const nextErrors = {}
  if (!checkout.name.trim()) nextErrors['customer.name'] = ['El nombre es obligatorio.']
  if (!validEmail(checkout.email)) nextErrors['customer.email'] = ['Ingresa un correo valido con @ y dominio.']
  if (!checkout.phone.trim()) nextErrors['customer.phone'] = ['El telefono es obligatorio.']
  if (!checkout.address.trim()) nextErrors['customer.address'] = ['La direccion es obligatoria.']
  if (!checkout.state) nextErrors.state = ['Selecciona el estado.']
  if (!checkout.municipality) nextErrors.municipality = ['Selecciona el municipio.']
  if (!checkout.neighborhood) nextErrors.neighborhood = ['Selecciona la colonia.']
  if (!checkout.postal_code.trim()) nextErrors.postal_code = ['El codigo postal es obligatorio.']
  if (checkout.payment_method === PAYMENT_METHODS[0]) {
    if (!checkout.card_name.trim()) nextErrors.card_name = ['El nombre del titular es obligatorio.']
    if (!/^\d{13,19}$/.test(checkout.card_number.replace(/\s/g, ''))) nextErrors.card_number = ['Ingresa un numero de tarjeta valido.']
    if (!/^\d{2}\/\d{2}$/.test(checkout.card_expiry)) nextErrors.card_expiry = ['Usa formato MM/AA.']
    if (!/^\d{3,4}$/.test(checkout.card_cvv)) nextErrors.card_cvv = ['CVV invalido.']
  }
  errors.value = nextErrors
  return Object.keys(nextErrors).length === 0
}

async function placeOrder() {
  if (!requireLogin('cart')) return
  if (!cart.value.length) throw new Error('Agrega al menos una prenda al carrito.')
  if (!validateCheckout()) throw new Error('Revisa los datos de pago y envio.')

  const payload = await api('/orders', {
    method: 'POST',
    body: JSON.stringify({
      customer: {
        name: checkout.name,
        email: checkout.email,
        phone: checkout.phone,
        address: `${checkout.address}, ${checkout.neighborhood}, ${checkout.municipality}, ${checkout.state}, ${checkout.country}, CP ${checkout.postal_code}`,
      },
      items: cart.value.map((item) => ({ product_id: item.product_id, size: item.size, quantity: item.quantity })),
    }),
  })

  cart.value = []
  persistCart()
  Object.assign(checkout, {
    name: '',
    email: '',
    phone: '',
    address: '',
    country: 'Mexico',
    state: '',
    municipality: '',
    neighborhood: '',
    postal_code: '',
    payment_method: PAYMENT_METHODS[0],
    card_name: '',
    card_number: '',
    card_expiry: '',
    card_cvv: '',
  })
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

  if (loginForm.email.trim().toLowerCase() === ADMIN_EMAIL.toLowerCase() && loginForm.password === ADMIN_PASSWORD) {
    user.value = { name: 'Administrador', email: ADMIN_EMAIL, role: 'admin', is_active: true }
    localStorage.setItem('shopUser', JSON.stringify(user.value))
    setMessage('Sesion de administrador iniciada.', 'success', true)
    view.value = localStorage.getItem('shopNextView') || 'admin'
    localStorage.removeItem('shopNextView')
    if (view.value === 'admin') await run(loadAdmin)
    return
  }

  const payload = await api('/login', { method: 'POST', body: JSON.stringify(loginForm), errorScope: 'login', timeout: 5000 })
  user.value = payload.user
  localStorage.setItem('shopUser', JSON.stringify(payload.user))
  Object.assign(checkout, {
    name: payload.user.name || '',
    email: payload.user.email || '',
    phone: payload.user.phone || '',
    address: payload.user.address || '',
  })
  setMessage(`Sesion iniciada como ${payload.user.name}.`, 'success', true)
  view.value = localStorage.getItem('shopNextView') || 'shop'
  localStorage.removeItem('shopNextView')
}

function validateRegister() {
  const nextErrors = {}
  if (!registerForm.name.trim()) nextErrors.name = ['El nombre es obligatorio.']
  if (!validEmail(registerForm.email)) nextErrors.email = ['Ingresa un correo valido con @ y dominio.']
  if (registerForm.email.trim().toLowerCase() === ADMIN_EMAIL.toLowerCase()) nextErrors.email = ['Este correo esta reservado para el administrador.']
  if (!passwordStrength.value.valid) {
    nextErrors.password = ['La contrasena es muy debil. Debe tener minimo 8 caracteres, una mayuscula y un numero.']
  }
  if (!registerForm.phone.trim()) nextErrors.phone = ['El telefono es obligatorio.']
  if (!registerForm.address.trim()) nextErrors.address = ['La direccion es obligatoria.']
  if (!registerForm.terms) nextErrors.terms = ['Debes leer y aceptar los terminos y condiciones.']
  errors.value = Object.fromEntries(Object.entries(nextErrors).map(([key, value]) => [`register.${key}`, value]))
  return Object.keys(nextErrors).length === 0
}

async function register() {
  if (!validateRegister()) throw new Error('Revisa los campos obligatorios del registro.')

  const payload = await api('/register', { method: 'POST', body: JSON.stringify(registerForm), errorScope: 'register', timeout: 6000 })
  user.value = payload.user
  localStorage.setItem('shopUser', JSON.stringify(payload.user))
  Object.assign(checkout, {
    name: payload.user.name || '',
    email: payload.user.email || '',
    phone: payload.user.phone || '',
    address: payload.user.address || '',
  })
  Object.assign(registerForm, { name: '', email: '', password: '', phone: '', address: '', terms: false })
  setMessage(`Cuenta creada para ${payload.user.name}.`, 'success', true)
  view.value = 'shop'
}

function logout() {
  user.value = null
  cart.value = []
  localStorage.removeItem('shopUser')
  persistCart()
  resetProductForm()
  setMessage('Sesion cerrada.', 'info')
  view.value = 'shop'
}

function validateProductForm() {
  const nextErrors = {}
  if (!isAdmin.value) nextErrors.admin = ['Solo el administrador puede gestionar productos.']
  if (!productForm.category_id) nextErrors.category_id = ['Selecciona una categoria.']
  if (!productForm.name.trim()) nextErrors.name = ['El nombre del producto es obligatorio.']
  if (!productForm.description.trim()) nextErrors.description = ['La descripcion es obligatoria.']
  if (!productForm.price || Number(productForm.price) <= 0) nextErrors.price = ['El precio debe ser mayor a cero.']
  if (productForm.stock === '' || Number(productForm.stock) < 0) nextErrors.stock = ['El stock es obligatorio y no puede ser negativo.']
  if (!productForm.sizes) nextErrors.sizes = ['Selecciona tallas.']
  if (productForm.image && !['image/png', 'image/jpeg'].includes(productForm.image.type)) nextErrors.image = ['Solo se aceptan imagenes PNG o JPG.']
  errors.value = nextErrors
  return Object.keys(nextErrors).length === 0
}

function buildProductPayload() {
  const formData = new FormData()
  const sizes = productForm.sizes.split(',').map((size) => size.trim()).filter(Boolean)
  formData.append('category_id', productForm.category_id)
  formData.append('name', productForm.name.trim())
  formData.append('description', productForm.description.trim())
  formData.append('price', String(Number(productForm.price)))
  formData.append('stock', String(Number(productForm.stock)))
  sizes.forEach((size) => formData.append('sizes[]', size))
  formData.append('brand', productForm.brand.trim())
  formData.append('is_available', '1')
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
  if (!requireAdmin()) return
  Object.assign(productForm, {
    id: product.id,
    category_id: product.category_id || categories.value.find((category) => category.name === product.category?.name)?.id || '',
    name: product.name || '',
    description: product.description || '',
    price: product.price || '',
    image_path: product.image_path || '',
    image: null,
    stock: product.stock ?? '',
    sizes: (product.sizes || []).join(','),
    brand: product.brand || '',
  })
  adminTab.value = 'products'
  view.value = 'admin'
  setMessage(`Editando ${product.name}.`, 'info')
}

async function deleteProduct(product) {
  if (!requireAdmin()) return
  askConfirmation({
    title: 'Eliminar producto',
    message: `Estas a punto de eliminar "${product.name}" del catalogo visible. Esta accion no se puede deshacer desde la tienda.`,
    confirmText: 'Eliminar producto',
    action: async () => {
      await api(`/admin/products/${product.id}`, { method: 'DELETE' })
      cart.value = cart.value.filter((item) => item.product_id !== product.id)
      persistCart()
      setMessage('Producto eliminado del catalogo.', 'success')
      await loadProducts()
      await loadAdmin()
    },
  })
}

function resetProductForm() {
  Object.assign(productForm, emptyProductForm())
  const fileInput = document.querySelector('#product-image')
  if (fileInput) fileInput.value = ''
}

function removeSelectedImage() {
  productForm.image = null
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

function onAdminPhoto(event) {
  const file = event.target.files?.[0]
  if (!file || !file.type.startsWith('image/')) return
  const reader = new FileReader()
  reader.onload = () => {
    adminSettings.photo = reader.result
    persistAdminSettings()
  }
  reader.readAsDataURL(file)
}

async function updateOrder(order, status) {
  if (!requireAdmin()) return
  await api(`/admin/orders/${order.id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) })
  await loadAdmin()
}

async function openAdmin() {
  if (!requireAdmin()) return
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
  loadCategories()
  run(loadProducts)
})
</script>

<template>
  <div :class="['app-shell', adminThemeClass, { 'admin-shell': view === 'admin' }]">
    <template v-if="view !== 'admin'">
      <header class="topbar">
        <button class="brand" type="button" @click="publicView('shop')">
          <span>SHOP HOLY</span>
          <small>Luxury Urban Fashion</small>
        </button>
        <nav class="nav">
          <button type="button" @click="publicView('shop')">Catalogo</button>
          <button type="button" @click="goToCart">Carrito ({{ cartCount }})</button>
          <button v-if="isAdmin" type="button" @click="openAdmin">Panel admin</button>
          <button v-if="!user" type="button" @click="view = 'auth'; authMode = 'login'">Entrar</button>
          <button v-else type="button" @click="logout">Cerrar sesion</button>
        </nav>
      </header>

      <p v-if="message" class="notice" :class="messageType">{{ message }}</p>
    </template>

    <main v-if="view === 'shop'" class="shop-page">
      <section class="hero">
        <form class="search" @submit.prevent>
          <input v-model.trim="search" placeholder="Buscar por prenda, marca o categoria" />
          <button type="button" @click="selectedCategory = 'Todos'">Ver todo</button>
        </form>
        <div>
          <p class="eyebrow">Coleccion oficial</p>
          <h1>Moda urbana premium para vestir todos los dias.</h1>
          <span>Compra segura, catalogo organizado y prendas listas para enviar.</span>
        </div>
      </section>

      <section class="category-tabs" aria-label="Categorias">
        <button v-for="category in categoryNames" :key="category" :class="{ active: selectedCategory === category }" type="button" @click="selectedCategory = category">
          {{ category }}
        </button>
      </section>

      <section v-for="(items, category) in groupedProducts" :key="category" class="catalog-section">
        <div class="section-head">
          <h2>{{ category }}</h2>
          <span>{{ items.length }} prendas</span>
        </div>
        <div class="product-grid">
          <article v-for="product in items" :key="product.id" class="product-card">
            <img :src="imageUrl(product.image_path)" :alt="product.name" @error="onImageError($event, product.image_path)" />
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
              <button type="button" @click="addToCart(product)">Agregar al carrito</button>
            </div>
          </article>
        </div>
      </section>
      <p v-if="loading" class="empty">Cargando catalogo...</p>
      <p v-else-if="!visibleProducts.length" class="empty">No hay productos disponibles.</p>
    </main>

    <main v-else-if="view === 'cart'" class="checkout-page">
      <section class="panel cart-panel">
        <h1>Resumen de compra</h1>
        <div v-if="cart.length" class="cart-list">
          <article v-for="item in cart" :key="item.key" class="cart-item">
            <img :src="imageUrl(item.image_path)" :alt="item.name" @error="onImageError($event, item.image_path)" />
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
        <p class="eyebrow">Pago seguro</p>
        <h2>Datos de envio y pago</h2>
        <div class="form-grid">
          <label>Nombre completo<input v-model.trim="checkout.name" required placeholder="Nombre completo" /></label>
          <label>Correo<input v-model.trim="checkout.email" required type="email" placeholder="correo@dominio.com" /></label>
        </div>
        <div class="form-grid">
          <label>Telefono<input v-model.trim="checkout.phone" required placeholder="Telefono" /></label>
          <label>Pais<select v-model="checkout.country" disabled><option>Mexico</option></select></label>
        </div>
        <div class="form-grid">
          <label>Codigo postal<input v-model.trim="checkout.postal_code" required maxlength="5" inputmode="numeric" placeholder="00000" @input="onPostalCodeInput" /></label>
          <label>Estado<select v-model="checkout.state" required @change="onStateChange"><option value="">Selecciona estado</option><option v-for="state in MEXICO_STATES" :key="state">{{ state }}</option></select></label>
        </div>
        <div class="form-grid">
          <label>Municipio<select v-model="checkout.municipality" required :disabled="!checkout.state" @change="onMunicipalityChange"><option value="">Selecciona municipio</option><option v-for="municipality in municipalityOptions" :key="municipality">{{ municipality }}</option></select></label>
          <label>Colonia<select v-model="checkout.neighborhood" required :disabled="!checkout.municipality"><option value="">Selecciona colonia</option><option v-for="neighborhood in neighborhoodOptions" :key="neighborhood">{{ neighborhood }}</option></select></label>
        </div>
        <label>Calle y numero<textarea v-model.trim="checkout.address" required placeholder="Calle, numero exterior/interior y referencias"></textarea></label>
        <label>Metodo de pago<select v-model="checkout.payment_method"><option v-for="method in PAYMENT_METHODS" :key="method">{{ method }}</option></select></label>
        <div v-if="checkout.payment_method === PAYMENT_METHODS[0]" class="payment-box">
          <label>Titular de la tarjeta<input v-model.trim="checkout.card_name" placeholder="Nombre como aparece en tarjeta" /></label>
          <label>Numero de tarjeta<input v-model.trim="checkout.card_number" inputmode="numeric" placeholder="0000 0000 0000 0000" /></label>
          <div class="form-grid">
            <label>Vencimiento<input v-model.trim="checkout.card_expiry" placeholder="MM/AA" /></label>
            <label>CVV<input v-model.trim="checkout.card_cvv" inputmode="numeric" placeholder="123" /></label>
          </div>
        </div>
        <div v-else-if="checkout.payment_method === PAYMENT_METHODS[1]" class="payment-box bank-transfer">
          <span>Cuenta para transferencia</span>
          <strong>{{ BANK_TRANSFER.account }} {{ BANK_TRANSFER.owner }}</strong>
          <p>{{ BANK_TRANSFER.bank }} - envia tu comprobante al finalizar tu pedido.</p>
        </div>
        <p v-for="error in Object.values(errors).flat()" :key="error" class="field-error">{{ error }}</p>
        <div class="totals">
          <span>Subtotal</span><strong>${{ subtotal.toFixed(2) }}</strong>
          <span>Envio</span><strong>${{ shipping.toFixed(2) }}</strong>
          <span>Total</span><strong>${{ total.toFixed(2) }}</strong>
        </div>
        <button type="submit" :disabled="!cart.length">Confirmar compra</button>
      </form>
    </main>

    <main v-else-if="view === 'auth'" class="auth-screen">
      <section class="auth-hero">
        <p>SHOP HOLY</p>
        <h1>{{ authMode === 'login' ? 'Accede a tu cuenta y continua tu compra.' : 'Crea tu cuenta y compra en SHOP HOLY.' }}</h1>
        <span>Una experiencia de moda urbana con acceso rapido, carrito persistente y compra segura.</span>
        <button class="auth-back" type="button" @click="publicView('shop')">Volver al inicio</button>
      </section>

      <section class="auth-panel">
        <form v-if="authMode === 'login'" novalidate @submit.prevent="run(login)">
          <p class="eyebrow">Iniciar sesion</p>
          <h2>Bienvenido de nuevo</h2>
          <span>Ingresa con tu correo y contrasena para continuar.</span>
          <label>Correo electronico<input v-model.trim="loginForm.email" type="email" required placeholder="correo@dominio.com" /></label>
          <p v-if="fieldError('email', 'login')" class="field-error">{{ fieldError('email', 'login') }}</p>
          <label>
            Contrasena
            <span class="password-field">
              <input v-model="loginForm.password" :type="showLoginPassword ? 'text' : 'password'" required placeholder="Contrasena" />
              <button type="button" :aria-label="showLoginPassword ? 'Ocultar contrasena' : 'Mostrar contrasena'" @click="showLoginPassword = !showLoginPassword">
                {{ showLoginPassword ? 'Ocultar' : 'Ver' }}
              </button>
            </span>
          </label>
          <p v-if="fieldError('password', 'login')" class="field-error">{{ fieldError('password', 'login') }}</p>
          <button type="submit">Iniciar sesion</button>
          <p class="auth-switch">Aun no tienes cuenta? <button type="button" @click="authMode = 'register'">Registrate aqui</button></p>
        </form>

        <form v-else novalidate @submit.prevent="run(register)">
          <p class="eyebrow">Registro</p>
          <h2>Crear cuenta</h2>
          <span>Completa tus datos para comprar en la tienda.</span>
          <div class="auth-grid">
            <label>Nombre completo<input v-model.trim="registerForm.name" required placeholder="Nombre" /></label>
            <label>Correo electronico<input v-model.trim="registerForm.email" type="email" required placeholder="correo@dominio.com" /></label>
          </div>
          <div class="auth-grid">
            <label>Telefono<input v-model.trim="registerForm.phone" required placeholder="246 123 4567" /></label>
            <label>Direccion<input v-model.trim="registerForm.address" required placeholder="Calle 5 de Mayo #12, Colonia Centro, Apizaco" /></label>
          </div>
          <label>
            Contrasena
            <span class="password-field">
              <input v-model="registerForm.password" :type="showRegisterPassword ? 'text' : 'password'" required placeholder="Minimo 8 caracteres, una mayuscula y un numero" />
              <button type="button" :aria-label="showRegisterPassword ? 'Ocultar contrasena' : 'Mostrar contrasena'" @click="showRegisterPassword = !showRegisterPassword">
                {{ showRegisterPassword ? 'Ocultar' : 'Ver' }}
              </button>
            </span>
          </label>
          <div class="password-meter" :class="passwordStrength.className" aria-live="polite">
            <div class="password-meter-track">
              <span :style="{ width: `${passwordStrength.percent}%` }"></span>
            </div>
            <div class="password-meter-copy">
              <strong>{{ passwordStrength.label }}</strong>
              <small>{{ passwordStrength.feedback }}</small>
            </div>
          </div>
          <label class="terms-check">
            <input v-model="registerForm.terms" type="checkbox" />
            <span>He leido y acepto los <a href="/terminos-condiciones.html" target="_blank" rel="noopener noreferrer">terminos y condiciones</a>.</span>
          </label>
          <p v-if="fieldError('terms', 'register')" class="field-error">{{ fieldError('terms', 'register') }}</p>
          <p v-for="error in Object.values(errors).flat()" :key="error" class="field-error">{{ error }}</p>
          <button type="submit">Crear cuenta</button>
          <p class="auth-switch">Ya tienes cuenta? <button type="button" @click="authMode = 'login'">Inicia sesion</button></p>
        </form>
      </section>
    </main>

    <main v-else class="admin-page">
      <aside class="admin-sidebar">
        <button class="admin-logo" type="button" @click="publicView('shop')">SHOP HOLY</button>
        <div class="admin-avatar">
          <img v-if="adminSettings.photo" :src="adminSettings.photo" alt="Foto de perfil" />
          <span v-else>A</span>
        </div>
        <nav>
          <button type="button" :class="{ active: adminTab === 'products' }" @click="adminTab = 'products'">Productos</button>
          <button type="button" :class="{ active: adminTab === 'orders' }" @click="adminTab = 'orders'">Pedidos</button>
          <button type="button" :class="{ active: adminTab === 'settings' }" @click="adminTab = 'settings'">Configuraciones</button>
          <button type="button" @click="publicView('shop')">Ver tienda</button>
        </nav>
        <button class="admin-logout" type="button" @click="logout">Cerrar sesion</button>
      </aside>

      <section class="admin-main">
        <p v-if="message" class="notice" :class="messageType">{{ message }}</p>
        <div class="admin-head">
          <div>
            <p class="eyebrow">Panel administrativo</p>
            <h1>{{ adminTab === 'settings' ? 'Configuraciones' : 'Gestion de tienda' }}</h1>
          </div>
          <div class="admin-profile">
            <span>{{ adminSettings.displayName }}</span>
            <strong>{{ user?.email }}</strong>
          </div>
        </div>

        <section class="stats" v-if="summary">
          <article><span>Pendientes</span><strong>{{ summary.pending }}</strong></article>
          <article><span>En proceso</span><strong>{{ summary.processing }}</strong></article>
          <article><span>Entregados</span><strong>{{ summary.delivered }}</strong></article>
          <article><span>Ventas</span><strong>${{ summary.sales.toFixed(2) }}</strong></article>
        </section>

        <template v-if="adminTab === 'products'">
          <section class="admin-grid">
            <form class="panel admin-form" novalidate @submit.prevent="run(saveProduct)">
              <h2>{{ isEditingProduct ? 'Editar producto' : 'Registrar producto' }}</h2>
              <label>Categoria<select v-model="productForm.category_id"><option value="">Selecciona categoria</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></label>
              <p v-if="fieldError('category_id')" class="field-error">{{ fieldError('category_id') }}</p>
              <label>Nombre<input v-model.trim="productForm.name" required placeholder="Nombre" /></label>
              <label>Descripcion<textarea v-model.trim="productForm.description" required placeholder="Descripcion"></textarea></label>
              <div class="admin-form-grid">
                <label>Precio<input v-model="productForm.price" required type="number" min="0.01" step="0.01" placeholder="Precio" /></label>
                <label>Stock<input v-model="productForm.stock" required type="number" min="0" step="1" placeholder="Stock" /></label>
              </div>
              <div class="admin-form-grid">
                <label>Tallas<select v-model="productForm.sizes"><option v-for="option in SIZE_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
                <label>Marca<input v-model.trim="productForm.brand" placeholder="Marca" /></label>
              </div>
              <label>Adjuntar imagen PNG/JPG<input id="product-image" type="file" accept="image/png,image/jpeg" @change="onProductImage" /></label>
              <p v-if="fieldError('image')" class="field-error">{{ fieldError('image') }}</p>
              <div v-if="selectedImagePreview || productForm.image_path" class="image-preview">
                <img :src="selectedImagePreview || imageUrl(productForm.image_path)" alt="Vista previa de producto" @error="onImageError($event, productForm.image_path)" />
                <button v-if="productForm.image" class="ghost danger" type="button" @click="removeSelectedImage">Eliminar imagen seleccionada</button>
              </div>
              <div class="form-actions">
                <button type="submit">{{ isEditingProduct ? 'Actualizar producto' : 'Registrar producto' }}</button>
                <button v-if="isEditingProduct" class="ghost" type="button" @click="resetProductForm">Cancelar</button>
              </div>
            </form>

            <section class="panel admin-products">
              <div class="panel-head">
                <h2>Catalogo administrable</h2>
                <span>{{ products.length }} productos</span>
              </div>
              <article v-for="product in products" :key="product.id" class="admin-product-row">
                <img :src="imageUrl(product.image_path)" :alt="product.name" @error="onImageError($event, product.image_path)" />
                <div>
                  <strong>{{ product.name }}</strong>
                  <p>{{ product.category?.name || 'General' }} - ${{ Number(product.price).toFixed(2) }}</p>
                </div>
                <button type="button" @click="editProduct(product)">Editar</button>
                <button class="danger-soft" type="button" @click="deleteProduct(product)">Borrar</button>
              </article>
            </section>
          </section>
        </template>

        <section v-else-if="adminTab === 'orders'" class="panel orders-panel">
          <div class="panel-head">
            <h2>Pedidos recientes</h2>
            <span>{{ orders.length }} registros</span>
          </div>
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

        <section v-else class="panel settings-panel">
          <h2>Perfil de administrador</h2>
          <div class="settings-grid">
            <label>Nombre visible<input v-model.trim="adminSettings.displayName" placeholder="Nombre del administrador" /></label>
            <label>Tema<select v-model="adminSettings.theme"><option value="dark">Oscuro dorado</option><option value="light">Claro elegante</option></select></label>
            <label>Foto de perfil<input type="file" accept="image/*" @change="onAdminPhoto" /></label>
          </div>
          <div class="profile-preview">
            <img v-if="adminSettings.photo" :src="adminSettings.photo" alt="Foto admin" />
            <span v-else>A</span>
            <div>
              <strong>{{ adminSettings.displayName }}</strong>
              <p>{{ user?.email }}</p>
            </div>
          </div>
          <button type="button" @click="persistAdminSettings">Guardar configuracion</button>
        </section>
      </section>
    </main>

    <footer v-if="view !== 'admin'" class="site-footer">
      <div><strong>SHOP HOLY</strong><p>Moda urbana con compra simple, catalogo claro y atencion confiable.</p></div>
      <div><span>Mision</span><p>Ofrecer prendas accesibles y actuales para uso diario.</p></div>
      <div><span>Vision</span><p>Ser una tienda digital practica para moda urbana local.</p></div>
      <div>
        <span>Terminos</span>
        <p><a href="/terminos-condiciones.html" target="_blank" rel="noopener noreferrer">Ver terminos y condiciones de compra, envio y devoluciones.</a></p>
      </div>
    </footer>

    <div v-if="alertDialog.open" class="modal-backdrop" role="dialog" aria-modal="true">
      <section class="modal-card" :class="`modal-${alertDialog.type}`">
        <p class="eyebrow">{{ alertDialog.type === 'success' ? 'Aviso' : 'Mensaje del sistema' }}</p>
        <h2>{{ alertDialog.title }}</h2>
        <p>{{ alertDialog.message }}</p>
        <div class="modal-actions single">
          <button type="button" @click="closeAlert">Entendido</button>
        </div>
      </section>
    </div>

    <div v-if="confirmDialog.open" class="modal-backdrop" role="dialog" aria-modal="true">
      <section class="modal-card">
        <p class="eyebrow">Confirmacion</p>
        <h2>{{ confirmDialog.title }}</h2>
        <p>{{ confirmDialog.message }}</p>
        <div class="modal-actions">
          <button class="ghost" type="button" @click="cancelConfirmation">Cancelar</button>
          <button class="danger-soft" type="button" @click="confirmAction">{{ confirmDialog.confirmText }}</button>
        </div>
      </section>
    </div>
  </div>
</template>
