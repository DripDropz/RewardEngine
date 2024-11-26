const lambda = require('./index');

const express = require('express');
var bodyParser = require('body-parser')
const app = express();
app.use(bodyParser.json());
const port = 3000;

app.post('/', async (req, res) => {
    res.send(await lambda.handler(req.body));
});

app.listen(port, () => {
    console.log(`Dev server listening on port ${port}`)
});

/**
 * Notes: npm install --omit=dev (for production sidecar deployment)
 */
