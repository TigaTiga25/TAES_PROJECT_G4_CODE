import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import { userStore } from "@/stores/userStore.js";

const app = createApp(App);


userStore.loadFromStorage();

app.use(router);
app.mount("#app");
