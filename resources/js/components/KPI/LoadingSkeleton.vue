<template>
  <div class="space-y-6 animate-pulse">
    <!-- Header Skeleton -->
    <div class="flex items-center justify-between">
      <div class="space-y-2">
        <div class="h-8 bg-gray-200 rounded w-64"></div>
        <div class="h-4 bg-gray-200 rounded w-48"></div>
      </div>
      <div class="flex gap-3">
        <div class="h-10 bg-gray-200 rounded w-32"></div>
        <div class="h-10 bg-gray-200 rounded w-24"></div>
      </div>
    </div>

    <!-- Summary Cards Skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="i in 4" :key="i" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="h-6 bg-gray-200 rounded w-24"></div>
          <div class="h-6 bg-gray-200 rounded w-12"></div>
        </div>
        <div class="space-y-2">
          <div class="h-8 bg-gray-200 rounded w-32"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
        </div>
        <div class="mt-4">
          <div class="h-2 bg-gray-200 rounded w-full"></div>
        </div>
      </div>
    </div>

    <!-- Performance Overview Skeleton -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="h-6 bg-gray-200 rounded w-32 mb-4"></div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="i in 3" :key="i" class="text-center space-y-2">
          <div class="h-10 bg-gray-200 rounded w-16 mx-auto"></div>
          <div class="h-4 bg-gray-200 rounded w-24 mx-auto"></div>
        </div>
      </div>
      <div class="mt-6 space-y-2">
        <div class="h-4 bg-gray-200 rounded w-full"></div>
        <div class="h-3 bg-gray-200 rounded w-full"></div>
      </div>
    </div>

    <!-- Table Skeleton -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="h-6 bg-gray-200 rounded w-48"></div>
      </div>
      <div class="p-6 space-y-4">
        <div v-for="i in 5" :key="i" class="flex items-center gap-4">
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Skeleton loader for dashboard
</script>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
