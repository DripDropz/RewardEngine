export default {
    defaultParameters: {
        linearFee: {
            minFeeA: "44",
            minFeeB: "155381",
        },
        minUtxo: "1000000",
        poolDeposit: "500000000",
        keyDeposit: "2000000",
        maxValSize: 5000,
        maxTxSize: 16384,
        costPerWord: "4310",
    },
    async getTip({
                     project_id
                 }) {
        let response;
        try {
            response = await axios.get(
                `https://api.koios.rest/api/v0/tip`,
                {
                    headers: {
                        Authorization: `Bearer ${project_id}`
                    }
                }
            );
        } catch (e) {
            console.error(`Could not query blockchain tip from Koios:`, e);
            return false;
        }

        return response.data[0];
    },
    async getParameters({epoch, project_id}) {
        if (epoch === undefined) {
            const tip = await this.getTip({project_id});
            if (!tip) {
                console.log(
                    `Could not fetch tip from Koios. Returning default parameters.`
                );
                return this.defaultParameters;
            }
            epoch = tip.epoch_no;
        }

        let response;
        try {
            response = await axios.get(
                `https://api.koios.rest/api/v0/epoch_params?_epoch_no=${epoch}`,
                {
                    headers: {
                        Authorization: `Bearer ${project_id}`
                    }
                }
            );
        } catch (e) {
            console.log(
                `Could not fetch parameters from Koios. Returning default parameters.`
            );
            return this.defaultParameters;
        }

        const params = response.data[0];

        if (!params.min_fee_a) {
            return this.defaultParameters;
        }

        return {
            linearFee: {
                minFeeA: params.min_fee_a,
                minFeeB: params.min_fee_b,
            },
            minUtxo: params.min_utxo_value,
            poolDeposit: params.pool_deposit,
            keyDeposit: params.key_deposit,
            maxValSize: params.max_val_size,
            maxTxSize: params.max_tx_size,
            costPerWord: params.coins_per_utxo_size,
        };
    },
};
