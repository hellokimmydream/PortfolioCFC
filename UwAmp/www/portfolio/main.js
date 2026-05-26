const app = Vue.createApp({
  data() {
    return {
      title: "Portfolio Camille Rais",
      description: "Bienvenue sur mon portfolio",

      currentPage: "accueil",

      modules: [
        { nom: "Mathématique", notes: [], sujets: [] },
        { nom: "Anglais", notes: [], sujets: [] },
      ],

      projets: [
        {
          id: 1,
          nom: "Projet 1",
          description: "Description du projet",
          resultat: [],
        },
      ],
      selectedProjet: null,
    };
  },

  methods: {
    showProjetDetail(projet) {
      this.selectedProjet = projet;
      alert("Projet sélectionnée : " + projet.nom);
    },
    goTo(page) {
      this.currentPage = page;
    },
  },

  mounted() {
    const header = this.$ref.headerComp;
    header.$on("change-page", (page) => {
      this.goTo(page);
    });
  },
});

app.mount("#app");
