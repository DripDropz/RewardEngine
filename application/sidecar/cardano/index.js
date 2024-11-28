const CMS = require('@emurgo/cardano-message-signing-nodejs');
const CSL = require('@emurgo/cardano-serialization-lib-nodejs');
const Buffer = require('buffer').Buffer;
const cbor = require('cbor');
const blakejs = require('blakejs');
const lucidWallet = require('@lucid-evolution/wallet');
const bip39 = require('@lucid-evolution/bip39');

const toHexBuffer = hex => Buffer.from(hex, 'hex');
const toHexString = array => Buffer.from(array).toString('hex');

const sigKeyToPublicKey = (sigKey) => {
    const decoded = cbor.decode(sigKey);
    return CSL.PublicKey.from_bytes(toHexBuffer(decoded.get(-2)));
};

const publicKeyToStakeKey = (publicKey, networkMode) => {
    const stakeArg = `e` + networkMode + toHexString(publicKey.hash('hex').to_bytes());
    return CSL.Address.from_bytes(toHexBuffer(stakeArg));
};

const verifyTransaction = (event) => {
    let { isValid, isValidTransaction, isValidSignature, isValidPayload, error } = false;
    try {
        const { transactionCbor, walletAuthChallengeHex, stakeKeyAddress } = event;
        const stakeKeyHex = CSL.Address.from_bech32(stakeKeyAddress).to_hex().substring(2);
        const tx = CSL.Transaction.from_bytes(toHexBuffer(transactionCbor));
        const witnesses = tx.witness_set();
        const fixedTransaction = CSL.FixedTransaction.new_from_body_bytes(tx.body().to_bytes());
        const txHash = fixedTransaction.transaction_hash().to_hex();
        const txMetadataHash = tx.body().auxiliary_data_hash().to_hex();
        const testMetadataHash = CSL.hash_auxiliary_data(tx.auxiliary_data()).to_hex();
        const payloadEntryValue = tx.auxiliary_data().metadata().get(CSL.BigNum.from_str('8'));
        let payloadVal = '';
        for (let i = 0; i < payloadEntryValue.as_list().len(); i++) {
            payloadVal += payloadEntryValue.as_list().get(i).as_text();
        }
        isValidPayload = (payloadVal === walletAuthChallengeHex && testMetadataHash === txMetadataHash);
        isValidTransaction = tx.is_valid();
        for (let i = 0; i < witnesses.vkeys().len(); i++) {
            const witness = witnesses.vkeys().get(i);
            const WitnessVkey = witness.vkey();
            const WitnessPubKey = WitnessVkey.public_key();
            if (WitnessPubKey.hash().to_hex() === stakeKeyHex) {
                isValidSignature = WitnessPubKey.verify(toHexBuffer(txHash), witness.signature());
            }
        }
        isValid = (isValidTransaction && isValidSignature && isValidPayload);
    } catch (e) {
        isValid = false;
        error = e.message || e;
    }
    return {
        isValid,
        isValidTransaction,
        isValidSignature,
        isValidPayload,
        error,
    };
};

const verifySignature = (event) => {
    let { isValid, walletMatches, payloadMatches, signatureValidates, error } = false;
    try {
        const { signatureCbor, signatureKey, walletAuthChallengeHex, stakeKeyAddress, networkMode } = event;
        const publicKey = sigKeyToPublicKey(signatureKey);
        const stakeAddr = publicKeyToStakeKey(publicKey, networkMode);
        const coseSign1Verify = CMS.COSESign1.from_bytes(toHexBuffer(signatureCbor));
        const signedSigStrucVerify = coseSign1Verify.signed_data();
        const sig = CSL.Ed25519Signature.from_bytes(coseSign1Verify.signature());
        const stakePrefix = networkMode === 1 ? 'stake' : 'stake_test';
        walletMatches = stakeAddr.to_bech32(stakePrefix) === stakeKeyAddress;
        signatureValidates = publicKey.verify(signedSigStrucVerify.to_bytes(), sig);
        const signedPayloadHex = toHexString(signedSigStrucVerify.payload());
        payloadMatches = (
            // Signed by lite wallet
            signedPayloadHex === walletAuthChallengeHex ||
            // Signed by hardware wallet
            signedPayloadHex === blakejs.blake2bHex(toHexBuffer(walletAuthChallengeHex), null, 28)
        );
        isValid = (walletMatches && payloadMatches && signatureValidates);
    } catch (e) {
        isValid = false;
        error = e.message || e;
    }
    return {
        isValid,
        walletMatches,
        payloadMatches,
        signatureValidates,
        error,
    };
};

const generateNewWallet = () => {
    const mnemonic = bip39.generateMnemonic(256);
    return {
        mnemonic,
        wallet: lucidWallet.walletFromSeed(mnemonic)
    };
}

exports.handler = async (event) => {
    switch (event.type) {
        case 'verifyTransaction': return verifyTransaction(event);
        case 'verifySignature': return verifySignature(event);
        case 'generateNewWallet': return generateNewWallet();
        default: return { error: 'Invalid Event Type' };
    }
};
