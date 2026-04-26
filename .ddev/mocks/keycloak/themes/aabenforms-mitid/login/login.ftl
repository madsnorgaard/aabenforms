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
    </#if>
</@layout.registrationLayout>
