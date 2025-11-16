import { reactive } from 'vue'
import axios from 'axios' 

export const userStore = reactive({
  isLoggedIn: false,
  isAnonymous: false,
  user: null, 
  token: null, // Guardará a prova de login

  // A função de login agora espera um TOKEN
  login(token) {
    this.isLoggedIn = true
    this.isAnonymous = false
    this.token = token
    localStorage.setItem('token', token) 
    
    // Diz ao axios para usar este token em todos os pedidos futuros
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  },

  loginAsGuest() {
   
  },

// ...
  
  async logout() {
    try {
        // Se tivermos um token, avisamos o backend para o destruir
        if (this.token) {
            await axios.post('/api/logout'); 
        }
    } catch (error) {
        console.error('Erro ao fazer logout no servidor:', error);
        // Mesmo que dê erro no servidor, queremos limpar o frontend
    } finally {
        // Limpeza do Frontend (Sempre executada)
        this.isLoggedIn = false
        this.isAnonymous = false
        this.user = null
        this.token = null
        localStorage.clear() // Limpa tudo do armazenamento
        delete axios.defaults.headers.common['Authorization']
    }
  },


  // loadFromStorage agora procura o TOKEN
  loadFromStorage() {
    const savedToken = localStorage.getItem('token')
    const guestMode = localStorage.getItem('guest')

    if (savedToken) {
      this.login(savedToken) // Re-login com o token guardado
    } else if (guestMode) {
      this.isAnonymous = true
    }
  },

  
  // Função para ir buscar os dados do utilizador (ex: nome)
  // async fetchUser() {
  //   if (!this.token) return;
  //   try {
  //     const response = await axios.get('/api/user');
  //     this.user = response.data;
  //   } catch (error) {
  //     console.error("Não foi possível ir buscar o utilizador", error);
  //     this.logout(); // Se o token for inválido, faz logout
  //   }
  // }
})