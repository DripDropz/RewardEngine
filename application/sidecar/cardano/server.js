const lambda = require('./index');

const express = require('express');
const bodyParser = require('body-parser')
const app = express();
const port = 3000;

app.use(bodyParser.json());

app.post('/', async (req, res) => {
    res.send(await lambda.handler(req.body));
});

app.listen(port, () => {
    console.log(`Cardano sidecar dev server listening on port ${port}`)
});

/**
 * Notes: npm install --omit=dev (for production sidecar deployment)
 */
