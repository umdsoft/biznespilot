import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { createAppRouter } from './router'

async function init() {
    const router = await createAppRouter()
    const app = createApp(App)
    app.use(createPinia())
    app.use(router)
    await router.isReady()
    app.mount('#miniapp')
}

init()
