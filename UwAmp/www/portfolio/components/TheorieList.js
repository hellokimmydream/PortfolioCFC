app.component("theorie-list", {
  props: ["matieres"],
  template: `
    <div class="page">
        <div class="page-title-block">
            <h1>Cours de théorie</h1>
        </div>

        <div v-for="matiere in matieres" :key="matiere.nom" class="matiere">
            <h2>{{ matiere.nom }}</h2>
            <ul>
                <li v-for="(sujet,index) in matiere.sujets" :key="index">{{ sujet }}</li>
            </ul>
            <div class="notes-grid">
                <div class="note-bar" v-for="(note,index) in matiere.notes" :key="index">
                    <div class="value" :style="'width:'+ (note*20)+'%'"></div>
                    <div class="label">{{ note }}</div>
                </div>
            </div>
        </div>
    </div>
    `,
});
