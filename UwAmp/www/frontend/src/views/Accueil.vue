<template>
  <main class="page">
    <div class="hero">
      <div class="hero-title">PORTFOLIO</div>
      <button @click="enterSite">Entrer <span>→</span></button>
    </div>

    <section class="about">
      <div class="about-img" @click="openModal"></div>
      <div class="about-texte">
        <h2>About me</h2>
        <p>Camille Rais, étudiante informaticienne en développement d'application</p>
      </div>
    </section>
    <div class="modal" v-if="showModal">
      <div class="modal-content">
        <h2>Camille Rais</h2>
        <p>Etudiante informaticienne en développement d'applications</p>
        <button @click="closeModal">Fermer</button>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref } from 'vue'

const showModal = ref(false)

function openModal() {
  showModal.value = true
}
function closeModal() {
  showModal.value = false
}
function enterSite() {
  alert('Bienvenue sur mon portfolio !')
}
</script>

<style scoped>
.page {
  font-family: Arial, sans-serif;
}

/* Hero */
.hero {
  text-align: center;
  padding: 100px 20px;
  background: #f0f0f0;
}
.hero-title {
  font-size: 48px;
  font-weight: bold;
  margin-bottom: 20px;
}
.hero button {
  padding: 10px 20px;
  font-size: 18px;
  cursor: pointer;
}

/* About */
.about {
  display: flex;
  gap: 20px;
  padding: 50px 20px;
}
.about-img {
  width: 200px;
  height: 200px;
  background-color: #ccc;
  cursor: pointer;
}
.about-text h2 {
  margin-bottom: 10px;
}

/* Modal */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
}
.modal-content {
  background: white;
  padding: 20px;
  border-radius: 10px;
}
</style>
