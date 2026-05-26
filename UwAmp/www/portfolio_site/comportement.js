document.addEventListener("DOMContentLoaded", () => {

    // 1) Scroll vers section About
    document.querySelector(".hero button")?.addEventListener("click", () => {
        document.querySelector(".about")?.scrollIntoView({ behavior: "smooth" });
    });

    // 2) Animation fade-in au scroll
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add("show");
        });
    });
    document.querySelectorAll(".hero, .about, .page-title-block").forEach(el => {
        el.classList.add("fade");
        observer.observe(el);
    });

    // 3) Mode sombre
    document.getElementById("dark-btn")?.addEventListener("click", () => {
        document.body.classList.toggle("dark");
    });

    // 4) Menu actif
    const navLinks = document.querySelectorAll(".nav a");
    const currentPage = window.location.pathname.split("/").pop();
    navLinks.forEach(link => {
        if (link.getAttribute("href") === currentPage) link.classList.add("active");
    });

    // 5) Modal popup (About Me)
    document.querySelector(".about-img")?.addEventListener("click", () => {
        document.getElementById("bio-modal")?.classList.add("show");
    });
    document.getElementById("close-modal")?.addEventListener("click", () => {
        document.getElementById("bio-modal")?.classList.remove("show");
    });

    // 6) Recherche avec suggestions
    const searchIcon = document.querySelector(".search");
    const searchBox = document.querySelector(".search-box");
    const searchInput = document.getElementById("search-input");
    const suggestionBox = document.getElementById("suggestionBox");
    const suggestions = ["Accueil","Projets","Théorie","Modules","About me","Développement","Javascript","HTML","CSS"];

    searchIcon?.addEventListener("click", e => {
        e.stopPropagation();
        searchBox?.classList.toggle("show");
        searchInput?.focus();
    });

    document.addEventListener("click", e => {
        if (!searchBox?.contains(e.target) && !searchIcon?.contains(e.target)) {
            searchBox?.classList.remove("show");
            if (suggestionBox) suggestionBox.style.display = "none";
        }
    });

    searchInput?.addEventListener("input", () => {
        if (!suggestionBox) return;
        const query = searchInput.value.toLowerCase();
        suggestionBox.innerHTML = "";
        if (!query) { suggestionBox.style.display = "none"; return; }

        const filtered = suggestions.filter(item => item.toLowerCase().includes(query));
        if (!filtered.length) { suggestionBox.style.display = "none"; return; }

        filtered.forEach(item => {
            const li = document.createElement("li");
            li.textContent = item;
            li.addEventListener("click", () => {
                searchInput.value = item;
                suggestionBox.style.display = "none";
            });
            suggestionBox.appendChild(li);
        });
        suggestionBox.style.display = "block";
    });

    // 7) Logo intro animation
    const logo = document.querySelector(".intro-logo img");
    logo?.addEventListener("animationend", () => { window.location.href = "accueil.php"; });

    // 8) Accordéon Théorie
    document.querySelectorAll('.matiere-header').forEach(header => {
        header.addEventListener('click', () => {
            const matiere = header.parentElement;
            matiere.classList.toggle('open');
        });
    });

    // 9) Gestion Projets
    const grid = document.querySelector(".projets-grid");
    const detail = document.querySelector(".projet-detail");
    const title = document.getElementById("detail-title");
    const desc = document.getElementById("detail-description");
    const lettre = document.querySelector(".note-lettre");
    const competencesList = document.querySelector(".competences-list");

    if (grid && typeof projets !== "undefined") {
        projets.forEach(proj => {
            const card = document.createElement("div");
            card.classList.add("projet-card");
            card.dataset.id = proj.projet_id;
            card.innerHTML = `
                <div class="projet-title">${proj.nom_projet}</div>
                <div class="projet-module">${proj.module_nom || ""}</div>
            `;
            grid.appendChild(card);

            card.addEventListener("click", () => {
                // Remplir le détail du projet
                if (title) title.textContent = proj.nom_projet;
                if (desc) desc.textContent = proj.description || "Pas de description disponible";
                if (lettre) lettre.textContent = proj.lettre || "";

                if (competencesList) {
                    competencesList.innerHTML = "";
                    if (proj.competences && proj.competences.length) {
                        proj.competences.forEach(c => {
                            const li = document.createElement("li");
                            li.textContent = c;
                            competencesList.appendChild(li);
                        });
                    }
                }

                // Ouvrir le détail si fermé
                detail?.classList.add("open");

                // Scroll vers le détail
                detail?.scrollIntoView({ behavior: "smooth", block: "start" });
            });
        });
    }
});
