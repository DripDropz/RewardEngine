const CMS = require('@emurgo/cardano-message-signing-nodejs');
const CSL = require('@emurgo/cardano-serialization-lib-nodejs');
const Buffer = require('buffer');
const cbor = require('cbor');

const toHexBuffer = hex => Buffer.Buffer.from(hex, 'hex');
const toHexString = array => Buffer.Buffer.from(array).toString('hex');

const sigKeyToPublicKey = (sigKey) => {
    const decoded = cbor.decode(sigKey);
    return CSL.PublicKey.from_bytes(toHexBuffer(decoded.get(-2)));
};

const publicKeyToStakeKey = (publicKey, networkMode) => {
    const stakeArg = `e` + networkMode + toHexString(publicKey.hash('hex').to_bytes());
    return CSL.Address.from_bytes(toHexBuffer(stakeArg));
};

const verifyTransaction = (event) => {
    try {
        const { transactionCbor, walletAuthChallengeHex, stakeKeyAddress } = event;
        const resp_object = {
            valid_transaction: false,
            valid_signature: false,
            valid_nonce: false,
        }
        const stake_key_hex = CSL.Address.from_bech32(stakeKeyAddress).to_hex().substring(2);
        const tx = CSL.Transaction.from_bytes(toHexBuffer(transactionCbor));
        const witnesses = tx.witness_set();
        // const tx_hash = CSL.hash_transaction(tx.body()).to_hex();
        const tx_hash = CSL.FixedTransaction.from_bytes(tx.body().to_bytes()).transaction_hash();
        const tx_metadata_hash = tx.body().auxiliary_data_hash().to_hex();
        const test_metadata_hash = CSL.hash_auxiliary_data(tx.auxiliary_data()).to_hex();
        const nonce_entry_value = tx.auxiliary_data().metadata().get(CSL.BigNum.from_str('8'));
        let nonce_val = '';
        for (let i = 0; i < nonce_entry_value.as_list().len(); i++) {
            nonce_val += nonce_entry_value.as_list().get(i).as_text();
        }
        resp_object.valid_nonce = (nonce_val === walletAuthChallengeHex && test_metadata_hash === tx_metadata_hash);
        resp_object.valid_transaction = tx.is_valid();
        for (let i = 0; i < witnesses.vkeys().len(); i++) {
            const witness = witnesses.vkeys().get(i);
            const WitnessVkey = witness.vkey();
            const WitnessPubKey = WitnessVkey.public_key();
            if (WitnessPubKey.hash().to_hex() === stake_key_hex) {
                resp_object.valid_signature = WitnessPubKey.verify(toHexBuffer(tx_hash), witness.signature());
            }
        }
        return {
            isValid: (resp_object.valid_transaction && resp_object.valid_signature && resp_object.valid_nonce),
        };
    } catch (error) {
        return { error: error.message || error };
    }
};

const verifySignature = (event) => {
    try {
        const { signatureCbor, signatureKey, walletAuthChallengeHex, stakeKeyAddress, networkMode } = event;
        const publicKey = sigKeyToPublicKey(signatureKey);
        const stakeAddr = publicKeyToStakeKey(publicKey, networkMode);
        const coseSign1Verify = CMS.COSESign1.from_bytes(toHexBuffer(signatureCbor));
        const signedSigStrucVerify = coseSign1Verify.signed_data();
        const sig = CSL.Ed25519Signature.from_bytes(coseSign1Verify.signature());
        const stakePrefix = networkMode === 1 ? 'stake' : 'stake_test';
        const walletMatches = stakeAddr.to_bech32(stakePrefix) === stakeKeyAddress;
        const signatureValidates = publicKey.verify(signedSigStrucVerify.to_bytes(), sig);
        const payloadMatches = toHexString(signedSigStrucVerify.payload()) === walletAuthChallengeHex;
        return {
            isValid: (walletMatches && payloadMatches && signatureValidates),
            walletMatches,
            payloadMatches,
            signatureValidates,
        };
    } catch (error) {
        return { error: error.message || error };
    }
};

const generateNewWallet = () => {
    return {
        message: 'TODO',
    };
}

exports.handler = async (event) =>
{
    switch (event.type) {
        case 'verifyTransaction': return verifyTransaction(event);
        case 'verifySignature': return verifySignature(event);
        case 'generateNewWallet': return generateNewWallet();
    }

    return {
        error: 'Invalid Event Type',
    };
};
