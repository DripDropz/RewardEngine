<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import {useToast} from "vue-toast-notification";
import {onMounted, ref, watch} from "vue";

const props = defineProps({
    publicApiKey: String,
    projectName: String,
});

// Initialise
const $toast = useToast();
const STATS_TYPE_OVERVIEW = 'overview';
const STATS_TYPE_QUALIFIER = 'qualifier';
const isLoading = ref(false);
const authProviders = ref([]);
const isSigningIn = ref(false);
const user = ref(null);
const stats = ref(null);

// Helper functions
const capitalize = s => (s && String(s[0]).toUpperCase() + String(s).slice(1)) || '';
const randomString = () => Math.random().toString(36).slice(2);

// On mounted
onMounted(() => {
    isLoading.value = true;
    axios.get(route('api.v1.auth.providers'))
        .then(res => authProviders.value = res.data)
        .catch(err => $toast.error('Failed to load available auth providers.', { duration: 5000 }))
        .finally(() => isLoading.value = false);
});

// Sign in handler
let signInCheck = null;
const signIn = (redirectUrl) => {
    const reference = randomString();
    isSigningIn.value = true;
    window.open(redirectUrl + '?reference=' + reference, '_blank').focus();
    signInCheck = setInterval(() => {
        isLoading.value = true;
        axios.get(route('api.v1.auth.check', { publicApiKey: props.publicApiKey }) + '?reference=' + reference)
            .then(checkRes => {
                if (checkRes.data.authenticated) {
                    user.value = checkRes.data;
                    clearInterval(signInCheck);
                }
            })
            .catch(err => $toast.error('Failed to check authentication state.', { duration: 5000 }))
            .finally(() => isLoading.value = false);
    }, 10000);
};
watch(user, () => {
    isLoading.value = true;
    axios.get(route('api.v1.stats.session', { publicApiKey: props.publicApiKey, reference: user.value.session.reference }))
        .then(statsRes => stats.value = statsRes.data)
        .catch(err => $toast.error('Failed to load stats.', { duration: 5000 }))
        .finally(() => isLoading.value = false);
});

</script>

<template>
    <GuestLayout :title="props.projectName + ': My Account'">
        <template v-slot:right-app-bar>
            <v-btn v-if="user" :href="route('leaderboard.myAccount', props.publicApiKey)" variant="tonal" class="me-2">
                Logout
            </v-btn>
            <v-btn :href="route('leaderboard.index', props.publicApiKey)" variant="tonal">
                Leaderboard
            </v-btn>
        </template>

        <!-- Sign In Container -->
        <v-container v-if="!user" class="mt-6">
            <v-card title="View your account" :loading="isLoading">
                <v-card-text class="pb-1">
                    To view your account, please sign in with the same account you used to play the game.
                </v-card-text>
                <v-card-actions class="px-4 pb-4">
                    <template v-if="isSigningIn">
                        <v-alert text="Waiting for you to complete signing in..." type="info" density="compact" />
                    </template>
                    <template v-else>
                        <v-btn
                            v-if="authProviders.length > 0"
                            v-for="authProvider in authProviders"
                            variant="tonal"
                            target="_blank"
                            @click="signIn(route('api.v1.auth.init', {publicApiKey: props.publicApiKey, authProvider: authProvider}))"
                        >
                            {{ capitalize(authProvider) }}
                        </v-btn>
                        <v-btn v-else>Loading available auth providers...</v-btn>
                    </template>
                </v-card-actions>
            </v-card>
        </v-container>

        <!-- Signed In -->
        <v-container v-else class="mt-6">

            <!-- Session Info -->
            <v-card title="Welcome Back" :loading="isLoading" class="mb-6">
                <v-card-text>
                    <div class="d-flex ga-1 align-center align-items-center">
                        <v-avatar size="128">
                            <v-img :src="user.account.auth_avatar" cover></v-img>
                        </v-avatar>
                        <div class="w-100">
                            <v-list-item title="Auth Provider" :subtitle="capitalize(user.account.auth_provider) + (user.account.auth_provider === 'wallet' ? ' (' + capitalize(user.account.auth_wallet) + ')' : '')" />
                            <v-list-item title="Auth Name" :subtitle="user.account.auth_name" />
                            <v-list-item v-if="user.account.auth_email" title="Auth Email" :subtitle="user.account.auth_email" />
                            <v-list-item v-if="user.account.auth_provider !== 'wallet' && !user.account.linked_wallet_stake_address">
                                <v-alert type="warning" density="compact">
                                    Wallet stake address not linked
                                </v-alert>
                                <v-form class="mt-2" @submit.prevent>
                                    <v-text-field v-model="firstName" label=" Wallet Stake Address" placeholder="e.g. stake1upafv37jqjy8pgrjdauxyxrruqme0hqhh9ryww34mm297agc0f3vc" hide-details />
                                    <v-btn class="mt-2" variant="tonal" type="submit" block>Link</v-btn>
                                </v-form>
                            </v-list-item>
                            <v-list-item v-if="user.account.linked_wallet_stake_address" title="Linked Wallet Stake Address" :subtitle="user.account.linked_wallet_stake_address" />
                        </div>
                    </div>
                </v-card-text>
            </v-card>

            <v-row>
                <v-col cols="6">
                    <v-card title="Overview Stats" :loading="isLoading" class="mb-6">
                        <v-card-text>
                            <v-table v-if="stats" density="compact">
                                <tbody>
                                    <tr v-for="[key, value] of Object.entries(stats[STATS_TYPE_OVERVIEW])">
                                        <th>{{ capitalize(key.replace(/_/g, ' ').trim()) }}</th>
                                        <td>{{ value }}</td>
                                    </tr>
                                </tbody>
                            </v-table>
                            <v-alert v-else type="info" density="compact">
                                Loading...
                            </v-alert>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col>
                    <v-card title="Qualifier Stats" :loading="isLoading" class="mb-6">
                        <v-card-text>
                            <v-table v-if="stats" density="compact">
                                <tbody>
                                    <tr v-for="[key, value] of Object.entries(stats[STATS_TYPE_QUALIFIER])">
                                        <th>{{ capitalize(key.replace(/_/g, ' ').trim()) }}</th>
                                        <td>{{ value }}</td>
                                    </tr>
                                </tbody>
                            </v-table>
                            <v-alert v-else type="info" density="compact">
                                Loading...
                            </v-alert>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

        </v-container>

    </GuestLayout>
</template>
