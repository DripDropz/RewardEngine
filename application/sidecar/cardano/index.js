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
    const { signatureCbor, signatureKey, walletAuthChallengeHex, stakeKeyAddress, networkMode } = event;
    const publicKey = sigKeyToPublicKey(signatureKey);
    const stakeAddr = publicKeyToStakeKey(publicKey, networkMode);
    const coseSign1_verify = CMS.COSESign1.from_bytes(toHexBuffer(signatureCbor));
    const signedSigStruc_verify = coseSign1_verify.signed_data();
    const sig = CSL.Ed25519Signature.from_bytes(coseSign1_verify.signature());
    const stakePrefix = networkMode === 1 ? 'stake' : 'stake_test';
    const walletMatches = stakeAddr.to_bech32(stakePrefix) === stakeKeyAddress;
    const validates = publicKey.verify(signedSigStruc_verify.to_bytes(), sig);
    const payloadMatches = toHexString(signedSigStruc_verify.payload()) === walletAuthChallengeHex;

    return {
        isValid: (walletMatches && payloadMatches && validates),
        walletMatches,
        payloadMatches,
        validates,
    };
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
