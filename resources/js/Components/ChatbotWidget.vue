<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const isOpen = ref(false);
const messages = reactive([
    { role: 'assistant', content: 'Salaam! I am your Halal Wellness Advisor. How can I help you today?' }
]);
const input = ref('');
const loading = ref(false);

const toggleChat = () => {
    isOpen.value = !isOpen.value;
};

const sendMessage = async () => {
    if (!input.value.trim()) return;

    const userMsg = input.value;
    messages.push({ role: 'user', content: userMsg });
    input.value = '';
    loading.value = true;

    try {
        // Get cart item IDs from Inertia props
        const cartItems = usePage().props.cart?.data?.items?.map(item => item.product_id) || [];
        
        const response = await axios.post('/api/ai/chat', {
            message: userMsg,
            cart_items: cartItems
        });
        messages.push({ role: 'assistant', content: response.data.response });
    } catch (error) {
        messages.push({ role: 'assistant', content: 'Sorry, I am having trouble connecting to the server.' });
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="fixed bottom-5 right-5 z-50">
        <!-- Chat Button -->
        <button @click="toggleChat" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-4 shadow-lg transition-transform transform hover:scale-110">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.159 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
        </button>

        <!-- Chat Window -->
        <div v-if="isOpen" class="absolute bottom-20 right-0 w-80 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden flex flex-col" style="height: 400px;">
            <!-- Header -->
            <div class="bg-indigo-600 p-4 text-white flex justify-between items-center">
                <h3 class="font-medium">Halal Advisor</h3>
                <button @click="toggleChat" class="text-indigo-200 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Messages -->
            <div class="flex-1 p-4 overflow-y-auto bg-gray-50 space-y-3">
                <div v-for="(msg, index) in messages" :key="index" 
                     :class="['flex', msg.role === 'user' ? 'justify-end' : 'justify-start']">
                    <div :class="['max-w-[80%] rounded-lg p-3 text-sm', 
                                  msg.role === 'user' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-800']">
                        {{ msg.content }}
                    </div>
                </div>
                <div v-if="loading" class="flex justify-start">
                    <div class="bg-white border border-gray-200 rounded-lg p-3 text-sm text-gray-500">
                        Thinking...
                    </div>
                </div>
            </div>

            <!-- Input -->
            <div class="p-3 bg-white border-t border-gray-200">
                <form @submit.prevent="sendMessage" class="flex gap-2">
                    <input v-model="input" type="text" placeholder="Ask about health..." 
                           class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:border-indigo-500">
                    <button type="submit" class="bg-indigo-600 text-white rounded-full p-2 hover:bg-indigo-700">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
