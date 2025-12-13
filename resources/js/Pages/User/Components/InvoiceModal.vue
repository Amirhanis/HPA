<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Invoice for Order #{{ order.id }}</h2>
                <button @click="close" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <p><strong>Status:</strong> {{ order.status }}</p>
                <p><strong>Total Price:</strong> RM{{ order.total_price }}</p>
            </div>
            <div class="mb-4">
                <h3 class="text-lg font-semibold">Order Items</h3>
                <ul>
                    <li v-for="item in order.order_items" :key="item.id" class="mb-2">
                        {{ item.product.title }} - RM{{ item.unit_price }} x {{ item.quantity }}
                    </li>
                </ul>
            </div>
            <div class="flex justify-end">
                <a :href="`/orders/${order.id}/download-invoice`"
                   target="_blank"
                   class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Download Invoice
                </a>
            </div>
        </div>
    </div>
</template>

  <script setup>
  import { ref, watch } from 'vue'

  const props = defineProps({
    show: Boolean,
    order: Object
  })

  const emit = defineEmits(['close'])

  const close = () => {
    emit('close')
  }
  </script>

  <style scoped>
  /* Add any additional styling here */
  </style>
