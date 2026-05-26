<template>
  <header class="header">
    <div class="logo-image">
        <img src="@/assets/logo.jpg" alt="logo">
    </div>
    <nav class="nav">
        <router-link to="/" class="active">HOME</router-link>
        <router-link to="/projets">PROJETS</router-link>
        <router-link to="/theorie">THEORIE</router-link>
        <router-link to="/modules">MODULES</router-link>
    </nav>
    <div class="search-container">
        <div class="search"></div>
        <div class="search-box">
            <input type="text" id="search-input" placeholder="Rechercher...">
            <ul id="suggestionBox"></ul>
        </div>
    </div>
  </Header>
</template>

<script setup>
</script>

<style scoped>
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 20px;
        background-color: #222;
        color: white;
    }
    .logo-image img {
        height: 50px;
    }
    .nav a {
        margin: 0 10px;
        color: white;
        text-decoration: none;
    }
    .nav a.active {
        font-weight: bold;
    }
    .search-container {
        position: relative;
    }
    #search-input {
        padding: 5px;
    }
</style>
