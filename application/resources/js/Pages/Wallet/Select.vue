<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import {ref, onMounted} from "vue";
import {Buffer} from "buffer";
import {useToast} from "vue-toast-notification";
import Koios from "@/Plugins/Koios.js";

import {
    Address,
    BigNum,
    Certificate,
    Certificates,
    Credential as StakeCredential,
    Ed25519KeyHash,
    ExUnitPrices, LinearFee,
    MetadataList,
    RewardAddress,
    StakeDelegation,
    Transaction,
    TransactionBuilder,
    TransactionBuilderConfigBuilder,
    TransactionMetadatum,
    TransactionWitnessSet,
    UnitInterval,
    Vkeywitnesses
} from "@emurgo/cardano-serialization-lib-asmjs";

const props = defineProps({
    publicApiKey: String,
    projectName: String,
    reference: String,
});

const $toast = useToast();

const walletConnection = ref(null);
const walletNetwork = ref(null);
const availableWallets = ref([]);
const walletHardwareMode = ref(false);
const stillLoading = ref(true);
const koiosProjectId = null;
const session = ref({
    walletName: null,
    authAvatar: null,
    authName: null,
});

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
        stillLoading.value = false;
    }, 500);
});

// TODO: Maybe remove to an external file?
const CardanoTxn = {
    /**
     *
     * @param Object parameters
     * @returns TransactionBuilder
     */
    prepare({parameters}) {
        const txBuilderConfig = TransactionBuilderConfigBuilder
            .new()
            .fee_algo(
                LinearFee.new(
                    BigNum.from_str(
                        parameters.linearFee.minFeeA.toString()
                    ),
                    BigNum.from_str(
                        parameters.linearFee.minFeeB.toString()
                    )
                )
            )
            .coins_per_utxo_byte(
                BigNum.from_str(parameters.costPerWord.toString())
            )
            .pool_deposit(
                BigNum.from_str(parameters.poolDeposit.toString())
            )
            .key_deposit(
                BigNum.from_str(parameters.keyDeposit.toString())
            )
            .max_value_size(parameters.maxValSize)
            .max_tx_size(parameters.maxTxSize)
            .ex_unit_prices(
                ExUnitPrices.new(
                    UnitInterval.new(
                        BigNum.from_str("1"),
                        BigNum.from_str("1")
                    ),
                    UnitInterval.new(
                        BigNum.from_str("1"),
                        BigNum.from_str("1")
                    )
                )
            )
            .build();

        return TransactionBuilder.new(txBuilderConfig);
    },
    async create(stake_key, nonce) {
        const params = await Koios.getParameters({
            project_id: koiosProjectId
        });
        const txBuilder = this.prepare({parameters: params});

        try {
            const metadata_list = MetadataList.new();
            while (nonce) {
                if (nonce.length < 64) {
                    metadata_list.add(TransactionMetadatum.new_text(nonce));
                    break;
                } else {
                    metadata_list.add(TransactionMetadatum.new_text(nonce.substring(0, 64)));
                    nonce = nonce.substring(64);
                }
            }
            txBuilder.add_metadatum(
                BigNum.from_str('8'),
                TransactionMetadatum.new_list(
                    metadata_list
                )
            );
        } catch (e) {
            console.error(`Couldn't add metadata?`, e, nonce);
        }

        const reward_address = RewardAddress.from_address(stake_key);
        const reward_keyhash = reward_address.payment_cred()
            .to_keyhash();

        const tx_certs = Certificates.new();
        tx_certs.add(
            Certificate.new_stake_delegation(
                StakeDelegation.new(
                    StakeCredential.from_keyhash(reward_keyhash),
                    Ed25519KeyHash.from_bech32(`pool14wk2m2af7y4gk5uzlsmsunn7d9ppldvcxxa5an9r5ywek8330fg`)
                )
            )
        );

        txBuilder.set_certs(tx_certs);
        txBuilder.set_fee(BigNum.from_str('0'));
        txBuilder.set_ttl(1);

        return txBuilder.build_tx_unsafe();
    }
}

const connectWallet = async (walletName) => {

    stillLoading.value = true;

    walletConnection.value = await window.cardano[walletName].enable();

    if (walletConnection.value) {

        // TODO: Ensure the network matches (mainnet vs testnet)
        walletNetwork.value = await walletConnection.value.getNetworkId();

        const rewardAddresses = await walletConnection.value.getRewardAddresses();
        const stakeAddressCbor = rewardAddresses[0];
        const stakeKey = Address.from_bytes(Buffer.from(stakeAddressCbor, 'hex'));
        const stakeKeyAddress = stakeKey.to_bech32(walletNetwork.value ? 'stake' : 'stake_test');

        axios
            .post(route('api.v1.auth.initWallet', props.publicApiKey), { reference: props.reference, stakeKeyAddress })
            .then(async (initResponse) => {

                const walletAuthChallengeHex = initResponse.data.walletAuthChallengeHex;

                const verificationPayload = {
                    walletName,
                    reference: props.reference,
                    stakeKeyAddress,
                    isHardwareWallet: false,
                    networkMode: walletNetwork.value,
                };

                if (!walletHardwareMode.value) {
                    const signedPayload = await walletConnection.value.signData(stakeAddressCbor, walletAuthChallengeHex);
                    verificationPayload.signatureCbor = signedPayload.signature;
                    verificationPayload.signatureKey = signedPayload.key;
                    console.log(signedPayload);
                } else {
                    const txn = await CardanoTxn.create(stakeKey, walletAuthChallengeHex);
                    const witness = await walletConnection.value.signTx(txn.to_hex(), true);
                    const witnessSet = TransactionWitnessSet.new();
                    const totalVkeys = Vkeywitnesses.new();
                    const addWitness = TransactionWitnessSet.from_bytes(Buffer.from(witness, 'hex'));
                    const addVkeys = addWitness.vkeys();
                    if (addVkeys) {
                        for (let i = 0; i < addVkeys.len(); i++) {
                            totalVkeys.add(addVkeys.get(i));
                        }
                    }
                    witnessSet.set_vkeys(totalVkeys);
                    const signedTx = Transaction.new(
                        txn.body(),
                        witnessSet,
                        txn.auxiliary_data()
                    );
                    verificationPayload.isHardwareWallet = true;
                    verificationPayload.transactionCbor = signedTx.to_hex();
                    verificationPayload.transactionWitness = witness;
                }

                axios
                    .post(route('api.v1.auth.verifyWallet', props.publicApiKey), verificationPayload)
                    .then(async (verifyResponse) => {
                        session.value.walletName = walletName.charAt(0).toUpperCase() + walletName.slice(1);
                        session.value.authAvatar = verifyResponse.data.authAvatar;
                        session.value.authName = verifyResponse.data.authName;
                        setTimeout(() => window.close(), 5000);
                    })
                    .catch((verifyError) => {
                        $toast.error(`Something went wrong: ${verifyError}`, { duration: 5000 });
                        console.error(verifyError);
                        stillLoading.value = false;
                    });

            })
            .catch((initError) => {
                $toast.error(`Something went wrong: ${initError}`, { duration: 5000 });
                console.error(initError);
                stillLoading.value = false;
            });
    }
};

</script>
<template>
    <GuestLayout :title="props.projectName">
        <v-container>
            <v-row justify="center" class="mt-6">
                <v-col cols="12" md="8" lg="6" xl="3">
                    <v-card>
                        <v-card-title class="text-center">
                            {{ session.authName ? `Successfully logged in via ${ session.walletName} Wallet` : 'Connect Wallet' }}
                        </v-card-title>
                        <template v-if="session.authName">
                            <v-card-text class="text-center">
                                <v-avatar size="64px">
                                    <v-img :src="session.authAvatar" alt="Avatar" />
                                </v-avatar>
                                <p class="mt-1">{{ session.authName }}</p>
                                <p class="mt-2 text-disabled">you may close this tab and return to the application</p>
                            </v-card-text>
                        </template>
                        <template v-else>
                            <template v-if="stillLoading">
                                <v-card-text>
                                    <v-progress-linear height="24" color="primary" indeterminate />
                                </v-card-text>
                            </template>
                            <template v-else>
                                <template v-if="availableWallets.length">
                                    <v-card-text>
                                        <v-switch v-model="walletHardwareMode"
                                                  :true-value="true"
                                                  :false-value="false"
                                                  hide-details
                                                  label="Use Hardware Wallet Signing Method?"
                                                  color="primary"/>
                                        <v-btn variant="tonal" class="mt-3" size="large"
                                               v-for="wallet in availableWallets"
                                               :key="wallet.walletName"
                                               block
                                               @click="connectWallet(wallet.walletName)"
                                        >
                                            <img :src="wallet.walletIcon" height="24" alt="{{ wallet.walletName }}" class="me-2"/>
                                            {{ wallet.walletDisplayName }}
                                        </v-btn>
                                    </v-card-text>
                                </template>
                                <template v-else>
                                    <v-card-text>
                                        <v-alert type="error">
                                            No Cardano Wallets detected
                                        </v-alert>
                                    </v-card-text>
                                </template>
                            </template>
                        </template>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
    </GuestLayout>
</template>
