<template>
  <div class="scoreboard-container">
    <h1>Scoreboards</h1>

    <!-- Botões de Alternância -->
    <div class="toggle-buttons">
      <button :class="{ active: currentTab === 'personal' }" @click="currentTab = 'personal'">
        Personal Best
      </button>
      <button :class="{ active: currentTab === 'global' }" @click="currentTab = 'global'">
        Global Rankings
      </button>
    </div>

    <!-- MENSAGEM DE ERRO -->
    <div v-if="errorMessage" class="error-container">
      <div class="error-message">⚠️ {{ errorMessage }}</div>
    </div>

    <!-- LOADING -->
    <div v-else-if="isLoading" class="loading">
      <div class="spinner"></div>
      <p>A carregar estatísticas...</p>
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <div v-else>
      <!-- TAB: PERSONAL BEST -->
      <div v-if="currentTab === 'personal'" class="stats-grid">

        <!-- SECÇÃO MATCHES (Partidas) -->
        <div class="stat-card">
          <h3>Total Matches</h3>
          <p class="stat-value">{{ personalStats.totalMatches }}</p>
        </div>
        <div class="stat-card highlight">
          <h3>Matches Won</h3>
          <p class="stat-value">{{ personalStats.winsMatches }}</p>
        </div>
        <div class="stat-card">
          <h3>Win Rate (Matches)</h3>
          <p class="stat-value">{{ matchWinRate }}%</p>
        </div>

        <!-- SECÇÃO GAMES (Jogos/Mãos) -->
        <div class="stat-card">
          <h3>Total Games</h3>
          <p class="stat-value">{{ personalStats.totalGames }}</p>
        </div>
        <div class="stat-card highlight-blue">
          <h3>Games Won</h3>
          <p class="stat-value">{{ personalStats.winsGames }}</p>
        </div>
        <div class="stat-card">
          <h3>Win Rate (Games)</h3>
          <p class="stat-value">{{ gameWinRate }}%</p>
        </div>

        <!-- SECÇÃO PONTUAÇÕES ESPECÍFICAS -->
        <div class="stat-card">
          <h3>Draws (60)</h3>
          <p class="stat-value">{{ personalStats.draws }}</p>
        </div>
        <div class="stat-card">
          <h3>Riscas</h3>
          <p class="stat-value">{{ personalStats.riscas }}</p>
        </div>
        <div class="stat-card">
          <h3>Capotes</h3>
          <p class="stat-value">{{ personalStats.capotes }}</p>
        </div>
        <div class="stat-card highlight-gold">
          <h3>Bandeiras</h3>
          <p class="stat-value">{{ personalStats.bandeiras }}</p>
        </div>

      </div>

      <!-- TAB: GLOBAL (Placeholder) -->
      <div v-else class="global-placeholder">
        <div class="icon-placeholder">🏆</div>
        <p>Global Rankings coming soon...</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

// CONFIGURAÇÃO DA API
const API_URL = 'http://localhost:8000/api/statistics/personal';

const currentTab = ref('personal');
const isLoading = ref(true);
const errorMessage = ref('');

// Estado dos dados
const personalStats = ref({
  totalMatches: 0,
  winsMatches: 0,
  totalGames: 0,
  winsGames: 0,
  draws: 0,
  riscas: 0,
  capotes: 0,
  bandeiras: 0
});

// Cálculo: Win Rate Matches
const matchWinRate = computed(() => {
  if (personalStats.value.totalMatches === 0) return 0;
  return ((personalStats.value.winsMatches / personalStats.value.totalMatches) * 100).toFixed(1);
});

// Cálculo: Win Rate Games
const gameWinRate = computed(() => {
  if (personalStats.value.totalGames === 0) return 0;
  return ((personalStats.value.winsGames / personalStats.value.totalGames) * 100).toFixed(1);
});

const fetchPersonalStats = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    // --- LÓGICA DE DETEÇÃO DE TOKEN ROBUSTA ---
    let token = null;

    // 1. Tenta 'token' (padrão comum)
    if (localStorage.getItem('token')) {
        token = localStorage.getItem('token');
    }
    // 2. Tenta 'userToken'
    else if (localStorage.getItem('userToken')) {
        token = localStorage.getItem('userToken');
    }
    // 3. Tenta 'access_token'
    else if (localStorage.getItem('access_token')) {
        token = localStorage.getItem('access_token');
    }
    // 4. Tenta sessionStorage
    else if (sessionStorage.getItem('token')) {
        token = sessionStorage.getItem('token');
    }

    if (!token) {
      throw new Error('Não foi encontrado nenhum token no browser. Tenta fazer Logout e Login novamente.');
    }

    // Faz o pedido
    const response = await fetch(API_URL, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Sessão expirada. Por favor faz login novamente.');
      }
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.message || `Erro do Servidor: ${response.status}`);
    }

    const data = await response.json();

    // Atualiza o estado com os dados vindos da API
    personalStats.value = {
      totalMatches: data.totalMatches || 0,
      winsMatches:  data.winsMatches || 0,
      totalGames:   data.totalGames || 0,
      winsGames:    data.winsGames || 0,
      draws:        data.draws || 0,
      riscas:       data.riscas || 0,
      capotes:      data.capotes || 0,
      bandeiras:    data.bandeiras || 0
    };

  } catch (error) {
    console.error("Erro Scoreboards:", error);
    errorMessage.value = error.message;
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchPersonalStats();
});
</script>

<style scoped>
.scoreboard-container { max-width: 900px; margin: 40px auto; padding: 20px; text-align: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; }
h1 { margin-bottom: 30px; font-size: 2.5rem; color: #2c3e50; font-weight: 700; }

.toggle-buttons { margin-bottom: 30px; display: flex; justify-content: center; gap: 15px; }
button { padding: 12px 24px; border: 1px solid #e0e0e0; background: #f8f9fa; cursor: pointer; border-radius: 30px; font-weight: 500; transition: all 0.2s; }
button:hover:not(.active) { background: #e2e6ea; transform: translateY(-2px); }
button.active { background: #3498db; color: white; border-color: #3498db; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.4); }

/* GRID LAYOUT */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; }

/* CARTÕES */
.stat-card { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-3px); }
.stat-card h3 { font-size: 13px; color: #95a5a6; margin: 0 0 10px 0; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; }
.stat-value { font-size: 32px; font-weight: 800; color: #2c3e50; margin: 0; }

/* ESTILOS DE DESTAQUE */
.highlight .stat-value { color: #3498db; } /* Azul standard */
.highlight-blue .stat-value { color: #2980b9; } /* Azul escuro */
.highlight-gold { border: 2px solid #f1c40f; background: linear-gradient(145deg, #fff, #fffbf0); }
.highlight-gold .stat-value { color: #f39c12; }
.highlight-gold h3 { color: #d35400; }

/* DEBUG & ERROS */
.error-container { background: #fff5f5; border: 1px solid #feb2b2; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: left; }
.error-message { color: #c53030; font-weight: bold; margin-bottom: 10px; }

/* LOADING */
.loading { color: #7f8c8d; margin-top: 50px; }
.spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.global-placeholder { padding: 80px; color: #bdc3c7; border: 2px dashed #e0e0e0; border-radius: 16px; background: #fafafa; }
.icon-placeholder { font-size: 48px; margin-bottom: 10px; opacity: 0.5; filter: grayscale(100%); }
</style>
