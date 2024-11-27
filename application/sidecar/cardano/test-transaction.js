const url = 'http://rewardengine-cardano-sidecar:3000';
const headers = {
    'Content-Type': 'application/json',
};
const data = {
    type: 'verifyTransaction',
    transactionCbor: "84a600d901028001800200030104d901028183028200581c71470d2cba2c712eaf433c7f1b312ed9cfa54fa3107b9cf9ce16f891581cabacadaba9f12a8b5382fc370e4e7e69421fb59831bb4ecca3a11d9b075820bff843f20f723ea0a6f843295b2fc89f9e5d6d6fbae6d4ee2120921a62636ab7a100d90102818258201d4fb3fa69d407b064317562389fba485dc0bf6540f588b1f47372d897d360405840142a159e5c2adfb55fc88dceb5a6d75ef36c2138af73730911b99367156638b264373785be57831197f019ca7745ffaf333235c7b012569d5aa283b6b240b50df5a108897840376232323635373837303639373236313734363936663665323233613232333233303332333432643331333132643332333735343330333133613332333033617840333433373262333033303361333033303232326332323639373337333735363536343232336132323332333033323334326433313331326433323337353433307840333133613331333733613334333732623330333033613330333032323263323236653666366536333635323233613232333736343632333433373334333533327840326433343634363636333264333436353633333932643631363336333337326433383338333933363338333736313333363436343331333032323263323237347840373937303635323233613232353537333635373234313735373436383635366537343639363336313734363936663665323232633232373537323639323233617840323236383734373437303361356332663563326636633666363336313663363836663733373433613338333233303330323232633232373537333635373234397840343432323361323237333734363136623635356637343635373337343331373537303633333537373732363637363638363736623338376137343334333036377840373633373338333737383635333333393664373637353663363633323330333537363637333836383338333836353635363337343330333337393637333837347830333436613663333832323263323237363635373237333639366636653232336132323331326533303265333032323764",
    walletAuthChallengeHex: "a100818258201d4fb3fa69d407b064317562389fba485dc0bf6540f588b1f47372d897d360405840142a159e5c2adfb55fc88dceb5a6d75ef36c2138af73730911b99367156638b264373785be57831197f019ca7745ffaf333235c7b012569d5aa283b6b240b50d",
    stakeKeyAddress: "stake_test1upc5wrfvhgk8zt40gv787xe39mvulf205vg8h88eect03yg8t4jl8"
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
