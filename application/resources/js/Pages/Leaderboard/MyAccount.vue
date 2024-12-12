<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import {useToast} from "vue-toast-notification";
import {useConfirm} from "vuetify-use-dialog";
import {onMounted, ref} from "vue";

const props = defineProps({
    publicApiKey: String,
    projectName: String,
    settings: Object,
});

// Initialise
const $toast = useToast();
const createConfirm = useConfirm();
const isLoading = ref(false);
const authProviders = ref([]);
const isSigningIn = ref(false);
const user = ref(null);
const linkedWalletAddress = ref('');

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
        console.log('signInCheck');
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

// Link wallet address handler
const linkWalletAddress = async () => {
    if (
        /^addr(_test)?1(?=[qpzry9x8gf2tvdw0s3jn54khce6mua7l]+)(?:.{98})$/.test(linkedWalletAddress.value.toLowerCase()) === false &&
        /^stake(_test)?1(?=[qpzry9x8gf2tvdw0s3jn54khce6mua7l]+)(?:.{53})$/.test(linkedWalletAddress.value.toLowerCase()) === false
    ) {
        $toast.error('That appears to be an invalid cardano wallet address.', {duration: 5000})
        return;
    }
    const isConfirmed = await createConfirm({
        title: 'Are you sure',
        content: 'Link your social account to wallet address ' + linkedWalletAddress.value.toLowerCase() + '?',
    });
    if (!isConfirmed) return;
    isLoading.value = true;
    axios.post(route('api.v1.stats.session.link-wallet-address', {publicApiKey: props.publicApiKey}), {
        session_id: user.value.session.session_id,
        wallet_address: linkedWalletAddress.value,
    })
    .then(res => {
        user.value.account.linked_wallet_stake_address = linkedWalletAddress.value;
        $toast.success('Wallet address successfully linked to your social account.', {duration: 5000});
    })
    .catch(err => $toast.error('Failed to link wallet address.', {duration: 5000}))
    .finally(() => isLoading.value = false);
};

// Link discord account handler
let linkDiscordCheck = null;
const linkDiscordAccount = () => {
    isLoading.value = true;
    $toast.info('Please sign in with your discord account to complete the linking process.', {duration: 5000});
    window.open(route('api.v1.stats.session.link-discord-account', { publicApiKey: props.publicApiKey, sessionId: user.value.session.session_id }), '_blank').focus();
    linkDiscordCheck = setInterval(() => {
        console.log('linkDiscordCheck');
        isLoading.value = true;
        axios.get(route('api.v1.auth.check', { publicApiKey: props.publicApiKey }) + '?reference=' + user.value.session.reference)
            .then(checkRes => {
                if (checkRes.data.account.linked_discord_account) {
                    user.value = checkRes.data;
                    clearInterval(linkDiscordCheck);
                }
            })
            .catch(err => $toast.error('Failed to check authentication state.', { duration: 5000 }))
            .finally(() => isLoading.value = false);
    }, 10000);
};

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
                                    Wallet address not linked
                                </v-alert>
                                <v-form class="mt-2" @submit.prevent="linkWalletAddress">
                                    <v-text-field v-model="linkedWalletAddress" clearable label="Wallet Address" placeholder="e.g. stake1upafv37jqjy8pgrjdauxyxrruqme0hqhh9ryww34mm297agc0f3vc" hide-details />
                                    <v-btn :loading="isLoading" class="mt-2" variant="tonal" type="submit" block>Link Wallet Address</v-btn>
                                </v-form>
                            </v-list-item>
                            <v-list-item v-if="user.account.linked_wallet_stake_address" title="Linked Wallet Address" :subtitle="user.account.linked_wallet_stake_address" />
                            <v-list-item v-if="user.account.auth_provider !== 'discord' && !user.account.linked_discord_account">
                                <v-alert type="warning" density="compact">
                                    Discord account not linked
                                </v-alert>
                                <v-btn :loading="isLoading" @click="linkDiscordAccount" class="mt-2" variant="tonal" block>Linked Discord Account</v-btn>
                            </v-list-item>
                            <v-list-item v-if="user.account.auth_provider !== 'discord' && user.account.linked_discord_account" title="Linked Discord Account" :subtitle="user.account.linked_discord_account.name + ' (' + user.account.linked_discord_account.id + ')'" />
                        </div>
                    </div>
                </v-card-text>
            </v-card>

            <v-row v-if="user">
                <v-col cols="12">
                    <v-card :loading="isLoading">
                        <v-card-text>
                            <v-alert
                                type="success"
                                icon="mdi-check-circle"
                                density="compact"
                                v-if="
                                    parseInt(user?.qualifier?.requirements.find(s => s?.actual_kill_count)?.actual_kill_count || 0) >= settings.commemorativeTokenAirdropRequirements.required_kill_count
                                    &&
                                    parseFloat(user?.qualifier?.requirements.find(s => s?.actual_play_minutes)?.actual_play_minutes || 0) >= settings.commemorativeTokenAirdropRequirements.required_play_minutes
                                "
                            >
                                Qualified for Commemorative Token Airdrop
                            </v-alert>

                            <v-alert
                                v-else
                                type="error"
                                icon="mdi-close-circle"
                                density="compact"
                            >
                                Not Qualified for Commemorative Token Airdrop
                            </v-alert>

                            <v-chip-group class="mt-1">
                                <v-chip :prepend-icon="parseInt(user?.qualifier?.requirements.find(s => s?.actual_kill_count)?.actual_kill_count || 0) >= settings.commemorativeTokenAirdropRequirements.required_kill_count ? 'mdi-check-circle' : 'mdi-close-circle'">
                                    Required Kill Count <strong class="ml-1">{{ settings.commemorativeTokenAirdropRequirements.required_kill_count }}</strong>
                                </v-chip>
                                <v-chip :prepend-icon="parseFloat(user?.qualifier?.requirements.find(s => s?.actual_play_minutes)?.actual_play_minutes || 0) >= settings.commemorativeTokenAirdropRequirements.required_play_minutes ? 'mdi-check-circle' : 'mdi-close-circle'">
                                    Required Play Minutes <strong class="ml-1">{{ settings.commemorativeTokenAirdropRequirements.required_play_minutes }}</strong>
                                </v-chip>
                            </v-chip-group>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12">
                    <v-card :loading="isLoading">
                        <v-card-text>
                            <v-alert
                                type="success"
                                icon="mdi-check-circle"
                                density="compact"
                                v-if="
                                    parseInt(user?.qualifier?.requirements.find(s => s?.actual_kill_count)?.actual_kill_count || 0) >= settings.usdmAirdropRequirements.required_kill_count
                                    &&
                                    parseFloat(user?.qualifier?.requirements.find(s => s?.actual_play_minutes)?.actual_play_minutes || 0) >= settings.usdmAirdropRequirements.required_play_minutes
                                "
                            >
                                Qualified for USDM Airdrop
                            </v-alert>

                            <v-alert
                                v-else
                                type="error"
                                icon="mdi-close-circle"
                                density="compact"
                            >
                                Not qualified for USDM Airdrop
                            </v-alert>

                            <v-chip-group class="mt-1">
                                <v-chip :prepend-icon="parseInt(user?.qualifier?.requirements.find(s => s?.actual_kill_count)?.actual_kill_count || 0) >= settings.usdmAirdropRequirements.required_kill_count ? 'mdi-check-circle' : 'mdi-close-circle'">
                                    Required Kill Count <strong class="ml-1">{{ settings.usdmAirdropRequirements.required_kill_count }}</strong>
                                </v-chip>
                                <v-chip :prepend-icon="parseFloat(user?.qualifier?.requirements.find(s => s?.actual_play_minutes)?.actual_play_minutes || 0) >= settings.usdmAirdropRequirements.required_play_minutes ? 'mdi-check-circle' : 'mdi-close-circle'">
                                    Required Play Minutes <strong class="ml-1">{{ settings.usdmAirdropRequirements.required_play_minutes }}</strong>
                                </v-chip>
                            </v-chip-group>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <v-row v-if="user">
                <v-col cols="12">
                    <v-card>
                        <v-card-text>
                            <v-alert
                                type="success"
                                icon="mdi-check-circle"
                                density="compact"
                                v-if="user.qualifier && user.qualifier.is_qualified === true"
                                class="mb-2"
                            >
                                Congratulations, you are qualified and met all of the requirements
                            </v-alert>

                            <v-alert
                                v-else
                                type="error"
                                icon="mdi-close-circle"
                                density="compact"
                                class="mb-2"
                            >
                                Sorry you did not qualify, you did not achieve all of the requirements
                            </v-alert>

                            <v-chip-group v-if="user.qualifier" v-for="requirement in user.qualifier.requirements">
                                <v-chip v-for="[key, value] of Object.entries(requirement).reverse()">
                                    <template v-if="typeof value === 'boolean'">
                                        Requirement <strong :class="'ml-1 ' + (value ? 'text-green' : 'text-red')">{{ value ? 'Achieved' : 'Not Achieved' }}</strong>
                                    </template>
                                    <template v-else>
                                        {{ capitalize(key.replace(/_/g, ' ')) }}:
                                        <strong class="ml-1">{{ typeof value === 'object' ? value.join(', ') : value }}</strong>
                                    </template>
                                </v-chip>
                            </v-chip-group>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

        </v-container>

    </GuestLayout>
</template>
