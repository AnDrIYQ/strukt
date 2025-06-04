<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ChartColumnDecreasing } from 'lucide-vue-next';
import { Bar } from 'vue-chartjs'
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const props = defineProps({
    userStats: Object,
    adminStats: Object,
})

const chartData = {
    labels: ['Адміни', 'Користувачі', 'Колекції', 'Сінглтони', 'Запити', 'Записи'],
    datasets: [
        {
            label: 'Кількість',
            data: [
                props.userStats.adminsCount,
                props.userStats.usersCount,
                props.adminStats.collectionsCount,
                props.adminStats.singletonCount,
                props.adminStats.endpointsCount,
                props.adminStats.entries,
            ],
            backgroundColor: '#1d4ed8',
            borderRadius: 6,
            barThickness: 40,
        },
    ],
}

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                font: {
                    size: 14,
                },
            },
        },
        tooltip: {
            backgroundColor: '#1e293b',
            titleColor: '#fff',
            bodyColor: '#e2e8f0',
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                color: '#334155',
                font: { size: 12 },
            },
        },
        x: {
            ticks: {
                color: '#334155',
                font: { size: 12 },
            },
        },
    },
}
</script>

<template>
    <admin-layout>
        <template #title>Головна</template>

        <div class="bg-white rounded-xl shadow-card p-lg">
            <h2 class="text-xl font-semibold mb-md flex items-center gap-sm">
                <ChartColumnDecreasing />
                <span>Статистика</span>
            </h2>
            <div class="h-72">
                <Bar :data="chartData" :options="chartOptions" />
            </div>
        </div>
    </admin-layout>
</template>
