<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed, onMounted } from "vue";
import { initFlowbite } from "flowbite";
import UserLayout from "./Layouts/UserLayout.vue";
import Hero from "./Layouts/Hero.vue";
import Products from "./Components/Products.vue";

onMounted(() => {
    initFlowbite();
});

const props = defineProps({
    products: {
        type: [Array, Object],
        default: () => ([]),
    },
});

const normalizedProducts = computed(() => {
    return Array.isArray(props.products) ? props.products : (props.products?.data ?? []);
});
</script>

<template>
    <UserLayout>
        <Hero></Hero>
        <div class="bg-white">
            <div
                class="mx-auto max-w-2xl px-4 py-16 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8"
            >
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                    Latest Product Lists
                </h2>

                <!-- product list component -->
                <Products :products="normalizedProducts"></Products>
                <div class="flex justify-center mt-5">
                    <Link
                        :href="route('products.index')"
                        type="button"
                        class="text-gray-900 bg-gradient-to-r from-teal-200 to-lime-200 hover:bg-gradient-to-l hover:from-teal-200 hover:to-lime-200 focus:ring-4 focus:outline-none focus:ring-lime-200 dark:focus:ring-teal-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2 mb-2"
                        >View All Products</Link
                    >
                </div>
            </div>
        </div>
        <a
            href="https://wa.me/601127063826"
            target="_blank"
            class="fixed bottom-16 right-5 text-white bg-green-500 hover:bg-green-600 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center mb-2 animate-bounce"
            >Contact Us on WhatsApp</a
        >
    </UserLayout>
</template>

<style scoped>
@keyframes bounce {
    0%,
    100% {
        transform: translateY(-25%);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }
    50% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
}

.animate-bounce {
    animation: bounce 1s infinite;
}
</style>
