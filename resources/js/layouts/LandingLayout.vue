<script setup>
import { ref } from 'vue'
import Navbar from '@/components/Landing/Navbar.vue'
import Footer from '@/components/Landing/Footer.vue'

const urgencyBarVisible = ref(true)
</script>

<template>
  <div class="min-h-screen bg-white scroll-smooth font-sans text-slate-900">
    <Navbar
      :urgency-bar-visible="urgencyBarVisible"
      @close-urgency="urgencyBarVisible = false"
    />
    <main>
      <slot :urgency-bar-visible="urgencyBarVisible" />
    </main>
    <Footer />
  </div>
</template>
