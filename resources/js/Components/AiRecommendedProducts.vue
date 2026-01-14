<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Link, usePage } from '@inertiajs/vue3';

const recommendations = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const cartItems = usePage().props.cart?.data?.items?.map(item => item.product_id) || [];
        
        const response = await axios.post('/api/ai/recommendations', {
            cart_items: cartItems
        });
        recommendations.value = response.data.data;
    } catch (error) {
        console.error('Failed to fetch recommendations', error);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <section v-if="recommendations.length > 0" class="text-gray-600 body-font">
        <div class="container px-5 py-12 mx-auto">
            <div class="flex flex-col text-center w-full mb-10">
                <h2 class="text-xs text-indigo-500 tracking-widest font-medium title-font mb-1">AI RECOMMENDED</h2>
                <h1 class="sm:text-3xl text-2xl font-medium title-font text-gray-900">Picked For You</h1>
            </div>
            <div class="flex flex-wrap -m-4">
                <div v-for="item in recommendations" :key="item.id" class="p-4 md:w-1/4">
                    <div class="h-full border-2 border-gray-200 border-opacity-60 rounded-lg overflow-hidden relative">
                        <!-- Badge/Reason -->
                        <div class="absolute top-0 right-0 bg-indigo-500 text-white text-xs px-2 py-1 rounded-bl">
                            {{ item.reason }}
                        </div>
                        
                        <img class="lg:h-48 md:h-36 w-full object-cover object-center" 
                             :src="item.product && item.product.product_images && item.product.product_images.length > 0 ? (item.product.product_images[0].url || '/' + item.product.product_images[0].image) : 'https://dummyimage.com/720x400'" 
                             alt="product">
                        <div class="p-6">
                            <h2 class="tracking-widest text-xs title-font font-medium text-gray-400 mb-1">
                                {{ item.product ? item.product.category_id : 'Category' }}
                            </h2>
                            <h1 class="title-font text-lg font-medium text-gray-900 mb-3">
                                {{ item.product ? item.product.title : 'Product ' + item.id }}
                            </h1>
                            <p class="leading-relaxed mb-3">RM {{ item.product ? item.product.price : '0.00' }}</p>
                            <div class="flex items-center flex-wrap ">
                                <Link v-if="item.product" :href="route('products.index')" class="text-indigo-500 inline-flex items-center md:mb-2 lg:mb-0">Learn More
                                    <svg class="w-4 h-4 ml-2" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14"></path>
                                        <path d="M12 5l7 7-7 7"></path>
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
