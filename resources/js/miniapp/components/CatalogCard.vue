<template>
    <component
        :is="cardComponent"
        :item="item"
    />
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue'
import ProductCard from './ProductCard.vue'

const props = defineProps({
    item: { type: Object, required: true },
})

const ServiceCard = defineAsyncComponent(() => import('./ServiceCard.vue'))
const MenuItemCard = defineAsyncComponent(() => import('./MenuItemCard.vue'))

const cardComponent = computed(() => {
    switch (props.item.catalog_type) {
        case 'service': return ServiceCard
        case 'menu_item': return MenuItemCard
        default: return ProductCard
    }
})
</script>
