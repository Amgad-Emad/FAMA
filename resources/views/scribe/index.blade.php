<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>FAMA API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "https://fama.test";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.11.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.11.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-talent-authentication" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-authentication">
                    <a href="#talent-authentication">Talent authentication</a>
                </li>
                                    <ul id="tocify-subheader-talent-authentication" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-authentication-POSTapi-v1-talent-register">
                                <a href="#talent-authentication-POSTapi-v1-talent-register">Register a talent</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-authentication-POSTapi-v1-talent-login">
                                <a href="#talent-authentication-POSTapi-v1-talent-login">Authenticate credentials and issue an ability-scoped token.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-authentication-GETapi-v1-talent-me">
                                <a href="#talent-authentication-GETapi-v1-talent-me">Return the authenticated entity (the token's tokenable).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-authentication-POSTapi-v1-talent-refresh">
                                <a href="#talent-authentication-POSTapi-v1-talent-refresh">Rotate the caller's token: revoke the one presented and issue a fresh
ability-scoped replacement. Sanctum tokens don't expire on their own, so
rotation is how a client renews a credential it wants to cycle.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-authentication-POSTapi-v1-talent-logout">
                                <a href="#talent-authentication-POSTapi-v1-talent-logout">Revoke the token presented with the request (single-device logout).</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-authentication" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-authentication">
                    <a href="#brand-authentication">Brand authentication</a>
                </li>
                                    <ul id="tocify-subheader-brand-authentication" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-authentication-POSTapi-v1-brand-register">
                                <a href="#brand-authentication-POSTapi-v1-brand-register">Register a brand</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-authentication-POSTapi-v1-brand-login">
                                <a href="#brand-authentication-POSTapi-v1-brand-login">Authenticate credentials and issue an ability-scoped token.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-authentication-GETapi-v1-brand-me">
                                <a href="#brand-authentication-GETapi-v1-brand-me">Return the authenticated entity (the token's tokenable).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-authentication-POSTapi-v1-brand-refresh">
                                <a href="#brand-authentication-POSTapi-v1-brand-refresh">Rotate the caller's token: revoke the one presented and issue a fresh
ability-scoped replacement. Sanctum tokens don't expire on their own, so
rotation is how a client renews a credential it wants to cycle.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-authentication-POSTapi-v1-brand-logout">
                                <a href="#brand-authentication-POSTapi-v1-brand-logout">Revoke the token presented with the request (single-device logout).</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-admin-authentication" class="tocify-header">
                <li class="tocify-item level-1" data-unique="admin-authentication">
                    <a href="#admin-authentication">Admin authentication</a>
                </li>
                                    <ul id="tocify-subheader-admin-authentication" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="admin-authentication-POSTapi-v1-admin-login">
                                <a href="#admin-authentication-POSTapi-v1-admin-login">Authenticate credentials and issue an ability-scoped token.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="admin-authentication-GETapi-v1-admin-me">
                                <a href="#admin-authentication-GETapi-v1-admin-me">Return the authenticated entity (the token's tokenable).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="admin-authentication-POSTapi-v1-admin-refresh">
                                <a href="#admin-authentication-POSTapi-v1-admin-refresh">Rotate the caller's token: revoke the one presented and issue a fresh
ability-scoped replacement. Sanctum tokens don't expire on their own, so
rotation is how a client renews a credential it wants to cycle.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="admin-authentication-POSTapi-v1-admin-logout">
                                <a href="#admin-authentication-POSTapi-v1-admin-logout">Revoke the token presented with the request (single-device logout).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="admin-authentication-POSTapi-v1-admin-register">
                                <a href="#admin-authentication-POSTapi-v1-admin-register">Provision an admin</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-discovery" class="tocify-header">
                <li class="tocify-item level-1" data-unique="discovery">
                    <a href="#discovery">Discovery</a>
                </li>
                                    <ul id="tocify-subheader-discovery" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="discovery-GETapi-v1-talents">
                                <a href="#discovery-GETapi-v1-talents">List talents</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="discovery-GETapi-v1-talents--talent_slug-">
                                <a href="#discovery-GETapi-v1-talents--talent_slug-">Show a talent</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="discovery-GETapi-v1-talents--talent_slug--projects--project_id-">
                                <a href="#discovery-GETapi-v1-talents--talent_slug--projects--project_id-">Show a case study</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="discovery-POSTapi-v1-talents--talent_slug--reviews">
                                <a href="#discovery-POSTapi-v1-talents--talent_slug--reviews">Submit a review</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="discovery-POSTapi-v1-talents--talent_slug--enquiries">
                                <a href="#discovery-POSTapi-v1-talents--talent_slug--enquiries">Send a booking enquiry</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="discovery-GETapi-v1-brands">
                                <a href="#discovery-GETapi-v1-brands">List brands</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="discovery-GETapi-v1-brands--brand_slug-">
                                <a href="#discovery-GETapi-v1-brands--brand_slug-">Show a brand</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="discovery-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-">
                                <a href="#discovery-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-">Show a public campaign</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-deals" class="tocify-header">
                <li class="tocify-item level-1" data-unique="deals">
                    <a href="#deals">Deals</a>
                </li>
                                    <ul id="tocify-subheader-deals" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="deals-GETapi-v1-deals">
                                <a href="#deals-GETapi-v1-deals">List my deals</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="deals-GETapi-v1-deals--deal_id-">
                                <a href="#deals-GETapi-v1-deals--deal_id-">Show a deal</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-admin-lite" class="tocify-header">
                <li class="tocify-item level-1" data-unique="admin-lite">
                    <a href="#admin-lite">Admin (lite)</a>
                </li>
                                    <ul id="tocify-subheader-admin-lite" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="admin-lite-GETapi-v1-admin-overview">
                                <a href="#admin-lite-GETapi-v1-admin-overview">Governance overview</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="admin-lite-GETapi-v1-admin-activity">
                                <a href="#admin-lite-GETapi-v1-admin-activity">Recent activity</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-account" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-account">
                    <a href="#brand-account">Brand · Account</a>
                </li>
                                    <ul id="tocify-subheader-brand-account" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-account-GETapi-v1-brand-account">
                                <a href="#brand-account-GETapi-v1-brand-account">Get my account</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-account-PATCHapi-v1-brand-account">
                                <a href="#brand-account-PATCHapi-v1-brand-account">Update my account</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-account-PATCHapi-v1-brand-account-publish">
                                <a href="#brand-account-PATCHapi-v1-brand-account-publish">Publish / unpublish my profile</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-campaigns" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-campaigns">
                    <a href="#brand-campaigns">Brand · Campaigns</a>
                </li>
                                    <ul id="tocify-subheader-brand-campaigns" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-campaigns-GETapi-v1-brand-campaigns">
                                <a href="#brand-campaigns-GETapi-v1-brand-campaigns">List my campaigns</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-campaigns-POSTapi-v1-brand-campaigns">
                                <a href="#brand-campaigns-POSTapi-v1-brand-campaigns">Create a campaign</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-campaigns-GETapi-v1-brand-campaigns--campaign_id-">
                                <a href="#brand-campaigns-GETapi-v1-brand-campaigns--campaign_id-">Get a campaign</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-campaigns-PATCHapi-v1-brand-campaigns--campaign_id-">
                                <a href="#brand-campaigns-PATCHapi-v1-brand-campaigns--campaign_id-">Update a campaign</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-campaigns-PATCHapi-v1-brand-campaigns--campaign_id--status">
                                <a href="#brand-campaigns-PATCHapi-v1-brand-campaigns--campaign_id--status">Transition a campaign's status</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-campaigns-PATCHapi-v1-brand-campaigns--campaign_id--public">
                                <a href="#brand-campaigns-PATCHapi-v1-brand-campaigns--campaign_id--public">Set a campaign public / private</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-campaigns-POSTapi-v1-brand-campaigns--campaign_id--media">
                                <a href="#brand-campaigns-POSTapi-v1-brand-campaigns--campaign_id--media">Add campaign media</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-campaigns-DELETEapi-v1-brand-campaigns--campaign_id-">
                                <a href="#brand-campaigns-DELETEapi-v1-brand-campaigns--campaign_id-">Delete a campaign</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-creative-needs" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-creative-needs">
                    <a href="#brand-creative-needs">Brand · Creative needs</a>
                </li>
                                    <ul id="tocify-subheader-brand-creative-needs" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-creative-needs-GETapi-v1-brand-creative-needs">
                                <a href="#brand-creative-needs-GETapi-v1-brand-creative-needs">Get my creative needs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-creative-needs-PATCHapi-v1-brand-creative-needs">
                                <a href="#brand-creative-needs-PATCHapi-v1-brand-creative-needs">Update my creative needs</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-credibility" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-credibility">
                    <a href="#brand-credibility">Brand · Credibility</a>
                </li>
                                    <ul id="tocify-subheader-brand-credibility" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-credibility-GETapi-v1-brand-credibility">
                                <a href="#brand-credibility-GETapi-v1-brand-credibility">Get my credibility</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-deals" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-deals">
                    <a href="#brand-deals">Brand · Deals</a>
                </li>
                                    <ul id="tocify-subheader-brand-deals" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-deals-POSTapi-v1-brand-deals">
                                <a href="#brand-deals-POSTapi-v1-brand-deals">Start a deal</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-deals-GETapi-v1-brand-deals">
                                <a href="#brand-deals-GETapi-v1-brand-deals">List my deals</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-deals-GETapi-v1-brand-deals--deal_id-">
                                <a href="#brand-deals-GETapi-v1-brand-deals--deal_id-">Get a deal room</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-deals-POSTapi-v1-brand-deals--deal_id--advance">
                                <a href="#brand-deals-POSTapi-v1-brand-deals--deal_id--advance">Complete the current step</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-deals-POSTapi-v1-brand-deals--deal_id--reject">
                                <a href="#brand-deals-POSTapi-v1-brand-deals--deal_id--reject">Reject the current step</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-deals-POSTapi-v1-brand-deals--deal_id--skip">
                                <a href="#brand-deals-POSTapi-v1-brand-deals--deal_id--skip">Skip the current step (if skippable)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-deals-POSTapi-v1-brand-deals--deal_id--message">
                                <a href="#brand-deals-POSTapi-v1-brand-deals--deal_id--message">Post a message to the deal thread</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-discovery" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-discovery">
                    <a href="#brand-discovery">Brand · Discovery</a>
                </li>
                                    <ul id="tocify-subheader-brand-discovery" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-discovery-GETapi-v1-brand-discover">
                                <a href="#brand-discovery-GETapi-v1-brand-discover">Discovery feed</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-discovery-POSTapi-v1-brand-discover-save">
                                <a href="#brand-discovery-POSTapi-v1-brand-discover-save">Save a talent</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-discovery-POSTapi-v1-brand-discover-brief">
                                <a href="#brand-discovery-POSTapi-v1-brand-discover-brief">Send a brief to a talent</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-enquiries" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-enquiries">
                    <a href="#brand-enquiries">Brand · Enquiries</a>
                </li>
                                    <ul id="tocify-subheader-brand-enquiries" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-enquiries-GETapi-v1-brand-enquiries">
                                <a href="#brand-enquiries-GETapi-v1-brand-enquiries">List my pending enquiries</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-enquiries-POSTapi-v1-brand-enquiries--enquiry_id--convert">
                                <a href="#brand-enquiries-POSTapi-v1-brand-enquiries--enquiry_id--convert">Convert an enquiry to a deal</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-onboarding" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-onboarding">
                    <a href="#brand-onboarding">Brand · Onboarding</a>
                </li>
                                    <ul id="tocify-subheader-brand-onboarding" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-onboarding-GETapi-v1-brand-onboarding">
                                <a href="#brand-onboarding-GETapi-v1-brand-onboarding">Onboarding status</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-onboarding-POSTapi-v1-brand-onboarding-identity">
                                <a href="#brand-onboarding-POSTapi-v1-brand-onboarding-identity">Step 1 · Identity</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-onboarding-POSTapi-v1-brand-onboarding-location">
                                <a href="#brand-onboarding-POSTapi-v1-brand-onboarding-location">Step 2 · Location</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-onboarding-POSTapi-v1-brand-onboarding-creative-needs">
                                <a href="#brand-onboarding-POSTapi-v1-brand-onboarding-creative-needs">Step 3 · Creative needs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-onboarding-POSTapi-v1-brand-onboarding-aesthetic">
                                <a href="#brand-onboarding-POSTapi-v1-brand-onboarding-aesthetic">Step 4 · Aesthetic</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-onboarding-POSTapi-v1-brand-onboarding-budget">
                                <a href="#brand-onboarding-POSTapi-v1-brand-onboarding-budget">Step 5 · Budget</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-onboarding-POSTapi-v1-brand-onboarding-complete">
                                <a href="#brand-onboarding-POSTapi-v1-brand-onboarding-complete">Step 6 · Complete</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-profile" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-profile">
                    <a href="#brand-profile">Brand · Profile</a>
                </li>
                                    <ul id="tocify-subheader-brand-profile" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-profile-GETapi-v1-brand-profile">
                                <a href="#brand-profile-GETapi-v1-brand-profile">Get my profile</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-profile-PATCHapi-v1-brand-profile">
                                <a href="#brand-profile-PATCHapi-v1-brand-profile">Update my core profile</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-profile-POSTapi-v1-brand-profile-logo">
                                <a href="#brand-profile-POSTapi-v1-brand-profile-logo">Upload my logo</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-profile-POSTapi-v1-brand-profile-cover">
                                <a href="#brand-profile-POSTapi-v1-brand-profile-cover">Upload my cover image</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-profile-PATCHapi-v1-brand-profile-aesthetic">
                                <a href="#brand-profile-PATCHapi-v1-brand-profile-aesthetic">Update my aesthetic</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-profile-GETapi-v1-brand-profile-images">
                                <a href="#brand-profile-GETapi-v1-brand-profile-images">List my images</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-profile-POSTapi-v1-brand-profile-images">
                                <a href="#brand-profile-POSTapi-v1-brand-profile-images">Add an image</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-profile-DELETEapi-v1-brand-profile-images--image_id-">
                                <a href="#brand-profile-DELETEapi-v1-brand-profile-images--image_id-">Remove an image</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-profile-GETapi-v1-brand-social">
                                <a href="#brand-profile-GETapi-v1-brand-social">List my social handles</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-profile-POSTapi-v1-brand-social">
                                <a href="#brand-profile-POSTapi-v1-brand-social">Add a social handle</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="brand-profile-DELETEapi-v1-brand-social--handle_id-">
                                <a href="#brand-profile-DELETEapi-v1-brand-social--handle_id-">Remove a social handle</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-brand-reviews" class="tocify-header">
                <li class="tocify-item level-1" data-unique="brand-reviews">
                    <a href="#brand-reviews">Brand · Reviews</a>
                </li>
                                    <ul id="tocify-subheader-brand-reviews" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="brand-reviews-GETapi-v1-brand-reviews">
                                <a href="#brand-reviews-GETapi-v1-brand-reviews">List reviews received</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-notifications" class="tocify-header">
                <li class="tocify-item level-1" data-unique="notifications">
                    <a href="#notifications">Notifications</a>
                </li>
                                    <ul id="tocify-subheader-notifications" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="notifications-GETapi-v1-notifications">
                                <a href="#notifications-GETapi-v1-notifications">List my notifications</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-GETapi-v1-notifications-unread-count">
                                <a href="#notifications-GETapi-v1-notifications-unread-count">Unread count</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-POSTapi-v1-notifications-read-all">
                                <a href="#notifications-POSTapi-v1-notifications-read-all">Mark all read</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="notifications-POSTapi-v1-notifications--id--read">
                                <a href="#notifications-POSTapi-v1-notifications--id--read">Mark one read</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-reference-lookups" class="tocify-header">
                <li class="tocify-item level-1" data-unique="reference-lookups">
                    <a href="#reference-lookups">Reference / lookups</a>
                </li>
                                    <ul id="tocify-subheader-reference-lookups" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="reference-lookups-GETapi-v1-lookups-talent-types">
                                <a href="#reference-lookups-GETapi-v1-lookups-talent-types">Talent types</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="reference-lookups-GETapi-v1-lookups-block-types">
                                <a href="#reference-lookups-GETapi-v1-lookups-block-types">Block types</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="reference-lookups-GETapi-v1-lookups-deal-flows">
                                <a href="#reference-lookups-GETapi-v1-lookups-deal-flows">Deal flows</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="reference-lookups-GETapi-v1-lookups-options">
                                <a href="#reference-lookups-GETapi-v1-lookups-options">Option lists</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-account" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-account">
                    <a href="#talent-account">Talent · Account</a>
                </li>
                                    <ul id="tocify-subheader-talent-account" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-account-GETapi-v1-talent-account">
                                <a href="#talent-account-GETapi-v1-talent-account">Get my account</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-account-PATCHapi-v1-talent-account">
                                <a href="#talent-account-PATCHapi-v1-talent-account">Update my account</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-account-PATCHapi-v1-talent-account-publish">
                                <a href="#talent-account-PATCHapi-v1-talent-account-publish">Publish / unpublish my profile</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-affiliations" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-affiliations">
                    <a href="#talent-affiliations">Talent · Affiliations</a>
                </li>
                                    <ul id="tocify-subheader-talent-affiliations" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-affiliations-GETapi-v1-talent-affiliations">
                                <a href="#talent-affiliations-GETapi-v1-talent-affiliations">List my affiliations</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-affiliations-POSTapi-v1-talent-affiliations">
                                <a href="#talent-affiliations-POSTapi-v1-talent-affiliations">Add an affiliation</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-affiliations-PATCHapi-v1-talent-affiliations--affiliation_id-">
                                <a href="#talent-affiliations-PATCHapi-v1-talent-affiliations--affiliation_id-">Update an affiliation</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-affiliations-PATCHapi-v1-talent-affiliations--affiliation_id--end">
                                <a href="#talent-affiliations-PATCHapi-v1-talent-affiliations--affiliation_id--end">End an affiliation</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-affiliations-DELETEapi-v1-talent-affiliations--affiliation_id-">
                                <a href="#talent-affiliations-DELETEapi-v1-talent-affiliations--affiliation_id-">Remove an affiliation</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-availability" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-availability">
                    <a href="#talent-availability">Talent · Availability</a>
                </li>
                                    <ul id="tocify-subheader-talent-availability" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-availability-GETapi-v1-talent-availability">
                                <a href="#talent-availability-GETapi-v1-talent-availability">Get my availability</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-availability-PATCHapi-v1-talent-availability">
                                <a href="#talent-availability-PATCHapi-v1-talent-availability">Update my availability & travel</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-comp-card" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-comp-card">
                    <a href="#talent-comp-card">Talent · Comp card</a>
                </li>
                                    <ul id="tocify-subheader-talent-comp-card" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-comp-card-GETapi-v1-talent-comp-card">
                                <a href="#talent-comp-card-GETapi-v1-talent-comp-card">Get my comp card</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-comp-card-PUTapi-v1-talent-comp-card">
                                <a href="#talent-comp-card-PUTapi-v1-talent-comp-card">Create / update my comp card</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-comp-card-DELETEapi-v1-talent-comp-card">
                                <a href="#talent-comp-card-DELETEapi-v1-talent-comp-card">Delete my comp card</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-content" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-content">
                    <a href="#talent-content">Talent · Content</a>
                </li>
                                    <ul id="tocify-subheader-talent-content" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-content-GETapi-v1-talent-content--type-">
                                <a href="#talent-content-GETapi-v1-talent-content--type-">List content items</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-content-POSTapi-v1-talent-content--type-">
                                <a href="#talent-content-POSTapi-v1-talent-content--type-">Create a content item</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-content-PATCHapi-v1-talent-content--type--reorder">
                                <a href="#talent-content-PATCHapi-v1-talent-content--type--reorder">Reorder content items</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-content-POSTapi-v1-talent-content--type---id--media">
                                <a href="#talent-content-POSTapi-v1-talent-content--type---id--media">Upload media for a content item</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-content-PATCHapi-v1-talent-content--type---id-">
                                <a href="#talent-content-PATCHapi-v1-talent-content--type---id-">Update a content item</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-content-DELETEapi-v1-talent-content--type---id-">
                                <a href="#talent-content-DELETEapi-v1-talent-content--type---id-">Delete a content item</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-deals" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-deals">
                    <a href="#talent-deals">Talent · Deals</a>
                </li>
                                    <ul id="tocify-subheader-talent-deals" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-deals-GETapi-v1-talent-deals">
                                <a href="#talent-deals-GETapi-v1-talent-deals">List my deals</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-deals-GETapi-v1-talent-deals--deal_id-">
                                <a href="#talent-deals-GETapi-v1-talent-deals--deal_id-">Get a deal room</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-deals-POSTapi-v1-talent-deals--deal_id--advance">
                                <a href="#talent-deals-POSTapi-v1-talent-deals--deal_id--advance">Complete the current step</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-deals-POSTapi-v1-talent-deals--deal_id--reject">
                                <a href="#talent-deals-POSTapi-v1-talent-deals--deal_id--reject">Reject the current step</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-deals-POSTapi-v1-talent-deals--deal_id--skip">
                                <a href="#talent-deals-POSTapi-v1-talent-deals--deal_id--skip">Skip the current step (if skippable)</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-deals-POSTapi-v1-talent-deals--deal_id--message">
                                <a href="#talent-deals-POSTapi-v1-talent-deals--deal_id--message">Post a message to the deal thread</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-enquiries" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-enquiries">
                    <a href="#talent-enquiries">Talent · Enquiries</a>
                </li>
                                    <ul id="tocify-subheader-talent-enquiries" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-enquiries-GETapi-v1-talent-enquiries">
                                <a href="#talent-enquiries-GETapi-v1-talent-enquiries">List my enquiries</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-enquiries-GETapi-v1-talent-enquiries--enquiry_id-">
                                <a href="#talent-enquiries-GETapi-v1-talent-enquiries--enquiry_id-">Get an enquiry</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-press" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-press">
                    <a href="#talent-press">Talent · Press</a>
                </li>
                                    <ul id="tocify-subheader-talent-press" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-press-GETapi-v1-talent-press">
                                <a href="#talent-press-GETapi-v1-talent-press">List my press features</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-press-POSTapi-v1-talent-press">
                                <a href="#talent-press-POSTapi-v1-talent-press">Add a press feature</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-press-DELETEapi-v1-talent-press--press_id-">
                                <a href="#talent-press-DELETEapi-v1-talent-press--press_id-">Remove a press feature</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-professions" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-professions">
                    <a href="#talent-professions">Talent · Professions</a>
                </li>
                                    <ul id="tocify-subheader-talent-professions" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-professions-GETapi-v1-talent-professions">
                                <a href="#talent-professions-GETapi-v1-talent-professions">List my professions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-professions-POSTapi-v1-talent-professions">
                                <a href="#talent-professions-POSTapi-v1-talent-professions">Add a profession</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-professions-PATCHapi-v1-talent-professions-reorder">
                                <a href="#talent-professions-PATCHapi-v1-talent-professions-reorder">Reorder my professions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-professions-PATCHapi-v1-talent-professions--type_id--primary">
                                <a href="#talent-professions-PATCHapi-v1-talent-professions--type_id--primary">Set my primary profession</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-professions-DELETEapi-v1-talent-professions--type_id-">
                                <a href="#talent-professions-DELETEapi-v1-talent-professions--type_id-">Remove a profession</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-profile" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-profile">
                    <a href="#talent-profile">Talent · Profile</a>
                </li>
                                    <ul id="tocify-subheader-talent-profile" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-profile-GETapi-v1-talent-profile">
                                <a href="#talent-profile-GETapi-v1-talent-profile">Get my profile</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-profile-PATCHapi-v1-talent-profile">
                                <a href="#talent-profile-PATCHapi-v1-talent-profile">Update my core profile</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-profile-POSTapi-v1-talent-profile-hero">
                                <a href="#talent-profile-POSTapi-v1-talent-profile-hero">Upload my hero image</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-profile-GETapi-v1-talent-profile-blocks">
                                <a href="#talent-profile-GETapi-v1-talent-profile-blocks">List my profile blocks</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-profile-GETapi-v1-talent-profile-block-picker">
                                <a href="#talent-profile-GETapi-v1-talent-profile-block-picker">List addable block types</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-profile-POSTapi-v1-talent-profile-blocks">
                                <a href="#talent-profile-POSTapi-v1-talent-profile-blocks">Add a profile block</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-profile-PATCHapi-v1-talent-profile-blocks-reorder">
                                <a href="#talent-profile-PATCHapi-v1-talent-profile-blocks-reorder">Reorder my profile blocks</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-profile-PATCHapi-v1-talent-profile-blocks--block_id-">
                                <a href="#talent-profile-PATCHapi-v1-talent-profile-blocks--block_id-">Fill / edit a profile block</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-profile-PATCHapi-v1-talent-profile-blocks--block_id--visibility">
                                <a href="#talent-profile-PATCHapi-v1-talent-profile-blocks--block_id--visibility">Show / hide a profile block</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-profile-DELETEapi-v1-talent-profile-blocks--block_id-">
                                <a href="#talent-profile-DELETEapi-v1-talent-profile-blocks--block_id-">Remove a profile block</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-reviews" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-reviews">
                    <a href="#talent-reviews">Talent · Reviews</a>
                </li>
                                    <ul id="tocify-subheader-talent-reviews" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-reviews-GETapi-v1-talent-reviews">
                                <a href="#talent-reviews-GETapi-v1-talent-reviews">List my reviews</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-reviews-PATCHapi-v1-talent-reviews--review_id--approve">
                                <a href="#talent-reviews-PATCHapi-v1-talent-reviews--review_id--approve">Approve a review</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-reviews-PATCHapi-v1-talent-reviews--review_id--reject">
                                <a href="#talent-reviews-PATCHapi-v1-talent-reviews--review_id--reject">Reject a review</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-talent-services" class="tocify-header">
                <li class="tocify-item level-1" data-unique="talent-services">
                    <a href="#talent-services">Talent · Services</a>
                </li>
                                    <ul id="tocify-subheader-talent-services" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="talent-services-GETapi-v1-talent-services">
                                <a href="#talent-services-GETapi-v1-talent-services">List my services</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-services-POSTapi-v1-talent-services">
                                <a href="#talent-services-POSTapi-v1-talent-services">Add a service</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-services-PATCHapi-v1-talent-services--service_id-">
                                <a href="#talent-services-PATCHapi-v1-talent-services--service_id-">Update a service</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-services-PATCHapi-v1-talent-services--service_id--toggle">
                                <a href="#talent-services-PATCHapi-v1-talent-services--service_id--toggle">Pause / activate a service</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="talent-services-DELETEapi-v1-talent-services--service_id-">
                                <a href="#talent-services-DELETEapi-v1-talent-services--service_id-">Remove a service</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: July 10, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<p>The Fama mobile API (v1) — token-authenticated endpoints for talents, brands and admins, sharing the same services and JSON envelope as the web app.</p>
<aside>
    <strong>Base URL</strong>: <code>https://fama.test</code>
</aside>
<pre><code>All endpoints live under `/api/v1` and return Fama's single JSON envelope:

```json
{ "success": true, "data": {}, "message": null, "errors": null, "meta": null }
```

Paginated lists put page info at `meta.pagination`. Send `Accept-Language: ar` (or `en`)
to receive translatable fields in that locale. Authenticate by sending the bearer token
returned from a login/register endpoint as `Authorization: Bearer {token}`.</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>To authenticate requests, include an <strong><code>Authorization</code></strong> header with the value <strong><code>"Bearer {YOUR_TOKEN}"</code></strong>.</p>
<p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation below.</p>
<p>Obtain a token from one of the <b>login</b> or <b>register</b> endpoints, then send it as <code>Authorization: Bearer {token}</code>. Tokens are ability-scoped to the entity (talent / brand / admin) they belong to.</p>

        <h1 id="talent-authentication">Talent authentication</h1>

    <p>Token auth for the <code>talent</code> guard: public sign-up plus the shared
login/logout/refresh/me flow. Issued tokens carry the <code>talent</code> ability.</p>

                                <h2 id="talent-authentication-POSTapi-v1-talent-register">Register a talent</h2>

<p>
</p>

<p>Create a new talent account and return an ability-scoped token. The
profile starts unpublished; the public slug is generated by the model.</p>

<span id="example-requests-POSTapi-v1-talent-register">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/register" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"display_name\": \"b\",
    \"email\": \"zbailey@example.net\",
    \"password\": \"architecto\",
    \"device_name\": \"n\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/register"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "display_name": "b",
    "email": "zbailey@example.net",
    "password": "architecto",
    "device_name": "n"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-register">
            <blockquote>
            <p>Example response (201, Created):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;token&quot;: &quot;1|xxxx&quot;,
        &quot;token_type&quot;: &quot;Bearer&quot;,
        &quot;abilities&quot;: [
            &quot;talent&quot;
        ],
        &quot;talent&quot;: {
            &quot;id&quot;: 1,
            &quot;slug&quot;: &quot;amgad-emad-ab12cd&quot;,
            &quot;display_name&quot;: &quot;Amgad Emad&quot;
        }
    },
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-talent-register" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-register"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-register"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-register" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-register">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-register" data-method="POST"
      data-path="api/v1/talent/register"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-register', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-register"
                    onclick="tryItOut('POSTapi-v1-talent-register');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-register"
                    onclick="cancelTryOut('POSTapi-v1-talent-register');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-register"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/register</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>display_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="display_name"                data-endpoint="POSTapi-v1-talent-register"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-talent-register"
               value="zbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>zbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-talent-register"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-talent-register"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
        </div>
        </form>

                    <h2 id="talent-authentication-POSTapi-v1-talent-login">Authenticate credentials and issue an ability-scoped token.</h2>

<p>
</p>

<p>Throttled by the route middleware; credentials are verified against the
entity model (constant-time hash check) with a single generic error to
avoid leaking which half was wrong.</p>

<span id="example-requests-POSTapi-v1-talent-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"gbailey@example.net\",
    \"password\": \"|]|{+-\",
    \"device_name\": \"v\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "gbailey@example.net",
    "password": "|]|{+-",
    "device_name": "v"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-login">
</span>
<span id="execution-results-POSTapi-v1-talent-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-login" data-method="POST"
      data-path="api/v1/talent/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-login"
                    onclick="tryItOut('POSTapi-v1-talent-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-login"
                    onclick="cancelTryOut('POSTapi-v1-talent-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-talent-login"
               value="gbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>gbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-talent-login"
               value="|]|{+-"
               data-component="body">
    <br>
<p>Example: <code>|]|{+-</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-talent-login"
               value="v"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>v</code></p>
        </div>
        </form>

                    <h2 id="talent-authentication-GETapi-v1-talent-me">Return the authenticated entity (the token&#039;s tokenable).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-talent-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/me" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/me"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-me">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-me" data-method="GET"
      data-path="api/v1/talent/me"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-me"
                    onclick="tryItOut('GETapi-v1-talent-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-me"
                    onclick="cancelTryOut('GETapi-v1-talent-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-me"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-authentication-POSTapi-v1-talent-refresh">Rotate the caller&#039;s token: revoke the one presented and issue a fresh
ability-scoped replacement. Sanctum tokens don&#039;t expire on their own, so
rotation is how a client renews a credential it wants to cycle.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-refresh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/refresh" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/refresh"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-refresh">
</span>
<span id="execution-results-POSTapi-v1-talent-refresh" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-refresh"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-refresh"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-refresh" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-refresh">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-refresh" data-method="POST"
      data-path="api/v1/talent/refresh"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-refresh', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-refresh"
                    onclick="tryItOut('POSTapi-v1-talent-refresh');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-refresh"
                    onclick="cancelTryOut('POSTapi-v1-talent-refresh');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-refresh"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/refresh</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-refresh"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-authentication-POSTapi-v1-talent-logout">Revoke the token presented with the request (single-device logout).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/logout" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/logout"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-logout">
</span>
<span id="execution-results-POSTapi-v1-talent-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-logout" data-method="POST"
      data-path="api/v1/talent/logout"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-logout"
                    onclick="tryItOut('POSTapi-v1-talent-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-logout"
                    onclick="cancelTryOut('POSTapi-v1-talent-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-logout"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="brand-authentication">Brand authentication</h1>

    <p>Token auth for the <code>brand</code> guard: public sign-up plus the shared
login/logout/refresh/me flow. Issued tokens carry the <code>brand</code> ability.</p>

                                <h2 id="brand-authentication-POSTapi-v1-brand-register">Register a brand</h2>

<p>
</p>

<p>Create a new brand account and return an ability-scoped token. The account
starts incomplete (onboarding gates the discovery feed) and unpublished.</p>

<span id="example-requests-POSTapi-v1-brand-register">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/register" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"b\",
    \"email\": \"zbailey@example.net\",
    \"password\": \"architecto\",
    \"device_name\": \"n\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/register"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "b",
    "email": "zbailey@example.net",
    "password": "architecto",
    "device_name": "n"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-register">
            <blockquote>
            <p>Example response (201, Created):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;token&quot;: &quot;1|xxxx&quot;,
        &quot;token_type&quot;: &quot;Bearer&quot;,
        &quot;abilities&quot;: [
            &quot;brand&quot;
        ],
        &quot;brand&quot;: {
            &quot;id&quot;: 1,
            &quot;slug&quot;: &quot;nomad-coffee-ab12cd&quot;,
            &quot;name&quot;: &quot;Nomad Coffee&quot;
        }
    },
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-brand-register" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-register"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-register"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-register" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-register">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-register" data-method="POST"
      data-path="api/v1/brand/register"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-register', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-register"
                    onclick="tryItOut('POSTapi-v1-brand-register');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-register"
                    onclick="cancelTryOut('POSTapi-v1-brand-register');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-register"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/register</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-brand-register"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-brand-register"
               value="zbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>zbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-brand-register"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-brand-register"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
        </div>
        </form>

                    <h2 id="brand-authentication-POSTapi-v1-brand-login">Authenticate credentials and issue an ability-scoped token.</h2>

<p>
</p>

<p>Throttled by the route middleware; credentials are verified against the
entity model (constant-time hash check) with a single generic error to
avoid leaking which half was wrong.</p>

<span id="example-requests-POSTapi-v1-brand-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"gbailey@example.net\",
    \"password\": \"|]|{+-\",
    \"device_name\": \"v\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "gbailey@example.net",
    "password": "|]|{+-",
    "device_name": "v"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-login">
</span>
<span id="execution-results-POSTapi-v1-brand-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-login" data-method="POST"
      data-path="api/v1/brand/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-login"
                    onclick="tryItOut('POSTapi-v1-brand-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-login"
                    onclick="cancelTryOut('POSTapi-v1-brand-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-brand-login"
               value="gbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>gbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-brand-login"
               value="|]|{+-"
               data-component="body">
    <br>
<p>Example: <code>|]|{+-</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-brand-login"
               value="v"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>v</code></p>
        </div>
        </form>

                    <h2 id="brand-authentication-GETapi-v1-brand-me">Return the authenticated entity (the token&#039;s tokenable).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-brand-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/me" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/me"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-me">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-me" data-method="GET"
      data-path="api/v1/brand/me"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-me"
                    onclick="tryItOut('GETapi-v1-brand-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-me"
                    onclick="cancelTryOut('GETapi-v1-brand-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-me"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-authentication-POSTapi-v1-brand-refresh">Rotate the caller&#039;s token: revoke the one presented and issue a fresh
ability-scoped replacement. Sanctum tokens don&#039;t expire on their own, so
rotation is how a client renews a credential it wants to cycle.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-refresh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/refresh" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/refresh"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-refresh">
</span>
<span id="execution-results-POSTapi-v1-brand-refresh" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-refresh"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-refresh"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-refresh" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-refresh">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-refresh" data-method="POST"
      data-path="api/v1/brand/refresh"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-refresh', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-refresh"
                    onclick="tryItOut('POSTapi-v1-brand-refresh');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-refresh"
                    onclick="cancelTryOut('POSTapi-v1-brand-refresh');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-refresh"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/refresh</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-refresh"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-authentication-POSTapi-v1-brand-logout">Revoke the token presented with the request (single-device logout).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/logout" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/logout"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-logout">
</span>
<span id="execution-results-POSTapi-v1-brand-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-logout" data-method="POST"
      data-path="api/v1/brand/logout"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-logout"
                    onclick="tryItOut('POSTapi-v1-brand-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-logout"
                    onclick="cancelTryOut('POSTapi-v1-brand-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-logout"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="admin-authentication">Admin authentication</h1>

    <p>Token auth for the <code>admin</code> (staff) guard: login/logout/refresh/me plus
provisioning of new staff. Admin tokens carry the <code>admin</code> ability AND the
admin's granular spatie permissions (manage-flows, moderate-content, …) as
abilities, so future admin API routes can gate with <code>abilities:&lt;permission&gt;</code>.
There is deliberately no public admin sign-up — staff are provisioned by an
existing admin holding <code>manage-users</code>.</p>

                                <h2 id="admin-authentication-POSTapi-v1-admin-login">Authenticate credentials and issue an ability-scoped token.</h2>

<p>
</p>

<p>Throttled by the route middleware; credentials are verified against the
entity model (constant-time hash check) with a single generic error to
avoid leaking which half was wrong.</p>

<span id="example-requests-POSTapi-v1-admin-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/admin/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"gbailey@example.net\",
    \"password\": \"|]|{+-\",
    \"device_name\": \"v\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/admin/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "gbailey@example.net",
    "password": "|]|{+-",
    "device_name": "v"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-login">
</span>
<span id="execution-results-POSTapi-v1-admin-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-login" data-method="POST"
      data-path="api/v1/admin/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-login"
                    onclick="tryItOut('POSTapi-v1-admin-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-login"
                    onclick="cancelTryOut('POSTapi-v1-admin-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-admin-login"
               value="gbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>gbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-admin-login"
               value="|]|{+-"
               data-component="body">
    <br>
<p>Example: <code>|]|{+-</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-admin-login"
               value="v"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>v</code></p>
        </div>
        </form>

                    <h2 id="admin-authentication-GETapi-v1-admin-me">Return the authenticated entity (the token&#039;s tokenable).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-admin-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/admin/me" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/admin/me"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-me">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-admin-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-me" data-method="GET"
      data-path="api/v1/admin/me"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-me"
                    onclick="tryItOut('GETapi-v1-admin-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-me"
                    onclick="cancelTryOut('GETapi-v1-admin-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-admin-me"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="admin-authentication-POSTapi-v1-admin-refresh">Rotate the caller&#039;s token: revoke the one presented and issue a fresh
ability-scoped replacement. Sanctum tokens don&#039;t expire on their own, so
rotation is how a client renews a credential it wants to cycle.</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-admin-refresh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/admin/refresh" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/admin/refresh"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-refresh">
</span>
<span id="execution-results-POSTapi-v1-admin-refresh" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-refresh"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-refresh"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-refresh" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-refresh">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-refresh" data-method="POST"
      data-path="api/v1/admin/refresh"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-refresh', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-refresh"
                    onclick="tryItOut('POSTapi-v1-admin-refresh');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-refresh"
                    onclick="cancelTryOut('POSTapi-v1-admin-refresh');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-refresh"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/refresh</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-admin-refresh"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="admin-authentication-POSTapi-v1-admin-logout">Revoke the token presented with the request (single-device logout).</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-admin-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/admin/logout" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/admin/logout"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-logout">
</span>
<span id="execution-results-POSTapi-v1-admin-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-logout" data-method="POST"
      data-path="api/v1/admin/logout"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-logout"
                    onclick="tryItOut('POSTapi-v1-admin-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-logout"
                    onclick="cancelTryOut('POSTapi-v1-admin-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-admin-logout"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="admin-authentication-POSTapi-v1-admin-register">Provision an admin</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Create a new staff account with optional roles. Restricted to an
authenticated admin holding <code>manage-users</code>; audited with the acting admin
as causer. Returns the created admin (no token — the new admin logs in
themselves).</p>

<span id="example-requests-POSTapi-v1-admin-register">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/admin/register" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": \"b\",
    \"email\": \"zbailey@example.net\",
    \"password\": \"architecto\",
    \"roles\": [
        \"architecto\"
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/admin/register"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": "b",
    "email": "zbailey@example.net",
    "password": "architecto",
    "roles": [
        "architecto"
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-register">
            <blockquote>
            <p>Example response (201, Created):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 2,
        &quot;name&quot;: &quot;Mod&quot;,
        &quot;email&quot;: &quot;mod@fama.test&quot;,
        &quot;roles&quot;: [
            &quot;moderator&quot;
        ]
    },
    &quot;message&quot;: &quot;Admin created.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-admin-register" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-register"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-register"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-register" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-register">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-register" data-method="POST"
      data-path="api/v1/admin/register"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-register', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-register"
                    onclick="tryItOut('POSTapi-v1-admin-register');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-register"
                    onclick="cancelTryOut('POSTapi-v1-admin-register');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-register"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/register</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-admin-register"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-register"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-admin-register"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-admin-register"
               value="zbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>zbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-admin-register"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>roles</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="roles[0]"                data-endpoint="POSTapi-v1-admin-register"
               data-component="body">
        <input type="text" style="display: none"
               name="roles[1]"                data-endpoint="POSTapi-v1-admin-register"
               data-component="body">
    <br>
<p>Must match an existing stored value.</p>
        </div>
        </form>

                <h1 id="discovery">Discovery</h1>

    <p>Public, read-only talent discovery for the mobile app plus the two public
write actions a visitor can take on a profile (leave a review, send a booking
enquiry) — the same query, services and validation the web pages use.
Published talents only.</p>

                                <h2 id="discovery-GETapi-v1-talents">List talents</h2>

<p>
</p>

<p>Paginated, filterable discovery feed. Supply <code>filter[...]</code> params
(type, category, availability, city, country, equipment, software, q) and
<code>sort</code> (view_count, created_at) exactly as the web feed does.</p>

<span id="example-requests-GETapi-v1-talents">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talents?filter%5Btype%5D=photographer&amp;filter%5Bcity%5D=Cairo&amp;filter%5Bavailability%5D=available&amp;filter%5Bq%5D=amgad&amp;sort=-view_count&amp;page=1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talents"
);

const params = {
    "filter[type]": "photographer",
    "filter[city]": "Cairo",
    "filter[availability]": "available",
    "filter[q]": "amgad",
    "sort": "-view_count",
    "page": "1",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talents">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-language: en
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 11,
            &quot;slug&quot;: &quot;adham-yousef&quot;,
            &quot;display_name&quot;: &quot;Adham Yousef&quot;,
            &quot;headline&quot;: &quot;Model &amp; creative director &mdash; Giza&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/59/fama_23df95601ca70b95e05e2478c1a7e9ef.jpg&quot;,
            &quot;city&quot;: &quot;Giza&quot;,
            &quot;country&quot;: &quot;Egypt&quot;,
            &quot;availability&quot;: &quot;available&quot;,
            &quot;view_count&quot;: 4894,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;model&quot;,
                &quot;name&quot;: &quot;Model&quot;,
                &quot;category&quot;: &quot;model&quot;
            }
        },
        {
            &quot;id&quot;: 8,
            &quot;slug&quot;: &quot;farida-nabil&quot;,
            &quot;display_name&quot;: &quot;Farida Nabil&quot;,
            &quot;headline&quot;: &quot;Commercial model &mdash; Alexandria&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/43/fama_a5ba6cf9e24223bc005ee9c156f6362f.jpg&quot;,
            &quot;city&quot;: &quot;Alexandria&quot;,
            &quot;country&quot;: &quot;Egypt&quot;,
            &quot;availability&quot;: &quot;booked&quot;,
            &quot;view_count&quot;: 4759,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;model&quot;,
                &quot;name&quot;: &quot;Model&quot;,
                &quot;category&quot;: &quot;model&quot;
            }
        },
        {
            &quot;id&quot;: 5,
            &quot;slug&quot;: &quot;omar-khaled&quot;,
            &quot;display_name&quot;: &quot;Omar Khaled&quot;,
            &quot;headline&quot;: &quot;Cinematographer / DOP &mdash; Giza&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/27/fama_c2262b1fb08ddc053d60f43edbdddb77.jpg&quot;,
            &quot;city&quot;: &quot;Giza&quot;,
            &quot;country&quot;: &quot;Egypt&quot;,
            &quot;availability&quot;: &quot;available&quot;,
            &quot;view_count&quot;: 3693,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;cinematographer&quot;,
                &quot;name&quot;: &quot;Cinematographer (DOP)&quot;,
                &quot;category&quot;: &quot;crew&quot;
            }
        },
        {
            &quot;id&quot;: 3,
            &quot;slug&quot;: &quot;karim-mansour&quot;,
            &quot;display_name&quot;: &quot;Karim Mansour&quot;,
            &quot;headline&quot;: &quot;Fashion &amp; commercial photographer &mdash; Alexandria&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/16/fama_8dce61690bc37a9e8e27dc24c2a8b102.jpg&quot;,
            &quot;city&quot;: &quot;Alexandria&quot;,
            &quot;country&quot;: &quot;Egypt&quot;,
            &quot;availability&quot;: &quot;available&quot;,
            &quot;view_count&quot;: 2942,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;photographer&quot;,
                &quot;name&quot;: &quot;Photographer&quot;,
                &quot;category&quot;: &quot;crew&quot;
            }
        },
        {
            &quot;id&quot;: 2,
            &quot;slug&quot;: &quot;nour-elsherif&quot;,
            &quot;display_name&quot;: &quot;Nour El-Sherif&quot;,
            &quot;headline&quot;: &quot;Editorial &amp; runway model &mdash; Cairo&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/10/fama_bb26d0eea69aa81f0d000dcd5ef700a4.jpg&quot;,
            &quot;city&quot;: &quot;Cairo&quot;,
            &quot;country&quot;: &quot;Egypt&quot;,
            &quot;availability&quot;: &quot;available&quot;,
            &quot;view_count&quot;: 2489,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;model&quot;,
                &quot;name&quot;: &quot;Model&quot;,
                &quot;category&quot;: &quot;model&quot;
            }
        },
        {
            &quot;id&quot;: 1,
            &quot;slug&quot;: &quot;demo-talent&quot;,
            &quot;display_name&quot;: &quot;Layla Hassan&quot;,
            &quot;headline&quot;: &quot;Model &amp; Photographer &mdash; Cairo&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/2/fama_452592f6ef73ef3537814895ad921aea.jpg&quot;,
            &quot;city&quot;: &quot;Cairo&quot;,
            &quot;country&quot;: &quot;Egypt&quot;,
            &quot;availability&quot;: &quot;available&quot;,
            &quot;view_count&quot;: 2449,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;model&quot;,
                &quot;name&quot;: &quot;Model&quot;,
                &quot;category&quot;: &quot;model&quot;
            }
        },
        {
            &quot;id&quot;: 9,
            &quot;slug&quot;: &quot;tarek-sobhy&quot;,
            &quot;display_name&quot;: &quot;Tarek Sobhy&quot;,
            &quot;headline&quot;: &quot;Photographer &amp; DOP &mdash; Cairo&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/48/fama_2b74c7e3e7b9092d50871144e50c6364.jpg&quot;,
            &quot;city&quot;: &quot;Cairo&quot;,
            &quot;country&quot;: &quot;Egypt&quot;,
            &quot;availability&quot;: &quot;available&quot;,
            &quot;view_count&quot;: 2241,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;photographer&quot;,
                &quot;name&quot;: &quot;Photographer&quot;,
                &quot;category&quot;: &quot;crew&quot;
            }
        },
        {
            &quot;id&quot;: 7,
            &quot;slug&quot;: &quot;ziad-rahman&quot;,
            &quot;display_name&quot;: &quot;Ziad Rahman&quot;,
            &quot;headline&quot;: &quot;Graphic designer &mdash; Cairo&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/38/fama_8289292746bec7c9e10d7bff7b684e7a.jpg&quot;,
            &quot;city&quot;: &quot;Cairo&quot;,
            &quot;country&quot;: &quot;Egypt&quot;,
            &quot;availability&quot;: &quot;available&quot;,
            &quot;view_count&quot;: 1886,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;graphic-designer&quot;,
                &quot;name&quot;: &quot;Graphic Designer&quot;,
                &quot;category&quot;: &quot;creative&quot;
            }
        },
        {
            &quot;id&quot;: 6,
            &quot;slug&quot;: &quot;hana-fahmy&quot;,
            &quot;display_name&quot;: &quot;Hana Fahmy&quot;,
            &quot;headline&quot;: &quot;Creative director &mdash; Dubai&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/33/fama_60c7821fdbb46d3d3745c262079b366c.jpg&quot;,
            &quot;city&quot;: &quot;Dubai&quot;,
            &quot;country&quot;: &quot;UAE&quot;,
            &quot;availability&quot;: &quot;available&quot;,
            &quot;view_count&quot;: 1434,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;creative-director&quot;,
                &quot;name&quot;: &quot;Creative Director&quot;,
                &quot;category&quot;: &quot;creative&quot;
            }
        },
        {
            &quot;id&quot;: 4,
            &quot;slug&quot;: &quot;yasmin-adel&quot;,
            &quot;display_name&quot;: &quot;Yasmin Adel&quot;,
            &quot;headline&quot;: &quot;Model &amp; stylist &mdash; Cairo&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/22/fama_f6b78ebcdedbf4990a3b07b1384067ec.jpg&quot;,
            &quot;city&quot;: &quot;Cairo&quot;,
            &quot;country&quot;: &quot;Egypt&quot;,
            &quot;availability&quot;: &quot;booked&quot;,
            &quot;view_count&quot;: 1370,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;model&quot;,
                &quot;name&quot;: &quot;Model&quot;,
                &quot;category&quot;: &quot;model&quot;
            }
        },
        {
            &quot;id&quot;: 10,
            &quot;slug&quot;: &quot;salma-ibrahim&quot;,
            &quot;display_name&quot;: &quot;Salma Ibrahim&quot;,
            &quot;headline&quot;: &quot;Fashion stylist &mdash; Cairo&quot;,
            &quot;avatar_url&quot;: &quot;https://fama.test/storage/54/fama_eab3b627b8acc1d1de623d08835247dc.jpg&quot;,
            &quot;city&quot;: &quot;Cairo&quot;,
            &quot;country&quot;: &quot;Egypt&quot;,
            &quot;availability&quot;: &quot;unavailable&quot;,
            &quot;view_count&quot;: 1210,
            &quot;primary_type&quot;: {
                &quot;slug&quot;: &quot;stylist&quot;,
                &quot;name&quot;: &quot;Stylist&quot;,
                &quot;category&quot;: &quot;creative&quot;
            }
        }
    ],
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: {
        &quot;pagination&quot;: {
            &quot;current_page&quot;: 1,
            &quot;last_page&quot;: 1,
            &quot;per_page&quot;: 12,
            &quot;total&quot;: 11,
            &quot;from&quot;: 1,
            &quot;to&quot;: 11
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talents" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talents"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talents"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talents" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talents">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talents" data-method="GET"
      data-path="api/v1/talents"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talents', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talents"
                    onclick="tryItOut('GETapi-v1-talents');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talents"
                    onclick="cancelTryOut('GETapi-v1-talents');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talents"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talents</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talents"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talents"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[type]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[type]"                data-endpoint="GETapi-v1-talents"
               value="photographer"
               data-component="query">
    <br>
<p>Comma-separated profession slugs. Example: <code>photographer</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[city]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[city]"                data-endpoint="GETapi-v1-talents"
               value="Cairo"
               data-component="query">
    <br>
<p>Partial city match. Example: <code>Cairo</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[availability]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[availability]"                data-endpoint="GETapi-v1-talents"
               value="available"
               data-component="query">
    <br>
<p>One of available, booked, unavailable. Example: <code>available</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[q]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[q]"                data-endpoint="GETapi-v1-talents"
               value="amgad"
               data-component="query">
    <br>
<p>Free-text display-name match. Example: <code>amgad</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>sort</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sort"                data-endpoint="GETapi-v1-talents"
               value="-view_count"
               data-component="query">
    <br>
<p>view_count or created_at (prefix with - for descending). Example: <code>-view_count</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-talents"
               value="1"
               data-component="query">
    <br>
<p>The page number. Example: <code>1</code></p>
            </div>
                </form>

                    <h2 id="discovery-GETapi-v1-talents--talent_slug-">Show a talent</h2>

<p>
</p>

<p>The full public passport for one published talent, resolved by slug: the
visible profile blocks, profession(s), comp card, rate-card services and
approved reviews. Translatable fields come back in the request locale
(Accept-Language). Bumps the profile view counter.</p>

<span id="example-requests-GETapi-v1-talents--talent_slug-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talents/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talents/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talents--talent_slug-">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Resource not found.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talents--talent_slug-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talents--talent_slug-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talents--talent_slug-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talents--talent_slug-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talents--talent_slug-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talents--talent_slug-" data-method="GET"
      data-path="api/v1/talents/{talent_slug}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talents--talent_slug-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talents--talent_slug-"
                    onclick="tryItOut('GETapi-v1-talents--talent_slug-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talents--talent_slug-"
                    onclick="cancelTryOut('GETapi-v1-talents--talent_slug-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talents--talent_slug-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talents/{talent_slug}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talents--talent_slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talents--talent_slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>talent_slug</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="talent_slug"                data-endpoint="GETapi-v1-talents--talent_slug-"
               value="1"
               data-component="url">
    <br>
<p>The slug of the talent. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="discovery-GETapi-v1-talents--talent_slug--projects--project_id-">Show a case study</h2>

<p>
</p>

<p>One published talent's project (case study), resolved by id and scoped to
the talent in the path (404 otherwise).</p>

<span id="example-requests-GETapi-v1-talents--talent_slug--projects--project_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talents/1/projects/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talents/1/projects/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talents--talent_slug--projects--project_id-">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Resource not found.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talents--talent_slug--projects--project_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talents--talent_slug--projects--project_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talents--talent_slug--projects--project_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talents--talent_slug--projects--project_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talents--talent_slug--projects--project_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talents--talent_slug--projects--project_id-" data-method="GET"
      data-path="api/v1/talents/{talent_slug}/projects/{project_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talents--talent_slug--projects--project_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talents--talent_slug--projects--project_id-"
                    onclick="tryItOut('GETapi-v1-talents--talent_slug--projects--project_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talents--talent_slug--projects--project_id-"
                    onclick="cancelTryOut('GETapi-v1-talents--talent_slug--projects--project_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talents--talent_slug--projects--project_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talents/{talent_slug}/projects/{project_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talents--talent_slug--projects--project_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talents--talent_slug--projects--project_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>talent_slug</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="talent_slug"                data-endpoint="GETapi-v1-talents--talent_slug--projects--project_id-"
               value="1"
               data-component="url">
    <br>
<p>The slug of the talent. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>project_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="project_id"                data-endpoint="GETapi-v1-talents--talent_slug--projects--project_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the project. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="discovery-POSTapi-v1-talents--talent_slug--reviews">Submit a review</h2>

<p>
</p>

<p>A past client leaves a review on a published talent; it lands pending for
the talent to moderate.</p>

<span id="example-requests-POSTapi-v1-talents--talent_slug--reviews">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talents/1/reviews" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reviewer_name\": \"b\",
    \"reviewer_role\": \"n\",
    \"reviewer_company\": \"g\",
    \"rating\": 2,
    \"body\": \"m\",
    \"project_type\": \"i\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talents/1/reviews"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reviewer_name": "b",
    "reviewer_role": "n",
    "reviewer_company": "g",
    "rating": 2,
    "body": "m",
    "project_type": "i"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talents--talent_slug--reviews">
</span>
<span id="execution-results-POSTapi-v1-talents--talent_slug--reviews" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talents--talent_slug--reviews"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talents--talent_slug--reviews"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talents--talent_slug--reviews" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talents--talent_slug--reviews">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talents--talent_slug--reviews" data-method="POST"
      data-path="api/v1/talents/{talent_slug}/reviews"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talents--talent_slug--reviews', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talents--talent_slug--reviews"
                    onclick="tryItOut('POSTapi-v1-talents--talent_slug--reviews');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talents--talent_slug--reviews"
                    onclick="cancelTryOut('POSTapi-v1-talents--talent_slug--reviews');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talents--talent_slug--reviews"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talents/{talent_slug}/reviews</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talents--talent_slug--reviews"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talents--talent_slug--reviews"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>talent_slug</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="talent_slug"                data-endpoint="POSTapi-v1-talents--talent_slug--reviews"
               value="1"
               data-component="url">
    <br>
<p>The slug of the talent. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reviewer_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reviewer_name"                data-endpoint="POSTapi-v1-talents--talent_slug--reviews"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reviewer_role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reviewer_role"                data-endpoint="POSTapi-v1-talents--talent_slug--reviews"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reviewer_company</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reviewer_company"                data-endpoint="POSTapi-v1-talents--talent_slug--reviews"
               value="g"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>g</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>rating</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="rating"                data-endpoint="POSTapi-v1-talents--talent_slug--reviews"
               value="2"
               data-component="body">
    <br>
<p>Must be between 1 and 5. Example: <code>2</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="POSTapi-v1-talents--talent_slug--reviews"
               value="m"
               data-component="body">
    <br>
<p>Must not be greater than 5000 characters. Example: <code>m</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>project_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="project_type"                data-endpoint="POSTapi-v1-talents--talent_slug--reviews"
               value="i"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>i</code></p>
        </div>
        </form>

                    <h2 id="discovery-POSTapi-v1-talents--talent_slug--enquiries">Send a booking enquiry</h2>

<p>
</p>

<p>The public booking CTA — lands in <code>deal_enquiries</code> (availability-checked)
and converts to a deal once a brand picks it up.</p>

<span id="example-requests-POSTapi-v1-talents--talent_slug--enquiries">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talents/1/enquiries" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"service_id\": 16,
    \"contact_name\": \"n\",
    \"contact_email\": \"ashly64@example.com\",
    \"contact_company\": \"v\",
    \"brief\": \"d\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talents/1/enquiries"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "service_id": 16,
    "contact_name": "n",
    "contact_email": "ashly64@example.com",
    "contact_company": "v",
    "brief": "d"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talents--talent_slug--enquiries">
</span>
<span id="execution-results-POSTapi-v1-talents--talent_slug--enquiries" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talents--talent_slug--enquiries"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talents--talent_slug--enquiries"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talents--talent_slug--enquiries" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talents--talent_slug--enquiries">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talents--talent_slug--enquiries" data-method="POST"
      data-path="api/v1/talents/{talent_slug}/enquiries"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talents--talent_slug--enquiries', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talents--talent_slug--enquiries"
                    onclick="tryItOut('POSTapi-v1-talents--talent_slug--enquiries');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talents--talent_slug--enquiries"
                    onclick="cancelTryOut('POSTapi-v1-talents--talent_slug--enquiries');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talents--talent_slug--enquiries"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talents/{talent_slug}/enquiries</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talents--talent_slug--enquiries"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talents--talent_slug--enquiries"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>talent_slug</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="talent_slug"                data-endpoint="POSTapi-v1-talents--talent_slug--enquiries"
               value="1"
               data-component="url">
    <br>
<p>The slug of the talent. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>service_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="service_id"                data-endpoint="POSTapi-v1-talents--talent_slug--enquiries"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>contact_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact_name"                data-endpoint="POSTapi-v1-talents--talent_slug--enquiries"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>contact_email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact_email"                data-endpoint="POSTapi-v1-talents--talent_slug--enquiries"
               value="ashly64@example.com"
               data-component="body">
    <br>
<p>Must be a valid email address. Must not be greater than 255 characters. Example: <code>ashly64@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>contact_company</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact_company"                data-endpoint="POSTapi-v1-talents--talent_slug--enquiries"
               value="v"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>v</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>brief</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="brief"                data-endpoint="POSTapi-v1-talents--talent_slug--enquiries"
               value="d"
               data-component="body">
    <br>
<p>Must not be greater than 5000 characters. Example: <code>d</code></p>
        </div>
        </form>

                    <h2 id="discovery-GETapi-v1-brands">List brands</h2>

<p>
</p>

<p>Paginated, filterable public brand directory (published brands only).
Whitelisted filters and sorts mirror the web search contract.</p>

<span id="example-requests-GETapi-v1-brands">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brands?filter%5Bindustry%5D=food_beverage&amp;filter%5Bstage%5D=growing&amp;filter%5Breach%5D=mena&amp;filter%5Bcity%5D=Cairo&amp;filter%5Bverified%5D=1&amp;filter%5Bq%5D=nomad&amp;sort=-created_at&amp;page=1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brands"
);

const params = {
    "filter[industry]": "food_beverage",
    "filter[stage]": "growing",
    "filter[reach]": "mena",
    "filter[city]": "Cairo",
    "filter[verified]": "1",
    "filter[q]": "nomad",
    "sort": "-created_at",
    "page": "1",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brands">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-language: en
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Nomad Coffee Co.&quot;,
            &quot;slug&quot;: &quot;nomad-coffee&quot;,
            &quot;description&quot;: &quot;Specialty coffee roasters based in Cairo.&quot;,
            &quot;industry&quot;: &quot;food_beverage&quot;,
            &quot;brand_stage&quot;: &quot;growing&quot;,
            &quot;base_city&quot;: &quot;Cairo&quot;,
            &quot;base_country&quot;: &quot;Egypt&quot;,
            &quot;geographic_reach&quot;: &quot;mena&quot;,
            &quot;founded_year&quot;: null,
            &quot;company_size&quot;: &quot;small&quot;,
            &quot;website&quot;: &quot;https://nomadcoffee.example&quot;,
            &quot;logo_url&quot;: &quot;https://fama.test/storage/64/fama_d600005f59d82bdf62bab01894e7fd87.jpg&quot;,
            &quot;cover_image_url&quot;: &quot;https://fama.test/storage/65/fama_053fababdf158426331a72b0bf6f6d85.jpg&quot;,
            &quot;is_complete&quot;: true,
            &quot;is_published&quot;: true,
            &quot;is_verified&quot;: true,
            &quot;status&quot;: &quot;published&quot;
        }
    ],
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: {
        &quot;pagination&quot;: {
            &quot;current_page&quot;: 1,
            &quot;last_page&quot;: 1,
            &quot;per_page&quot;: 12,
            &quot;total&quot;: 1,
            &quot;from&quot;: 1,
            &quot;to&quot;: 1
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brands" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brands"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brands"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brands" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brands">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brands" data-method="GET"
      data-path="api/v1/brands"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brands', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brands"
                    onclick="tryItOut('GETapi-v1-brands');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brands"
                    onclick="cancelTryOut('GETapi-v1-brands');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brands"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brands</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brands"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brands"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[industry]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[industry]"                data-endpoint="GETapi-v1-brands"
               value="food_beverage"
               data-component="query">
    <br>
<p>Exact industry. Example: <code>food_beverage</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[stage]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[stage]"                data-endpoint="GETapi-v1-brands"
               value="growing"
               data-component="query">
    <br>
<p>Exact brand stage. Example: <code>growing</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[reach]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[reach]"                data-endpoint="GETapi-v1-brands"
               value="mena"
               data-component="query">
    <br>
<p>Exact geographic reach. Example: <code>mena</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[city]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[city]"                data-endpoint="GETapi-v1-brands"
               value="Cairo"
               data-component="query">
    <br>
<p>Partial city match. Example: <code>Cairo</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[verified]</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="GETapi-v1-brands" style="display: none">
            <input type="radio" name="filter[verified]"
                   value="1"
                   data-endpoint="GETapi-v1-brands"
                   data-component="query"             >
            <code>true</code>
        </label>
        <label data-endpoint="GETapi-v1-brands" style="display: none">
            <input type="radio" name="filter[verified]"
                   value="0"
                   data-endpoint="GETapi-v1-brands"
                   data-component="query"             >
            <code>false</code>
        </label>
    <br>
<p>Only verified brands. Example: <code>true</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[q]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[q]"                data-endpoint="GETapi-v1-brands"
               value="nomad"
               data-component="query">
    <br>
<p>Free-text name match. Example: <code>nomad</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>sort</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sort"                data-endpoint="GETapi-v1-brands"
               value="-created_at"
               data-component="query">
    <br>
<p>created_at or name (prefix - for descending). Example: <code>-created_at</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-brands"
               value="1"
               data-component="query">
    <br>
<p>The page number. Example: <code>1</code></p>
            </div>
                </form>

                    <h2 id="discovery-GETapi-v1-brands--brand_slug-">Show a brand</h2>

<p>
</p>

<p>The public brand profile, resolved by slug: credibility, aesthetic, social
handles, image gallery, approved reviews and public campaigns. <code>description</code>
comes back in the request locale (Accept-Language). 404 if unpublished.</p>

<span id="example-requests-GETapi-v1-brands--brand_slug-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brands/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brands/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brands--brand_slug-">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Resource not found.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brands--brand_slug-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brands--brand_slug-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brands--brand_slug-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brands--brand_slug-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brands--brand_slug-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brands--brand_slug-" data-method="GET"
      data-path="api/v1/brands/{brand_slug}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brands--brand_slug-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brands--brand_slug-"
                    onclick="tryItOut('GETapi-v1-brands--brand_slug-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brands--brand_slug-"
                    onclick="cancelTryOut('GETapi-v1-brands--brand_slug-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brands--brand_slug-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brands/{brand_slug}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brands--brand_slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brands--brand_slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>brand_slug</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="brand_slug"                data-endpoint="GETapi-v1-brands--brand_slug-"
               value="1"
               data-component="url">
    <br>
<p>The slug of the brand. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="discovery-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-">Show a public campaign</h2>

<p>
</p>

<p>One public campaign under a published brand, resolved by slug and scoped to
the brand in the path (404 otherwise).</p>

<span id="example-requests-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brands/1/campaigns/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brands/1/campaigns/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-">
            <blockquote>
            <p>Example response (404):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Resource not found.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-" data-method="GET"
      data-path="api/v1/brands/{brand_slug}/campaigns/{campaign_slug}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brands--brand_slug--campaigns--campaign_slug-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-"
                    onclick="tryItOut('GETapi-v1-brands--brand_slug--campaigns--campaign_slug-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-"
                    onclick="cancelTryOut('GETapi-v1-brands--brand_slug--campaigns--campaign_slug-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brands--brand_slug--campaigns--campaign_slug-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brands/{brand_slug}/campaigns/{campaign_slug}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brands--brand_slug--campaigns--campaign_slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brands--brand_slug--campaigns--campaign_slug-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>brand_slug</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="brand_slug"                data-endpoint="GETapi-v1-brands--brand_slug--campaigns--campaign_slug-"
               value="1"
               data-component="url">
    <br>
<p>The slug of the brand. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>campaign_slug</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="campaign_slug"                data-endpoint="GETapi-v1-brands--brand_slug--campaigns--campaign_slug-"
               value="1"
               data-component="url">
    <br>
<p>The slug of the campaign. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="deals">Deals</h1>

    <p>The authenticated party's view of the shared deal engine. A talent token sees
the deals it is party to; a brand token sees the deals it initiated/received.
Read-only here — step actions (advance/reject/message) stay on the web deal
room for now and are a later API slice.</p>

                                <h2 id="deals-GETapi-v1-deals">List my deals</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Paginated deals scoped to the authenticated talent or brand, newest
activity first, with the counterparty, service and current step loaded.</p>

<span id="example-requests-GETapi-v1-deals">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/deals" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/deals"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-deals">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-deals" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-deals"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-deals"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-deals" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-deals">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-deals" data-method="GET"
      data-path="api/v1/deals"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-deals', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-deals"
                    onclick="tryItOut('GETapi-v1-deals');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-deals"
                    onclick="cancelTryOut('GETapi-v1-deals');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-deals"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/deals</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-deals"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-deals"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-deals"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="deals-GETapi-v1-deals--deal_id-">Show a deal</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>A single deal the caller is a party to (403 otherwise).</p>

<span id="example-requests-GETapi-v1-deals--deal_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/deals/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/deals/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-deals--deal_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-deals--deal_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-deals--deal_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-deals--deal_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-deals--deal_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-deals--deal_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-deals--deal_id-" data-method="GET"
      data-path="api/v1/deals/{deal_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-deals--deal_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-deals--deal_id-"
                    onclick="tryItOut('GETapi-v1-deals--deal_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-deals--deal_id-"
                    onclick="cancelTryOut('GETapi-v1-deals--deal_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-deals--deal_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/deals/{deal_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-deals--deal_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-deals--deal_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-deals--deal_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="GETapi-v1-deals--deal_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="admin-lite">Admin (lite)</h1>

    

                                <h2 id="admin-lite-GETapi-v1-admin-overview">Governance overview</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Pending queues, deals awaiting admin, and flow/catalog counts.</p>

<span id="example-requests-GETapi-v1-admin-overview">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/admin/overview" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/admin/overview"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-overview">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-admin-overview" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-overview"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-overview"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-overview" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-overview">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-overview" data-method="GET"
      data-path="api/v1/admin/overview"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-overview', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-overview"
                    onclick="tryItOut('GETapi-v1-admin-overview');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-overview"
                    onclick="cancelTryOut('GETapi-v1-admin-overview');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-overview"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/overview</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-admin-overview"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-overview"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-overview"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="admin-lite-GETapi-v1-admin-activity">Recent activity</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-admin-activity">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/admin/activity?log=moderation&amp;q=verified" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/admin/activity"
);

const params = {
    "log": "moderation",
    "q": "verified",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-activity">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-admin-activity" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-activity"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-activity"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-activity" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-activity">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-activity" data-method="GET"
      data-path="api/v1/admin/activity"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-activity', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-activity"
                    onclick="tryItOut('GETapi-v1-admin-activity');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-activity"
                    onclick="cancelTryOut('GETapi-v1-admin-activity');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-activity"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/activity</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-admin-activity"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-activity"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-activity"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>log</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="log"                data-endpoint="GETapi-v1-admin-activity"
               value="moderation"
               data-component="query">
    <br>
<p>Filter by log name (e.g. deal_flow, moderation). Example: <code>moderation</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETapi-v1-admin-activity"
               value="verified"
               data-component="query">
    <br>
<p>Free-text description search. Example: <code>verified</code></p>
            </div>
                </form>

                <h1 id="brand-account">Brand · Account</h1>

    

                                <h2 id="brand-account-GETapi-v1-brand-account">Get my account</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-brand-account">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/account" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/account"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-account">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-account" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-account"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-account"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-account" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-account">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-account" data-method="GET"
      data-path="api/v1/brand/account"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-account', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-account"
                    onclick="tryItOut('GETapi-v1-brand-account');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-account"
                    onclick="cancelTryOut('GETapi-v1-brand-account');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-account"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/account</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-account"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-account"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-account"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-account-PATCHapi-v1-brand-account">Update my account</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-brand-account">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/brand/account" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"slug\": \"b\",
    \"founded_year\": 22,
    \"website\": \"g\",
    \"phone\": \"z\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/account"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "slug": "b",
    "founded_year": 22,
    "website": "g",
    "phone": "z"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-brand-account">
</span>
<span id="execution-results-PATCHapi-v1-brand-account" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-brand-account"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-brand-account"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-brand-account" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-brand-account">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-brand-account" data-method="PATCH"
      data-path="api/v1/brand/account"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-brand-account', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-brand-account"
                    onclick="tryItOut('PATCHapi-v1-brand-account');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-brand-account"
                    onclick="cancelTryOut('PATCHapi-v1-brand-account');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-brand-account"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/brand/account</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-brand-account"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-brand-account"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-brand-account"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="PATCHapi-v1-brand-account"
               value="b"
               data-component="body">
    <br>
<p>Must contain only letters, numbers, dashes and underscores. Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>founded_year</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="founded_year"                data-endpoint="PATCHapi-v1-brand-account"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1900. Must not be greater than 2100. Example: <code>22</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>company_size</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="company_size"                data-endpoint="PATCHapi-v1-brand-account"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>website</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="website"                data-endpoint="PATCHapi-v1-brand-account"
               value="g"
               data-component="body">
    <br>
<p>Must be a valid URL. Must not be greater than 255 characters. Example: <code>g</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="PATCHapi-v1-brand-account"
               value="z"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>z</code></p>
        </div>
        </form>

                    <h2 id="brand-account-PATCHapi-v1-brand-account-publish">Publish / unpublish my profile</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-brand-account-publish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/brand/account/publish" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"publish\": true
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/account/publish"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "publish": true
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-brand-account-publish">
</span>
<span id="execution-results-PATCHapi-v1-brand-account-publish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-brand-account-publish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-brand-account-publish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-brand-account-publish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-brand-account-publish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-brand-account-publish" data-method="PATCH"
      data-path="api/v1/brand/account/publish"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-brand-account-publish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-brand-account-publish"
                    onclick="tryItOut('PATCHapi-v1-brand-account-publish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-brand-account-publish"
                    onclick="cancelTryOut('PATCHapi-v1-brand-account-publish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-brand-account-publish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/brand/account/publish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-brand-account-publish"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-brand-account-publish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-brand-account-publish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>publish</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
 &nbsp;
 &nbsp;
                <label data-endpoint="PATCHapi-v1-brand-account-publish" style="display: none">
            <input type="radio" name="publish"
                   value="true"
                   data-endpoint="PATCHapi-v1-brand-account-publish"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PATCHapi-v1-brand-account-publish" style="display: none">
            <input type="radio" name="publish"
                   value="false"
                   data-endpoint="PATCHapi-v1-brand-account-publish"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether to publish (true) or unpublish (false). Example: <code>true</code></p>
        </div>
        </form>

                <h1 id="brand-campaigns">Brand · Campaigns</h1>

    

                                <h2 id="brand-campaigns-GETapi-v1-brand-campaigns">List my campaigns</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Paginated, newest first, with <code>deals_count</code>.</p>

<span id="example-requests-GETapi-v1-brand-campaigns">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/campaigns" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/campaigns"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-campaigns">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-campaigns" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-campaigns"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-campaigns"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-campaigns" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-campaigns">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-campaigns" data-method="GET"
      data-path="api/v1/brand/campaigns"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-campaigns', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-campaigns"
                    onclick="tryItOut('GETapi-v1-brand-campaigns');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-campaigns"
                    onclick="cancelTryOut('GETapi-v1-brand-campaigns');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-campaigns"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/campaigns</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-campaigns"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-campaigns"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-campaigns"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-campaigns-POSTapi-v1-brand-campaigns">Create a campaign</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-campaigns">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/campaigns" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"title\": \"b\",
    \"type\": \"shoot\",
    \"description\": {
        \"en\": \"n\",
        \"ar\": \"g\"
    },
    \"budget_min\": 12,
    \"budget_max\": 77,
    \"currency\": \"iyv\",
    \"location_city\": \"d\",
    \"location_country\": \"l\",
    \"start_date\": \"2026-07-10T11:49:04\",
    \"end_date\": \"2052-08-02\",
    \"is_public\": false,
    \"positions_count\": 39,
    \"roles\": [
        {
            \"talent_type_id\": 16,
            \"quantity\": 22
        }
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/campaigns"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "title": "b",
    "type": "shoot",
    "description": {
        "en": "n",
        "ar": "g"
    },
    "budget_min": 12,
    "budget_max": 77,
    "currency": "iyv",
    "location_city": "d",
    "location_country": "l",
    "start_date": "2026-07-10T11:49:04",
    "end_date": "2052-08-02",
    "is_public": false,
    "positions_count": 39,
    "roles": [
        {
            "talent_type_id": 16,
            "quantity": 22
        }
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-campaigns">
</span>
<span id="execution-results-POSTapi-v1-brand-campaigns" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-campaigns"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-campaigns"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-campaigns" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-campaigns">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-campaigns" data-method="POST"
      data-path="api/v1/brand/campaigns"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-campaigns', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-campaigns"
                    onclick="tryItOut('POSTapi-v1-brand-campaigns');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-campaigns"
                    onclick="cancelTryOut('POSTapi-v1-brand-campaigns');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-campaigns"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/campaigns</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-campaigns"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="shoot"
               data-component="body">
    <br>
<p>Example: <code>shoot</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>campaign</code></li> <li><code>shoot</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>en</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description.en"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>n</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description.ar"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="g"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>g</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>budget_min</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="budget_min"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="12"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>12</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>budget_max</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="budget_max"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="77"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>77</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="iyv"
               data-component="body">
    <br>
<p>Must be 3 characters. Example: <code>iyv</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>location_city</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="location_city"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="d"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>d</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>location_country</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="location_country"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="l"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>l</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>start_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="start_date"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="2026-07-10T11:49:04"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-07-10T11:49:04</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>end_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="end_date"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="2052-08-02"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>start_date</code>. Example: <code>2052-08-02</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_public</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-brand-campaigns" style="display: none">
            <input type="radio" name="is_public"
                   value="true"
                   data-endpoint="POSTapi-v1-brand-campaigns"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-brand-campaigns" style="display: none">
            <input type="radio" name="is_public"
                   value="false"
                   data-endpoint="POSTapi-v1-brand-campaigns"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>positions_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="positions_count"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="39"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>39</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>roles</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>talent_type_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="roles.0.talent_type_id"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>quantity</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="roles.0.quantity"                data-endpoint="POSTapi-v1-brand-campaigns"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>22</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="brand-campaigns-GETapi-v1-brand-campaigns--campaign_id-">Get a campaign</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>The campaign (roles + gallery) and the deals running under it.</p>

<span id="example-requests-GETapi-v1-brand-campaigns--campaign_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/campaigns/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/campaigns/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-campaigns--campaign_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-campaigns--campaign_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-campaigns--campaign_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-campaigns--campaign_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-campaigns--campaign_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-campaigns--campaign_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-campaigns--campaign_id-" data-method="GET"
      data-path="api/v1/brand/campaigns/{campaign_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-campaigns--campaign_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-campaigns--campaign_id-"
                    onclick="tryItOut('GETapi-v1-brand-campaigns--campaign_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-campaigns--campaign_id-"
                    onclick="cancelTryOut('GETapi-v1-brand-campaigns--campaign_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-campaigns--campaign_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/campaigns/{campaign_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-campaigns--campaign_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-campaigns--campaign_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-campaigns--campaign_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>campaign_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="campaign_id"                data-endpoint="GETapi-v1-brand-campaigns--campaign_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the campaign. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="brand-campaigns-PATCHapi-v1-brand-campaigns--campaign_id-">Update a campaign</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-brand-campaigns--campaign_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/brand/campaigns/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"title\": \"b\",
    \"type\": \"campaign\",
    \"description\": {
        \"en\": \"n\",
        \"ar\": \"g\"
    },
    \"budget_min\": 12,
    \"budget_max\": 77,
    \"currency\": \"iyv\",
    \"location_city\": \"d\",
    \"location_country\": \"l\",
    \"start_date\": \"2026-07-10T11:49:04\",
    \"end_date\": \"2052-08-02\",
    \"is_public\": false,
    \"positions_count\": 39,
    \"roles\": [
        {
            \"talent_type_id\": 16,
            \"quantity\": 22
        }
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/campaigns/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "title": "b",
    "type": "campaign",
    "description": {
        "en": "n",
        "ar": "g"
    },
    "budget_min": 12,
    "budget_max": 77,
    "currency": "iyv",
    "location_city": "d",
    "location_country": "l",
    "start_date": "2026-07-10T11:49:04",
    "end_date": "2052-08-02",
    "is_public": false,
    "positions_count": 39,
    "roles": [
        {
            "talent_type_id": 16,
            "quantity": 22
        }
    ]
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-brand-campaigns--campaign_id-">
</span>
<span id="execution-results-PATCHapi-v1-brand-campaigns--campaign_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-brand-campaigns--campaign_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-brand-campaigns--campaign_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-brand-campaigns--campaign_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-brand-campaigns--campaign_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-brand-campaigns--campaign_id-" data-method="PATCH"
      data-path="api/v1/brand/campaigns/{campaign_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-brand-campaigns--campaign_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-brand-campaigns--campaign_id-"
                    onclick="tryItOut('PATCHapi-v1-brand-campaigns--campaign_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-brand-campaigns--campaign_id-"
                    onclick="cancelTryOut('PATCHapi-v1-brand-campaigns--campaign_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-brand-campaigns--campaign_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/brand/campaigns/{campaign_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>campaign_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="campaign_id"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the campaign. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="campaign"
               data-component="body">
    <br>
<p>Example: <code>campaign</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>campaign</code></li> <li><code>shoot</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>en</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description.en"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>n</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description.ar"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="g"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>g</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>budget_min</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="budget_min"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="12"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>12</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>budget_max</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="budget_max"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="77"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>77</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="iyv"
               data-component="body">
    <br>
<p>Must be 3 characters. Example: <code>iyv</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>location_city</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="location_city"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="d"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>d</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>location_country</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="location_country"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="l"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>l</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>start_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="start_date"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="2026-07-10T11:49:04"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-07-10T11:49:04</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>end_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="end_date"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="2052-08-02"
               data-component="body">
    <br>
<p>Must be a valid date. Must be a date after or equal to <code>start_date</code>. Example: <code>2052-08-02</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_public</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-" style="display: none">
            <input type="radio" name="is_public"
                   value="true"
                   data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-" style="display: none">
            <input type="radio" name="is_public"
                   value="false"
                   data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>positions_count</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="positions_count"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="39"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>39</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>roles</code></b>&nbsp;&nbsp;
<small>object[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>talent_type_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="roles.0.talent_type_id"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>quantity</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="roles.0.quantity"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id-"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>22</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="brand-campaigns-PATCHapi-v1-brand-campaigns--campaign_id--status">Transition a campaign&#039;s status</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-brand-campaigns--campaign_id--status">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/brand/campaigns/1/status" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"action\": \"open\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/campaigns/1/status"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "action": "open"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-brand-campaigns--campaign_id--status">
</span>
<span id="execution-results-PATCHapi-v1-brand-campaigns--campaign_id--status" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-brand-campaigns--campaign_id--status"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-brand-campaigns--campaign_id--status"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-brand-campaigns--campaign_id--status" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-brand-campaigns--campaign_id--status">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-brand-campaigns--campaign_id--status" data-method="PATCH"
      data-path="api/v1/brand/campaigns/{campaign_id}/status"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-brand-campaigns--campaign_id--status', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-brand-campaigns--campaign_id--status"
                    onclick="tryItOut('PATCHapi-v1-brand-campaigns--campaign_id--status');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-brand-campaigns--campaign_id--status"
                    onclick="cancelTryOut('PATCHapi-v1-brand-campaigns--campaign_id--status');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-brand-campaigns--campaign_id--status"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/brand/campaigns/{campaign_id}/status</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--status"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--status"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>campaign_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="campaign_id"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--status"
               value="1"
               data-component="url">
    <br>
<p>The ID of the campaign. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>action</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="action"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--status"
               value="open"
               data-component="body">
    <br>
<p>One of open, start, complete, cancel. Example: <code>open</code></p>
        </div>
        </form>

                    <h2 id="brand-campaigns-PATCHapi-v1-brand-campaigns--campaign_id--public">Set a campaign public / private</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-brand-campaigns--campaign_id--public">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/brand/campaigns/1/public" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"public\": true
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/campaigns/1/public"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "public": true
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-brand-campaigns--campaign_id--public">
</span>
<span id="execution-results-PATCHapi-v1-brand-campaigns--campaign_id--public" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-brand-campaigns--campaign_id--public"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-brand-campaigns--campaign_id--public"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-brand-campaigns--campaign_id--public" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-brand-campaigns--campaign_id--public">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-brand-campaigns--campaign_id--public" data-method="PATCH"
      data-path="api/v1/brand/campaigns/{campaign_id}/public"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-brand-campaigns--campaign_id--public', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-brand-campaigns--campaign_id--public"
                    onclick="tryItOut('PATCHapi-v1-brand-campaigns--campaign_id--public');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-brand-campaigns--campaign_id--public"
                    onclick="cancelTryOut('PATCHapi-v1-brand-campaigns--campaign_id--public');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-brand-campaigns--campaign_id--public"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/brand/campaigns/{campaign_id}/public</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--public"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--public"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--public"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>campaign_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="campaign_id"                data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--public"
               value="1"
               data-component="url">
    <br>
<p>The ID of the campaign. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>public</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
 &nbsp;
 &nbsp;
                <label data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--public" style="display: none">
            <input type="radio" name="public"
                   value="true"
                   data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--public"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--public" style="display: none">
            <input type="radio" name="public"
                   value="false"
                   data-endpoint="PATCHapi-v1-brand-campaigns--campaign_id--public"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether the campaign is publicly listed. Example: <code>true</code></p>
        </div>
        </form>

                    <h2 id="brand-campaigns-POSTapi-v1-brand-campaigns--campaign_id--media">Add campaign media</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Multipart <code>file</code>. Returns the created media row + its URL.</p>

<span id="example-requests-POSTapi-v1-brand-campaigns--campaign_id--media">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/campaigns/1/media" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "file=@/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phph11mqrd3gpih0lbW4al" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/campaigns/1/media"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-campaigns--campaign_id--media">
</span>
<span id="execution-results-POSTapi-v1-brand-campaigns--campaign_id--media" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-campaigns--campaign_id--media"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-campaigns--campaign_id--media"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-campaigns--campaign_id--media" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-campaigns--campaign_id--media">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-campaigns--campaign_id--media" data-method="POST"
      data-path="api/v1/brand/campaigns/{campaign_id}/media"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-campaigns--campaign_id--media', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-campaigns--campaign_id--media"
                    onclick="tryItOut('POSTapi-v1-brand-campaigns--campaign_id--media');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-campaigns--campaign_id--media"
                    onclick="cancelTryOut('POSTapi-v1-brand-campaigns--campaign_id--media');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-campaigns--campaign_id--media"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/campaigns/{campaign_id}/media</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-campaigns--campaign_id--media"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-campaigns--campaign_id--media"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-campaigns--campaign_id--media"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>campaign_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="campaign_id"                data-endpoint="POSTapi-v1-brand-campaigns--campaign_id--media"
               value="1"
               data-component="url">
    <br>
<p>The ID of the campaign. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-v1-brand-campaigns--campaign_id--media"
               value=""
               data-component="body">
    <br>
<p>Must be an image. Must not be greater than 5120 kilobytes. Example: <code>/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phph11mqrd3gpih0lbW4al</code></p>
        </div>
        </form>

                    <h2 id="brand-campaigns-DELETEapi-v1-brand-campaigns--campaign_id-">Delete a campaign</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-brand-campaigns--campaign_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/brand/campaigns/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/campaigns/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-brand-campaigns--campaign_id-">
</span>
<span id="execution-results-DELETEapi-v1-brand-campaigns--campaign_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-brand-campaigns--campaign_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-brand-campaigns--campaign_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-brand-campaigns--campaign_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-brand-campaigns--campaign_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-brand-campaigns--campaign_id-" data-method="DELETE"
      data-path="api/v1/brand/campaigns/{campaign_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-brand-campaigns--campaign_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-brand-campaigns--campaign_id-"
                    onclick="tryItOut('DELETEapi-v1-brand-campaigns--campaign_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-brand-campaigns--campaign_id-"
                    onclick="cancelTryOut('DELETEapi-v1-brand-campaigns--campaign_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-brand-campaigns--campaign_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/brand/campaigns/{campaign_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-brand-campaigns--campaign_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-brand-campaigns--campaign_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-brand-campaigns--campaign_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>campaign_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="campaign_id"                data-endpoint="DELETEapi-v1-brand-campaigns--campaign_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the campaign. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="brand-creative-needs">Brand · Creative needs</h1>

    

                                <h2 id="brand-creative-needs-GETapi-v1-brand-creative-needs">Get my creative needs</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-brand-creative-needs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/creative-needs" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/creative-needs"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-creative-needs">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-creative-needs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-creative-needs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-creative-needs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-creative-needs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-creative-needs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-creative-needs" data-method="GET"
      data-path="api/v1/brand/creative-needs"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-creative-needs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-creative-needs"
                    onclick="tryItOut('GETapi-v1-brand-creative-needs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-creative-needs"
                    onclick="cancelTryOut('GETapi-v1-brand-creative-needs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-creative-needs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/creative-needs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-creative-needs"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-creative-needs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-creative-needs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-creative-needs-PATCHapi-v1-brand-creative-needs">Update my creative needs</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-brand-creative-needs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/brand/creative-needs" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"talent_type_ids\": [
        16
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/creative-needs"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "talent_type_ids": [
        16
    ]
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-brand-creative-needs">
</span>
<span id="execution-results-PATCHapi-v1-brand-creative-needs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-brand-creative-needs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-brand-creative-needs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-brand-creative-needs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-brand-creative-needs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-brand-creative-needs" data-method="PATCH"
      data-path="api/v1/brand/creative-needs"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-brand-creative-needs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-brand-creative-needs"
                    onclick="tryItOut('PATCHapi-v1-brand-creative-needs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-brand-creative-needs"
                    onclick="cancelTryOut('PATCHapi-v1-brand-creative-needs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-brand-creative-needs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/brand/creative-needs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-brand-creative-needs"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-brand-creative-needs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-brand-creative-needs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>talent_type_ids</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="talent_type_ids[0]"                data-endpoint="PATCHapi-v1-brand-creative-needs"
               data-component="body">
        <input type="number" style="display: none"
               name="talent_type_ids[1]"                data-endpoint="PATCHapi-v1-brand-creative-needs"
               data-component="body">
    <br>
<p>Must match an existing stored value.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>project_types</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="project_types[0]"                data-endpoint="PATCHapi-v1-brand-creative-needs"
               data-component="body">
        <input type="text" style="display: none"
               name="project_types[1]"                data-endpoint="PATCHapi-v1-brand-creative-needs"
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>project_frequency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="project_frequency"                data-endpoint="PATCHapi-v1-brand-creative-needs"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>budget_tier</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="budget_tier"                data-endpoint="PATCHapi-v1-brand-creative-needs"
               value=""
               data-component="body">
    <br>

        </div>
        </form>

                <h1 id="brand-credibility">Brand · Credibility</h1>

    

                                <h2 id="brand-credibility-GETapi-v1-brand-credibility">Get my credibility</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-brand-credibility">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/credibility" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/credibility"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-credibility">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-credibility" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-credibility"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-credibility"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-credibility" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-credibility">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-credibility" data-method="GET"
      data-path="api/v1/brand/credibility"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-credibility', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-credibility"
                    onclick="tryItOut('GETapi-v1-brand-credibility');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-credibility"
                    onclick="cancelTryOut('GETapi-v1-brand-credibility');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-credibility"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/credibility</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-credibility"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-credibility"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-credibility"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="brand-deals">Brand · Deals</h1>

    

                                <h2 id="brand-deals-POSTapi-v1-brand-deals">Start a deal</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Brand-initiated ("Start a deal"): creates a deal with the resolved active
flow, snapshots its steps, activates the first step and notifies the talent.
Guard failures (talent unpublished / not bookable, brand incomplete, no
active flow) return <strong>422</strong>. Optionally links a campaign (<code>deals.campaign_id</code>).</p>

<span id="example-requests-POSTapi-v1-brand-deals">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/deals" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"talent_id\": 16,
    \"service_id\": 16,
    \"deal_flow_id\": 16,
    \"campaign_id\": 16,
    \"brief\": \"n\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/deals"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "talent_id": 16,
    "service_id": 16,
    "deal_flow_id": 16,
    "campaign_id": 16,
    "brief": "n"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-deals">
            <blockquote>
            <p>Example response (201, Created):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;reference&quot;: &quot;FAMA-2026-00001&quot;,
        &quot;status&quot;: &quot;awaiting_brand&quot;
    },
    &quot;message&quot;: &quot;Deal started.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: {
        &quot;room&quot;: &quot;/api/v1/brand/deals/1&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-brand-deals" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-deals"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-deals"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-deals" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-deals">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-deals" data-method="POST"
      data-path="api/v1/brand/deals"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-deals', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-deals"
                    onclick="tryItOut('POSTapi-v1-brand-deals');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-deals"
                    onclick="cancelTryOut('POSTapi-v1-brand-deals');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-deals"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/deals</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-deals"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-deals"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-deals"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>talent_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="talent_id"                data-endpoint="POSTapi-v1-brand-deals"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>service_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="service_id"                data-endpoint="POSTapi-v1-brand-deals"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>deal_flow_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_flow_id"                data-endpoint="POSTapi-v1-brand-deals"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>campaign_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="campaign_id"                data-endpoint="POSTapi-v1-brand-deals"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>brief</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="brief"                data-endpoint="POSTapi-v1-brand-deals"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 5000 characters. Example: <code>n</code></p>
        </div>
        </form>

                    <h2 id="brand-deals-GETapi-v1-brand-deals">List my deals</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Paginated inbox, newest first, with the counterparty + current step loaded.</p>

<span id="example-requests-GETapi-v1-brand-deals">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/deals?status=awaiting_brand" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/deals"
);

const params = {
    "status": "awaiting_brand",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-deals">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-deals" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-deals"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-deals"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-deals" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-deals">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-deals" data-method="GET"
      data-path="api/v1/brand/deals"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-deals', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-deals"
                    onclick="tryItOut('GETapi-v1-brand-deals');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-deals"
                    onclick="cancelTryOut('GETapi-v1-brand-deals');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-deals"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/deals</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-deals"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-deals"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-deals"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-brand-deals"
               value="awaiting_brand"
               data-component="query">
    <br>
<p>Filter by deal status (e.g. awaiting_brand, completed). Example: <code>awaiting_brand</code></p>
            </div>
                </form>

                    <h2 id="brand-deals-GETapi-v1-brand-deals--deal_id-">Get a deal room</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>The deal, its stepper, the message timeline and whether the brand can act
on the current step. Reading the room marks the talent's messages read.</p>

<span id="example-requests-GETapi-v1-brand-deals--deal_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/deals/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/deals/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-deals--deal_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-deals--deal_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-deals--deal_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-deals--deal_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-deals--deal_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-deals--deal_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-deals--deal_id-" data-method="GET"
      data-path="api/v1/brand/deals/{deal_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-deals--deal_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-deals--deal_id-"
                    onclick="tryItOut('GETapi-v1-brand-deals--deal_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-deals--deal_id-"
                    onclick="cancelTryOut('GETapi-v1-brand-deals--deal_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-deals--deal_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/deals/{deal_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-deals--deal_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-deals--deal_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-deals--deal_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="GETapi-v1-brand-deals--deal_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="brand-deals-POSTapi-v1-brand-deals--deal_id--advance">Complete the current step</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Submits the current step for the brand (submit brief / accept quote / sign /
pay). The body shape depends on the step's <code>step_type</code>.</p>

<span id="example-requests-POSTapi-v1-brand-deals--deal_id--advance">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/deals/1/advance" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/deals/1/advance"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-deals--deal_id--advance">
</span>
<span id="execution-results-POSTapi-v1-brand-deals--deal_id--advance" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-deals--deal_id--advance"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-deals--deal_id--advance"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-deals--deal_id--advance" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-deals--deal_id--advance">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-deals--deal_id--advance" data-method="POST"
      data-path="api/v1/brand/deals/{deal_id}/advance"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-deals--deal_id--advance', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-deals--deal_id--advance"
                    onclick="tryItOut('POSTapi-v1-brand-deals--deal_id--advance');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-deals--deal_id--advance"
                    onclick="cancelTryOut('POSTapi-v1-brand-deals--deal_id--advance');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-deals--deal_id--advance"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/deals/{deal_id}/advance</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-deals--deal_id--advance"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-deals--deal_id--advance"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-deals--deal_id--advance"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="POSTapi-v1-brand-deals--deal_id--advance"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="brand-deals-POSTapi-v1-brand-deals--deal_id--reject">Reject the current step</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-deals--deal_id--reject">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/deals/1/reject" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reason\": \"Please revise the quote.\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/deals/1/reject"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reason": "Please revise the quote."
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-deals--deal_id--reject">
</span>
<span id="execution-results-POSTapi-v1-brand-deals--deal_id--reject" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-deals--deal_id--reject"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-deals--deal_id--reject"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-deals--deal_id--reject" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-deals--deal_id--reject">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-deals--deal_id--reject" data-method="POST"
      data-path="api/v1/brand/deals/{deal_id}/reject"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-deals--deal_id--reject', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-deals--deal_id--reject"
                    onclick="tryItOut('POSTapi-v1-brand-deals--deal_id--reject');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-deals--deal_id--reject"
                    onclick="cancelTryOut('POSTapi-v1-brand-deals--deal_id--reject');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-deals--deal_id--reject"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/deals/{deal_id}/reject</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-deals--deal_id--reject"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-deals--deal_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-deals--deal_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="POSTapi-v1-brand-deals--deal_id--reject"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-brand-deals--deal_id--reject"
               value="Please revise the quote."
               data-component="body">
    <br>
<p>A note explaining the rejection. Example: <code>Please revise the quote.</code></p>
        </div>
        </form>

                    <h2 id="brand-deals-POSTapi-v1-brand-deals--deal_id--skip">Skip the current step (if skippable)</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-deals--deal_id--skip">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/deals/1/skip" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/deals/1/skip"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-deals--deal_id--skip">
</span>
<span id="execution-results-POSTapi-v1-brand-deals--deal_id--skip" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-deals--deal_id--skip"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-deals--deal_id--skip"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-deals--deal_id--skip" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-deals--deal_id--skip">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-deals--deal_id--skip" data-method="POST"
      data-path="api/v1/brand/deals/{deal_id}/skip"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-deals--deal_id--skip', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-deals--deal_id--skip"
                    onclick="tryItOut('POSTapi-v1-brand-deals--deal_id--skip');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-deals--deal_id--skip"
                    onclick="cancelTryOut('POSTapi-v1-brand-deals--deal_id--skip');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-deals--deal_id--skip"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/deals/{deal_id}/skip</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-deals--deal_id--skip"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-deals--deal_id--skip"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-deals--deal_id--skip"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="POSTapi-v1-brand-deals--deal_id--skip"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="brand-deals-POSTapi-v1-brand-deals--deal_id--message">Post a message to the deal thread</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-deals--deal_id--message">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/deals/1/message" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"body\": \"Looks great — approving now.\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/deals/1/message"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "body": "Looks great — approving now."
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-deals--deal_id--message">
</span>
<span id="execution-results-POSTapi-v1-brand-deals--deal_id--message" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-deals--deal_id--message"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-deals--deal_id--message"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-deals--deal_id--message" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-deals--deal_id--message">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-deals--deal_id--message" data-method="POST"
      data-path="api/v1/brand/deals/{deal_id}/message"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-deals--deal_id--message', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-deals--deal_id--message"
                    onclick="tryItOut('POSTapi-v1-brand-deals--deal_id--message');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-deals--deal_id--message"
                    onclick="cancelTryOut('POSTapi-v1-brand-deals--deal_id--message');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-deals--deal_id--message"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/deals/{deal_id}/message</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-deals--deal_id--message"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-deals--deal_id--message"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-deals--deal_id--message"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="POSTapi-v1-brand-deals--deal_id--message"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="POSTapi-v1-brand-deals--deal_id--message"
               value="Looks great — approving now."
               data-component="body">
    <br>
<p>The message. Example: <code>Looks great — approving now.</code></p>
        </div>
        </form>

                <h1 id="brand-discovery">Brand · Discovery</h1>

    

                                <h2 id="brand-discovery-GETapi-v1-brand-discover">Discovery feed</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Paginated, personalised talents. Ad-hoc narrowing via <code>filter[...]</code>
(availability, city, type) and <code>sort</code> (view_count, created_at).</p>

<span id="example-requests-GETapi-v1-brand-discover">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/discover?filter%5Bavailability%5D=available&amp;filter%5Bcity%5D=Cairo&amp;filter%5Btype%5D=model&amp;sort=-view_count" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/discover"
);

const params = {
    "filter[availability]": "available",
    "filter[city]": "Cairo",
    "filter[type]": "model",
    "sort": "-view_count",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-discover">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-discover" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-discover"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-discover"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-discover" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-discover">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-discover" data-method="GET"
      data-path="api/v1/brand/discover"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-discover', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-discover"
                    onclick="tryItOut('GETapi-v1-brand-discover');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-discover"
                    onclick="cancelTryOut('GETapi-v1-brand-discover');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-discover"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/discover</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-discover"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-discover"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-discover"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[availability]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[availability]"                data-endpoint="GETapi-v1-brand-discover"
               value="available"
               data-component="query">
    <br>
<p>Example: <code>available</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[city]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[city]"                data-endpoint="GETapi-v1-brand-discover"
               value="Cairo"
               data-component="query">
    <br>
<p>Partial city match. Example: <code>Cairo</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>filter[type]</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="filter[type]"                data-endpoint="GETapi-v1-brand-discover"
               value="model"
               data-component="query">
    <br>
<p>Comma-separated profession slugs. Example: <code>model</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>sort</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sort"                data-endpoint="GETapi-v1-brand-discover"
               value="-view_count"
               data-component="query">
    <br>
<p>view_count or created_at (prefix - for desc). Example: <code>-view_count</code></p>
            </div>
                </form>

                    <h2 id="brand-discovery-POSTapi-v1-brand-discover-save">Save a talent</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-discover-save">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/discover/save" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"talent_id\": 1
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/discover/save"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "talent_id": 1
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-discover-save">
</span>
<span id="execution-results-POSTapi-v1-brand-discover-save" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-discover-save"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-discover-save"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-discover-save" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-discover-save">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-discover-save" data-method="POST"
      data-path="api/v1/brand/discover/save"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-discover-save', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-discover-save"
                    onclick="tryItOut('POSTapi-v1-brand-discover-save');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-discover-save"
                    onclick="cancelTryOut('POSTapi-v1-brand-discover-save');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-discover-save"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/discover/save</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-discover-save"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-discover-save"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-discover-save"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>talent_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="talent_id"                data-endpoint="POSTapi-v1-brand-discover-save"
               value="1"
               data-component="body">
    <br>
<p>The talent to save. Example: <code>1</code></p>
        </div>
        </form>

                    <h2 id="brand-discovery-POSTapi-v1-brand-discover-brief">Send a brief to a talent</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-discover-brief">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/discover/brief" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"talent_id\": 1
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/discover/brief"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "talent_id": 1
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-discover-brief">
</span>
<span id="execution-results-POSTapi-v1-brand-discover-brief" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-discover-brief"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-discover-brief"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-discover-brief" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-discover-brief">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-discover-brief" data-method="POST"
      data-path="api/v1/brand/discover/brief"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-discover-brief', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-discover-brief"
                    onclick="tryItOut('POSTapi-v1-brand-discover-brief');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-discover-brief"
                    onclick="cancelTryOut('POSTapi-v1-brand-discover-brief');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-discover-brief"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/discover/brief</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-discover-brief"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-discover-brief"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-discover-brief"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>talent_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="talent_id"                data-endpoint="POSTapi-v1-brand-discover-brief"
               value="1"
               data-component="body">
    <br>
<p>The talent to brief. Example: <code>1</code></p>
        </div>
        </form>

                <h1 id="brand-enquiries">Brand · Enquiries</h1>

    

                                <h2 id="brand-enquiries-GETapi-v1-brand-enquiries">List my pending enquiries</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Paginated pre-auth enquiries addressed to the brand's email that are still
awaiting conversion (<code>status = new</code>).</p>

<span id="example-requests-GETapi-v1-brand-enquiries">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/enquiries" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/enquiries"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-enquiries">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-enquiries" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-enquiries"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-enquiries"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-enquiries" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-enquiries">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-enquiries" data-method="GET"
      data-path="api/v1/brand/enquiries"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-enquiries', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-enquiries"
                    onclick="tryItOut('GETapi-v1-brand-enquiries');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-enquiries"
                    onclick="cancelTryOut('GETapi-v1-brand-enquiries');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-enquiries"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/enquiries</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-enquiries"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-enquiries"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-enquiries"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-enquiries-POSTapi-v1-brand-enquiries--enquiry_id--convert">Convert an enquiry to a deal</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Turns a matched pending enquiry into a real deal (carrying its talent /
service / brief), marks it converted, and notifies the talent. <strong>403</strong> if
the enquiry's email isn't the brand's; <strong>422</strong> if already handled.</p>

<span id="example-requests-POSTapi-v1-brand-enquiries--enquiry_id--convert">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/enquiries/1/convert" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/enquiries/1/convert"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-enquiries--enquiry_id--convert">
            <blockquote>
            <p>Example response (201, Created):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;reference&quot;: &quot;FAMA-2026-00001&quot;,
        &quot;status&quot;: &quot;awaiting_brand&quot;
    },
    &quot;message&quot;: &quot;Enquiry converted to a deal.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: {
        &quot;room&quot;: &quot;/api/v1/brand/deals/1&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-brand-enquiries--enquiry_id--convert" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-enquiries--enquiry_id--convert"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-enquiries--enquiry_id--convert"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-enquiries--enquiry_id--convert" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-enquiries--enquiry_id--convert">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-enquiries--enquiry_id--convert" data-method="POST"
      data-path="api/v1/brand/enquiries/{enquiry_id}/convert"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-enquiries--enquiry_id--convert', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-enquiries--enquiry_id--convert"
                    onclick="tryItOut('POSTapi-v1-brand-enquiries--enquiry_id--convert');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-enquiries--enquiry_id--convert"
                    onclick="cancelTryOut('POSTapi-v1-brand-enquiries--enquiry_id--convert');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-enquiries--enquiry_id--convert"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/enquiries/{enquiry_id}/convert</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-enquiries--enquiry_id--convert"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-enquiries--enquiry_id--convert"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-enquiries--enquiry_id--convert"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>enquiry_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="enquiry_id"                data-endpoint="POSTapi-v1-brand-enquiries--enquiry_id--convert"
               value="1"
               data-component="url">
    <br>
<p>The ID of the enquiry. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="brand-onboarding">Brand · Onboarding</h1>

    

                                <h2 id="brand-onboarding-GETapi-v1-brand-onboarding">Onboarding status</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Whether onboarding is complete plus the current saved values, so the app
can resume the wizard.</p>

<span id="example-requests-GETapi-v1-brand-onboarding">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/onboarding" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/onboarding"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-onboarding">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-onboarding" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-onboarding"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-onboarding"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-onboarding" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-onboarding">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-onboarding" data-method="GET"
      data-path="api/v1/brand/onboarding"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-onboarding', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-onboarding"
                    onclick="tryItOut('GETapi-v1-brand-onboarding');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-onboarding"
                    onclick="cancelTryOut('GETapi-v1-brand-onboarding');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-onboarding"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/onboarding</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-onboarding"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-onboarding"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-onboarding"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-onboarding-POSTapi-v1-brand-onboarding-identity">Step 1 · Identity</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-onboarding-identity">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/onboarding/identity" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/onboarding/identity"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-onboarding-identity">
</span>
<span id="execution-results-POSTapi-v1-brand-onboarding-identity" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-onboarding-identity"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-onboarding-identity"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-onboarding-identity" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-onboarding-identity">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-onboarding-identity" data-method="POST"
      data-path="api/v1/brand/onboarding/identity"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-onboarding-identity', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-onboarding-identity"
                    onclick="tryItOut('POSTapi-v1-brand-onboarding-identity');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-onboarding-identity"
                    onclick="cancelTryOut('POSTapi-v1-brand-onboarding-identity');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-onboarding-identity"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/onboarding/identity</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-onboarding-identity"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-onboarding-identity"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-onboarding-identity"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-onboarding-POSTapi-v1-brand-onboarding-location">Step 2 · Location</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-onboarding-location">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/onboarding/location" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/onboarding/location"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-onboarding-location">
</span>
<span id="execution-results-POSTapi-v1-brand-onboarding-location" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-onboarding-location"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-onboarding-location"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-onboarding-location" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-onboarding-location">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-onboarding-location" data-method="POST"
      data-path="api/v1/brand/onboarding/location"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-onboarding-location', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-onboarding-location"
                    onclick="tryItOut('POSTapi-v1-brand-onboarding-location');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-onboarding-location"
                    onclick="cancelTryOut('POSTapi-v1-brand-onboarding-location');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-onboarding-location"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/onboarding/location</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-onboarding-location"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-onboarding-location"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-onboarding-location"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-onboarding-POSTapi-v1-brand-onboarding-creative-needs">Step 3 · Creative needs</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-onboarding-creative-needs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/onboarding/creative-needs" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/onboarding/creative-needs"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-onboarding-creative-needs">
</span>
<span id="execution-results-POSTapi-v1-brand-onboarding-creative-needs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-onboarding-creative-needs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-onboarding-creative-needs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-onboarding-creative-needs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-onboarding-creative-needs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-onboarding-creative-needs" data-method="POST"
      data-path="api/v1/brand/onboarding/creative-needs"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-onboarding-creative-needs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-onboarding-creative-needs"
                    onclick="tryItOut('POSTapi-v1-brand-onboarding-creative-needs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-onboarding-creative-needs"
                    onclick="cancelTryOut('POSTapi-v1-brand-onboarding-creative-needs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-onboarding-creative-needs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/onboarding/creative-needs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-onboarding-creative-needs"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-onboarding-creative-needs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-onboarding-creative-needs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-onboarding-POSTapi-v1-brand-onboarding-aesthetic">Step 4 · Aesthetic</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-onboarding-aesthetic">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/onboarding/aesthetic" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/onboarding/aesthetic"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-onboarding-aesthetic">
</span>
<span id="execution-results-POSTapi-v1-brand-onboarding-aesthetic" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-onboarding-aesthetic"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-onboarding-aesthetic"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-onboarding-aesthetic" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-onboarding-aesthetic">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-onboarding-aesthetic" data-method="POST"
      data-path="api/v1/brand/onboarding/aesthetic"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-onboarding-aesthetic', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-onboarding-aesthetic"
                    onclick="tryItOut('POSTapi-v1-brand-onboarding-aesthetic');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-onboarding-aesthetic"
                    onclick="cancelTryOut('POSTapi-v1-brand-onboarding-aesthetic');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-onboarding-aesthetic"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/onboarding/aesthetic</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-onboarding-aesthetic"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-onboarding-aesthetic"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-onboarding-aesthetic"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-onboarding-POSTapi-v1-brand-onboarding-budget">Step 5 · Budget</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-onboarding-budget">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/onboarding/budget" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"budget_tier\": \"architecto\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/onboarding/budget"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "budget_tier": "architecto"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-onboarding-budget">
</span>
<span id="execution-results-POSTapi-v1-brand-onboarding-budget" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-onboarding-budget"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-onboarding-budget"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-onboarding-budget" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-onboarding-budget">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-onboarding-budget" data-method="POST"
      data-path="api/v1/brand/onboarding/budget"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-onboarding-budget', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-onboarding-budget"
                    onclick="tryItOut('POSTapi-v1-brand-onboarding-budget');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-onboarding-budget"
                    onclick="cancelTryOut('POSTapi-v1-brand-onboarding-budget');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-onboarding-budget"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/onboarding/budget</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-onboarding-budget"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-onboarding-budget"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-onboarding-budget"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>budget_tier</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="budget_tier"                data-endpoint="POSTapi-v1-brand-onboarding-budget"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
        </form>

                    <h2 id="brand-onboarding-POSTapi-v1-brand-onboarding-complete">Step 6 · Complete</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Flips <code>is_complete</code> and unlocks the dashboard/feed.</p>

<span id="example-requests-POSTapi-v1-brand-onboarding-complete">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/onboarding/complete" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/onboarding/complete"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-onboarding-complete">
</span>
<span id="execution-results-POSTapi-v1-brand-onboarding-complete" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-onboarding-complete"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-onboarding-complete"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-onboarding-complete" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-onboarding-complete">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-onboarding-complete" data-method="POST"
      data-path="api/v1/brand/onboarding/complete"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-onboarding-complete', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-onboarding-complete"
                    onclick="tryItOut('POSTapi-v1-brand-onboarding-complete');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-onboarding-complete"
                    onclick="cancelTryOut('POSTapi-v1-brand-onboarding-complete');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-onboarding-complete"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/onboarding/complete</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-onboarding-complete"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-onboarding-complete"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-onboarding-complete"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="brand-profile">Brand · Profile</h1>

    

                                <h2 id="brand-profile-GETapi-v1-brand-profile">Get my profile</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-brand-profile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/profile" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/profile"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-profile">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-profile" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-profile"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-profile"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-profile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-profile">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-profile" data-method="GET"
      data-path="api/v1/brand/profile"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-profile', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-profile"
                    onclick="tryItOut('GETapi-v1-brand-profile');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-profile"
                    onclick="cancelTryOut('GETapi-v1-brand-profile');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-profile"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/profile</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-profile"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-profile-PATCHapi-v1-brand-profile">Update my core profile</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-brand-profile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/brand/profile" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/profile"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-brand-profile">
</span>
<span id="execution-results-PATCHapi-v1-brand-profile" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-brand-profile"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-brand-profile"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-brand-profile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-brand-profile">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-brand-profile" data-method="PATCH"
      data-path="api/v1/brand/profile"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-brand-profile', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-brand-profile"
                    onclick="tryItOut('PATCHapi-v1-brand-profile');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-brand-profile"
                    onclick="cancelTryOut('PATCHapi-v1-brand-profile');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-brand-profile"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/brand/profile</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-brand-profile"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-brand-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-brand-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-profile-POSTapi-v1-brand-profile-logo">Upload my logo</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Multipart <code>file</code>. Returns the resolved logo URL.</p>

<span id="example-requests-POSTapi-v1-brand-profile-logo">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/profile/logo" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "file=@/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/php2gpm7fom67023vyStSQ" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/profile/logo"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-profile-logo">
</span>
<span id="execution-results-POSTapi-v1-brand-profile-logo" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-profile-logo"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-profile-logo"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-profile-logo" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-profile-logo">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-profile-logo" data-method="POST"
      data-path="api/v1/brand/profile/logo"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-profile-logo', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-profile-logo"
                    onclick="tryItOut('POSTapi-v1-brand-profile-logo');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-profile-logo"
                    onclick="cancelTryOut('POSTapi-v1-brand-profile-logo');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-profile-logo"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/profile/logo</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-profile-logo"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-profile-logo"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-profile-logo"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-v1-brand-profile-logo"
               value=""
               data-component="body">
    <br>
<p>Must be an image. Must not be greater than 5120 kilobytes. Example: <code>/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/php2gpm7fom67023vyStSQ</code></p>
        </div>
        </form>

                    <h2 id="brand-profile-POSTapi-v1-brand-profile-cover">Upload my cover image</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Multipart <code>file</code>. Returns the resolved cover URL.</p>

<span id="example-requests-POSTapi-v1-brand-profile-cover">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/profile/cover" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "file=@/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phprpmgp3vn0frk03erQve" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/profile/cover"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-profile-cover">
</span>
<span id="execution-results-POSTapi-v1-brand-profile-cover" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-profile-cover"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-profile-cover"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-profile-cover" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-profile-cover">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-profile-cover" data-method="POST"
      data-path="api/v1/brand/profile/cover"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-profile-cover', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-profile-cover"
                    onclick="tryItOut('POSTapi-v1-brand-profile-cover');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-profile-cover"
                    onclick="cancelTryOut('POSTapi-v1-brand-profile-cover');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-profile-cover"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/profile/cover</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-profile-cover"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-profile-cover"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-profile-cover"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-v1-brand-profile-cover"
               value=""
               data-component="body">
    <br>
<p>Must be an image. Must not be greater than 5120 kilobytes. Example: <code>/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phprpmgp3vn0frk03erQve</code></p>
        </div>
        </form>

                    <h2 id="brand-profile-PATCHapi-v1-brand-profile-aesthetic">Update my aesthetic</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>References + mood tags. Reuses the transactional onboarding service.</p>

<span id="example-requests-PATCHapi-v1-brand-profile-aesthetic">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/brand/profile/aesthetic" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/profile/aesthetic"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-brand-profile-aesthetic">
</span>
<span id="execution-results-PATCHapi-v1-brand-profile-aesthetic" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-brand-profile-aesthetic"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-brand-profile-aesthetic"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-brand-profile-aesthetic" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-brand-profile-aesthetic">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-brand-profile-aesthetic" data-method="PATCH"
      data-path="api/v1/brand/profile/aesthetic"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-brand-profile-aesthetic', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-brand-profile-aesthetic"
                    onclick="tryItOut('PATCHapi-v1-brand-profile-aesthetic');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-brand-profile-aesthetic"
                    onclick="cancelTryOut('PATCHapi-v1-brand-profile-aesthetic');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-brand-profile-aesthetic"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/brand/profile/aesthetic</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-brand-profile-aesthetic"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-brand-profile-aesthetic"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-brand-profile-aesthetic"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-profile-GETapi-v1-brand-profile-images">List my images</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-brand-profile-images">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/profile/images" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/profile/images"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-profile-images">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-profile-images" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-profile-images"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-profile-images"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-profile-images" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-profile-images">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-profile-images" data-method="GET"
      data-path="api/v1/brand/profile/images"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-profile-images', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-profile-images"
                    onclick="tryItOut('GETapi-v1-brand-profile-images');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-profile-images"
                    onclick="cancelTryOut('GETapi-v1-brand-profile-images');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-profile-images"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/profile/images</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-profile-images"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-profile-images"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-profile-images"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-profile-POSTapi-v1-brand-profile-images">Add an image</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Multipart <code>file</code>. Returns the created row + its URL.</p>

<span id="example-requests-POSTapi-v1-brand-profile-images">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/profile/images" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "file=@/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phpeivdaf7qgmgjeYvEzxL" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/profile/images"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-profile-images">
</span>
<span id="execution-results-POSTapi-v1-brand-profile-images" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-profile-images"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-profile-images"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-profile-images" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-profile-images">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-profile-images" data-method="POST"
      data-path="api/v1/brand/profile/images"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-profile-images', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-profile-images"
                    onclick="tryItOut('POSTapi-v1-brand-profile-images');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-profile-images"
                    onclick="cancelTryOut('POSTapi-v1-brand-profile-images');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-profile-images"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/profile/images</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-profile-images"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-profile-images"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-profile-images"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-v1-brand-profile-images"
               value=""
               data-component="body">
    <br>
<p>Must be an image. Must not be greater than 5120 kilobytes. Example: <code>/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phpeivdaf7qgmgjeYvEzxL</code></p>
        </div>
        </form>

                    <h2 id="brand-profile-DELETEapi-v1-brand-profile-images--image_id-">Remove an image</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-brand-profile-images--image_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/brand/profile/images/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/profile/images/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-brand-profile-images--image_id-">
</span>
<span id="execution-results-DELETEapi-v1-brand-profile-images--image_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-brand-profile-images--image_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-brand-profile-images--image_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-brand-profile-images--image_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-brand-profile-images--image_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-brand-profile-images--image_id-" data-method="DELETE"
      data-path="api/v1/brand/profile/images/{image_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-brand-profile-images--image_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-brand-profile-images--image_id-"
                    onclick="tryItOut('DELETEapi-v1-brand-profile-images--image_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-brand-profile-images--image_id-"
                    onclick="cancelTryOut('DELETEapi-v1-brand-profile-images--image_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-brand-profile-images--image_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/brand/profile/images/{image_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-brand-profile-images--image_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-brand-profile-images--image_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-brand-profile-images--image_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>image_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="image_id"                data-endpoint="DELETEapi-v1-brand-profile-images--image_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the image. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="brand-profile-GETapi-v1-brand-social">List my social handles</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-brand-social">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/social" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/social"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-social">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-social" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-social"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-social"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-social" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-social">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-social" data-method="GET"
      data-path="api/v1/brand/social"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-social', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-social"
                    onclick="tryItOut('GETapi-v1-brand-social');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-social"
                    onclick="cancelTryOut('GETapi-v1-brand-social');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-social"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/social</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-social"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-social"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-social"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="brand-profile-POSTapi-v1-brand-social">Add a social handle</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-brand-social">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/brand/social" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"platform\": \"architecto\",
    \"handle\": \"n\",
    \"url\": \"http:\\/\\/crooks.biz\\/et-fugiat-sunt-nihil-accusantium\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/social"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "platform": "architecto",
    "handle": "n",
    "url": "http:\/\/crooks.biz\/et-fugiat-sunt-nihil-accusantium"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-brand-social">
</span>
<span id="execution-results-POSTapi-v1-brand-social" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-brand-social"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-brand-social"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-brand-social" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-brand-social">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-brand-social" data-method="POST"
      data-path="api/v1/brand/social"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-brand-social', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-brand-social"
                    onclick="tryItOut('POSTapi-v1-brand-social');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-brand-social"
                    onclick="cancelTryOut('POSTapi-v1-brand-social');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-brand-social"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/brand/social</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-brand-social"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-brand-social"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-brand-social"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>platform</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="platform"                data-endpoint="POSTapi-v1-brand-social"
               value="architecto"
               data-component="body">
    <br>
<p>Example: <code>architecto</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>handle</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="handle"                data-endpoint="POSTapi-v1-brand-social"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="url"                data-endpoint="POSTapi-v1-brand-social"
               value="http://crooks.biz/et-fugiat-sunt-nihil-accusantium"
               data-component="body">
    <br>
<p>Must be a valid URL. Must not be greater than 255 characters. Example: <code>http://crooks.biz/et-fugiat-sunt-nihil-accusantium</code></p>
        </div>
        </form>

                    <h2 id="brand-profile-DELETEapi-v1-brand-social--handle_id-">Remove a social handle</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-brand-social--handle_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/brand/social/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/social/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-brand-social--handle_id-">
</span>
<span id="execution-results-DELETEapi-v1-brand-social--handle_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-brand-social--handle_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-brand-social--handle_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-brand-social--handle_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-brand-social--handle_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-brand-social--handle_id-" data-method="DELETE"
      data-path="api/v1/brand/social/{handle_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-brand-social--handle_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-brand-social--handle_id-"
                    onclick="tryItOut('DELETEapi-v1-brand-social--handle_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-brand-social--handle_id-"
                    onclick="cancelTryOut('DELETEapi-v1-brand-social--handle_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-brand-social--handle_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/brand/social/{handle_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-brand-social--handle_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-brand-social--handle_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-brand-social--handle_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>handle_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="handle_id"                data-endpoint="DELETEapi-v1-brand-social--handle_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the handle. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="brand-reviews">Brand · Reviews</h1>

    

                                <h2 id="brand-reviews-GETapi-v1-brand-reviews">List reviews received</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Paginated, approved-only, newest first.</p>

<span id="example-requests-GETapi-v1-brand-reviews">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/brand/reviews" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/brand/reviews"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-brand-reviews">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-brand-reviews" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-brand-reviews"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-brand-reviews"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-brand-reviews" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-brand-reviews">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-brand-reviews" data-method="GET"
      data-path="api/v1/brand/reviews"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-brand-reviews', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-brand-reviews"
                    onclick="tryItOut('GETapi-v1-brand-reviews');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-brand-reviews"
                    onclick="cancelTryOut('GETapi-v1-brand-reviews');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-brand-reviews"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/brand/reviews</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-brand-reviews"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-brand-reviews"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-brand-reviews"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="notifications">Notifications</h1>

    

                                <h2 id="notifications-GETapi-v1-notifications">List my notifications</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Paginated, newest first.</p>

<span id="example-requests-GETapi-v1-notifications">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/notifications" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/notifications"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-notifications">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-notifications" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-notifications"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-notifications"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-notifications" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-notifications">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-notifications" data-method="GET"
      data-path="api/v1/notifications"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-notifications', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-notifications"
                    onclick="tryItOut('GETapi-v1-notifications');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-notifications"
                    onclick="cancelTryOut('GETapi-v1-notifications');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-notifications"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/notifications</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-notifications"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-notifications"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="notifications-GETapi-v1-notifications-unread-count">Unread count</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>The number of unread notifications (for a badge).</p>

<span id="example-requests-GETapi-v1-notifications-unread-count">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/notifications/unread-count" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/notifications/unread-count"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-notifications-unread-count">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-notifications-unread-count" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-notifications-unread-count"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-notifications-unread-count"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-notifications-unread-count" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-notifications-unread-count">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-notifications-unread-count" data-method="GET"
      data-path="api/v1/notifications/unread-count"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-notifications-unread-count', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-notifications-unread-count"
                    onclick="tryItOut('GETapi-v1-notifications-unread-count');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-notifications-unread-count"
                    onclick="cancelTryOut('GETapi-v1-notifications-unread-count');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-notifications-unread-count"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/notifications/unread-count</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-notifications-unread-count"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-notifications-unread-count"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-notifications-unread-count"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="notifications-POSTapi-v1-notifications-read-all">Mark all read</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-notifications-read-all">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/notifications/read-all" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/notifications/read-all"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-notifications-read-all">
</span>
<span id="execution-results-POSTapi-v1-notifications-read-all" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-notifications-read-all"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-notifications-read-all"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-notifications-read-all" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-notifications-read-all">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-notifications-read-all" data-method="POST"
      data-path="api/v1/notifications/read-all"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-notifications-read-all', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-notifications-read-all"
                    onclick="tryItOut('POSTapi-v1-notifications-read-all');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-notifications-read-all"
                    onclick="cancelTryOut('POSTapi-v1-notifications-read-all');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-notifications-read-all"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/notifications/read-all</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-notifications-read-all"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-notifications-read-all"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-notifications-read-all"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="notifications-POSTapi-v1-notifications--id--read">Mark one read</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-notifications--id--read">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/notifications/architecto/read" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/notifications/architecto/read"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-notifications--id--read">
</span>
<span id="execution-results-POSTapi-v1-notifications--id--read" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-notifications--id--read"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-notifications--id--read"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-notifications--id--read" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-notifications--id--read">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-notifications--id--read" data-method="POST"
      data-path="api/v1/notifications/{id}/read"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-notifications--id--read', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-notifications--id--read"
                    onclick="tryItOut('POSTapi-v1-notifications--id--read');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-notifications--id--read"
                    onclick="cancelTryOut('POSTapi-v1-notifications--id--read');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-notifications--id--read"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/notifications/{id}/read</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-notifications--id--read"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-notifications--id--read"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-notifications--id--read"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-v1-notifications--id--read"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the notification. Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="reference-lookups">Reference / lookups</h1>

    <p>Public catalog data the mobile app needs to render dynamic UI (dropdowns,
profession pickers, the block catalog, the deal flows on offer). Read-only and
unauthenticated — onboarding forms need these before a token exists.
Translatable names come back in the request locale (Accept-Language).</p>

                                <h2 id="reference-lookups-GETapi-v1-lookups-talent-types">Talent types</h2>

<p>
</p>

<p>The full profession catalog.</p>

<span id="example-requests-GETapi-v1-lookups-talent-types">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/lookups/talent-types" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/lookups/talent-types"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-lookups-talent-types">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-language: en
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;slug&quot;: &quot;model&quot;,
            &quot;name&quot;: &quot;Model&quot;,
            &quot;category&quot;: &quot;model&quot;,
            &quot;icon&quot;: &quot;lucide-model&quot;,
            &quot;description&quot;: &quot;Model on Fama.&quot;
        },
        {
            &quot;id&quot;: 2,
            &quot;slug&quot;: &quot;photographer&quot;,
            &quot;name&quot;: &quot;Photographer&quot;,
            &quot;category&quot;: &quot;crew&quot;,
            &quot;icon&quot;: &quot;lucide-photographer&quot;,
            &quot;description&quot;: &quot;Photographer on Fama.&quot;
        },
        {
            &quot;id&quot;: 3,
            &quot;slug&quot;: &quot;cinematographer&quot;,
            &quot;name&quot;: &quot;Cinematographer (DOP)&quot;,
            &quot;category&quot;: &quot;crew&quot;,
            &quot;icon&quot;: &quot;lucide-cinematographer&quot;,
            &quot;description&quot;: &quot;Cinematographer (DOP) on Fama.&quot;
        },
        {
            &quot;id&quot;: 4,
            &quot;slug&quot;: &quot;creative-director&quot;,
            &quot;name&quot;: &quot;Creative Director&quot;,
            &quot;category&quot;: &quot;creative&quot;,
            &quot;icon&quot;: &quot;lucide-creative-director&quot;,
            &quot;description&quot;: &quot;Creative Director on Fama.&quot;
        },
        {
            &quot;id&quot;: 5,
            &quot;slug&quot;: &quot;stylist&quot;,
            &quot;name&quot;: &quot;Stylist&quot;,
            &quot;category&quot;: &quot;creative&quot;,
            &quot;icon&quot;: &quot;lucide-stylist&quot;,
            &quot;description&quot;: &quot;Stylist on Fama.&quot;
        },
        {
            &quot;id&quot;: 6,
            &quot;slug&quot;: &quot;graphic-designer&quot;,
            &quot;name&quot;: &quot;Graphic Designer&quot;,
            &quot;category&quot;: &quot;creative&quot;,
            &quot;icon&quot;: &quot;lucide-graphic-designer&quot;,
            &quot;description&quot;: &quot;Graphic Designer on Fama.&quot;
        }
    ],
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-lookups-talent-types" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-lookups-talent-types"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-lookups-talent-types"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-lookups-talent-types" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-lookups-talent-types">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-lookups-talent-types" data-method="GET"
      data-path="api/v1/lookups/talent-types"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-lookups-talent-types', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-lookups-talent-types"
                    onclick="tryItOut('GETapi-v1-lookups-talent-types');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-lookups-talent-types"
                    onclick="cancelTryOut('GETapi-v1-lookups-talent-types');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-lookups-talent-types"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/lookups/talent-types</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-lookups-talent-types"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-lookups-talent-types"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="reference-lookups-GETapi-v1-lookups-block-types">Block types</h2>

<p>
</p>

<p>The active profile-block catalog (what a talent can add + how it renders).</p>

<span id="example-requests-GETapi-v1-lookups-block-types">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/lookups/block-types" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/lookups/block-types"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-lookups-block-types">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-language: en
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;key&quot;: &quot;hero&quot;,
            &quot;name&quot;: &quot;Hero&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-hero&quot;,
            &quot;availability&quot;: &quot;universal&quot;,
            &quot;content_source&quot;: &quot;inline&quot;,
            &quot;default_layout&quot;: null,
            &quot;is_repeatable&quot;: false
        },
        {
            &quot;id&quot;: 2,
            &quot;key&quot;: &quot;gallery&quot;,
            &quot;name&quot;: &quot;Gallery&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-gallery&quot;,
            &quot;availability&quot;: &quot;universal&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;masonry&quot;,
            &quot;is_repeatable&quot;: true
        },
        {
            &quot;id&quot;: 3,
            &quot;key&quot;: &quot;brand_collabs&quot;,
            &quot;name&quot;: &quot;Brand Collaborations&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-brand-collabs&quot;,
            &quot;availability&quot;: &quot;universal&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;grid&quot;,
            &quot;is_repeatable&quot;: false
        },
        {
            &quot;id&quot;: 4,
            &quot;key&quot;: &quot;reviews&quot;,
            &quot;name&quot;: &quot;Reviews&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-reviews&quot;,
            &quot;availability&quot;: &quot;universal&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;list&quot;,
            &quot;is_repeatable&quot;: false
        },
        {
            &quot;id&quot;: 5,
            &quot;key&quot;: &quot;services&quot;,
            &quot;name&quot;: &quot;Services&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-services&quot;,
            &quot;availability&quot;: &quot;universal&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;list&quot;,
            &quot;is_repeatable&quot;: false
        },
        {
            &quot;id&quot;: 6,
            &quot;key&quot;: &quot;comp_card&quot;,
            &quot;name&quot;: &quot;Comp Card&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-comp-card&quot;,
            &quot;availability&quot;: &quot;by_category&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: null,
            &quot;is_repeatable&quot;: false
        },
        {
            &quot;id&quot;: 7,
            &quot;key&quot;: &quot;look_types&quot;,
            &quot;name&quot;: &quot;Looks&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-look-types&quot;,
            &quot;availability&quot;: &quot;by_category&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;list&quot;,
            &quot;is_repeatable&quot;: false
        },
        {
            &quot;id&quot;: 8,
            &quot;key&quot;: &quot;digitals&quot;,
            &quot;name&quot;: &quot;Digitals&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-digitals&quot;,
            &quot;availability&quot;: &quot;by_category&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;grid&quot;,
            &quot;is_repeatable&quot;: false
        },
        {
            &quot;id&quot;: 9,
            &quot;key&quot;: &quot;showreel&quot;,
            &quot;name&quot;: &quot;Showreel&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-showreel&quot;,
            &quot;availability&quot;: &quot;by_category&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;carousel&quot;,
            &quot;is_repeatable&quot;: true
        },
        {
            &quot;id&quot;: 10,
            &quot;key&quot;: &quot;equipment&quot;,
            &quot;name&quot;: &quot;Equipment&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-equipment&quot;,
            &quot;availability&quot;: &quot;by_category&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;list&quot;,
            &quot;is_repeatable&quot;: false
        },
        {
            &quot;id&quot;: 11,
            &quot;key&quot;: &quot;projects&quot;,
            &quot;name&quot;: &quot;Projects&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-projects&quot;,
            &quot;availability&quot;: &quot;by_category&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;list&quot;,
            &quot;is_repeatable&quot;: true
        },
        {
            &quot;id&quot;: 12,
            &quot;key&quot;: &quot;software_stack&quot;,
            &quot;name&quot;: &quot;Software&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-software-stack&quot;,
            &quot;availability&quot;: &quot;by_category&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;grid&quot;,
            &quot;is_repeatable&quot;: false
        },
        {
            &quot;id&quot;: 13,
            &quot;key&quot;: &quot;agency_affiliations&quot;,
            &quot;name&quot;: &quot;Agencies&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-agency-affiliations&quot;,
            &quot;availability&quot;: &quot;universal&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;list&quot;,
            &quot;is_repeatable&quot;: false
        },
        {
            &quot;id&quot;: 14,
            &quot;key&quot;: &quot;press_features&quot;,
            &quot;name&quot;: &quot;Press&quot;,
            &quot;description&quot;: null,
            &quot;icon&quot;: &quot;lucide-press-features&quot;,
            &quot;availability&quot;: &quot;universal&quot;,
            &quot;content_source&quot;: &quot;table&quot;,
            &quot;default_layout&quot;: &quot;grid&quot;,
            &quot;is_repeatable&quot;: false
        }
    ],
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-lookups-block-types" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-lookups-block-types"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-lookups-block-types"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-lookups-block-types" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-lookups-block-types">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-lookups-block-types" data-method="GET"
      data-path="api/v1/lookups/block-types"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-lookups-block-types', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-lookups-block-types"
                    onclick="tryItOut('GETapi-v1-lookups-block-types');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-lookups-block-types"
                    onclick="cancelTryOut('GETapi-v1-lookups-block-types');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-lookups-block-types"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/lookups/block-types</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-lookups-block-types"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-lookups-block-types"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="reference-lookups-GETapi-v1-lookups-deal-flows">Deal flows</h2>

<p>
</p>

<p>The active deal flows on offer, each with its ordered steps so the app can
preview the deal loop before starting one.</p>

<span id="example-requests-GETapi-v1-lookups-deal-flows">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/lookups/deal-flows" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/lookups/deal-flows"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-lookups-deal-flows">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-language: en
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Standard Booking&quot;,
            &quot;slug&quot;: &quot;standard-booking&quot;,
            &quot;description&quot;: &quot;The default brand &harr; talent booking flow.&quot;,
            &quot;applies_to&quot;: null,
            &quot;status&quot;: &quot;active&quot;,
            &quot;is_active&quot;: true,
            &quot;is_default&quot;: true,
            &quot;steps&quot;: [
                {
                    &quot;id&quot;: 1,
                    &quot;key&quot;: &quot;brief&quot;,
                    &quot;name&quot;: &quot;Project brief&quot;,
                    &quot;instructions&quot;: &quot;Describe the project, dates and budget.&quot;,
                    &quot;actor&quot;: &quot;brand&quot;,
                    &quot;step_type&quot;: &quot;form&quot;,
                    &quot;position&quot;: 0,
                    &quot;is_required&quot;: true,
                    &quot;is_skippable&quot;: false,
                    &quot;settings&quot;: {
                        &quot;fields&quot;: [
                            &quot;scope&quot;,
                            &quot;dates&quot;,
                            &quot;budget&quot;
                        ]
                    }
                },
                {
                    &quot;id&quot;: 2,
                    &quot;key&quot;: &quot;quote&quot;,
                    &quot;name&quot;: &quot;Talent quote&quot;,
                    &quot;instructions&quot;: &quot;Send your quote for the brief.&quot;,
                    &quot;actor&quot;: &quot;talent&quot;,
                    &quot;step_type&quot;: &quot;form&quot;,
                    &quot;position&quot;: 1,
                    &quot;is_required&quot;: true,
                    &quot;is_skippable&quot;: false,
                    &quot;settings&quot;: {
                        &quot;fields&quot;: [
                            &quot;amount&quot;,
                            &quot;note&quot;
                        ],
                        &quot;amount_field&quot;: &quot;amount&quot;
                    }
                },
                {
                    &quot;id&quot;: 3,
                    &quot;key&quot;: &quot;agreement&quot;,
                    &quot;name&quot;: &quot;Approve quote&quot;,
                    &quot;instructions&quot;: &quot;Approve or reject the quote.&quot;,
                    &quot;actor&quot;: &quot;brand&quot;,
                    &quot;step_type&quot;: &quot;approval&quot;,
                    &quot;position&quot;: 2,
                    &quot;is_required&quot;: true,
                    &quot;is_skippable&quot;: false,
                    &quot;settings&quot;: []
                },
                {
                    &quot;id&quot;: 4,
                    &quot;key&quot;: &quot;payment&quot;,
                    &quot;name&quot;: &quot;Deposit&quot;,
                    &quot;instructions&quot;: &quot;Pay the deposit to lock the booking.&quot;,
                    &quot;actor&quot;: &quot;brand&quot;,
                    &quot;step_type&quot;: &quot;payment&quot;,
                    &quot;position&quot;: 3,
                    &quot;is_required&quot;: true,
                    &quot;is_skippable&quot;: true,
                    &quot;settings&quot;: {
                        &quot;percentage&quot;: 50,
                        &quot;confirmation&quot;: &quot;manual&quot;
                    }
                },
                {
                    &quot;id&quot;: 5,
                    &quot;key&quot;: &quot;delivery&quot;,
                    &quot;name&quot;: &quot;Deliver work&quot;,
                    &quot;instructions&quot;: &quot;Upload the final deliverables.&quot;,
                    &quot;actor&quot;: &quot;talent&quot;,
                    &quot;step_type&quot;: &quot;upload&quot;,
                    &quot;position&quot;: 4,
                    &quot;is_required&quot;: true,
                    &quot;is_skippable&quot;: false,
                    &quot;settings&quot;: {
                        &quot;collection&quot;: &quot;delivery&quot;
                    }
                },
                {
                    &quot;id&quot;: 6,
                    &quot;key&quot;: &quot;signoff&quot;,
                    &quot;name&quot;: &quot;Approve delivery&quot;,
                    &quot;instructions&quot;: &quot;Approve the delivery to complete the deal.&quot;,
                    &quot;actor&quot;: &quot;brand&quot;,
                    &quot;step_type&quot;: &quot;approval&quot;,
                    &quot;position&quot;: 5,
                    &quot;is_required&quot;: true,
                    &quot;is_skippable&quot;: false,
                    &quot;settings&quot;: []
                },
                {
                    &quot;id&quot;: 7,
                    &quot;key&quot;: &quot;complete&quot;,
                    &quot;name&quot;: &quot;Deal complete&quot;,
                    &quot;instructions&quot;: null,
                    &quot;actor&quot;: &quot;system&quot;,
                    &quot;step_type&quot;: &quot;info&quot;,
                    &quot;position&quot;: 6,
                    &quot;is_required&quot;: true,
                    &quot;is_skippable&quot;: false,
                    &quot;settings&quot;: []
                }
            ]
        },
        {
            &quot;id&quot;: 3,
            &quot;name&quot;: &quot;Premium Booking&quot;,
            &quot;slug&quot;: &quot;premium-booking-26bbgw&quot;,
            &quot;description&quot;: null,
            &quot;applies_to&quot;: &quot;creative&quot;,
            &quot;status&quot;: &quot;active&quot;,
            &quot;is_active&quot;: true,
            &quot;is_default&quot;: false,
            &quot;steps&quot;: [
                {
                    &quot;id&quot;: 11,
                    &quot;key&quot;: &quot;brief&quot;,
                    &quot;name&quot;: &quot;Creative brief&quot;,
                    &quot;instructions&quot;: null,
                    &quot;actor&quot;: &quot;brand&quot;,
                    &quot;step_type&quot;: &quot;form&quot;,
                    &quot;position&quot;: 0,
                    &quot;is_required&quot;: true,
                    &quot;is_skippable&quot;: false,
                    &quot;settings&quot;: []
                },
                {
                    &quot;id&quot;: 12,
                    &quot;key&quot;: &quot;contract&quot;,
                    &quot;name&quot;: &quot;Contract&quot;,
                    &quot;instructions&quot;: null,
                    &quot;actor&quot;: &quot;both&quot;,
                    &quot;step_type&quot;: &quot;contract&quot;,
                    &quot;position&quot;: 1,
                    &quot;is_required&quot;: true,
                    &quot;is_skippable&quot;: false,
                    &quot;settings&quot;: []
                }
            ]
        }
    ],
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-lookups-deal-flows" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-lookups-deal-flows"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-lookups-deal-flows"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-lookups-deal-flows" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-lookups-deal-flows">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-lookups-deal-flows" data-method="GET"
      data-path="api/v1/lookups/deal-flows"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-lookups-deal-flows', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-lookups-deal-flows"
                    onclick="tryItOut('GETapi-v1-lookups-deal-flows');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-lookups-deal-flows"
                    onclick="cancelTryOut('GETapi-v1-lookups-deal-flows');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-lookups-deal-flows"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/lookups/deal-flows</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-lookups-deal-flows"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-lookups-deal-flows"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="reference-lookups-GETapi-v1-lookups-options">Option lists</h2>

<p>
</p>

<p>The enum option lists (brand + talent) that back the app's select inputs, so
forms stay in sync with server-side validation.</p>

<span id="example-requests-GETapi-v1-lookups-options">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/lookups/options" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/lookups/options"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-lookups-options">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
content-language: en
x-ratelimit-limit: 60
x-ratelimit-remaining: 59
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;brand&quot;: {
            &quot;industries&quot;: [
                &quot;fashion&quot;,
                &quot;beauty&quot;,
                &quot;food_beverage&quot;,
                &quot;lifestyle&quot;,
                &quot;tech&quot;,
                &quot;other&quot;
            ],
            &quot;stages&quot;: [
                &quot;new&quot;,
                &quot;growing&quot;,
                &quot;established&quot;
            ],
            &quot;geographic_reach&quot;: [
                &quot;same_city&quot;,
                &quot;mena&quot;,
                &quot;international&quot;
            ],
            &quot;project_frequency&quot;: [
                &quot;occasional&quot;,
                &quot;monthly&quot;,
                &quot;weekly&quot;,
                &quot;ongoing&quot;
            ],
            &quot;project_types&quot;: [
                &quot;editorial&quot;,
                &quot;lookbook&quot;,
                &quot;campaign_video&quot;,
                &quot;social_content&quot;,
                &quot;brand_identity&quot;
            ],
            &quot;moods&quot;: [
                &quot;editorial&quot;,
                &quot;minimal&quot;,
                &quot;bold&quot;,
                &quot;warm&quot;,
                &quot;dark&quot;,
                &quot;playful&quot;,
                &quot;luxurious&quot;,
                &quot;raw&quot;,
                &quot;nostalgic&quot;,
                &quot;commercial&quot;
            ],
            &quot;budgets&quot;: [
                &quot;under_500&quot;,
                &quot;500_2000&quot;,
                &quot;2000_10000&quot;,
                &quot;10000_plus&quot;
            ],
            &quot;social_platforms&quot;: [
                &quot;instagram&quot;,
                &quot;tiktok&quot;,
                &quot;x&quot;,
                &quot;linkedin&quot;,
                &quot;youtube&quot;,
                &quot;facebook&quot;,
                &quot;behance&quot;,
                &quot;website&quot;,
                &quot;other&quot;
            ],
            &quot;company_sizes&quot;: [
                &quot;solo&quot;,
                &quot;small&quot;,
                &quot;medium&quot;,
                &quot;large&quot;,
                &quot;enterprise&quot;
            ],
            &quot;campaign_types&quot;: [
                &quot;campaign&quot;,
                &quot;shoot&quot;
            ]
        },
        &quot;talent&quot;: {
            &quot;availability&quot;: [
                &quot;available&quot;,
                &quot;booked&quot;,
                &quot;unavailable&quot;
            ],
            &quot;rate_tiers&quot;: [
                &quot;emerging&quot;,
                &quot;established&quot;,
                &quot;premium&quot;,
                &quot;elite&quot;
            ],
            &quot;booking_types&quot;: [
                &quot;email&quot;,
                &quot;calendar&quot;,
                &quot;form&quot;,
                &quot;external&quot;
            ],
            &quot;representation_types&quot;: [
                &quot;exclusive&quot;,
                &quot;non_exclusive&quot;,
                &quot;mother_agency&quot;,
                &quot;freelance&quot;
            ],
            &quot;service_price_units&quot;: [
                &quot;hour&quot;,
                &quot;day&quot;,
                &quot;project&quot;,
                &quot;fixed&quot;
            ]
        }
    },
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-lookups-options" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-lookups-options"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-lookups-options"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-lookups-options" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-lookups-options">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-lookups-options" data-method="GET"
      data-path="api/v1/lookups/options"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-lookups-options', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-lookups-options"
                    onclick="tryItOut('GETapi-v1-lookups-options');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-lookups-options"
                    onclick="cancelTryOut('GETapi-v1-lookups-options');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-lookups-options"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/lookups/options</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-lookups-options"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-lookups-options"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="talent-account">Talent · Account</h1>

    

                                <h2 id="talent-account-GETapi-v1-talent-account">Get my account</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-talent-account">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/account" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/account"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-account">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-account" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-account"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-account"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-account" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-account">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-account" data-method="GET"
      data-path="api/v1/talent/account"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-account', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-account"
                    onclick="tryItOut('GETapi-v1-talent-account');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-account"
                    onclick="cancelTryOut('GETapi-v1-talent-account');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-account"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/account</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-account"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-account"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-account"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-account-PATCHapi-v1-talent-account">Update my account</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-account">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/account" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"slug\": \"b\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/account"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "slug": "b"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-account">
</span>
<span id="execution-results-PATCHapi-v1-talent-account" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-account"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-account"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-account" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-account">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-account" data-method="PATCH"
      data-path="api/v1/talent/account"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-account', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-account"
                    onclick="tryItOut('PATCHapi-v1-talent-account');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-account"
                    onclick="cancelTryOut('PATCHapi-v1-talent-account');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-account"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/account</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-account"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-account"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-account"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="PATCHapi-v1-talent-account"
               value="b"
               data-component="body">
    <br>
<p>Must contain only letters, numbers, dashes and underscores. Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>meta</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="meta"                data-endpoint="PATCHapi-v1-talent-account"
               value=""
               data-component="body">
    <br>

        </div>
        </form>

                    <h2 id="talent-account-PATCHapi-v1-talent-account-publish">Publish / unpublish my profile</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-account-publish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/account/publish" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"publish\": true
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/account/publish"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "publish": true
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-account-publish">
</span>
<span id="execution-results-PATCHapi-v1-talent-account-publish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-account-publish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-account-publish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-account-publish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-account-publish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-account-publish" data-method="PATCH"
      data-path="api/v1/talent/account/publish"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-account-publish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-account-publish"
                    onclick="tryItOut('PATCHapi-v1-talent-account-publish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-account-publish"
                    onclick="cancelTryOut('PATCHapi-v1-talent-account-publish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-account-publish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/account/publish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-account-publish"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-account-publish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-account-publish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>publish</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
 &nbsp;
 &nbsp;
                <label data-endpoint="PATCHapi-v1-talent-account-publish" style="display: none">
            <input type="radio" name="publish"
                   value="true"
                   data-endpoint="PATCHapi-v1-talent-account-publish"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PATCHapi-v1-talent-account-publish" style="display: none">
            <input type="radio" name="publish"
                   value="false"
                   data-endpoint="PATCHapi-v1-talent-account-publish"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether to publish (true) or unpublish (false). Example: <code>true</code></p>
        </div>
        </form>

                <h1 id="talent-affiliations">Talent · Affiliations</h1>

    

                                <h2 id="talent-affiliations-GETapi-v1-talent-affiliations">List my affiliations</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-talent-affiliations">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/affiliations" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/affiliations"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-affiliations">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-affiliations" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-affiliations"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-affiliations"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-affiliations" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-affiliations">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-affiliations" data-method="GET"
      data-path="api/v1/talent/affiliations"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-affiliations', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-affiliations"
                    onclick="tryItOut('GETapi-v1-talent-affiliations');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-affiliations"
                    onclick="cancelTryOut('GETapi-v1-talent-affiliations');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-affiliations"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/affiliations</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-affiliations"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-affiliations"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-affiliations"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-affiliations-POSTapi-v1-talent-affiliations">Add an affiliation</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-affiliations">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/affiliations" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"agency_name\": \"b\",
    \"agency_url\": \"http:\\/\\/bailey.com\\/\",
    \"representation_type\": \"exclusive\",
    \"region\": \"m\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/affiliations"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "agency_name": "b",
    "agency_url": "http:\/\/bailey.com\/",
    "representation_type": "exclusive",
    "region": "m"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-affiliations">
</span>
<span id="execution-results-POSTapi-v1-talent-affiliations" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-affiliations"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-affiliations"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-affiliations" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-affiliations">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-affiliations" data-method="POST"
      data-path="api/v1/talent/affiliations"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-affiliations', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-affiliations"
                    onclick="tryItOut('POSTapi-v1-talent-affiliations');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-affiliations"
                    onclick="cancelTryOut('POSTapi-v1-talent-affiliations');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-affiliations"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/affiliations</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-affiliations"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-affiliations"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-affiliations"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>agency_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="agency_name"                data-endpoint="POSTapi-v1-talent-affiliations"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>agency_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="agency_url"                data-endpoint="POSTapi-v1-talent-affiliations"
               value="http://bailey.com/"
               data-component="body">
    <br>
<p>Must be a valid URL. Must not be greater than 255 characters. Example: <code>http://bailey.com/</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>representation_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="representation_type"                data-endpoint="POSTapi-v1-talent-affiliations"
               value="exclusive"
               data-component="body">
    <br>
<p>Example: <code>exclusive</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>exclusive</code></li> <li><code>non_exclusive</code></li> <li><code>mother_agency</code></li> <li><code>freelance</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>region</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="region"                data-endpoint="POSTapi-v1-talent-affiliations"
               value="m"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>m</code></p>
        </div>
        </form>

                    <h2 id="talent-affiliations-PATCHapi-v1-talent-affiliations--affiliation_id-">Update an affiliation</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-affiliations--affiliation_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/affiliations/16" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"agency_name\": \"b\",
    \"agency_url\": \"http:\\/\\/bailey.com\\/\",
    \"representation_type\": \"exclusive\",
    \"region\": \"m\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/affiliations/16"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "agency_name": "b",
    "agency_url": "http:\/\/bailey.com\/",
    "representation_type": "exclusive",
    "region": "m"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-affiliations--affiliation_id-">
</span>
<span id="execution-results-PATCHapi-v1-talent-affiliations--affiliation_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-affiliations--affiliation_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-affiliations--affiliation_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-affiliations--affiliation_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-affiliations--affiliation_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-affiliations--affiliation_id-" data-method="PATCH"
      data-path="api/v1/talent/affiliations/{affiliation_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-affiliations--affiliation_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-affiliations--affiliation_id-"
                    onclick="tryItOut('PATCHapi-v1-talent-affiliations--affiliation_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-affiliations--affiliation_id-"
                    onclick="cancelTryOut('PATCHapi-v1-talent-affiliations--affiliation_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-affiliations--affiliation_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/affiliations/{affiliation_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>affiliation_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="affiliation_id"                data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id-"
               value="16"
               data-component="url">
    <br>
<p>The ID of the affiliation. Example: <code>16</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>agency_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="agency_name"                data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id-"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>agency_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="agency_url"                data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id-"
               value="http://bailey.com/"
               data-component="body">
    <br>
<p>Must be a valid URL. Must not be greater than 255 characters. Example: <code>http://bailey.com/</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>representation_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="representation_type"                data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id-"
               value="exclusive"
               data-component="body">
    <br>
<p>Example: <code>exclusive</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>exclusive</code></li> <li><code>non_exclusive</code></li> <li><code>mother_agency</code></li> <li><code>freelance</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>region</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="region"                data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id-"
               value="m"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>m</code></p>
        </div>
        </form>

                    <h2 id="talent-affiliations-PATCHapi-v1-talent-affiliations--affiliation_id--end">End an affiliation</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-affiliations--affiliation_id--end">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/affiliations/16/end" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/affiliations/16/end"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-affiliations--affiliation_id--end">
</span>
<span id="execution-results-PATCHapi-v1-talent-affiliations--affiliation_id--end" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-affiliations--affiliation_id--end"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-affiliations--affiliation_id--end"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-affiliations--affiliation_id--end" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-affiliations--affiliation_id--end">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-affiliations--affiliation_id--end" data-method="PATCH"
      data-path="api/v1/talent/affiliations/{affiliation_id}/end"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-affiliations--affiliation_id--end', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-affiliations--affiliation_id--end"
                    onclick="tryItOut('PATCHapi-v1-talent-affiliations--affiliation_id--end');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-affiliations--affiliation_id--end"
                    onclick="cancelTryOut('PATCHapi-v1-talent-affiliations--affiliation_id--end');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-affiliations--affiliation_id--end"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/affiliations/{affiliation_id}/end</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id--end"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id--end"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id--end"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>affiliation_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="affiliation_id"                data-endpoint="PATCHapi-v1-talent-affiliations--affiliation_id--end"
               value="16"
               data-component="url">
    <br>
<p>The ID of the affiliation. Example: <code>16</code></p>
            </div>
                    </form>

                    <h2 id="talent-affiliations-DELETEapi-v1-talent-affiliations--affiliation_id-">Remove an affiliation</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-talent-affiliations--affiliation_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/talent/affiliations/16" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/affiliations/16"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-talent-affiliations--affiliation_id-">
</span>
<span id="execution-results-DELETEapi-v1-talent-affiliations--affiliation_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-talent-affiliations--affiliation_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-talent-affiliations--affiliation_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-talent-affiliations--affiliation_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-talent-affiliations--affiliation_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-talent-affiliations--affiliation_id-" data-method="DELETE"
      data-path="api/v1/talent/affiliations/{affiliation_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-talent-affiliations--affiliation_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-talent-affiliations--affiliation_id-"
                    onclick="tryItOut('DELETEapi-v1-talent-affiliations--affiliation_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-talent-affiliations--affiliation_id-"
                    onclick="cancelTryOut('DELETEapi-v1-talent-affiliations--affiliation_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-talent-affiliations--affiliation_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/talent/affiliations/{affiliation_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-talent-affiliations--affiliation_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-talent-affiliations--affiliation_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-talent-affiliations--affiliation_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>affiliation_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="affiliation_id"                data-endpoint="DELETEapi-v1-talent-affiliations--affiliation_id-"
               value="16"
               data-component="url">
    <br>
<p>The ID of the affiliation. Example: <code>16</code></p>
            </div>
                    </form>

                <h1 id="talent-availability">Talent · Availability</h1>

    

                                <h2 id="talent-availability-GETapi-v1-talent-availability">Get my availability</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-talent-availability">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/availability" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/availability"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-availability">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-availability" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-availability"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-availability"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-availability" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-availability">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-availability" data-method="GET"
      data-path="api/v1/talent/availability"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-availability', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-availability"
                    onclick="tryItOut('GETapi-v1-talent-availability');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-availability"
                    onclick="cancelTryOut('GETapi-v1-talent-availability');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-availability"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/availability</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-availability"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-availability"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-availability"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-availability-PATCHapi-v1-talent-availability">Update my availability &amp; travel</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-availability">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/availability" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"availability_status\": \"available\",
    \"willing_to_travel\": true,
    \"travel_regions\": [
        \"b\"
    ],
    \"rate_tier\": \"emerging\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/availability"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "availability_status": "available",
    "willing_to_travel": true,
    "travel_regions": [
        "b"
    ],
    "rate_tier": "emerging"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-availability">
</span>
<span id="execution-results-PATCHapi-v1-talent-availability" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-availability"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-availability"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-availability" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-availability">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-availability" data-method="PATCH"
      data-path="api/v1/talent/availability"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-availability', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-availability"
                    onclick="tryItOut('PATCHapi-v1-talent-availability');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-availability"
                    onclick="cancelTryOut('PATCHapi-v1-talent-availability');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-availability"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/availability</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-availability"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-availability"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-availability"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>availability_status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="availability_status"                data-endpoint="PATCHapi-v1-talent-availability"
               value="available"
               data-component="body">
    <br>
<p>Example: <code>available</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>available</code></li> <li><code>booked</code></li> <li><code>unavailable</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>willing_to_travel</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PATCHapi-v1-talent-availability" style="display: none">
            <input type="radio" name="willing_to_travel"
                   value="true"
                   data-endpoint="PATCHapi-v1-talent-availability"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PATCHapi-v1-talent-availability" style="display: none">
            <input type="radio" name="willing_to_travel"
                   value="false"
                   data-endpoint="PATCHapi-v1-talent-availability"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>true</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>travel_regions</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="travel_regions[0]"                data-endpoint="PATCHapi-v1-talent-availability"
               data-component="body">
        <input type="text" style="display: none"
               name="travel_regions[1]"                data-endpoint="PATCHapi-v1-talent-availability"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>rate_tier</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="rate_tier"                data-endpoint="PATCHapi-v1-talent-availability"
               value="emerging"
               data-component="body">
    <br>
<p>Example: <code>emerging</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>emerging</code></li> <li><code>established</code></li> <li><code>premium</code></li> <li><code>elite</code></li></ul>
        </div>
        </form>

                <h1 id="talent-comp-card">Talent · Comp card</h1>

    

                                <h2 id="talent-comp-card-GETapi-v1-talent-comp-card">Get my comp card</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-talent-comp-card">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/comp-card" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/comp-card"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-comp-card">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-comp-card" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-comp-card"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-comp-card"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-comp-card" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-comp-card">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-comp-card" data-method="GET"
      data-path="api/v1/talent/comp-card"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-comp-card', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-comp-card"
                    onclick="tryItOut('GETapi-v1-talent-comp-card');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-comp-card"
                    onclick="cancelTryOut('GETapi-v1-talent-comp-card');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-comp-card"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/comp-card</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-comp-card"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-comp-card"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-comp-card"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-comp-card-PUTapi-v1-talent-comp-card">Create / update my comp card</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PUTapi-v1-talent-comp-card">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "https://fama.test/api/v1/talent/comp-card" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"height_cm\": 1,
    \"bust_cm\": 22,
    \"waist_cm\": 7,
    \"hips_cm\": 16,
    \"shoe_size\": \"miyvdljnikhwaykc\",
    \"dress_size\": \"myuwpwlvqwrsitcp\",
    \"hair_color\": \"s\",
    \"eye_color\": \"c\",
    \"skin_tone\": \"q\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/comp-card"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "height_cm": 1,
    "bust_cm": 22,
    "waist_cm": 7,
    "hips_cm": 16,
    "shoe_size": "miyvdljnikhwaykc",
    "dress_size": "myuwpwlvqwrsitcp",
    "hair_color": "s",
    "eye_color": "c",
    "skin_tone": "q"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-talent-comp-card">
</span>
<span id="execution-results-PUTapi-v1-talent-comp-card" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-talent-comp-card"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-talent-comp-card"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-talent-comp-card" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-talent-comp-card">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-talent-comp-card" data-method="PUT"
      data-path="api/v1/talent/comp-card"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-talent-comp-card', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-talent-comp-card"
                    onclick="tryItOut('PUTapi-v1-talent-comp-card');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-talent-comp-card"
                    onclick="cancelTryOut('PUTapi-v1-talent-comp-card');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-talent-comp-card"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/talent/comp-card</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PUTapi-v1-talent-comp-card"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>height_cm</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="height_cm"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="1"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 300. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>bust_cm</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="bust_cm"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 300. Example: <code>22</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>waist_cm</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="waist_cm"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="7"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 300. Example: <code>7</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>hips_cm</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="hips_cm"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="16"
               data-component="body">
    <br>
<p>Must be at least 0. Must not be greater than 300. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>shoe_size</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="shoe_size"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="miyvdljnikhwaykc"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>miyvdljnikhwaykc</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>dress_size</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="dress_size"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="myuwpwlvqwrsitcp"
               data-component="body">
    <br>
<p>Must not be greater than 20 characters. Example: <code>myuwpwlvqwrsitcp</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>hair_color</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="hair_color"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="s"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>s</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>eye_color</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="eye_color"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="c"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>c</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>skin_tone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="skin_tone"                data-endpoint="PUTapi-v1-talent-comp-card"
               value="q"
               data-component="body">
    <br>
<p>Must not be greater than 50 characters. Example: <code>q</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>measurements</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="measurements"                data-endpoint="PUTapi-v1-talent-comp-card"
               value=""
               data-component="body">
    <br>

        </div>
        </form>

                    <h2 id="talent-comp-card-DELETEapi-v1-talent-comp-card">Delete my comp card</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-talent-comp-card">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/talent/comp-card" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/comp-card"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-talent-comp-card">
</span>
<span id="execution-results-DELETEapi-v1-talent-comp-card" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-talent-comp-card"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-talent-comp-card"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-talent-comp-card" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-talent-comp-card">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-talent-comp-card" data-method="DELETE"
      data-path="api/v1/talent/comp-card"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-talent-comp-card', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-talent-comp-card"
                    onclick="tryItOut('DELETEapi-v1-talent-comp-card');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-talent-comp-card"
                    onclick="cancelTryOut('DELETEapi-v1-talent-comp-card');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-talent-comp-card"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/talent/comp-card</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-talent-comp-card"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-talent-comp-card"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-talent-comp-card"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="talent-content">Talent · Content</h1>

    

                                <h2 id="talent-content-GETapi-v1-talent-content--type-">List content items</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Paginated rows for <code>{type}</code>, in display order.</p>

<span id="example-requests-GETapi-v1-talent-content--type-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/content/gallery" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/content/gallery"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-content--type-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-content--type-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-content--type-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-content--type-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-content--type-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-content--type-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-content--type-" data-method="GET"
      data-path="api/v1/talent/content/{type}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-content--type-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-content--type-"
                    onclick="tryItOut('GETapi-v1-talent-content--type-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-content--type-"
                    onclick="cancelTryOut('GETapi-v1-talent-content--type-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-content--type-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/content/{type}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-content--type-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-content--type-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-content--type-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="GETapi-v1-talent-content--type-"
               value="gallery"
               data-component="url">
    <br>
<p>The content type. Example: <code>gallery</code></p>
            </div>
                    </form>

                    <h2 id="talent-content-POSTapi-v1-talent-content--type-">Create a content item</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-content--type-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/content/gallery" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/content/gallery"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-content--type-">
</span>
<span id="execution-results-POSTapi-v1-talent-content--type-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-content--type-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-content--type-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-content--type-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-content--type-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-content--type-" data-method="POST"
      data-path="api/v1/talent/content/{type}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-content--type-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-content--type-"
                    onclick="tryItOut('POSTapi-v1-talent-content--type-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-content--type-"
                    onclick="cancelTryOut('POSTapi-v1-talent-content--type-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-content--type-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/content/{type}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-content--type-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-content--type-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-content--type-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-v1-talent-content--type-"
               value="gallery"
               data-component="url">
    <br>
<p>The content type. Example: <code>gallery</code></p>
            </div>
                    </form>

                    <h2 id="talent-content-PATCHapi-v1-talent-content--type--reorder">Reorder content items</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-content--type--reorder">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/content/gallery/reorder" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"order\": [
        3,
        1,
        2
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/content/gallery/reorder"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "order": [
        3,
        1,
        2
    ]
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-content--type--reorder">
</span>
<span id="execution-results-PATCHapi-v1-talent-content--type--reorder" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-content--type--reorder"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-content--type--reorder"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-content--type--reorder" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-content--type--reorder">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-content--type--reorder" data-method="PATCH"
      data-path="api/v1/talent/content/{type}/reorder"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-content--type--reorder', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-content--type--reorder"
                    onclick="tryItOut('PATCHapi-v1-talent-content--type--reorder');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-content--type--reorder"
                    onclick="cancelTryOut('PATCHapi-v1-talent-content--type--reorder');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-content--type--reorder"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/content/{type}/reorder</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-content--type--reorder"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-content--type--reorder"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-content--type--reorder"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="PATCHapi-v1-talent-content--type--reorder"
               value="gallery"
               data-component="url">
    <br>
<p>The content type. Example: <code>gallery</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>order</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="order[0]"                data-endpoint="PATCHapi-v1-talent-content--type--reorder"
               data-component="body">
        <input type="number" style="display: none"
               name="order[1]"                data-endpoint="PATCHapi-v1-talent-content--type--reorder"
               data-component="body">
    <br>
<p>The item ids in the desired order.</p>
        </div>
        </form>

                    <h2 id="talent-content-POSTapi-v1-talent-content--type---id--media">Upload media for a content item</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Multipart <code>file</code> (image/video). Returns the item with its conversion URL.</p>

<span id="example-requests-POSTapi-v1-talent-content--type---id--media">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/content/gallery/architecto/media" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "file=@/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phpuqmjskdn7evv4Sz0Y64" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/content/gallery/architecto/media"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-content--type---id--media">
</span>
<span id="execution-results-POSTapi-v1-talent-content--type---id--media" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-content--type---id--media"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-content--type---id--media"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-content--type---id--media" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-content--type---id--media">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-content--type---id--media" data-method="POST"
      data-path="api/v1/talent/content/{type}/{id}/media"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-content--type---id--media', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-content--type---id--media"
                    onclick="tryItOut('POSTapi-v1-talent-content--type---id--media');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-content--type---id--media"
                    onclick="cancelTryOut('POSTapi-v1-talent-content--type---id--media');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-content--type---id--media"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/content/{type}/{id}/media</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-content--type---id--media"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-content--type---id--media"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-content--type---id--media"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-v1-talent-content--type---id--media"
               value="gallery"
               data-component="url">
    <br>
<p>The content type. Example: <code>gallery</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-v1-talent-content--type---id--media"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the {type}. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-v1-talent-content--type---id--media"
               value=""
               data-component="body">
    <br>
<p>The asset to upload. Example: <code>/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phpuqmjskdn7evv4Sz0Y64</code></p>
        </div>
        </form>

                    <h2 id="talent-content-PATCHapi-v1-talent-content--type---id-">Update a content item</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-content--type---id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/content/gallery/architecto" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/content/gallery/architecto"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-content--type---id-">
</span>
<span id="execution-results-PATCHapi-v1-talent-content--type---id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-content--type---id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-content--type---id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-content--type---id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-content--type---id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-content--type---id-" data-method="PATCH"
      data-path="api/v1/talent/content/{type}/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-content--type---id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-content--type---id-"
                    onclick="tryItOut('PATCHapi-v1-talent-content--type---id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-content--type---id-"
                    onclick="cancelTryOut('PATCHapi-v1-talent-content--type---id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-content--type---id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/content/{type}/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-content--type---id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-content--type---id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-content--type---id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="PATCHapi-v1-talent-content--type---id-"
               value="gallery"
               data-component="url">
    <br>
<p>The content type. Example: <code>gallery</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="PATCHapi-v1-talent-content--type---id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the {type}. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="talent-content-DELETEapi-v1-talent-content--type---id-">Delete a content item</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-talent-content--type---id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/talent/content/gallery/architecto" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/content/gallery/architecto"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-talent-content--type---id-">
</span>
<span id="execution-results-DELETEapi-v1-talent-content--type---id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-talent-content--type---id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-talent-content--type---id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-talent-content--type---id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-talent-content--type---id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-talent-content--type---id-" data-method="DELETE"
      data-path="api/v1/talent/content/{type}/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-talent-content--type---id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-talent-content--type---id-"
                    onclick="tryItOut('DELETEapi-v1-talent-content--type---id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-talent-content--type---id-"
                    onclick="cancelTryOut('DELETEapi-v1-talent-content--type---id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-talent-content--type---id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/talent/content/{type}/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-talent-content--type---id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-talent-content--type---id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-talent-content--type---id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="DELETEapi-v1-talent-content--type---id-"
               value="gallery"
               data-component="url">
    <br>
<p>The content type. Example: <code>gallery</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-v1-talent-content--type---id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the {type}. Example: <code>architecto</code></p>
            </div>
                    </form>

                <h1 id="talent-deals">Talent · Deals</h1>

    

                                <h2 id="talent-deals-GETapi-v1-talent-deals">List my deals</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Paginated inbox, newest first, with the counterparty + current step loaded.</p>

<span id="example-requests-GETapi-v1-talent-deals">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/deals?status=awaiting_talent" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/deals"
);

const params = {
    "status": "awaiting_talent",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-deals">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-deals" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-deals"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-deals"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-deals" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-deals">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-deals" data-method="GET"
      data-path="api/v1/talent/deals"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-deals', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-deals"
                    onclick="tryItOut('GETapi-v1-talent-deals');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-deals"
                    onclick="cancelTryOut('GETapi-v1-talent-deals');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-deals"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/deals</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-deals"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-deals"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-deals"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-talent-deals"
               value="awaiting_talent"
               data-component="query">
    <br>
<p>Filter by deal status (e.g. awaiting_talent, completed). Example: <code>awaiting_talent</code></p>
            </div>
                </form>

                    <h2 id="talent-deals-GETapi-v1-talent-deals--deal_id-">Get a deal room</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>The deal, its stepper, the message timeline and whether the talent can act
on the current step. Reading the room marks the brand's messages read.</p>

<span id="example-requests-GETapi-v1-talent-deals--deal_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/deals/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/deals/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-deals--deal_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-deals--deal_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-deals--deal_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-deals--deal_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-deals--deal_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-deals--deal_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-deals--deal_id-" data-method="GET"
      data-path="api/v1/talent/deals/{deal_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-deals--deal_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-deals--deal_id-"
                    onclick="tryItOut('GETapi-v1-talent-deals--deal_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-deals--deal_id-"
                    onclick="cancelTryOut('GETapi-v1-talent-deals--deal_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-deals--deal_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/deals/{deal_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-deals--deal_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-deals--deal_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-deals--deal_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="GETapi-v1-talent-deals--deal_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="talent-deals-POSTapi-v1-talent-deals--deal_id--advance">Complete the current step</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Submits the current step for the talent (send quote / accept / upload / sign
/ pay). The body shape depends on the step's <code>step_type</code>.</p>

<span id="example-requests-POSTapi-v1-talent-deals--deal_id--advance">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/deals/1/advance" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/deals/1/advance"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-deals--deal_id--advance">
</span>
<span id="execution-results-POSTapi-v1-talent-deals--deal_id--advance" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-deals--deal_id--advance"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-deals--deal_id--advance"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-deals--deal_id--advance" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-deals--deal_id--advance">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-deals--deal_id--advance" data-method="POST"
      data-path="api/v1/talent/deals/{deal_id}/advance"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-deals--deal_id--advance', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-deals--deal_id--advance"
                    onclick="tryItOut('POSTapi-v1-talent-deals--deal_id--advance');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-deals--deal_id--advance"
                    onclick="cancelTryOut('POSTapi-v1-talent-deals--deal_id--advance');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-deals--deal_id--advance"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/deals/{deal_id}/advance</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-deals--deal_id--advance"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-deals--deal_id--advance"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-deals--deal_id--advance"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="POSTapi-v1-talent-deals--deal_id--advance"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="talent-deals-POSTapi-v1-talent-deals--deal_id--reject">Reject the current step</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-deals--deal_id--reject">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/deals/1/reject" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reason\": \"Please revise the scope.\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/deals/1/reject"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reason": "Please revise the scope."
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-deals--deal_id--reject">
</span>
<span id="execution-results-POSTapi-v1-talent-deals--deal_id--reject" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-deals--deal_id--reject"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-deals--deal_id--reject"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-deals--deal_id--reject" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-deals--deal_id--reject">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-deals--deal_id--reject" data-method="POST"
      data-path="api/v1/talent/deals/{deal_id}/reject"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-deals--deal_id--reject', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-deals--deal_id--reject"
                    onclick="tryItOut('POSTapi-v1-talent-deals--deal_id--reject');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-deals--deal_id--reject"
                    onclick="cancelTryOut('POSTapi-v1-talent-deals--deal_id--reject');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-deals--deal_id--reject"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/deals/{deal_id}/reject</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-deals--deal_id--reject"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-deals--deal_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-deals--deal_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="POSTapi-v1-talent-deals--deal_id--reject"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-talent-deals--deal_id--reject"
               value="Please revise the scope."
               data-component="body">
    <br>
<p>A note explaining the rejection. Example: <code>Please revise the scope.</code></p>
        </div>
        </form>

                    <h2 id="talent-deals-POSTapi-v1-talent-deals--deal_id--skip">Skip the current step (if skippable)</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-deals--deal_id--skip">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/deals/1/skip" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/deals/1/skip"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-deals--deal_id--skip">
</span>
<span id="execution-results-POSTapi-v1-talent-deals--deal_id--skip" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-deals--deal_id--skip"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-deals--deal_id--skip"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-deals--deal_id--skip" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-deals--deal_id--skip">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-deals--deal_id--skip" data-method="POST"
      data-path="api/v1/talent/deals/{deal_id}/skip"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-deals--deal_id--skip', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-deals--deal_id--skip"
                    onclick="tryItOut('POSTapi-v1-talent-deals--deal_id--skip');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-deals--deal_id--skip"
                    onclick="cancelTryOut('POSTapi-v1-talent-deals--deal_id--skip');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-deals--deal_id--skip"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/deals/{deal_id}/skip</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-deals--deal_id--skip"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-deals--deal_id--skip"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-deals--deal_id--skip"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="POSTapi-v1-talent-deals--deal_id--skip"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="talent-deals-POSTapi-v1-talent-deals--deal_id--message">Post a message to the deal thread</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-deals--deal_id--message">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/deals/1/message" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"body\": \"Thanks — sending the files now.\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/deals/1/message"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "body": "Thanks — sending the files now."
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-deals--deal_id--message">
</span>
<span id="execution-results-POSTapi-v1-talent-deals--deal_id--message" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-deals--deal_id--message"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-deals--deal_id--message"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-deals--deal_id--message" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-deals--deal_id--message">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-deals--deal_id--message" data-method="POST"
      data-path="api/v1/talent/deals/{deal_id}/message"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-deals--deal_id--message', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-deals--deal_id--message"
                    onclick="tryItOut('POSTapi-v1-talent-deals--deal_id--message');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-deals--deal_id--message"
                    onclick="cancelTryOut('POSTapi-v1-talent-deals--deal_id--message');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-deals--deal_id--message"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/deals/{deal_id}/message</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-deals--deal_id--message"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-deals--deal_id--message"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-deals--deal_id--message"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deal_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deal_id"                data-endpoint="POSTapi-v1-talent-deals--deal_id--message"
               value="1"
               data-component="url">
    <br>
<p>The ID of the deal. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="POSTapi-v1-talent-deals--deal_id--message"
               value="Thanks — sending the files now."
               data-component="body">
    <br>
<p>The message. Example: <code>Thanks — sending the files now.</code></p>
        </div>
        </form>

                <h1 id="talent-enquiries">Talent · Enquiries</h1>

    

                                <h2 id="talent-enquiries-GETapi-v1-talent-enquiries">List my enquiries</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Paginated, newest first.</p>

<span id="example-requests-GETapi-v1-talent-enquiries">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/enquiries" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/enquiries"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-enquiries">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-enquiries" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-enquiries"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-enquiries"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-enquiries" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-enquiries">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-enquiries" data-method="GET"
      data-path="api/v1/talent/enquiries"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-enquiries', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-enquiries"
                    onclick="tryItOut('GETapi-v1-talent-enquiries');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-enquiries"
                    onclick="cancelTryOut('GETapi-v1-talent-enquiries');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-enquiries"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/enquiries</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-enquiries"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-enquiries"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-enquiries"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-enquiries-GETapi-v1-talent-enquiries--enquiry_id-">Get an enquiry</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-talent-enquiries--enquiry_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/enquiries/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/enquiries/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-enquiries--enquiry_id-">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-enquiries--enquiry_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-enquiries--enquiry_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-enquiries--enquiry_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-enquiries--enquiry_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-enquiries--enquiry_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-enquiries--enquiry_id-" data-method="GET"
      data-path="api/v1/talent/enquiries/{enquiry_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-enquiries--enquiry_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-enquiries--enquiry_id-"
                    onclick="tryItOut('GETapi-v1-talent-enquiries--enquiry_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-enquiries--enquiry_id-"
                    onclick="cancelTryOut('GETapi-v1-talent-enquiries--enquiry_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-enquiries--enquiry_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/enquiries/{enquiry_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-enquiries--enquiry_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-enquiries--enquiry_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-enquiries--enquiry_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>enquiry_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="enquiry_id"                data-endpoint="GETapi-v1-talent-enquiries--enquiry_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the enquiry. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="talent-press">Talent · Press</h1>

    

                                <h2 id="talent-press-GETapi-v1-talent-press">List my press features</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-talent-press">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/press" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/press"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-press">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-press" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-press"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-press"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-press" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-press">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-press" data-method="GET"
      data-path="api/v1/talent/press"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-press', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-press"
                    onclick="tryItOut('GETapi-v1-talent-press');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-press"
                    onclick="cancelTryOut('GETapi-v1-talent-press');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-press"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/press</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-press"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-press"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-press"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-press-POSTapi-v1-talent-press">Add a press feature</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-press">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/press" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"publication\": \"b\",
    \"title\": \"n\",
    \"url\": \"http:\\/\\/crooks.biz\\/et-fugiat-sunt-nihil-accusantium\",
    \"published_date\": \"2026-07-10T11:49:04\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/press"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "publication": "b",
    "title": "n",
    "url": "http:\/\/crooks.biz\/et-fugiat-sunt-nihil-accusantium",
    "published_date": "2026-07-10T11:49:04"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-press">
</span>
<span id="execution-results-POSTapi-v1-talent-press" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-press"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-press"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-press" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-press">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-press" data-method="POST"
      data-path="api/v1/talent/press"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-press', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-press"
                    onclick="tryItOut('POSTapi-v1-talent-press');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-press"
                    onclick="cancelTryOut('POSTapi-v1-talent-press');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-press"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/press</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-press"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-press"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-press"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>publication</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="publication"                data-endpoint="POSTapi-v1-talent-press"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="POSTapi-v1-talent-press"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="url"                data-endpoint="POSTapi-v1-talent-press"
               value="http://crooks.biz/et-fugiat-sunt-nihil-accusantium"
               data-component="body">
    <br>
<p>Must be a valid URL. Must not be greater than 255 characters. Example: <code>http://crooks.biz/et-fugiat-sunt-nihil-accusantium</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>published_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="published_date"                data-endpoint="POSTapi-v1-talent-press"
               value="2026-07-10T11:49:04"
               data-component="body">
    <br>
<p>Must be a valid date. Example: <code>2026-07-10T11:49:04</code></p>
        </div>
        </form>

                    <h2 id="talent-press-DELETEapi-v1-talent-press--press_id-">Remove a press feature</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-talent-press--press_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/talent/press/16" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/press/16"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-talent-press--press_id-">
</span>
<span id="execution-results-DELETEapi-v1-talent-press--press_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-talent-press--press_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-talent-press--press_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-talent-press--press_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-talent-press--press_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-talent-press--press_id-" data-method="DELETE"
      data-path="api/v1/talent/press/{press_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-talent-press--press_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-talent-press--press_id-"
                    onclick="tryItOut('DELETEapi-v1-talent-press--press_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-talent-press--press_id-"
                    onclick="cancelTryOut('DELETEapi-v1-talent-press--press_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-talent-press--press_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/talent/press/{press_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-talent-press--press_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-talent-press--press_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-talent-press--press_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>press_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="press_id"                data-endpoint="DELETEapi-v1-talent-press--press_id-"
               value="16"
               data-component="url">
    <br>
<p>The ID of the press. Example: <code>16</code></p>
            </div>
                    </form>

                <h1 id="talent-professions">Talent · Professions</h1>

    

                                <h2 id="talent-professions-GETapi-v1-talent-professions">List my professions</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>The linked (ordered) professions and the ones still available to add.</p>

<span id="example-requests-GETapi-v1-talent-professions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/professions" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/professions"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-professions">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-professions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-professions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-professions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-professions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-professions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-professions" data-method="GET"
      data-path="api/v1/talent/professions"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-professions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-professions"
                    onclick="tryItOut('GETapi-v1-talent-professions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-professions"
                    onclick="cancelTryOut('GETapi-v1-talent-professions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-professions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/professions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-professions"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-professions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-professions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-professions-POSTapi-v1-talent-professions">Add a profession</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-professions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/professions" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"talent_type_id\": 16,
    \"is_primary\": true
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/professions"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "talent_type_id": 16,
    "is_primary": true
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-professions">
</span>
<span id="execution-results-POSTapi-v1-talent-professions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-professions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-professions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-professions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-professions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-professions" data-method="POST"
      data-path="api/v1/talent/professions"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-professions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-professions"
                    onclick="tryItOut('POSTapi-v1-talent-professions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-professions"
                    onclick="cancelTryOut('POSTapi-v1-talent-professions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-professions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/professions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-professions"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-professions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-professions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>talent_type_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="talent_type_id"                data-endpoint="POSTapi-v1-talent-professions"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>is_primary</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-talent-professions" style="display: none">
            <input type="radio" name="is_primary"
                   value="true"
                   data-endpoint="POSTapi-v1-talent-professions"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-talent-professions" style="display: none">
            <input type="radio" name="is_primary"
                   value="false"
                   data-endpoint="POSTapi-v1-talent-professions"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>true</code></p>
        </div>
        </form>

                    <h2 id="talent-professions-PATCHapi-v1-talent-professions-reorder">Reorder my professions</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-professions-reorder">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/professions/reorder" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"order\": [
        16
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/professions/reorder"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "order": [
        16
    ]
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-professions-reorder">
</span>
<span id="execution-results-PATCHapi-v1-talent-professions-reorder" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-professions-reorder"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-professions-reorder"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-professions-reorder" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-professions-reorder">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-professions-reorder" data-method="PATCH"
      data-path="api/v1/talent/professions/reorder"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-professions-reorder', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-professions-reorder"
                    onclick="tryItOut('PATCHapi-v1-talent-professions-reorder');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-professions-reorder"
                    onclick="cancelTryOut('PATCHapi-v1-talent-professions-reorder');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-professions-reorder"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/professions/reorder</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-professions-reorder"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-professions-reorder"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-professions-reorder"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>order</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="order[0]"                data-endpoint="PATCHapi-v1-talent-professions-reorder"
               data-component="body">
        <input type="number" style="display: none"
               name="order[1]"                data-endpoint="PATCHapi-v1-talent-professions-reorder"
               data-component="body">
    <br>

        </div>
        </form>

                    <h2 id="talent-professions-PATCHapi-v1-talent-professions--type_id--primary">Set my primary profession</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-professions--type_id--primary">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/professions/1/primary" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/professions/1/primary"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-professions--type_id--primary">
</span>
<span id="execution-results-PATCHapi-v1-talent-professions--type_id--primary" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-professions--type_id--primary"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-professions--type_id--primary"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-professions--type_id--primary" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-professions--type_id--primary">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-professions--type_id--primary" data-method="PATCH"
      data-path="api/v1/talent/professions/{type_id}/primary"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-professions--type_id--primary', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-professions--type_id--primary"
                    onclick="tryItOut('PATCHapi-v1-talent-professions--type_id--primary');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-professions--type_id--primary"
                    onclick="cancelTryOut('PATCHapi-v1-talent-professions--type_id--primary');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-professions--type_id--primary"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/professions/{type_id}/primary</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-professions--type_id--primary"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-professions--type_id--primary"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-professions--type_id--primary"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="type_id"                data-endpoint="PATCHapi-v1-talent-professions--type_id--primary"
               value="1"
               data-component="url">
    <br>
<p>The ID of the type. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="talent-professions-DELETEapi-v1-talent-professions--type_id-">Remove a profession</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-talent-professions--type_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/talent/professions/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/professions/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-talent-professions--type_id-">
</span>
<span id="execution-results-DELETEapi-v1-talent-professions--type_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-talent-professions--type_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-talent-professions--type_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-talent-professions--type_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-talent-professions--type_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-talent-professions--type_id-" data-method="DELETE"
      data-path="api/v1/talent/professions/{type_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-talent-professions--type_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-talent-professions--type_id-"
                    onclick="tryItOut('DELETEapi-v1-talent-professions--type_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-talent-professions--type_id-"
                    onclick="cancelTryOut('DELETEapi-v1-talent-professions--type_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-talent-professions--type_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/talent/professions/{type_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-talent-professions--type_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-talent-professions--type_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-talent-professions--type_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="type_id"                data-endpoint="DELETEapi-v1-talent-professions--type_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the type. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="talent-profile">Talent · Profile</h1>

    

                                <h2 id="talent-profile-GETapi-v1-talent-profile">Get my profile</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Core fields (translatables as per-locale maps), linked professions and the
ordered profile blocks.</p>

<span id="example-requests-GETapi-v1-talent-profile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/profile" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/profile"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-profile">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-profile" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-profile"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-profile"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-profile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-profile">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-profile" data-method="GET"
      data-path="api/v1/talent/profile"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-profile', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-profile"
                    onclick="tryItOut('GETapi-v1-talent-profile');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-profile"
                    onclick="cancelTryOut('GETapi-v1-talent-profile');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-profile"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/profile</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-profile"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-profile-PATCHapi-v1-talent-profile">Update my core profile</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-profile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/profile" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"display_name\": \"b\",
    \"headline\": {
        \"en\": \"n\",
        \"ar\": \"g\"
    },
    \"bio\": {
        \"en\": \"z\",
        \"ar\": \"m\"
    },
    \"slug\": \"i\",
    \"base_city\": \"y\",
    \"base_country\": \"v\",
    \"booking_type\": \"external\",
    \"booking_value\": \"d\",
    \"willing_to_travel\": false,
    \"travel_regions\": [
        \"l\"
    ],
    \"rate_tier\": \"elite\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/profile"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "display_name": "b",
    "headline": {
        "en": "n",
        "ar": "g"
    },
    "bio": {
        "en": "z",
        "ar": "m"
    },
    "slug": "i",
    "base_city": "y",
    "base_country": "v",
    "booking_type": "external",
    "booking_value": "d",
    "willing_to_travel": false,
    "travel_regions": [
        "l"
    ],
    "rate_tier": "elite"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-profile">
</span>
<span id="execution-results-PATCHapi-v1-talent-profile" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-profile"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-profile"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-profile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-profile">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-profile" data-method="PATCH"
      data-path="api/v1/talent/profile"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-profile', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-profile"
                    onclick="tryItOut('PATCHapi-v1-talent-profile');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-profile"
                    onclick="cancelTryOut('PATCHapi-v1-talent-profile');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-profile"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/profile</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-profile"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-profile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>display_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="display_name"                data-endpoint="PATCHapi-v1-talent-profile"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>headline</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>en</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="headline.en"                data-endpoint="PATCHapi-v1-talent-profile"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="headline.ar"                data-endpoint="PATCHapi-v1-talent-profile"
               value="g"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>g</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>bio</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>en</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="bio.en"                data-endpoint="PATCHapi-v1-talent-profile"
               value="z"
               data-component="body">
    <br>
<p>Must not be greater than 5000 characters. Example: <code>z</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="bio.ar"                data-endpoint="PATCHapi-v1-talent-profile"
               value="m"
               data-component="body">
    <br>
<p>Must not be greater than 5000 characters. Example: <code>m</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="PATCHapi-v1-talent-profile"
               value="i"
               data-component="body">
    <br>
<p>Must contain only letters, numbers, dashes and underscores. Must not be greater than 255 characters. Example: <code>i</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>base_city</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="base_city"                data-endpoint="PATCHapi-v1-talent-profile"
               value="y"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>y</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>base_country</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="base_country"                data-endpoint="PATCHapi-v1-talent-profile"
               value="v"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>v</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>booking_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="booking_type"                data-endpoint="PATCHapi-v1-talent-profile"
               value="external"
               data-component="body">
    <br>
<p>Example: <code>external</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>email</code></li> <li><code>calendar</code></li> <li><code>form</code></li> <li><code>external</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>booking_value</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="booking_value"                data-endpoint="PATCHapi-v1-talent-profile"
               value="d"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>d</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>willing_to_travel</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PATCHapi-v1-talent-profile" style="display: none">
            <input type="radio" name="willing_to_travel"
                   value="true"
                   data-endpoint="PATCHapi-v1-talent-profile"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PATCHapi-v1-talent-profile" style="display: none">
            <input type="radio" name="willing_to_travel"
                   value="false"
                   data-endpoint="PATCHapi-v1-talent-profile"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>travel_regions</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="travel_regions[0]"                data-endpoint="PATCHapi-v1-talent-profile"
               data-component="body">
        <input type="text" style="display: none"
               name="travel_regions[1]"                data-endpoint="PATCHapi-v1-talent-profile"
               data-component="body">
    <br>
<p>Must not be greater than 100 characters.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>rate_tier</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="rate_tier"                data-endpoint="PATCHapi-v1-talent-profile"
               value="elite"
               data-component="body">
    <br>
<p>Example: <code>elite</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>emerging</code></li> <li><code>established</code></li> <li><code>premium</code></li> <li><code>elite</code></li></ul>
        </div>
        </form>

                    <h2 id="talent-profile-POSTapi-v1-talent-profile-hero">Upload my hero image</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Multipart <code>image</code>. Returns the resolved hero URL (medialibrary).</p>

<span id="example-requests-POSTapi-v1-talent-profile-hero">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/profile/hero" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "image=@/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phpk77bfs038ac38ggg1XE" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/profile/hero"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('image', document.querySelector('input[name="image"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-profile-hero">
</span>
<span id="execution-results-POSTapi-v1-talent-profile-hero" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-profile-hero"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-profile-hero"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-profile-hero" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-profile-hero">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-profile-hero" data-method="POST"
      data-path="api/v1/talent/profile/hero"
      data-authed="1"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-profile-hero', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-profile-hero"
                    onclick="tryItOut('POSTapi-v1-talent-profile-hero');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-profile-hero"
                    onclick="cancelTryOut('POSTapi-v1-talent-profile-hero');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-profile-hero"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/profile/hero</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-profile-hero"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-profile-hero"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-profile-hero"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>image</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="image"                data-endpoint="POSTapi-v1-talent-profile-hero"
               value=""
               data-component="body">
    <br>
<p>Must be an image. Must not be greater than 8192 kilobytes. Example: <code>/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phpk77bfs038ac38ggg1XE</code></p>
        </div>
        </form>

                    <h2 id="talent-profile-GETapi-v1-talent-profile-blocks">List my profile blocks</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-talent-profile-blocks">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/profile/blocks" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/profile/blocks"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-profile-blocks">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-profile-blocks" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-profile-blocks"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-profile-blocks"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-profile-blocks" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-profile-blocks">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-profile-blocks" data-method="GET"
      data-path="api/v1/talent/profile/blocks"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-profile-blocks', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-profile-blocks"
                    onclick="tryItOut('GETapi-v1-talent-profile-blocks');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-profile-blocks"
                    onclick="cancelTryOut('GETapi-v1-talent-profile-blocks');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-profile-blocks"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/profile/blocks</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-profile-blocks"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-profile-blocks"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-profile-blocks"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-profile-GETapi-v1-talent-profile-block-picker">List addable block types</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>The block types still available to add, filtered by the talent's profession
eligibility.</p>

<span id="example-requests-GETapi-v1-talent-profile-block-picker">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/profile/block-picker" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/profile/block-picker"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-profile-block-picker">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-profile-block-picker" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-profile-block-picker"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-profile-block-picker"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-profile-block-picker" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-profile-block-picker">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-profile-block-picker" data-method="GET"
      data-path="api/v1/talent/profile/block-picker"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-profile-block-picker', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-profile-block-picker"
                    onclick="tryItOut('GETapi-v1-talent-profile-block-picker');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-profile-block-picker"
                    onclick="cancelTryOut('GETapi-v1-talent-profile-block-picker');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-profile-block-picker"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/profile/block-picker</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-profile-block-picker"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-profile-block-picker"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-profile-block-picker"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-profile-POSTapi-v1-talent-profile-blocks">Add a profile block</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-profile-blocks">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/profile/blocks" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"block_type_id\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/profile/blocks"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "block_type_id": 16
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-profile-blocks">
</span>
<span id="execution-results-POSTapi-v1-talent-profile-blocks" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-profile-blocks"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-profile-blocks"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-profile-blocks" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-profile-blocks">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-profile-blocks" data-method="POST"
      data-path="api/v1/talent/profile/blocks"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-profile-blocks', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-profile-blocks"
                    onclick="tryItOut('POSTapi-v1-talent-profile-blocks');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-profile-blocks"
                    onclick="cancelTryOut('POSTapi-v1-talent-profile-blocks');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-profile-blocks"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/profile/blocks</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-profile-blocks"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-profile-blocks"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-profile-blocks"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>block_type_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="block_type_id"                data-endpoint="POSTapi-v1-talent-profile-blocks"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
        </form>

                    <h2 id="talent-profile-PATCHapi-v1-talent-profile-blocks-reorder">Reorder my profile blocks</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-profile-blocks-reorder">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/profile/blocks/reorder" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"order\": [
        16
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/profile/blocks/reorder"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "order": [
        16
    ]
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-profile-blocks-reorder">
</span>
<span id="execution-results-PATCHapi-v1-talent-profile-blocks-reorder" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-profile-blocks-reorder"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-profile-blocks-reorder"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-profile-blocks-reorder" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-profile-blocks-reorder">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-profile-blocks-reorder" data-method="PATCH"
      data-path="api/v1/talent/profile/blocks/reorder"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-profile-blocks-reorder', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-profile-blocks-reorder"
                    onclick="tryItOut('PATCHapi-v1-talent-profile-blocks-reorder');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-profile-blocks-reorder"
                    onclick="cancelTryOut('PATCHapi-v1-talent-profile-blocks-reorder');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-profile-blocks-reorder"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/profile/blocks/reorder</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-profile-blocks-reorder"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-profile-blocks-reorder"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-profile-blocks-reorder"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>order</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="order[0]"                data-endpoint="PATCHapi-v1-talent-profile-blocks-reorder"
               data-component="body">
        <input type="number" style="display: none"
               name="order[1]"                data-endpoint="PATCHapi-v1-talent-profile-blocks-reorder"
               data-component="body">
    <br>

        </div>
        </form>

                    <h2 id="talent-profile-PATCHapi-v1-talent-profile-blocks--block_id-">Fill / edit a profile block</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-profile-blocks--block_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/profile/blocks/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"title\": {
        \"en\": \"b\",
        \"ar\": \"n\"
    },
    \"layout\": \"carousel\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/profile/blocks/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "title": {
        "en": "b",
        "ar": "n"
    },
    "layout": "carousel"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-profile-blocks--block_id-">
</span>
<span id="execution-results-PATCHapi-v1-talent-profile-blocks--block_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-profile-blocks--block_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-profile-blocks--block_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-profile-blocks--block_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-profile-blocks--block_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-profile-blocks--block_id-" data-method="PATCH"
      data-path="api/v1/talent/profile/blocks/{block_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-profile-blocks--block_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-profile-blocks--block_id-"
                    onclick="tryItOut('PATCHapi-v1-talent-profile-blocks--block_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-profile-blocks--block_id-"
                    onclick="cancelTryOut('PATCHapi-v1-talent-profile-blocks--block_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-profile-blocks--block_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/profile/blocks/{block_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>block_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="block_id"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the block. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>en</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title.en"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id-"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title.ar"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id-"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>content</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="content"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id-"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>settings</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="settings"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id-"
               value=""
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>layout</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="layout"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id-"
               value="carousel"
               data-component="body">
    <br>
<p>Example: <code>carousel</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>grid</code></li> <li><code>carousel</code></li> <li><code>list</code></li> <li><code>masonry</code></li></ul>
        </div>
        </form>

                    <h2 id="talent-profile-PATCHapi-v1-talent-profile-blocks--block_id--visibility">Show / hide a profile block</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-profile-blocks--block_id--visibility">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/profile/blocks/1/visibility" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/profile/blocks/1/visibility"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-profile-blocks--block_id--visibility">
</span>
<span id="execution-results-PATCHapi-v1-talent-profile-blocks--block_id--visibility" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-profile-blocks--block_id--visibility"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-profile-blocks--block_id--visibility"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-profile-blocks--block_id--visibility" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-profile-blocks--block_id--visibility">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-profile-blocks--block_id--visibility" data-method="PATCH"
      data-path="api/v1/talent/profile/blocks/{block_id}/visibility"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-profile-blocks--block_id--visibility', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-profile-blocks--block_id--visibility"
                    onclick="tryItOut('PATCHapi-v1-talent-profile-blocks--block_id--visibility');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-profile-blocks--block_id--visibility"
                    onclick="cancelTryOut('PATCHapi-v1-talent-profile-blocks--block_id--visibility');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-profile-blocks--block_id--visibility"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/profile/blocks/{block_id}/visibility</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id--visibility"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id--visibility"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id--visibility"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>block_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="block_id"                data-endpoint="PATCHapi-v1-talent-profile-blocks--block_id--visibility"
               value="1"
               data-component="url">
    <br>
<p>The ID of the block. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="talent-profile-DELETEapi-v1-talent-profile-blocks--block_id-">Remove a profile block</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-talent-profile-blocks--block_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/talent/profile/blocks/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/profile/blocks/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-talent-profile-blocks--block_id-">
</span>
<span id="execution-results-DELETEapi-v1-talent-profile-blocks--block_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-talent-profile-blocks--block_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-talent-profile-blocks--block_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-talent-profile-blocks--block_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-talent-profile-blocks--block_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-talent-profile-blocks--block_id-" data-method="DELETE"
      data-path="api/v1/talent/profile/blocks/{block_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-talent-profile-blocks--block_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-talent-profile-blocks--block_id-"
                    onclick="tryItOut('DELETEapi-v1-talent-profile-blocks--block_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-talent-profile-blocks--block_id-"
                    onclick="cancelTryOut('DELETEapi-v1-talent-profile-blocks--block_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-talent-profile-blocks--block_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/talent/profile/blocks/{block_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-talent-profile-blocks--block_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-talent-profile-blocks--block_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-talent-profile-blocks--block_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>block_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="block_id"                data-endpoint="DELETEapi-v1-talent-profile-blocks--block_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the block. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="talent-reviews">Talent · Reviews</h1>

    

                                <h2 id="talent-reviews-GETapi-v1-talent-reviews">List my reviews</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-talent-reviews">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/reviews?status=pending" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/reviews"
);

const params = {
    "status": "pending",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-reviews">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-reviews" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-reviews"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-reviews"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-reviews" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-reviews">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-reviews" data-method="GET"
      data-path="api/v1/talent/reviews"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-reviews', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-reviews"
                    onclick="tryItOut('GETapi-v1-talent-reviews');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-reviews"
                    onclick="cancelTryOut('GETapi-v1-talent-reviews');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-reviews"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/reviews</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-reviews"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-reviews"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-reviews"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-talent-reviews"
               value="pending"
               data-component="query">
    <br>
<p>Filter by pending, approved or rejected. Example: <code>pending</code></p>
            </div>
                </form>

                    <h2 id="talent-reviews-PATCHapi-v1-talent-reviews--review_id--approve">Approve a review</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-reviews--review_id--approve">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/reviews/1/approve" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/reviews/1/approve"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-reviews--review_id--approve">
</span>
<span id="execution-results-PATCHapi-v1-talent-reviews--review_id--approve" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-reviews--review_id--approve"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-reviews--review_id--approve"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-reviews--review_id--approve" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-reviews--review_id--approve">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-reviews--review_id--approve" data-method="PATCH"
      data-path="api/v1/talent/reviews/{review_id}/approve"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-reviews--review_id--approve', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-reviews--review_id--approve"
                    onclick="tryItOut('PATCHapi-v1-talent-reviews--review_id--approve');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-reviews--review_id--approve"
                    onclick="cancelTryOut('PATCHapi-v1-talent-reviews--review_id--approve');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-reviews--review_id--approve"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/reviews/{review_id}/approve</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-reviews--review_id--approve"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-reviews--review_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-reviews--review_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>review_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="review_id"                data-endpoint="PATCHapi-v1-talent-reviews--review_id--approve"
               value="1"
               data-component="url">
    <br>
<p>The ID of the review. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="talent-reviews-PATCHapi-v1-talent-reviews--review_id--reject">Reject a review</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-reviews--review_id--reject">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/reviews/1/reject" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/reviews/1/reject"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-reviews--review_id--reject">
</span>
<span id="execution-results-PATCHapi-v1-talent-reviews--review_id--reject" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-reviews--review_id--reject"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-reviews--review_id--reject"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-reviews--review_id--reject" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-reviews--review_id--reject">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-reviews--review_id--reject" data-method="PATCH"
      data-path="api/v1/talent/reviews/{review_id}/reject"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-reviews--review_id--reject', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-reviews--review_id--reject"
                    onclick="tryItOut('PATCHapi-v1-talent-reviews--review_id--reject');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-reviews--review_id--reject"
                    onclick="cancelTryOut('PATCHapi-v1-talent-reviews--review_id--reject');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-reviews--review_id--reject"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/reviews/{review_id}/reject</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-reviews--review_id--reject"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-reviews--review_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-reviews--review_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>review_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="review_id"                data-endpoint="PATCHapi-v1-talent-reviews--review_id--reject"
               value="1"
               data-component="url">
    <br>
<p>The ID of the review. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="talent-services">Talent · Services</h1>

    

                                <h2 id="talent-services-GETapi-v1-talent-services">List my services</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-GETapi-v1-talent-services">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talent/services" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/services"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-talent-services">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: false,
    &quot;data&quot;: null,
    &quot;message&quot;: &quot;Unauthenticated.&quot;,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talent-services" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talent-services"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talent-services"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talent-services" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talent-services">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talent-services" data-method="GET"
      data-path="api/v1/talent/services"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talent-services', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talent-services"
                    onclick="tryItOut('GETapi-v1-talent-services');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talent-services"
                    onclick="cancelTryOut('GETapi-v1-talent-services');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talent-services"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talent/services</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETapi-v1-talent-services"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talent-services"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-talent-services"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="talent-services-POSTapi-v1-talent-services">Add a service</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-POSTapi-v1-talent-services">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/talent/services" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": {
        \"en\": \"b\",
        \"ar\": \"n\"
    },
    \"description\": {
        \"en\": \"g\",
        \"ar\": \"z\"
    },
    \"price\": 77,
    \"currency\": \"iyv\",
    \"price_unit\": \"hour\",
    \"duration_minutes\": 42,
    \"position\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/services"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": {
        "en": "b",
        "ar": "n"
    },
    "description": {
        "en": "g",
        "ar": "z"
    },
    "price": 77,
    "currency": "iyv",
    "price_unit": "hour",
    "duration_minutes": 42,
    "position": 16
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-talent-services">
</span>
<span id="execution-results-POSTapi-v1-talent-services" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-talent-services"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-talent-services"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-talent-services" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-talent-services">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-talent-services" data-method="POST"
      data-path="api/v1/talent/services"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-talent-services', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-talent-services"
                    onclick="tryItOut('POSTapi-v1-talent-services');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-talent-services"
                    onclick="cancelTryOut('POSTapi-v1-talent-services');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-talent-services"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/talent/services</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTapi-v1-talent-services"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-talent-services"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-talent-services"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>en</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name.en"                data-endpoint="POSTapi-v1-talent-services"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name.ar"                data-endpoint="POSTapi-v1-talent-services"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>en</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description.en"                data-endpoint="POSTapi-v1-talent-services"
               value="g"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>g</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description.ar"                data-endpoint="POSTapi-v1-talent-services"
               value="z"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>z</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="price"                data-endpoint="POSTapi-v1-talent-services"
               value="77"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>77</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="POSTapi-v1-talent-services"
               value="iyv"
               data-component="body">
    <br>
<p>Must be 3 characters. Example: <code>iyv</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price_unit</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="price_unit"                data-endpoint="POSTapi-v1-talent-services"
               value="hour"
               data-component="body">
    <br>
<p>Example: <code>hour</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>hour</code></li> <li><code>day</code></li> <li><code>project</code></li> <li><code>fixed</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>duration_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="duration_minutes"                data-endpoint="POSTapi-v1-talent-services"
               value="42"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>42</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>position</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="position"                data-endpoint="POSTapi-v1-talent-services"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
        </div>
        </form>

                    <h2 id="talent-services-PATCHapi-v1-talent-services--service_id-">Update a service</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-services--service_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/services/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"name\": {
        \"en\": \"b\",
        \"ar\": \"n\"
    },
    \"description\": {
        \"en\": \"g\",
        \"ar\": \"z\"
    },
    \"price\": 77,
    \"currency\": \"iyv\",
    \"price_unit\": \"day\",
    \"duration_minutes\": 42,
    \"position\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/services/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "name": {
        "en": "b",
        "ar": "n"
    },
    "description": {
        "en": "g",
        "ar": "z"
    },
    "price": 77,
    "currency": "iyv",
    "price_unit": "day",
    "duration_minutes": 42,
    "position": 16
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-services--service_id-">
</span>
<span id="execution-results-PATCHapi-v1-talent-services--service_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-services--service_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-services--service_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-services--service_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-services--service_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-services--service_id-" data-method="PATCH"
      data-path="api/v1/talent/services/{service_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-services--service_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-services--service_id-"
                    onclick="tryItOut('PATCHapi-v1-talent-services--service_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-services--service_id-"
                    onclick="cancelTryOut('PATCHapi-v1-talent-services--service_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-services--service_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/services/{service_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>service_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="service_id"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the service. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>en</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name.en"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name.ar"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>

            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>en</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description.en"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="g"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>g</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>ar</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description.ar"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="z"
               data-component="body">
    <br>
<p>Must not be greater than 2000 characters. Example: <code>z</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="price"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="77"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>77</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>currency</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="currency"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="iyv"
               data-component="body">
    <br>
<p>Must be 3 characters. Example: <code>iyv</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price_unit</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="price_unit"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="day"
               data-component="body">
    <br>
<p>Example: <code>day</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>hour</code></li> <li><code>day</code></li> <li><code>project</code></li> <li><code>fixed</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>duration_minutes</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="duration_minutes"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="42"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>42</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>position</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="position"                data-endpoint="PATCHapi-v1-talent-services--service_id-"
               value="16"
               data-component="body">
    <br>
<p>Example: <code>16</code></p>
        </div>
        </form>

                    <h2 id="talent-services-PATCHapi-v1-talent-services--service_id--toggle">Pause / activate a service</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-PATCHapi-v1-talent-services--service_id--toggle">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/talent/services/1/toggle" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/services/1/toggle"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-talent-services--service_id--toggle">
</span>
<span id="execution-results-PATCHapi-v1-talent-services--service_id--toggle" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-talent-services--service_id--toggle"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-talent-services--service_id--toggle"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-talent-services--service_id--toggle" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-talent-services--service_id--toggle">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-talent-services--service_id--toggle" data-method="PATCH"
      data-path="api/v1/talent/services/{service_id}/toggle"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-talent-services--service_id--toggle', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-talent-services--service_id--toggle"
                    onclick="tryItOut('PATCHapi-v1-talent-services--service_id--toggle');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-talent-services--service_id--toggle"
                    onclick="cancelTryOut('PATCHapi-v1-talent-services--service_id--toggle');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-talent-services--service_id--toggle"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/talent/services/{service_id}/toggle</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="PATCHapi-v1-talent-services--service_id--toggle"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-talent-services--service_id--toggle"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-talent-services--service_id--toggle"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>service_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="service_id"                data-endpoint="PATCHapi-v1-talent-services--service_id--toggle"
               value="1"
               data-component="url">
    <br>
<p>The ID of the service. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="talent-services-DELETEapi-v1-talent-services--service_id-">Remove a service</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>



<span id="example-requests-DELETEapi-v1-talent-services--service_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/talent/services/1" \
    --header "Authorization: Bearer {YOUR_TOKEN}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talent/services/1"
);

const headers = {
    "Authorization": "Bearer {YOUR_TOKEN}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-talent-services--service_id-">
</span>
<span id="execution-results-DELETEapi-v1-talent-services--service_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-talent-services--service_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-talent-services--service_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-talent-services--service_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-talent-services--service_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-talent-services--service_id-" data-method="DELETE"
      data-path="api/v1/talent/services/{service_id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-talent-services--service_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-talent-services--service_id-"
                    onclick="tryItOut('DELETEapi-v1-talent-services--service_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-talent-services--service_id-"
                    onclick="cancelTryOut('DELETEapi-v1-talent-services--service_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-talent-services--service_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/talent/services/{service_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="DELETEapi-v1-talent-services--service_id-"
               value="Bearer {YOUR_TOKEN}"
               data-component="header">
    <br>
<p>Example: <code>Bearer {YOUR_TOKEN}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-talent-services--service_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-talent-services--service_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>service_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="service_id"                data-endpoint="DELETEapi-v1-talent-services--service_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the service. Example: <code>1</code></p>
            </div>
                    </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
