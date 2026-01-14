<script setup>
import { onMounted } from 'vue'
import { initFlowbite } from 'flowbite';
import Header from './Header.vue';
import Footer from './Footer.vue';
import Hero from './Hero.vue';
import ChatbotWidget from '@/Components/ChatbotWidget.vue';

onMounted(() => {
    initFlowbite();
});

 </script>

<template>

    <Header></Header>



<!-- Main content -->

<slot/>

<ChatbotWidget />
<Footer></Footer>


</template>

