// filepath: /c:/laragon/www/hpa/resources/js/Pages/Admin/Components/SalesChart.vue
<template>
    <div>
        <canvas id="salesChart"></canvas>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { Chart } from 'chart.js/auto';

const salesData = {
    labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
    datasets: [
        {
            label: 'Sales Performance',
            data: [65, 59, 80, 81, 56, 55, 40],
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1,
        },
    ],
};

const config = {
    type: 'line',
    data: salesData,
    options: {
        scales: {
            y: {
                beginAtZero: true,
            },
        },
    },
};

onMounted(() => {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, config);
});
</script>

<style scoped>
canvas {
    max-width: 100%;
}
</style>
