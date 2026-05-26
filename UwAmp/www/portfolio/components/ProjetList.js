app.component("projet-list", {
  props: ["projets"],
  template: `
    <div class="page">
        <div class="page-title-block">
            <h1>Projets</h1>
            <p>Découvrez tous mes projets récents.</p>
        </div>

        <ul>
            <li v-for="p in projets" :key="p.id">
                {{ p.nom }} 
                <button @click="$emit('select-projet',p)">Voir détail</button>
            </li>
        </ul>
    </div>
    `,
});
