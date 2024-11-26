<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import {ref, onMounted} from "vue";
import {Buffer} from "buffer";
import {useToast} from "vue-toast-notification";

import {
    Address,
    BigNum,
    Certificate,
    Certificates,
    Ed25519KeyHash,
    MetadataList,
    RewardAddress,
    ScriptHash,
    StakeCredential,
    StakeDelegation, Transaction,
    TransactionMetadatum, TransactionWitnessSet,
    Value, Vkeywitnesses
} from "@emurgo/cardano-serialization-lib-asmjs";

const props = defineProps({
    publicApiKey: String,
    reference: String,
});

const $toast = useToast();

const walletConnection = ref(null);
const walletNetwork = ref(null);
const availableWallets = ref([]);

onMounted(async () => {
    setTimeout(() => {
        const knownWallet = [];
        if (window.cardano !== undefined) {
            for (const [walletName, walletObject] of Object.entries(window.cardano)) {
                if (!['enable', 'isEnabled'].includes(walletName)) {
                    let walletDisplayName = walletObject.name.replace('Wallet', '').trim();
                    walletDisplayName = walletDisplayName.charAt(0).toUpperCase() + walletDisplayName.slice(1);
                    if (!knownWallet.includes(walletDisplayName) && walletObject.icon) {
                        availableWallets.value.push({
                            walletName,
                            walletDisplayName,
                            walletIcon: walletObject.icon,
                        })
                        knownWallet.push(walletDisplayName);
                    }
                }
            }
        }
    }, 500);
});

const connectWallet = async (walletName) => {

    walletConnection.value = await window.cardano[walletName].enable();

    if (walletConnection.value) {

        // TODO: Ensure the network matches (mainnet vs testnet)
        walletNetwork.value = await walletConnection.value.getNetworkId();

        const rewardAddresses = await walletConnection.value.getRewardAddresses();
        const stakeAddressCbor = rewardAddresses[0];
        const stakeKey = Address.from_bytes(Buffer.from(stakeAddressCbor, 'hex'));
        const stakeKeyBech32 = stakeKey.to_bech32(walletNetwork.value ? 'stake' : 'stake_test');

        axios
            .post(route('api.v1.auth.initWallet', props.publicApiKey), {
                walletName: walletName,
                stakeKeyAddress: stakeKeyBech32,
            })
            .then(async (response) => {
                const walletAuthChallengeHex = response.data.walletAuthChallengeHex;
                console.log(walletConnection.value);
                const signedPayload = await walletConnection.value.signData(stakeAddressCbor, walletAuthChallengeHex);
                console.log('walletAuthChallengeHex', walletAuthChallengeHex);
                console.log('stakeKeyBech32', stakeKeyBech32);
                console.log('signedPayload', signedPayload);
                // TODO: we will send the network mode along with the request (it will be 0 for testnet, 1 for mainnet)
            })
            .catch((error) => {
                $toast.error(`Something went wrong: ${error}`);
                console.error(error);
            });

    }

};

</script>
<template>
    <GuestLayout title="Test Page">
        <p>Connect Wallet</p>
        <v-btn class="me-2" v-for="wallet in availableWallets" @click="connectWallet(wallet.walletName)">
            {{ wallet.walletDisplayName }}
        </v-btn>
    </GuestLayout>
</template>
