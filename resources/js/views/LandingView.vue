<template>
  <div class="landing">
    <header id="main-header">
      <div class="container-header">
        <img :src="img('LOGO.svg')" alt="Logo" class="logo-img">
        <div class="menu-nav">
          <router-link to="/login">Ingresar</router-link>
          <a href="#menu-section">Menú</a>
          <a href="#experiencia">Nosotros</a>
        </div>
      </div>
    </header>

    <main>
      <div class="hero">
        <div class="background">
          <video autoplay loop muted playsinline>
            <source :src="vid('pagina central.mp4')" type="video/mp4">
          </video>
        </div>
        <div class="overlay">
          <div class="rapid-logos-container">
            <div class="logos-wrapper">
              <img class="logo-institucion active" :src="img('UPDS.svg')" alt="UPDS">
              <img class="logo-institucion" :src="img('LOGO.svg')" alt="LOGO">
              <img class="logo-institucion" :src="img('MEDICINA.svg')" alt="MEDICINA">
              <img class="logo-institucion" :src="img('PROFESIONALES.svg')" alt="PROFESIONALES">
            </div>
          </div>
          <div class="logo-final">
            <img :src="img('CAFETERIA.svg')" alt="Logo Cafetería UPDS">
            <div class="hero-descripcion">
              Menos teoría, más sabor. Nos unimos a tu ritmo diario en la UPDS para traerte calidad sin vueltas, justo cuando más lo necesitas. El punto de encuentro oficial para tus descansos, para consentir el antojo y recargar la energía necesaria para liderar el día. Pásate y dale un break a tu jornada.
            </div>
          </div>
          <router-link to="/login" class="hero-btn">Ingresar al sistema</router-link>
        </div>
      </div>

      <div class="textSplit align-left" id="experiencia">
        <div class="container">
          <div class="media">
            <video autoplay loop muted playsinline>
              <source :src="vid('experiencia.mp4')" type="video/mp4">
            </video>
          </div>
          <div class="text">
            <h2>Vive la experiencia Cafetería UPDS</h2>
            <p><strong>Comodidad, sabor y momentos únicos</strong></p>
            <p>Disfrutar en nuestra cafetería no es solo tomar un café: es sumergirte en un espacio diseñado para relajarte, conectar con amigos o recargar energías entre clases. Nuestros sillones, la luz cálida y la música suave te envolverán mientras pruebas nuestras creaciones.</p>
            <p>Cada taza está hecha pensando en ti: desde un espresso intenso hasta un latte art que alegra la vista. Además, acompañamos tu bebida con repostería casera y snacks saludables. Ven, siéntete como en casa y déjate mimar.</p>
            <router-link to="/login" class="button">Ver menú completo →</router-link>
          </div>
        </div>
      </div>

      <div class="menu-section" id="menu-section">
        <video class="menu-bg-video" autoplay loop muted playsinline>
          <source :src="vid('menu.mp4')" type="video/mp4">
        </video>
        <div class="container">
          <h2>Nuestro Menú</h2>
          <div v-if="cargandoMenu" class="text-center text-ink-dim py-8">Cargando menú...</div>
          <div v-else class="menu-categories">
            <div v-for="cat in menuCategorias" :key="cat.nombre" class="menu-category">
              <h3>{{ cat.nombre }}</h3>
              <div v-for="item in cat.items" :key="item.id" class="menu-item" :class="{ 'sin-stock': stockFaltante(item) }">
                <img :src="item.imagen_url || '/assets/img/placeholder-food.svg'" :alt="item.nombre" @error="$event.target.src='/assets/img/placeholder-food.svg'">
                <div class="menu-item-info">
                  <h4>{{ item.nombre }}</h4>
                  <p>{{ item.descripcion || '' }}</p>
                  <span v-if="!stockFaltante(item)" class="price">Bs. {{ Number(item.precio_venta).toFixed(0) }}</span>
                  <span v-else class="sin-stock-label">Stock insuficiente para "{{ stockFaltante(item) }}".</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="doubleSplit">
        <div class="container">
          <div class="a-split">
            <div class="background">
              <video autoplay loop muted playsinline>
                <source :src="vid('menu 1.mp4')" type="video/mp4">
              </video>
            </div>
            <div class="text">
              <h3>Pasión por el sabor</h3>
              <p>Seleccionamos los mejores granos y materias primas para ofrecerte productos de calidad excepcional.</p>
              <router-link to="/login">Descubrir más →</router-link>
            </div>
          </div>
          <div class="a-split">
            <div class="background">
              <video autoplay loop muted playsinline>
                <source :src="vid('pagina central.mp4')" type="video/mp4">
              </video>
            </div>
            <div class="text">
              <h3>Ambiente único</h3>
              <p>Un espacio pensado para que disfrutes cada momento, ya sea solo o acompañado.</p>
              <router-link to="/login">Conocer más →</router-link>
            </div>
          </div>
        </div>
      </div>

      <div class="misc-grid">
        <div class="tile">
          <img src="https://images.pexels.com/photos/1695052/pexels-photo-1695052.jpeg?auto=compress&cs=tinysrgb&w=200" alt="Café">
          <h3>Variedad</h3>
          <p class="text-ink-mute">Bebidas calientes y frías para todos los gustos</p>
        </div>
        <div class="tile">
          <img src="https://images.pexels.com/photos/2067429/pexels-photo-2067429.jpeg?auto=compress&cs=tinysrgb&w=200" alt="Postre">
          <h3>Repostería</h3>
          <p class="text-ink-mute">Dulces artesanales horneados cada mañana</p>
        </div>
        <div class="tile">
          <img src="https://images.pexels.com/photos/1640772/pexels-photo-1640772.jpeg?auto=compress&cs=tinysrgb&w=200" alt="Snack">
          <h3>Snacks</h3>
          <p class="text-ink-mute">Opciones ligeras y nutritivas para tu día</p>
        </div>
        <div class="tile">
          <img src="https://images.pexels.com/photos/1267696/pexels-photo-1267696.jpeg?auto=compress&cs=tinysrgb&w=200" alt="Evento">
          <h3>Eventos</h3>
          <p class="text-ink-mute">Organizamos reuniones y catas privadas</p>
        </div>
      </div>
    </main>

    <footer>
      <div class="colophon">
        <p>© Cafetería UPDS · Menos teoría, más sabor.</p>
      </div>
    </footer>

    <router-link to="/login" class="carrito-flotante" id="cart-icon">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white">
        <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
      </svg>
      <span class="cart-count">0</span>
    </router-link>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

const BASE = '/landing'
const img = (f) => `${BASE}/imagenes/${f}`
const vid = (f) => `${BASE}/videos/${f}`

const menuCategorias = ref([])
const cargandoMenu = ref(true)

onMounted(() => {
  initLanding()
  cargarMenu()
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

async function cargarMenu() {
  try {
    const res = await fetch('/api/menus-publicos')
    const body = await res.json()
    const menus = body.data ?? []
    const agrupados = {}
    for (const m of menus) {
      const cat = m.categoria?.nombre ?? 'Otros'
      if (!agrupados[cat]) agrupados[cat] = []
      agrupados[cat].push(m)
    }
    menuCategorias.value = Object.entries(agrupados).map(([nombre, items]) => ({ nombre, items }))
    await nextTick()
    observarMenuCategorias()
  } catch (e) {
    console.error('Error al cargar menú:', e)
  } finally {
    cargandoMenu.value = false
  }
}

function stockFaltante(item) {
  if (item.tipo !== 'preparado' || !item.ingredientes?.length) return null
  for (const ing of item.ingredientes) {
    const porciones = Math.floor(Number(ing.producto.stock_actual) / Number(ing.cantidad))
    if (porciones < 1) return ing.producto.nombre
  }
  return null
}

function initLanding() {
  window.addEventListener('scroll', handleScroll)

  const logosWrapper = document.querySelector('.logos-wrapper')
  const logoFinalDiv = document.querySelector('.logo-final')
  const logos = document.querySelectorAll('.logo-institucion')

  if (logosWrapper && logoFinalDiv && logos.length) {
    logosWrapper.style.display = 'block'
    logoFinalDiv.style.display = 'none'
    logos.forEach(l => l.classList.remove('active'))
    logos[0].classList.add('active')

    let running = true
    let currentTimeout = null

    async function mostrarSecuencia() {
      for (let i = 0; i < logos.length; i++) {
        if (!running) return
        logos.forEach(l => l.classList.remove('active'))
        logos[i].classList.add('active')
        await delay(1000)
      }
      if (!running) return
      logosWrapper.style.display = 'none'
      logoFinalDiv.style.display = 'block'
      logoFinalDiv.classList.remove('animar')
      void logoFinalDiv.offsetHeight
      logoFinalDiv.classList.add('animar')
      await delay(3500)
      if (!running) return
      logoFinalDiv.style.display = 'none'
      logosWrapper.style.display = 'block'
      setTimeout(() => { if (running) mostrarSecuencia() }, 200)
    }

    mostrarSecuencia()
  }

  const textSplit = document.querySelector('.textSplit')
  if (textSplit) {
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal-text')
          obs.unobserve(entry.target)
        }
      })
    }, { threshold: 0.2 })
    obs.observe(textSplit)
  }

  observarMenuCategorias()
}

function observarMenuCategorias() {
  const menuCategories = document.querySelectorAll('.menu-category')
  if (menuCategories.length) {
    const menuObs = new IntersectionObserver((entries) => {
      entries.forEach((entry, idx) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.classList.add('revealed')
          }, idx * 150)
          menuObs.unobserve(entry.target)
        }
      })
    }, { threshold: 0.2 })
    menuCategories.forEach(cat => menuObs.observe(cat))
  }
}

function handleScroll() {
  const header = document.getElementById('main-header')
  if (header) {
    if (window.scrollY > 50) header.classList.add('scrolled')
    else header.classList.remove('scrolled')
  }
}

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms))
}
</script>

<style>
@font-face {
  font-family: 'Room-205';
  font-display: swap;
  src: url('https://onyxcoffeelab.com/cdn/shop/t/31/assets/Room-205.woff2?v=18721117088091669681705351138') format('woff2');
}

.landing {
  font-family: 'Montserrat', sans-serif;
  background-color: #0a0a0a;
  color: #f0f0f0;
  line-height: 1.4;
  overflow-x: hidden;
}

.landing * { margin: 0; padding: 0; box-sizing: border-box; }

.landing .container { max-width: 1280px; margin: 0 auto; padding: 0 2rem; }

.landing header {
  position: fixed;
  top: 0; left: 0; width: 100%;
  z-index: 100;
  padding: 1.5rem 2rem;
  background: transparent;
  transition: all 0.3s ease;
}
.landing header.scrolled {
  background: #0a0a0a;
  padding: 0.8rem 2rem;
  box-shadow: 0 2px 15px rgba(0,0,0,0.3);
}
.landing header .container-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
}
.landing .logo-img {
  height: 45px; width: auto; display: block;
  filter: brightness(0) invert(1);
}
.landing .menu-nav { display: flex; gap: 2rem; align-items: center; }
.landing .menu-nav a {
  color: white; text-decoration: none;
  font-size: 0.9rem; letter-spacing: 1px;
  font-weight: 500; transition: opacity 0.2s;
}
.landing .menu-nav a:hover { opacity: 0.7; }
.landing .menu-nav a:first-child {
  background: #f5c542; color: #1a1a1a;
  padding: 0.5rem 1.5rem; border-radius: 40px;
  font-weight: 600;
}
.landing .menu-nav a:first-child:hover { opacity: 0.85; }

.landing .hero {
  position: relative;
  height: 100vh; min-height: 700px;
  display: flex; align-items: center; justify-content: center;
  text-align: center; overflow: hidden;
  background: #0a0a0a;
}
.landing .hero .background {
  position: absolute; top: 0; left: 0;
  width: 100%; height: 100%; z-index: 0;
}
.landing .hero .background video {
  width: 100%; height: 100%;
  object-fit: cover; opacity: 0.6;
  filter: brightness(0.7) contrast(1.1);
}
.landing .hero .overlay {
  position: relative; z-index: 2;
  width: 100%; max-width: 950px;
  padding: 2rem;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
}
.landing .rapid-logos-container {
  width: 500px; max-width: 90%;
  height: 240px; position: relative;
  margin-bottom: 1rem;
}
.landing .logos-wrapper { position: relative; width: 100%; height: 100%; }
.landing .logo-institucion {
  position: absolute; top: 0; left: 0;
  width: 100%; height: 100%;
  object-fit: contain;
  opacity: 0;
  transition: opacity 0.6s ease-in-out;
  filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
}
.landing .logo-institucion.active { opacity: 1; }
.landing .logo-final { width: 100%; display: none; text-align: center; }
.landing .logo-final.animar {
  animation: logoProAnimation 1.2s cubic-bezier(0.2, 0.9, 0.4, 1.2) forwards;
}
.landing .logo-final img {
  width: 100%; max-width: 340px; height: auto;
  margin: 0 auto 1rem auto; display: block;
  filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4));
  animation: glowPulsante 2s ease-in-out 0.8s infinite alternate;
}

@keyframes logoProAnimation {
  0% { opacity: 0; transform: scale(0.3) rotateY(-90deg); filter: blur(10px) brightness(0.3); }
  40% { opacity: 0.9; transform: scale(1.08) rotateY(5deg); filter: blur(2px) brightness(1.2); }
  70% { transform: scale(0.98) rotateY(0deg); }
  100% { opacity: 1; transform: scale(1) rotateY(0); filter: blur(0) brightness(1); }
}
@keyframes glowPulsante {
  0% { filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4)) brightness(1); }
  100% { filter: drop-shadow(0 0 22px rgba(245, 197, 66, 0.7)) brightness(1.05); }
}

.landing .hero-btn {
  margin-top: 1.5rem;
  display: inline-block;
  background: #f5c542;
  color: #1a1a1a;
  padding: 0.8rem 2.5rem;
  border-radius: 40px;
  font-weight: 600;
  font-size: 1rem;
  text-decoration: none;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(245, 197, 66, 0.3);
}
.landing .hero-btn:hover {
  background: #ffd966;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(245, 197, 66, 0.4);
}

.landing .textSplit { padding: 5rem 0; background: #fbfaf3; color: #1a1a1a; overflow-x: hidden; }
.landing .textSplit .container { display: flex; flex-wrap: wrap; gap: 3rem; align-items: center; }
.landing .textSplit .media { flex: 1; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 35px -15px rgba(0,0,0,0.2); }
.landing .textSplit .media video { width: 100%; height: auto; display: block; transition: transform 0.4s ease; }
.landing .textSplit .media video:hover { transform: scale(1.02); }
.landing .textSplit .text { flex: 1; }
.landing .textSplit .text h2,
.landing .textSplit .text p,
.landing .textSplit .text .button {
  opacity: 0; transform: translateX(-40px);
  transition: opacity 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.2), transform 0.6s cubic-bezier(0.2, 0.9, 0.4, 1.2);
}
.landing .textSplit.reveal-text .text h2 { opacity: 1; transform: translateX(0); transition-delay: 0.1s; }
.landing .textSplit.reveal-text .text p { opacity: 1; transform: translateX(0); transition-delay: 0.3s; }
.landing .textSplit.reveal-text .text .button { opacity: 1; transform: translateX(0); transition-delay: 0.5s; }
.landing .textSplit .text h2 { font-family: 'Room-205', serif; font-size: 2.8rem; margin-bottom: 1rem; color: #1a1a1a; }
.landing .textSplit .text p { margin-bottom: 1.5rem; line-height: 1.6; color: #2e2e2e; }
.landing .textSplit .button {
  background: #1a1a1a; color: white; padding: 0.8rem 2rem;
  text-decoration: none; font-weight: 500; display: inline-block;
  transition: all 0.3s ease; border-radius: 40px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}
.landing .textSplit .button:hover {
  background: #3b3b3b; transform: translateY(-3px);
  box-shadow: 0 8px 18px rgba(0,0,0,0.1);
}

.landing .menu-section {
  position: relative; padding: 5rem 0; overflow: hidden;
}
.landing .menu-bg-video {
  position: absolute; top: 0; left: 0; width: 100%; height: 100%;
  object-fit: cover; z-index: 0; opacity: 0.45; filter: brightness(0.6);
}
.landing .menu-section .container { position: relative; z-index: 2; }
.landing .menu-section h2 {
  font-family: 'Room-205', serif; font-size: 2.5rem;
  text-align: center; margin-bottom: 3rem;
  color: white; text-shadow: 0 2px 8px rgba(0,0,0,0.5);
}
.landing .menu-categories { display: flex; flex-wrap: wrap; gap: 2rem; justify-content: center; }
.landing .menu-category {
  flex: 1; min-width: 280px;
  background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
  border-radius: 28px; padding: 2rem;
  box-shadow: 0 10px 25px rgba(0,0,0,0.3);
  transition: transform 0.4s ease, box-shadow 0.3s;
  opacity: 0; transform: translateY(40px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
.landing .menu-category.revealed { opacity: 1; transform: translateY(0); }
.landing .menu-category:hover { transform: translateY(-8px); box-shadow: 0 20px 35px rgba(0,0,0,0.4); }
.landing .menu-category h3 {
  font-size: 1.8rem; text-align: center; margin-bottom: 1.5rem;
  color: #f5c542; border-bottom: 2px solid #f5c542;
  display: inline-block; width: auto; padding-bottom: 0.3rem;
}
.landing .menu-item {
  display: flex; gap: 1rem; margin-bottom: 1.5rem;
  align-items: center; transition: transform 0.2s;
  background: rgba(255,255,255,0.1); padding: 0.8rem;
  border-radius: 20px; backdrop-filter: blur(2px);
}
.landing .menu-item:hover { transform: translateX(8px); background: rgba(255,255,255,0.2); }
.landing .menu-item img { width: 70px; height: 70px; object-fit: cover; border-radius: 16px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
.landing .menu-item-info { flex: 1; }
.landing .menu-item-info h4 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.2rem; color: white; }
.landing .menu-item-info p { font-size: 0.85rem; color: #ddd; margin-bottom: 0.3rem; }
.landing .menu-item-info .price { font-weight: 700; color: #f5c542; }
.landing .menu-item.sin-stock { opacity: 0.5; filter: grayscale(0.6); }
.landing .menu-item .sin-stock-label { font-size: 0.75rem; color: #ff6b6b; font-weight: 600; display: inline-block; background: rgba(255,80,80,0.15); padding: 0.15rem 0.5rem; border-radius: 8px; }

.landing .doubleSplit { padding: 5rem 0; background: #e8e4da; color: #1a1a1a; }
.landing .doubleSplit .container { display: flex; flex-wrap: wrap; gap: 2rem; }
.landing .a-split { flex: 1; min-width: 280px; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 30px rgba(0,0,0,0.05); transition: transform 0.3s ease; }
.landing .a-split:hover { transform: translateY(-8px); }
.landing .a-split .background video { width: 100%; height: 280px; object-fit: cover; display: block; }
.landing .a-split .text { padding: 2rem; }
.landing .a-split h3 { font-family: 'Room-205', serif; font-size: 1.8rem; margin-bottom: 0.5rem; }
.landing .a-split p { margin-bottom: 1.2rem; color: #3a3a3a; }
.landing .a-split a { font-weight: 600; text-decoration: none; border-bottom: 1px solid currentColor; color: #1a1a1a; transition: opacity 0.2s; }
.landing .a-split a:hover { opacity: 0.7; }

.landing .misc-grid {
  display: flex; flex-wrap: wrap; gap: 2rem; justify-content: center;
  padding: 5rem 2rem; max-width: 1280px; margin: 0 auto; background: #fbfaf3;
}
.landing .tile {
  flex: 1 1 220px; text-align: center; background: white;
  border-radius: 24px; padding: 1.5rem;
  transition: all 0.25s; box-shadow: 0 5px 15px rgba(0,0,0,0.03);
}
.landing .tile:hover { transform: translateY(-8px); box-shadow: 0 15px 25px rgba(0,0,0,0.08); }
.landing .tile img { width: 100%; aspect-ratio: 1/1; object-fit: cover; border-radius: 50%; margin-bottom: 1rem; }
.landing .tile h3 { font-size: 1.4rem; margin-bottom: 0.8rem; color: #1a1a1a; }

.landing footer { background: #121212; color: #cccccc; padding: 4rem 2rem 2rem; }
.landing footer .colophon { border-top: 1px solid #2c2c2c; padding-top: 2rem; font-size: 0.8rem; text-align: center; }

.landing .carrito-flotante {
  position: fixed; bottom: 2rem; right: 2rem;
  background: #1e1e1e; color: white; width: 55px; height: 55px;
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  z-index: 90; text-decoration: none; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
  transition: transform 0.2s, background 0.2s; cursor: pointer;
}
.landing .carrito-flotante:hover { transform: scale(1.1); background: #3a3a3a; }
.landing .carrito-flotante svg { width: 26px; height: 26px; fill: white; }
.landing .cart-count {
  position: absolute; top: -5px; right: -5px;
  background: #f5c542; color: #1a1a1a;
  font-size: 0.7rem; font-weight: bold;
  width: 20px; height: 20px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}

@media (max-width: 800px) {
  .landing .rapid-logos-container { width: 90%; height: 180px; }
  .landing .logo-final img { max-width: 260px; margin-bottom: 0.8rem; }
  .landing .menu-item img { width: 55px; height: 55px; }
  .landing .textSplit .container { flex-direction: column; }
  .landing .textSplit .text h2,
  .landing .textSplit .text p,
  .landing .textSplit .text .button { transform: translateY(30px); }
  .landing .textSplit.reveal-text .text h2,
  .landing .textSplit.reveal-text .text p,
  .landing .textSplit.reveal-text .text .button { transform: translateY(0); }
}
</style>
