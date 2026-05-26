app.component("site-header", {
  template: /*html*/ `
    <header class="header">
      <div class="logo-image">
        <img src="assets/logo.jpg" alt="Logo">
      </div>
      <nav class="nav">
        <button @click="$emit('change-page','accueil')">HOME</button>
        <button @click="$emit('change-page', 'projets')">PROJETS</button>
        <button @click="$emit('change-page', 'theorie')">THEORIE</button>
        <button @click="$emit('change-page', 'modules')">MODULES</button>
      </nav>
    </header>
  `,
});
