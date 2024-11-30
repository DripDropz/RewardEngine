<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import {ref, computed, onMounted} from "vue";
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

const props = defineProps({
    publicApiKey: String,
    projectName: String,
});

// TODO: Fetch this data from the api route (api.v1.stats.leaderboard)
// TODO: show progress bar/loading screen as we are fetching
const leaderboardData = ref(JSON.parse('{"overview":[{"auth_name":"stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8","auth_avatar":"https://api.dicebear.com/9.x/pixel-art/svg?seed=stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8","total_kills":"28","total_deaths":"40","total_suicides":"0","total_game_starts":"0","total_player_joins":"3","kill_death_ratio":"0.7"},{"auth_name":"Latheesan Kanesamoorthy","auth_avatar":"https://lh3.googleusercontent.com/a/ACg8ocJYZyURUYgovmom0jCRV9fRwnqb3gM07XUNQ3izr_hikxFQQ4g=s96-c","total_kills":"3","total_deaths":"9","total_suicides":"1","total_game_starts":"0","total_player_joins":"1","kill_death_ratio":"0.33"}],"kills":[{"auth_name":"stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8","auth_avatar":"https://api.dicebear.com/9.x/pixel-art/svg?seed=stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8","total_kills":"28"},{"auth_name":"Latheesan Kanesamoorthy","auth_avatar":"https://lh3.googleusercontent.com/a/ACg8ocJYZyURUYgovmom0jCRV9fRwnqb3gM07XUNQ3izr_hikxFQQ4g=s96-c","total_kills":"3"}],"deaths":[{"auth_name":"stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8","auth_avatar":"https://api.dicebear.com/9.x/pixel-art/svg?seed=stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8","total_deaths":"40"},{"auth_name":"Latheesan Kanesamoorthy","auth_avatar":"https://lh3.googleusercontent.com/a/ACg8ocJYZyURUYgovmom0jCRV9fRwnqb3gM07XUNQ3izr_hikxFQQ4g=s96-c","total_deaths":"9"}],"suicides":[{"auth_name":"Latheesan Kanesamoorthy","auth_avatar":"https://lh3.googleusercontent.com/a/ACg8ocJYZyURUYgovmom0jCRV9fRwnqb3gM07XUNQ3izr_hikxFQQ4g=s96-c","total_suicides":"1"},{"auth_name":"stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8","auth_avatar":"https://api.dicebear.com/9.x/pixel-art/svg?seed=stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8","total_suicides":"0"}],"killDeathRatio":[{"auth_name":"stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8","auth_avatar":"https://api.dicebear.com/9.x/pixel-art/svg?seed=stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8","total_kills":"28","total_deaths":"40","kill_death_ratio":"0.7"},{"auth_name":"Latheesan Kanesamoorthy","auth_avatar":"https://lh3.googleusercontent.com/a/ACg8ocJYZyURUYgovmom0jCRV9fRwnqb3gM07XUNQ3izr_hikxFQQ4g=s96-c","total_kills":"3","total_deaths":"9","kill_death_ratio":"0.33"}],"generatedAt":"2024-11-30 00:13:24"}'));

// Register Chart.js components
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

// Table Headers
const tableHeaders = [
    { title: 'Avatar', key: 'auth_avatar' },
    { title: 'Name', key: 'auth_name' },
    { title: 'Total Kills', key: 'total_kills' },
    { title: 'Total Deaths', key: 'total_deaths' },
    { title: 'Suicides', key: 'total_suicides' },
    { title: 'K/D Ratio', key: 'kill_death_ratio' }
];

// Chart Options
const barChartOptions = {
    responsive: true,
    plugins: {
        legend: {
            position: 'top',
        }
    },
    scales: {
        y: {
            beginAtZero: true
        }
    }
};

// Utility function to color-code K/D Ratio
const getKDRatioColor = (ratio) => {
    if (ratio >= 0.7) return 'success';
    if (ratio >= 0.25 && ratio <= 0.5) return 'warning';
    return 'info';
};

// Computed Aggregate Statistics
const totalKills = computed(() =>
    leaderboardData.value.overview.reduce((sum, player) => sum + parseInt(player.total_kills), 0)
);
const totalDeaths = computed(() =>
    leaderboardData.value.overview.reduce((sum, player) => sum + parseInt(player.total_deaths), 0)
);
const averageKDRatio = computed(() =>
    leaderboardData.value.overview.reduce((sum, player) => sum + parseFloat(player.kill_death_ratio), 0) /
    leaderboardData.value.overview.length
);

// Top 10 Players by Kills Chart
const topKillsChartData = computed(() => {
    // Sort players by kills and take top 10
    const topPlayers = [...leaderboardData.value.kills]
        .sort((a, b) => parseInt(b.total_kills) - parseInt(a.total_kills))
        .slice(0, 10);

    // Return data
    return {
        labels: topPlayers.map(player => player.auth_name),
        datasets: [{
            label: 'Total Kills',
            data: topPlayers.map(player => parseInt(player.total_kills)),
            backgroundColor: '#36A2EB'
        }]
    };
});

// K/D Ratio Distribution Chart
const kdRatioDistributionData = computed(() => {
    // Create buckets for K/D ratios
    const buckets = [
        { range: '0 - 0.5', count: 0 },
        { range: '0.5 - 1', count: 0 },
        { range: '1 - 1.5', count: 0 },
        { range: '1.5 - 2', count: 0 },
        { range: '2+', count: 0 }
    ];

    // Categorize players into K/D ratio buckets
    leaderboardData.value.killDeathRatio.forEach(player => {
        const kdRatio = parseFloat(player.kill_death_ratio);
        if (kdRatio < 0.5) buckets[0].count++;
        else if (kdRatio < 1) buckets[1].count++;
        else if (kdRatio < 1.5) buckets[2].count++;
        else if (kdRatio < 2) buckets[3].count++;
        else buckets[4].count++;
    });

    // Return data
    return {
        labels: buckets.map(bucket => bucket.range),
        datasets: [{
            label: 'Number of Players',
            data: buckets.map(bucket => bucket.count),
            backgroundColor: '#FF6384'
        }]
    };
});

</script>

<template>
    <GuestLayout :title="props.projectName + ': Leaderboard'">
        <v-container fluid>

            <!-- Overview Statistics Cards -->
            <v-row>
                <v-col cols="12">
                    <v-card>
                        <v-card-title>Leaderboard Overview</v-card-title>
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" md="4">
                                    <v-card outlined>
                                        <v-card-title>Total Kills</v-card-title>
                                        <v-card-text class="text-h4 text-center">
                                            {{ totalKills }}
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-card outlined>
                                        <v-card-title>Total Deaths</v-card-title>
                                        <v-card-text class="text-h4 text-center">
                                            {{ totalDeaths }}
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                                <v-col cols="12" md="4">
                                    <v-card outlined>
                                        <v-card-title>Average K/D Ratio</v-card-title>
                                        <v-card-text class="text-h4 text-center">
                                            {{ averageKDRatio.toFixed(2) }}
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Leaderboard Data Table -->
            <v-row class="mt-4">
                <v-col cols="12">
                    <v-card>
                        <v-card-title>Player Statistics</v-card-title>
                        <v-data-table
                            :headers="tableHeaders"
                            :items="leaderboardData.overview"
                            :items-per-page="5"
                            class="elevation-1"
                        >
                            <template v-slot:item.auth_avatar="{ item }">
                                <v-avatar size="40">
                                    <v-img :src="item.auth_avatar" cover></v-img>
                                </v-avatar>
                            </template>
                            <template v-slot:item.kill_death_ratio="{ item }">
                                <v-chip
                                    :color="getKDRatioColor(parseFloat(item.kill_death_ratio))"
                                    text-color="white"
                                >
                                    {{ item.kill_death_ratio }}
                                </v-chip>
                            </template>
                        </v-data-table>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Charts -->
            <v-row class="mt-4">
                <v-col cols="12" md="6">
                    <v-card>
                        <v-card-title>Top 10 Players by Kills</v-card-title>
                        <v-card-text>
                            <Bar
                                :data="topKillsChartData"
                                :options="barChartOptions"
                            />
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" md="6">
                    <v-card>
                        <v-card-title>K/D Ratio Distribution</v-card-title>
                        <v-card-text>
                            <Bar
                                :data="kdRatioDistributionData"
                                :options="kdRatioChartOptions"
                            />
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

        </v-container>
    </GuestLayout>
</template>
