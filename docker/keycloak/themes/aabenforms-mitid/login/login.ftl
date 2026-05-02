<#import "template.ftl" as layout>
<@layout.registrationLayout displayMessage=!messagesPerField.existsError('username','password') displayInfo=false; section>
    <#if section = "header">
        <span class="aabenforms-hidden-title">${msg("loginAccountTitle")}</span>
    <#elseif section = "form">
        <div class="aabenforms-brand-header">
            <div class="aabenforms-brand-row">
                <img src="${url.resourcesPath}/img/logo.svg" alt="ÅbenForms" class="aabenforms-logo" />
                <span class="aabenforms-brand-mark">MitID - Demo</span>
            </div>
            <p class="aabenforms-brand-tag">${msg("aabenformsBrandTagline")}</p>
        </div>
        <div class="aabenforms-demo-banner" role="note">
            <span class="aabenforms-demo-banner-icon" aria-hidden="true">●</span>
            <div>
                <strong>${msg("aabenformsDemoBannerTitle")}</strong>
                <span class="aabenforms-demo-banner-body">${msg("aabenformsDemoBannerBody")}</span>
            </div>
        </div>
        <h2 class="aabenforms-pagetitle">${msg("loginAccountTitle")}</h2>

        <div class="aabenforms-personas" role="group" aria-label="${msg('aabenformsPersonaSwitcherLabel')}">
            <p class="aabenforms-personas-title">${msg('aabenformsPersonaSwitcherTitle')}</p>
            <div class="aabenforms-personas-grid">
                <button type="button" class="aabenforms-persona" data-username="freja.nielsen" data-default="true">
                    <span class="aabenforms-persona-name">Freja Nielsen</span>
                    <span class="aabenforms-persona-meta">København</span>
                </button>
                <button type="button" class="aabenforms-persona" data-username="mikkel.jensen">
                    <span class="aabenforms-persona-name">Mikkel Jensen</span>
                    <span class="aabenforms-persona-meta">Aarhus</span>
                </button>
                <button type="button" class="aabenforms-persona" data-username="sofie.hansen">
                    <span class="aabenforms-persona-name">Sofie Hansen</span>
                    <span class="aabenforms-persona-meta">Odense</span>
                </button>
                <button type="button" class="aabenforms-persona" data-username="karen.christensen">
                    <span class="aabenforms-persona-name">Karen Christensen</span>
                    <span class="aabenforms-persona-meta">Aalborg</span>
                </button>
            </div>
        </div>

        <div id="kc-form">
            <div id="kc-form-wrapper">
                <#if realm.password>
                    <form id="kc-form-login" class="aabenforms-form" onsubmit="login.disabled = true; return true;" action="${url.loginAction}" method="post">
                        <#if !usernameHidden??>
                            <div class="aabenforms-field">
                                <label for="username" class="aabenforms-label">
                                    <#if !realm.loginWithEmailAllowed>${msg("username")}<#elseif !realm.registrationEmailAsUsername>${msg("usernameOrEmail")}<#else>${msg("email")}</#if>
                                </label>
                                <input
                                    tabindex="2"
                                    id="username"
                                    class="aabenforms-input"
                                    name="username"
                                    value="${(login.username!'')}"
                                    type="text"
                                    autofocus
                                    autocomplete="username"
                                    aria-invalid="<#if messagesPerField.existsError('username','password')>true</#if>"
                                />
                                <#if messagesPerField.existsError('username','password')>
                                    <span id="input-error" class="aabenforms-error" aria-live="polite">
                                        ${kcSanitize(messagesPerField.getFirstError('username','password'))?no_esc}
                                    </span>
                                </#if>
                            </div>
                        </#if>

                        <div class="aabenforms-field">
                            <label for="password" class="aabenforms-label">${msg("password")}</label>
                            <input
                                tabindex="3"
                                id="password"
                                class="aabenforms-input"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                aria-invalid="<#if messagesPerField.existsError('username','password')>true</#if>"
                            />
                        </div>

                        <div class="aabenforms-helper-row">
                            <span class="aabenforms-helper">${msg("aabenformsCredentialHint")}</span>
                        </div>

                        <input type="hidden" id="id-hidden-input" name="credentialId" <#if auth.selectedCredential?has_content>value="${auth.selectedCredential}"</#if>/>

                        <div id="kc-form-buttons" class="aabenforms-actions">
                            <button
                                tabindex="4"
                                class="aabenforms-button-primary"
                                name="login"
                                id="kc-login"
                                type="submit"
                            >
                                <span class="aabenforms-button-label">${msg("doLogIn")}</span>
                                <span class="aabenforms-button-arrow" aria-hidden="true">→</span>
                            </button>
                        </div>
                    </form>
                </#if>
            </div>
        </div>

        <div class="aabenforms-foot">
            <p class="aabenforms-foot-line">${msg("aabenformsFooterLicense")}</p>
            <p class="aabenforms-foot-line">${msg("aabenformsFooterReturn")}</p>
        </div>

        <script>
            (function () {
                var DEMO_PASSWORD = 'test1234';
                var KNOWN = ['freja.nielsen', 'mikkel.jensen', 'sofie.hansen', 'karen.christensen'];
                var u = document.getElementById('username');
                var p = document.getElementById('password');
                var personas = document.querySelectorAll('.aabenforms-persona');
                if (!u || !p || !personas.length) return;

                function setPersona(name, focusPassword) {
                    u.value = name;
                    p.value = DEMO_PASSWORD;
                    personas.forEach(function (b) {
                        b.classList.toggle('is-active', b.getAttribute('data-username') === name);
                    });
                    if (focusPassword) p.focus();
                }

                personas.forEach(function (b) {
                    b.addEventListener('click', function () {
                        setPersona(b.getAttribute('data-username'), true);
                    });
                });

                // Initial state: if Keycloak already echoed back a known persona
                // (e.g. after a failed login retry), keep it active and refill
                // the cleared password. Otherwise default to Freja so the demo
                // is one click away.
                var hasError = !!document.querySelector('#input-error, .alert-error');
                if (KNOWN.indexOf(u.value) !== -1) {
                    setPersona(u.value, false);
                } else if (!u.value && !hasError) {
                    setPersona('freja.nielsen', false);
                }
            })();
        </script>
    </#if>
</@layout.registrationLayout>
