<template>
  <div class="scoreboard-container">
    <h1>Scoreboards</h1>
 
    <!-- Botões de Alternância (Tabs) -->
    <div class="toggle-buttons">
      <button :class="{ active: currentTab === 'personal' }" @click="switchTab('personal')">
        Personal Best
      </button>
      <button :class="{ active: currentTab === 'global' }" @click="switchTab('global')">
        Global Rankings
      </button>
    </div>
 
    <!-- MENSAGEM DE ERRO -->
    <div v-if="errorMessage" class="error-container">
      <div class="error-message">⚠️ {{ errorMessage }}</div>
    </div>
 
    <!-- LOADING STATE -->
    <div v-if="isLoading" class="loading">
      <div class="spinner"></div>
      <p>A carregar estatísticas...</p>
    </div>
 
    <!-- CONTEÚDO PRINCIPAL -->
    <div v-else>
 
      <!-- === TAB: PERSONAL BEST === -->
      <div v-if="currentTab === 'personal'" class="stats-grid">
 
        <!-- LINHA 1: MATCHES -->
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
 
        <!-- LINHA 2: GAMES -->
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
 
        <!-- LINHA 3: PONTOS E TEMPO -->
        <div class="stat-card">
          <h3>Total Points</h3>
          <p class="stat-value">{{ personalStats.totalPoints }}</p>
        </div>
        <div class="stat-card highlight-purple">
          <h3>Time Played</h3>
          <p class="stat-value time-value">{{ formattedTime }}</p>
        </div>
        <div class="stat-card">
          <h3>Draws (60 pts)</h3>
          <p class="stat-value">{{ personalStats.draws }}</p>
        </div>
 
        <!-- LINHA 4: CONQUISTAS -->
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
 
      <!-- === TAB: GLOBAL RANKINGS COM FILTROS === -->
      <div v-else class="global-wrapper">
 
        <!-- CARD DE FILTROS -->
        <div class="filter-card">
 
          <!-- Seletor de Categoria -->
          <div class="filter-group">
            <label>Ranking Category</label>
            <!-- Reinicia para página 1 ao mudar de filtro -->
            <select v-model="filters.type" @change="fetchGlobalStats(1)">
              <option value="wins">🏆 Matches Won</option>
              <option value="coins">💰 Coins Earned</option>
              <option value="capotes">🧹 Capotes</option>
              <option value="bandeiras">🚩 Bandeiras</option>
            </select>
          </div>
 
          <!-- Pesquisa por Nome -->
          <div class="filter-group search-group">
            <label>Search Nickname</label>
            <input
              type="text"
              v-model="filters.search"
              @input="debounceSearch"
              placeholder="Ex: Player1..."
            >
          </div>
 
          <!-- Ordenação -->
          <div class="filter-group">
            <label>Order</label>
            <div class="toggle-sort">
              <button
                :class="{ active: filters.order === 'desc' }"
                @click="setOrder('desc')"
                title="Highest First"
              >⬇️ High</button>
              <button
                :class="{ active: filters.order === 'asc' }"
                @click="setOrder('asc')"
                title="Lowest First"
              >⬆️ Low</button>
            </div>
          </div>
        </div>
 
        <!-- LISTA DE RESULTADOS -->
        <div class="ranking-list-card">
          <!-- Cabeçalho da Tabela -->
          <div class="list-header" :class="headerColorClass">
            <div class="col-rank">#</div>
            <div class="col-name">Player</div>
            <div class="col-value">{{ valueLabel }}</div>
          </div>
 
          <!-- Corpo da Tabela -->
          <div class="list-body">
            <div v-for="(item, index) in globalRankingData" :key="index" class="list-row">
              <div class="col-rank">
                <!-- Calcula o rank real com base na página atual: (page-1)*20 + index + 1 -->
                <span class="rank-badge" :class="getRankClass(index + ((currentPage - 1) * 20))">
                  {{ ((currentPage - 1) * 20) + index + 1 }}
                </span>
              </div>
              <div class="col-name">{{ item.name }}</div>
              <div class="col-value">
                {{ item.value }}
                <span v-if="filters.type === 'coins'" class="coin-icon">🪙</span>
              </div>
            </div>
 
            <!-- Estado Vazio -->
            <div v-if="globalRankingData.length === 0 && !isLoading" class="empty-state">
              No players found for this category.
            </div>
          </div>
 
          <!-- PAGINAÇÃO -->
          <div class="pagination-controls" v-if="lastPage > 1">
            <button
              class="page-btn"
              :disabled="currentPage === 1"
              @click="changePage(currentPage - 1)"
            >
              &laquo; Prev
            </button>
 
            <span class="page-info">
              Page <strong>{{ currentPage }}</strong> of <strong>{{ lastPage }}</strong>
            </span>
 
            <button
              class="page-btn"
              :disabled="currentPage === lastPage"
              @click="changePage(currentPage + 1)"
            >
              Next &raquo;
            </button>
          </div>
 
        </div>
 
      </div>
 
    </div>
  </div>
</template>
 
<script setup>
import { ref, computed, onMounted, watch } from 'vue';
 
// --- CONFIGURAÇÃO API ---
const API_PERSONAL = 'http://localhost:8000/api/statistics/personal';
const API_GLOBAL   = 'http://localhost:8000/api/statistics/global';
 
const currentTab = ref('personal');
const isLoading = ref(false);
const errorMessage = ref('');
 
// Dados Pessoais
const personalStats = ref({
  totalMatches: 0, winsMatches: 0, totalGames: 0, winsGames: 0,
  totalTime: 0, totalPoints: 0, draws: 0, riscas: 0, capotes: 0, bandeiras: 0
});
 
// Filtros Globais & Paginação
const filters = ref({
  type: 'wins',
  search: '',
  order: 'desc'
});
 
const globalRankingData = ref([]);
const currentPage = ref(1);
const lastPage = ref(1);
let searchTimeout = null;
 
// --- COMPUTEDS (Cálculos Automáticos) ---
 
const matchWinRate = computed(() => {
  if (personalStats.value.totalMatches === 0) return 0;
  return ((personalStats.value.winsMatches / personalStats.value.totalMatches) * 100).toFixed(1);
});
 
const gameWinRate = computed(() => {
  if (personalStats.value.totalGames === 0) return 0;
  return ((personalStats.value.winsGames / personalStats.value.totalGames) * 100).toFixed(1);
});
 
const formattedTime = computed(() => {
  const totalSeconds = Number(personalStats.value.totalTime) || 0;
  const h = Math.floor(totalSeconds / 3600);
  const m = Math.floor((totalSeconds % 3600) / 60);
  return `${h}h ${m}m`;
});
 
// Label da coluna de valor
const valueLabel = computed(() => {
  switch(filters.value.type) {
    case 'coins': return 'Coins';
    case 'wins': return 'Wins';
    case 'capotes': return 'Count';
    case 'bandeiras': return 'Count';
    default: return 'Value';
  }
});
 
// Cor do cabeçalho
const headerColorClass = computed(() => {
  switch(filters.value.type) {
    case 'coins': return 'bg-gold';
    case 'wins': return 'bg-blue';
    case 'capotes': return 'bg-orange';
    case 'bandeiras': return 'bg-green';
    default: return 'bg-gray';
  }
});
 
// --- MÉTODOS ---
 
const getToken = () => {
  return localStorage.getItem('token') ||
         localStorage.getItem('userToken') ||
         localStorage.getItem('access_token') ||
         sessionStorage.getItem('token');
};
 
const switchTab = (tab) => {
  currentTab.value = tab;
};
 
// Buscar Estatísticas Pessoais
const fetchPersonalStats = async () => {
  isLoading.value = true;
  try {
    const token = getToken();
    if (!token) throw new Error('Utilizador não autenticado.');
 
    const response = await fetch(API_PERSONAL, {
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
    });
 
    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `Erro ${response.status}: ${response.statusText}`);
    }
    const data = await response.json();
    personalStats.value = { ...personalStats.value, ...data };
  } catch (err) {
    errorMessage.value = err.message;
  } finally {
    isLoading.value = false;
  }
};
 
// Buscar Rankings Globais (Com Paginação)
const fetchGlobalStats = async (page = 1) => {
  // Se for pesquisa (debounce), não mostra spinner de ecrã inteiro
  if(!searchTimeout) isLoading.value = true;
  errorMessage.value = '';
 
  try {
    const token = getToken();
 
    const query = new URLSearchParams({
      type: filters.value.type,
      search: filters.value.search,
      order: filters.value.order,
      page: page // Adiciona o parâmetro da página
    }).toString();
 
    const response = await fetch(`${API_GLOBAL}?${query}`, {
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
    });
 
    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `Erro do Servidor (${response.status})`);
    }
 
    const json = await response.json();
 
    if (json.data && json.data.data) {
        globalRankingData.value = json.data.data;
        currentPage.value = json.data.current_page;
        lastPage.value = json.data.last_page;
    } else {
        globalRankingData.value = json.data || [];
        currentPage.value = 1;
        lastPage.value = 1;
    }
 
  } catch (err) {
    console.error("Global Stats Error:", err);
    errorMessage.value = err.message;
  } finally {
    isLoading.value = false;
  }
};
 
const setOrder = (order) => {
  filters.value.order = order;
  fetchGlobalStats(1); // Reset para página 1
};
 
const changePage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    fetchGlobalStats(page);
  }
};
 
const debounceSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchGlobalStats(1); // Reset para página 1 na pesquisa
    searchTimeout = null;
  }, 500);
};
 
const getRankClass = (absoluteIndex) => {
  // O ranking é absoluto (considera a página)
  if (absoluteIndex === 0) return 'rank-1';
  if (absoluteIndex === 1) return 'rank-2';
  if (absoluteIndex === 2) return 'rank-3';
  return 'rank-default';
};
 
// Observar mudança de tabs
watch(currentTab, (newTab) => {
  if (newTab === 'global') {
    fetchGlobalStats(1);
  }
});
 
onMounted(() => {
  fetchPersonalStats();
});
</script>
 
<style scoped>
/* ESTILOS GERAIS */
.scoreboard-container { max-width: 1000px; margin: 40px auto; padding: 20px; font-family: 'Segoe UI', sans-serif; color: #333; text-align: center; }
h1 { margin-bottom: 30px; font-size: 2.5rem; color: #2c3e50; font-weight: 700; }
 
/* TABS */
.toggle-buttons { margin-bottom: 30px; display: flex; justify-content: center; gap: 15px; }
button { padding: 12px 24px; border: 1px solid #e0e0e0; background: #f8f9fa; cursor: pointer; border-radius: 30px; font-weight: 500; transition: all 0.2s; }
button:hover:not(.active) { background: #e2e6ea; }
button.active { background: #3498db; color: white; border-color: #3498db; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.4); }
 
/* GRID PESSOAL */
.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.stat-card { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; transition: transform 0.2s; }
.stat-card:hover { transform: translateY(-3px); }
.stat-card h3 { font-size: 13px; color: #95a5a6; margin: 0 0 10px 0; text-transform: uppercase; font-weight: 700; }
.stat-value { font-size: 32px; font-weight: 800; color: #2c3e50; margin: 0; }
.time-value { font-size: 28px; }
 
/* CORES STATS */
.highlight .stat-value { color: #3498db; }
.highlight-blue .stat-value { color: #2980b9; }
.highlight-purple .stat-value { color: #9b59b6; }
.highlight-gold { border: 2px solid #f1c40f; background: linear-gradient(145deg, #fff, #fffbf0); }
.highlight-gold .stat-value { color: #f39c12; }
 
/* --- CARTÃO DE FILTROS --- */
.global-wrapper { text-align: left; }
.filter-card {
  background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end;
  border: 1px solid #eee;
}
.filter-group { flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 8px; }
.filter-group label { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #888; }
.filter-group select, .filter-group input {
  padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; outline: none; transition: border 0.2s; width: 100%; box-sizing: border-box;
}
.filter-group select:focus, .filter-group input:focus { border-color: #3498db; }
 
.toggle-sort { display: flex; gap: 5px; }
.toggle-sort button {
  flex: 1; padding: 8px; border-radius: 6px; font-size: 13px; border: 1px solid #ddd; background: white; color: #555;
}
.toggle-sort button.active { background: #2c3e50; color: white; border-color: #2c3e50; }
 
/* --- LISTA DE RANKING --- */
.ranking-list-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.05); border: 1px solid #eee; }
.list-header { display: flex; padding: 15px 20px; color: white; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; }
.list-row { display: flex; padding: 15px 20px; border-bottom: 1px solid #f9f9f9; align-items: center; transition: background 0.1s; }
.list-row:hover { background: #f8fbff; }
.list-row:last-child { border-bottom: none; }
 
.col-rank { width: 50px; text-align: center; font-weight: bold; }
.col-name { flex: 1; font-weight: 600; color: #333; }
.col-value { width: 120px; text-align: right; font-weight: 800; color: #555; font-size: 1.1rem; }
.coin-icon { font-size: 0.9rem; margin-left: 4px; }
 
/* PAGINAÇÃO */
.pagination-controls {
  display: flex; justify-content: space-between; align-items: center;
  padding: 15px 20px; background: #f9f9f9; border-top: 1px solid #eee;
}
.page-btn {
  padding: 8px 16px; border: 1px solid #ddd; background: white; border-radius: 6px; cursor: pointer; color: #555; font-weight: 600;
}
.page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.page-btn:hover:not(:disabled) { background: #eee; border-color: #ccc; }
.page-info { font-size: 14px; color: #666; }
 
/* BADGES DE RANK */
.rank-badge {
  display: inline-flex; width: 28px; height: 28px; align-items: center; justify-content: center;
  border-radius: 50%; font-size: 14px; color: #555; background: #eee;
}
.rank-1 { background: #f1c40f; color: white; box-shadow: 0 2px 5px rgba(241, 196, 15, 0.4); }
.rank-2 { background: #bdc3c7; color: white; }
.rank-3 { background: #d35400; color: white; }
 
/* CORES DOS CABEÇALHOS */
.bg-blue { background: linear-gradient(135deg, #3498db, #2980b9); }
.bg-gold { background: linear-gradient(135deg, #f1c40f, #f39c12); }
.bg-orange { background: linear-gradient(135deg, #e67e22, #d35400); }
.bg-green { background: linear-gradient(135deg, #2ecc71, #27ae60); }
.bg-gray { background: #95a5a6; }
 
/* ESTADOS DE CARREGAMENTO */
.empty-state { padding: 40px; text-align: center; color: #999; font-style: italic; }
.loading { color: #7f8c8d; margin-top: 50px; }
.spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.error-container { background: #fff5f5; border: 1px solid #feb2b2; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
 
/* RESPONSIVE */
@media (max-width: 768px) {
  .stats-grid { grid-template-columns: 1fr; }
  .filter-card { flex-direction: column; align-items: stretch; }
  .filter-group { width: 100%; }
}
</style>
 