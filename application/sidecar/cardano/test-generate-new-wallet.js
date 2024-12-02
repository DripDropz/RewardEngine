const url = 'http://rewardengine-cardano-sidecar:3000';
const headers = {
    'Content-Type': 'application/json',
};
const data = {
    type: 'generateNewWallet',
};

fetch(url, {
    method: 'POST',
    headers,
    body: JSON.stringify(data),
})
    .then((response) => response.json())
    .then((result) => {
        console.log(result);
    });
