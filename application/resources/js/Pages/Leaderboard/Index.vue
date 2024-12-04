<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import {ref, computed, onMounted} from "vue";
import { Pie } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import {useToast} from "vue-toast-notification";

const props = defineProps({
    publicApiKey: String,
    projectName: String,
});

// Register Chart.js components
ChartJS.register(ArcElement, Tooltip, Legend);

// Initialise
const $toast = useToast();
const STATS_TYPE_OVERVIEW = 'overview';
const STATS_TYPE_QUALIFIER = 'qualifier';
const tab = ref(STATS_TYPE_OVERVIEW);
const isLoading = ref(false);
const leaderboardData = ref(JSON.parse('{"overview":{"summary":[],"kills":[],"deaths":[],"suicides":[],"killDeathRatio":[]},"qualifier":{"summary":[],"kills":[],"deaths":[],"suicides":[],"killDeathRatio":[]},"generatedAt":"2024-12-01 18:12:43"}'));
const search = ref(null);

// Periodically load leaderboard data
const loadLeaderboardData = () => {
    isLoading.value = true;
    axios.get(route('api.v1.stats.leaderboard', props.publicApiKey))
        .then(res => leaderboardData.value = res.data)
        .catch(err => $toast.error('Failed to load leaderboard data.'))
        .finally(() => isLoading.value = false);
};
onMounted(() => {
    loadLeaderboardData();
    setInterval(loadLeaderboardData, 20000);
});

// Computed Aggregate Statistics
const gamesPlayed = computed(() =>
    leaderboardData.value[tab.value].summary.reduce((sum, player) => sum + parseInt(player.total_game_starts), 0)
);
const totalKills = computed(() =>
    leaderboardData.value[tab.value].summary.reduce((sum, player) => sum + parseInt(player.total_kills), 0)
);
const totalDeaths = computed(() =>
    leaderboardData.value[tab.value].summary.reduce((sum, player) => sum + parseInt(player.total_deaths), 0)
);
const averageKDRatio = computed(() =>
    (leaderboardData.value[tab.value].summary.reduce((sum, player) => sum + parseFloat(player.kill_death_ratio), 0) /
    leaderboardData.value[tab.value].summary.length) || 0
);

// Computed Top Statistics
const mostGamesPlayed = computed(() => [...leaderboardData.value[tab.value].summary]
    .sort((a, b) => parseInt(b.total_game_starts) - parseInt(a.total_game_starts))
    .at(0));
const mostKills = computed(() => [...leaderboardData.value[tab.value].summary]
    .sort((a, b) => parseInt(b.total_kills) - parseInt(a.total_kills))
    .at(0));
const mostDeaths = computed(() => [...leaderboardData.value[tab.value].summary]
    .sort((a, b) => parseInt(b.total_deaths) - parseInt(a.total_deaths))
    .at(0));
const mostKillDeathRatio = computed(() => [...leaderboardData.value[tab.value].summary]
    .sort((a, b) => parseFloat(b.kill_death_ratio) - parseFloat(a.kill_death_ratio))
    .at(0));

// All Player Statistics Headers
const allPlayerStatisticsTableHeaders = [
    { title: 'Avatar', key: 'auth_avatar' },
    { title: 'Name', key: 'auth_name' },
    { title: 'Games Played', key: 'total_game_starts' },
    { title: 'Total Kills', key: 'total_kills' },
    { title: 'Total Deaths', key: 'total_deaths' },
    { title: 'Suicides', key: 'total_suicides' },
    { title: 'K/D Ratio', key: 'kill_death_ratio' }
];

// Utility function to color-code K/D Ratio
const getKDRatioColor = (ratio) => {
    if (ratio >= 0.7) return 'success';
    if (ratio >= 0.25 && ratio <= 0.5) return 'warning';
    return 'info';
};

// Top 10 Players By Kills Headers
const top10PlayersByKillsTableHeaders = [
    { title: 'Player', key: 'auth_name' },
    { title: 'Kills', key: 'total_kills' },
];
const top10PlayersByKillsTableData = computed(() => [...leaderboardData.value[tab.value].kills]
    .sort((a, b) => parseInt(b.total_kills) - parseInt(a.total_kills))
    .slice(0, 10));

// Categorize kill/death ratios into meaningful buckets
const categorizeKDRatio = (ratio) => {
    const numRatio = parseFloat(ratio)
    if (numRatio < 1) return 'Below 1.0'
    if (numRatio < 5) return '1.0 - 5.0'
    if (numRatio < 10) return '5.0 - 10.0'
    if (numRatio < 20) return '10.0 - 20.0'
    if (numRatio < 50) return '20.0 - 50.0'
    return '50.0+'
};

// Compute chart data from leaderboard
const chartData = computed(() => {
    // Group KD ratios into categories
    const kdCategories = leaderboardData.value[tab.value].killDeathRatio.reduce((acc, player) => {
        const category = categorizeKDRatio(player.kill_death_ratio)
        acc[category] = (acc[category] || 0) + 1
        return acc
    }, {})

    return {
        labels: Object.keys(kdCategories),
        datasets: [{
            label: 'Players per KD Ratio Category',
            data: Object.values(kdCategories),
            backgroundColor: [
                'rgba(255, 99, 132, 0.6)',   // Below 1.0
                'rgba(54, 162, 235, 0.6)',   // 1.0 - 5.0
                'rgba(255, 206, 86, 0.6)',   // 5.0 - 10.0
                'rgba(75, 192, 192, 0.6)',   // 10.0 - 20.0
                'rgba(153, 102, 255, 0.6)',  // 20.0 - 50.0
                'rgba(255, 159, 64, 0.6)'    // 50.0+
            ]
        }]
    }
});

// Chart configuration options
const chartOptions = ref({
    responsive: true,
    plugins: {
        legend: {
            position: 'top'
        },
    }
});

// Top 10 Players By Deaths Headers
const top10PlayersByDeathsTableHeaders = [
    { title: 'Player', key: 'auth_name' },
    { title: 'Deaths', key: 'total_deaths' },
];
const top10PlayersByDeathsTableData = computed(() => [...leaderboardData.value[tab.value].deaths]
    .sort((a, b) => parseInt(b.total_deaths) - parseInt(a.total_deaths))
    .slice(0, 10));

</script>

<template>
    <GuestLayout :title="props.projectName + ': Leaderboard'">
        <v-container>

            <!-- Grouped Stats -->
            <v-card class="mt-4 mb-6">
                <v-tabs v-model="tab" bg-color="primary">
                    <v-tab :value="STATS_TYPE_OVERVIEW">{{ STATS_TYPE_OVERVIEW }}</v-tab>
                    <v-tab :value="STATS_TYPE_QUALIFIER">{{ STATS_TYPE_QUALIFIER }}</v-tab>
                </v-tabs>
                <v-card-text>
                    <div class="d-flex align-center" style="height: 30px;">
                        <v-progress-linear v-if="isLoading" height="16" color="primary" indeterminate />
                        <span v-else>Leaderboard data generated at {{ leaderboardData.generatedAt }} (UTC)</span>
                    </div>
                </v-card-text>
            </v-card>

            <!-- Summary Statistics Cards -->
            <v-row>
                <v-col cols="12">
                    <v-row>
                        <v-col cols="12" md="3">
                            <v-card :loading="isLoading" outlined>
                                <v-card-title class="bg-green-lighten-4">Total Games Played</v-card-title>
                                <v-card-text class="text-h4 text-center py-2">
                                    {{ gamesPlayed }}
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card :loading="isLoading" outlined>
                                <v-card-title class="bg-green-lighten-4">Total Kills</v-card-title>
                                <v-card-text class="text-h4 text-center py-2">
                                    {{ totalKills }}
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card :loading="isLoading" outlined>
                                <v-card-title class="bg-green-lighten-4">Total Deaths</v-card-title>
                                <v-card-text class="text-h4 text-center py-2">
                                    {{ totalDeaths }}
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card :loading="isLoading" outlined>
                                <v-card-title class="bg-green-lighten-4">Average K/D Ratio</v-card-title>
                                <v-card-text class="text-h4 text-center py-2">
                                    {{ averageKDRatio.toFixed(2) }}
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col v-if="mostGamesPlayed" cols="12" md="3">
                            <v-card :loading="isLoading" outlined>
                                <v-card-title class="bg-green-lighten-4">Top Games Played</v-card-title>
                                <v-card-text class="text-h4 text-center py-2">
                                    <v-avatar size="80">
                                        <v-img :src="mostGamesPlayed.auth_avatar" cover></v-img>
                                    </v-avatar>
                                    <v-list-item
                                        :title="mostGamesPlayed.auth_name"
                                        :subtitle="mostGamesPlayed.total_game_starts + ' Games Played'"
                                    />
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col v-if="mostKills" cols="12" md="3">
                            <v-card :loading="isLoading" outlined>
                                <v-card-title class="bg-green-lighten-4">Top Kills</v-card-title>
                                <v-card-text class="text-h4 text-center py-2">
                                    <v-avatar size="80">
                                        <v-img :src="mostKills.auth_avatar" cover></v-img>
                                    </v-avatar>
                                    <v-list-item
                                        :title="mostKills.auth_name"
                                        :subtitle="mostKills.total_kills + ' Kills'"
                                    />
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col v-if="mostDeaths" cols="12" md="3">
                            <v-card :loading="isLoading" outlined>
                                <v-card-title class="bg-green-lighten-4">Top Deaths</v-card-title>
                                <v-card-text class="text-h4 text-center py-2">
                                    <v-avatar size="80">
                                        <v-img :src="mostDeaths.auth_avatar" cover></v-img>
                                    </v-avatar>
                                    <v-list-item
                                        :title="mostDeaths.auth_name"
                                        :subtitle="mostDeaths.total_deaths + ' Deaths'"
                                    />
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col v-if="mostKillDeathRatio" cols="12" md="3">
                            <v-card :loading="isLoading" outlined>
                                <v-card-title class="bg-green-lighten-4">Top K/D Ratio</v-card-title>
                                <v-card-text class="text-h4 text-center py-2">
                                    <v-avatar size="80">
                                        <v-img :src="mostKillDeathRatio.auth_avatar" cover></v-img>
                                    </v-avatar>
                                    <v-list-item
                                        :title="mostKillDeathRatio.auth_name"
                                        :subtitle="mostKillDeathRatio.kill_death_ratio + ' K/D Ratio'"
                                    />
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-col>
            </v-row>

            <!-- All Player Statistics Data Table -->
            <v-row class="mt-4">
                <v-col cols="12">
                    <v-card>
                        <v-card-title>
                            <v-row>
                                <v-col align-self="center" cols="6">
                                    All Player Statistics
                                </v-col>
                                <v-col cols="6">
                                    <v-text-field
                                        v-model="search"
                                        label="Search"
                                        prepend-inner-icon="mdi-magnify"
                                        variant="outlined"
                                        hide-details
                                        single-line
                                        density="compact"
                                    />
                                </v-col>
                            </v-row>
                        </v-card-title>
                        <v-data-table
                            :headers="allPlayerStatisticsTableHeaders"
                            :items="leaderboardData[tab].summary"
                            :items-per-page="10"
                            :loading="isLoading"
                            :search="search"
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
                            <v-data-table
                                :headers="top10PlayersByKillsTableHeaders"
                                :items="top10PlayersByKillsTableData"
                                :items-per-page="10"
                                :loading="isLoading"
                                hide-default-footer
                            >
                                <template v-slot:item.auth_name="{ item }">
                                    <div class="d-flex ga-2 align-center align-items-center">
                                        <v-avatar size="40">
                                            <v-img :src="item.auth_avatar" cover></v-img>
                                        </v-avatar>
                                        <v-list-item :title="item.auth_name" />
                                    </div>
                                </template>
                            </v-data-table>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" md="6">
                    <v-card>
                        <v-card-title>Top 10 Players by Deaths</v-card-title>
                        <v-card-text>
                            <v-data-table
                                :headers="top10PlayersByDeathsTableHeaders"
                                :items="top10PlayersByDeathsTableData"
                                :items-per-page="10"
                                :loading="isLoading"
                                hide-default-footer
                            >
                                <template v-slot:item.auth_name="{ item }">
                                    <div class="d-flex ga-2 align-center align-items-center">
                                        <v-avatar size="40">
                                            <v-img :src="item.auth_avatar" cover></v-img>
                                        </v-avatar>
                                        <v-list-item :title="item.auth_name" />
                                    </div>
                                </template>
                            </v-data-table>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
            <v-row class="mt-4 mb-2">
                <v-col cols="12" md="4"></v-col>
                <v-col cols="12" md="4">
                    <v-card :loading="isLoading">
                        <v-card-title class="text-center">Distribution of Kill/Death Ratios</v-card-title>
                        <v-card-text>
                            <Pie
                                :data="chartData"
                                :options="chartOptions"
                            />
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12" md="4"></v-col>
            </v-row>

        </v-container>
    </GuestLayout>
</template>
