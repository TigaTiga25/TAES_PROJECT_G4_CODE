# TAES_PROJECT_G4_CODE
### 🃏 TAES Project - Multiplayer Card Game
Este projeto é uma aplicação web composta por uma API em Laravel (Backend) e uma interface em Vue.js (Frontend).

### Group elements
- Fátima Marto
- Miguel Pontes
- Tiago Neto
- Tiago Vital

### 📋 Pré-requisitos

Certifique-se de ter o seguinte software instalado na sua máquina:
* **PHP** (v8.1 ou superior) & **Composer**
* **Node.js** (v18 ou superior) & **NPM**
* **SQLite** (Motor de Base de Dados, não requer instalação de servidor)

### 🚀 Como Instalar e Correr o Projeto
### Passo 1: Configurar o Backend (API)
1. Abra o terminal na pasta do projeto(dentro da api) EX:*\TAES_PROJECT_G4_CODE\api>
2. Instale as dependências do PHP: composer install
3. Crie o ficheiro de ambiente .env copiando o exemplo:
  .Windows: copy .env.example .env
  .Mac/Linux: cp .env.example .env
4. Ir a Mailtrap.io->My Inbox->PHP(Code Samples)->Laravel 9+->Copy->Substituir bloco de codigo no .env seccao marcada com comentário
5. Gere a chave da aplicação: php artisan key:generate
6. Criar Tabelas e Dados de Teste (Seeders): php artisan migrate:fresh --seed
7. Criar o link simbólico para as imagens (Uploads de fotos): php artisan storage:link
8. Arrancar o servidor Backend: php artisan serve

Note: O backend ficará a correr em: http://localhost:8000

### Passo 2: Configurar o Frontend (Vue.js)
1. Abra um novo terminal (mantenha o do PHP a correr).
2. Navegue até à pasta do projeto EX:*\TAES_PROJECT_G4_CODE\frontend>
3. Instale as dependências de Javascript: npm install
4. Arranque o servidor de desenvolvimento: npm run dev
5. Abra o link fornecido no terminal (geralmente http://localhost:5173) no seu browser.

### 🔑 Credenciais de Teste
Após correr o comando migrate:fresh --seed, pode usar estes utilizadores pré-configurados:
Administrador	a1@mail.pt	123	Utilizador sem histórico
Jogador	pa@mail.pt	123	Utilizador normal com histórico
