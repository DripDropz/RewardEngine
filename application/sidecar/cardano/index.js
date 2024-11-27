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
        return {
            error: error.message,
        };
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
        case 'verifySignature': return verifySignature(event);
        case 'generateNewWallet': return generateNewWallet();
    }

    return {
        error: 'Invalid Event Type',
    };
};
