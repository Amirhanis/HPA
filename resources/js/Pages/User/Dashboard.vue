<script setup>
import UserLayouts from './Layouts/UserLayout.vue'
import Invoice from '../User/Components/InvoiceModal.vue'
import { ref } from 'vue'

defineProps({
    orders: Array
})

const selectedOrder = ref(null)
const showModal = ref(false)

const openModal = (order) => {
    selectedOrder.value = order
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    selectedOrder.value = null
}
</script>

<template>
    <UserLayouts>
        <div class="relative max-w-screen-xl py-24 mx-auto overflow-x-auto">
            <table v-show="order.order_items.length > 0" v-for="order in orders" :key="order.id"
                class="w-full text-sm text-left text-gray-500 dark:text-gray-400 mb-5">
                <thead class="text-xs text-gray-700 uppercase dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Order ID # {{ order.id }}</th>
                        <th scope="col" class="px-6 py-3">Product</th>
                        <th scope="col" class="px-6 py-3">Brand</th>
                        <th scope="col" class="px-6 py-3">Category</th>
                        <th scope="col" class="px-6 py-3">Price</th>
                        <th scope="col" class="px-6 py-3">Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in order.order_items" :key="item.id"
                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-6 py-4">{{ item.id }}</td>
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ item.product.title }}
                        </th>
                        <td class="px-6 py-4">{{ item.product.brand.name }}</td>
                        <td class="px-6 py-4">{{ item.product.category.name }}</td>
                        <td class="px-6 py-4">${{ item.product.price }}</td>
                        <td class="px-6 py-4">
                            <button @click="openModal(order)" class="text-blue-600 hover:underline">View Invoice</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Invoice v-if="selectedOrder" :show="showModal" :order="selectedOrder" @close="closeModal" />
    </UserLayouts>
</template>
