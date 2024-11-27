<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RewardEngine Auth Demo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" integrity="sha512-jnSuA4Ss2PkkikSOLtYs8BlYIeeIK1h99ty4YfvRPAlzr377vr3CXDb7sb7eEEBYjDtcYj+AjBH3FLv5uSJuXg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        html,
        body {
            height: 100%;
        }
        .form-sign-in {
            max-width: 600px;
            padding: 1rem;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body class="d-flex align-items-center py-4 bg-body-tertiary">

    <main class="form-sign-in w-100 m-auto bg-secondary-subtle">

        <div id="status" style="display: none;">...</div>

        <div id="demo">
            <div class="mb-3">
                <label class="form-label">API Base URL</label>
                <input id="apiBaseUrl" value="{{ url('') }}/api/v1" type="text" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Public API Key</label>
                <input id="apiPublicKey" value="067d20be-8baa-49cb-b501-e004af358870" placeholder="e.g. 067d20be-8baa-49cb-b501-e004af358870" type="text" class="form-control">
            </div>
            <button id="loadAvailableAuthProvidersButton" type="button" class="btn btn-sm btn-primary">Load Available Auth Providers</button>
        </div>

        <div id="authContainer" style="display: none;">...</div>

    </main>

    <script type="text/javascript">

        // References
        const $status = $('div#status');
        const $demo = $('div#demo');
        const $authContainer = $('div#authContainer');
        let apiBaseUrl, publicApiKey, reference;

        // Auth init helper
        const signIn = (target) => {
            const redirectUrl = $(target).data('redirect-url');
            $authContainer.hide();
            $status.html('Waiting for you to sign-in...').show();
            startPolling();
            window.open(redirectUrl, '_blank').focus();
        };

        // Auth check helper
        let timer = null;
        const startPolling = () => {
            timer = setInterval(() => {
                $.ajax({
                    type: 'get',
                    url: `${apiBaseUrl}/auth/check/${ publicApiKey }/?reference=${ reference }`,
                    success: function (authState) {
                        if (authState && authState.authenticated === true) {
                            clearInterval(timer);
                            $status.html(`
                                <h1>Successfully Signed In</h1>
                                <hr>
                                <strong>Account</strong>
                                <div class="mb-1">
                                    <label class="form-label">Auth Provider</label>
                                    <input value="${ authState.account.auth_provider }" type="text" class="form-control form-control-sm" disabled>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Auth Provider ID</label>
                                    <input value="${ authState.account.auth_provider_id }" type="text" class="form-control form-control-sm" disabled>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Auth Wallet</label>
                                    <input value="${ authState.account.auth_wallet ?? 'N/A' }" type="text" class="form-control form-control-sm" disabled>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Auth Name</label>
                                    <input value="${ authState.account.auth_name }" type="text" class="form-control form-control-sm" disabled>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Auth Email</label>
                                    <input value="${ authState.account.auth_email }" type="text" class="form-control form-control-sm" disabled>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Auth Avatar</label>
                                    <br>
                                    <img src="${ authState.account.auth_avatar }" width="128" alt="" class="rounded-circle" />
                                </div>
                                <hr>
                                <strong>Session</strong>
                                <div class="mb-1">
                                    <label class="form-label">Reference (e.g. Ephemeral Key)</label>
                                    <input value="${ authState.session.reference }" type="text" class="form-control form-control-sm" disabled>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Session Id</label>
                                    <input value="${ authState.session.session_id }" type="text" class="form-control form-control-sm" disabled>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Auth Country Code</label>
                                    <input value="${ authState.session.auth_country_code }" type="text" class="form-control form-control-sm" disabled>
                                </div>
                                <div>
                                    <label class="form-label">Authenticated At (UTC)</label>
                                    <input value="${ authState.session.authenticated_at }" type="text" class="form-control form-control-sm" disabled>
                                </div>
                            `);
                        }
                    },
                    error: function (request) {
                        alert(`Error: ${request.responseJSON.error}\n\nReason: ${request.responseJSON.reason}`)
                    },
                });
            }, (10 * 1000));
        };

        // When page loads
        $(document).ready(function()
        {
            // Helpers
            const capitalize = s => (s && String(s[0]).toUpperCase() + String(s).slice(1)) || '';
            const randomString = () => Math.random().toString(36).slice(2);

            // Load available sign-in methods
            $('button#loadAvailableAuthProvidersButton').click(function() {

                // Remember api base url & public api key
                apiBaseUrl = $('input#apiBaseUrl').val();
                publicApiKey = $('input#apiPublicKey').val();
                if (!publicApiKey || publicApiKey.length <= 0) {
                    alert('Please specify your public api key');
                    return;
                }

                // Fetch available sign in methods
                $demo.hide();
                $status.html('Loading available auth providers...').show();
                $.ajax({
                    type: 'get',
                    url: `${ apiBaseUrl }/auth/providers`,
                    success: function (authProviders) {
                        reference = randomString();
                        let authUI = `
                            <h1>Sign In</h1>
                            <div class="mb-3">
                                <label class="form-label">Reference (e.g. Ephemeral Key)</label>
                                <input id="reference" value="${ reference }" type="text" class="form-control" disabled>
                            </div>
                        `;
                        authProviders.forEach(authProvider => {
                            authUI += `
                                <div class="mb-3">
                                    <button type="button" onclick="signIn(this)" data-redirect-url="${ apiBaseUrl }/auth/init/${ publicApiKey }/${ authProvider }/?reference=${ reference }" class="btn col-12 btn-primary">
                                        ${ capitalize(authProvider) }
                                    </button>
                                </div>
                            `;
                        });
                        $status.hide();
                        $authContainer.html(authUI).show();
                    },
                    error: function (request) {
                        alert(`Error: ${request.responseJSON.error}\n\nReason: ${request.responseJSON.reason}`)
                    },
                });
            });
        });

    </script>

</body>
</html>
