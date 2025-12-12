import { reactive } from "vue";
import axios from "axios";

export const userStore = reactive({
  // === ESTADO (STATE) =================================================
  isLoggedIn: false,
  isAnonymous: false,
  user: null,
  token: null,

  // === LOGIN NORMAL ==================================================
  login(token, user) {
    this.token = token;
    this.user = user;
    this.isLoggedIn = true;
    this.isAnonymous = false;

    // Configura o header para todos os pedidos futuros
    axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;

    localStorage.setItem("token", token);
    localStorage.setItem("user", JSON.stringify(user));
  },

  // === LOGIN CONVIDADO ==============================================
  loginAsGuest() {
    this.token = null;
    this.user = { name: "Guest" };
    this.isLoggedIn = false;
    this.isAnonymous = true;

    localStorage.setItem("guest", "true");
  },

  // === UPDATE DO USER (LOCAL) =======================================
  // Útil para atualizar saldo ou inventário sem fazer refresh à página
  updateUser(partialData) {
    if (!this.user) return;

    Object.assign(this.user, partialData);
    localStorage.setItem("user", JSON.stringify(this.user));
  },

  // === LOGOUT ========================================================
  async logout() {
    // Aqui mantemos o try/catch apenas para não bloquear o clear() se a API falhar
    try {
      if (this.isLoggedIn) {
        await axios.post("/api/logout");
      }
    } catch (e) {
      console.error("Erro no logout (ignorável):", e);
    }

    this.clear();
  },

  // === LIMPAR SESSÃO ================================================
  clear() {
    this.isLoggedIn = false;
    this.isAnonymous = false;
    this.user = null;
    this.token = null;

    localStorage.clear();
    delete axios.defaults.headers.common["Authorization"];
  },

  // === CARREGAR ESTADO APÓS REFRESH ================================
  loadFromStorage() {
    const token = localStorage.getItem("token");
    const user = localStorage.getItem("user");
    const guest = localStorage.getItem("guest");

    // --- Sessão normal ---
    if (token && user && user !== "undefined") {
      this.token = token;
      this.user = JSON.parse(user);
      this.isLoggedIn = true;

      axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
      return;
    }

    // --- Sessão anónima ---
    if (guest) {
      this.loginAsGuest();
      return;
    }

    // --- Nenhuma sessão ---
    this.clear();
  },

  // =================================================================
  // === LOJA E FINANÇAS =============================================
  // =================================================================

  // 1. CARREGAR HISTÓRICO DE TRANSAÇÕES
  async fetchTransactions() {
    const response = await axios.get("/api/transactions");
    return response.data;
  },

  // 2. COMPRAR MOEDAS (EUROS -> MOEDAS)
  async buyCoins(euros, paymentType, paymentRef) {
    const response = await axios.post("/api/shop/buy-coins", {
      euros: euros,
      payment_type: paymentType,
      payment_ref: paymentRef,
    });

    // Atualiza o saldo localmente
    this.updateUser({ coins_balance: response.data.new_balance });

    return response.data;
  },

  // 3. COMPRAR AVATAR (MOEDAS -> ITEM)
  async buyAvatar(avatarId, price) {
    const response = await axios.post("/api/shop/buy-avatar", {
      avatar_id: avatarId,
      price: price,
    });

    // Atualiza saldo e inventário
    this.updateUser({
      coins_balance: response.data.new_balance,
      unlocked_avatars: response.data.wallet,
    });

    return response.data;
  },

  // 4. COMPRAR BARALHO (MOEDAS -> ITEM)
  async buyDeck(deckId, price) {
    const response = await axios.post("/api/shop/buy-deck", {
      deck_id: deckId,
      price: price,
    });

    // Atualiza saldo e inventário
    this.updateUser({
      coins_balance: response.data.new_balance,
      unlocked_decks: response.data.wallet,
    });

    return response.data;
  },

  // 5. EQUIPAR BARALHO
  async equipDeck(deckId) {
    const response = await axios.patch("/api/users/me/deck", {
      deck: deckId,
    });

    this.updateUser({ current_deck: response.data.current_deck });

    return response.data;
  },
});
