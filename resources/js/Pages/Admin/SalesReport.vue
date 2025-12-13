<script setup>
import { onMounted, ref, computed } from 'vue'
import AdminLayout from './Components/AdminLayout.vue'
import { Chart } from 'chart.js/auto'

const props = defineProps({
    salesData: Array,
    expensesData: Array,
    totalExpenses: Number
})

const totalRevenue = computed(() => {
    return props.salesData.reduce((sum, item) => sum + parseFloat(item.revenue), 0)
})

const totalExpenses = computed(() => {
    return props.totalExpenses || 0
})

const profit = computed(() => {
    return totalRevenue.value - totalExpenses.value
})

onMounted(() => {
    const ctx = document.getElementById('revenueChart').getContext('2d')
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.salesData.map(data => data.date),
            datasets: [
                {
                    label: 'Revenue',
                    data: props.salesData.map(data => data.revenue),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1
                },
                {
                    label: 'Expenses',
                    data: props.expensesData.map(data => data.revenue),
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    })
})
</script>

<template>
    <AdminLayout>
        <div class="p-4">
            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Left Side - Profit -->
                <div>
                    <h1 class="text-2xl font-bold mb-4">Profit</h1>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h2 class="text-lg font-semibold mb-2">Total Profit</h2>
                        <p class="text-4xl font-bold" :class="totalRevenue >= 0 ? 'text-green-600' : 'text-red-600'">
                            RM{{ totalRevenue.toFixed(2) }}
                        </p>
                        <p class="text-sm text-gray-500 mt-2">Total Revenue: RM{{ totalRevenue.toFixed(2) }}</p>
                    </div>
                </div>

                <!-- Right Side - Expenses -->
                <div>
                    <h1 class="text-2xl font-bold mb-4">Expenses</h1>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h2 class="text-lg font-semibold mb-2">Total Expenses</h2>
                        <p class="text-4xl font-bold text-red-600">
                            RM{{ totalExpenses }}
                        </p>
                        <p class="text-sm text-gray-500 mt-2">From all transactions</p>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <h2 class="text-lg font-semibold mb-4">Revenue vs Expenses</h2>
                <canvas id="revenueChart" height="100"></canvas>
            </div>

            <!-- Detailed Table -->
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <h2 class="text-lg font-semibold mb-4">Revenue Details</h2>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="data in salesData" :key="data.date">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ data.date }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-green-600">
                                        RM{{ parseFloat(data.revenue).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h2 class="text-lg font-semibold mb-4">Expenses Details</h2>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expenses</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="data in expensesData" :key="data.date">
                                    <td class="px-6 py-4 whitespace-nowrap">{{ data.date }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-red-600">
                                        RM{{ parseFloat(data.revenue).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
