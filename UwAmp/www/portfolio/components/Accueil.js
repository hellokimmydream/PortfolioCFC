app.component("hero-section", {
  data() {
    return { showModal: false };
  },
  methods: {
    openModal() {
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
    },
  },
  template: /*html*/ `
    <section>
      <div class="hero">
        <div class="hero-title">PORTFOLIO</div>
        <button @click="openModal">En savoir plus →</button>
      </div>

      <section class="about">
        <div class="about-img" @click="openModal"></div>
        <div class="about-text">
          <h2>About me</h2>
          <p>Camille Rais, étudiante informaticienne en développement d'applications.</p>
        </div>
      </section>

      <div class="modal" v-if="showModal">
        <div class="modal-content">
          <h2>Camille Rais</h2>
          <p>Etudiante informaticienne en développement d'applications</p>
          <button @click="closeModal">Fermer</button>
        </div>
      </div>
    </section>
  `,
});
