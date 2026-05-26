app.components("module-list", {
  props: ["modules"],
  template: `
        <div class="page">
        <div class="page-title-block">
            <h1>Modules</h1>
            <p>Liste des modules suivis, notes par thème.</p>
        </div>

        <div class="list-matieres" v-for="mod in modules" :key="mod.nom">
            <div class="module-name">{{ mod.nom }}</div>
            <div class="notes-grid">
                <div class="note-bar" v-for="(note,index) in mod.notes" :key="index">
                    <div class="value" :style="'width:'+ (note*20)+'%'"></div>
                    <div class="label">{{ note }}</div>
                </div>
            </div>
            <div class="moyenne">
                Note finale <span>{{ average(mod.notes).toFixed(2) }}</span>
            </div>
        </div>
    </div>
    `,

  methods: {
    average(notes) {
      return notes.reduce((a, b) => a + b, 0) / notes.length;
    },
  },
});
