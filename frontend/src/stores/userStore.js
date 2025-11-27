import { reactive } from "vue";
import axios from "axios";

export const userStore = reactive({
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

    axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;

    localStorage.setItem("token", token);
    localStorage.setItem("user", JSON.stringify(user));
  },

  // === LOGIN CONVIDADO ==============================================
  loginAsGuest() {
    this.token = null;
    this.user = { name: "Convidado" };
    this.isLoggedIn = false;
    this.isAnonymous = true;

    localStorage.setItem("guest", "true");
  },

  // === UPDATE DO USER ===============================================
  updateUser(partialData) {
    if (!this.user) return;

    Object.assign(this.user, partialData)
    localStorage.setItem("user", JSON.stringify(this.user));
  },

  // === LOGOUT ========================================================
  async logout() {
    try {
      await axios.post("/api/logout"); // protegido por sanctum
    } catch (e) {
      console.error(e);
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
});
