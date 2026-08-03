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
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-feed-items">
                                <a href="#endpoints-GETapi-v1-feed-items">One ranked feed page: items in frozen-list order, casting-slot
positions, and the opaque next cursor (D10).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-feed-casting">
                                <a href="#endpoints-GETapi-v1-feed-casting">One casting strip: discovery talents with the viewer's follow-state,
never repeating within the feed session.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-posts--post_id-">
                                <a href="#endpoints-GETapi-v1-posts--post_id-">The post as JSON (the API mirror's detail shape).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-talents--username--posts">
                                <a href="#endpoints-GETapi-v1-talents--username--posts">A talent's published posts, newest first (public).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-posts">
                                <a href="#endpoints-POSTapi-v1-posts">Create a note or media post as the current actor.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PATCHapi-v1-posts--post_id-">
                                <a href="#endpoints-PATCHapi-v1-posts--post_id-">Edit the post inside the 15-minute window (media immutable).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-posts--post_id-">
                                <a href="#endpoints-DELETEapi-v1-posts--post_id-">Soft-delete the post (author or moderating admin).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-posts--post_id--pin">
                                <a href="#endpoints-POSTapi-v1-posts--post_id--pin">Pin/unpin the post on the author's presence (max one pinned).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-projects--project_id--share">
                                <a href="#endpoints-POSTapi-v1-projects--project_id--share">Share the Project as a project_share post.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-posts--post_id--comments">
                                <a href="#endpoints-GETapi-v1-posts--post_id--comments">Paginated top-level thread with preview replies and viewer state.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-posts--post_id--comments">
                                <a href="#endpoints-POSTapi-v1-posts--post_id--comments">Create a comment (or reply) on the post as the current actor.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-comments--comment_id--replies">
                                <a href="#endpoints-GETapi-v1-comments--comment_id--replies">A comment's replies, paginated separately, oldest first.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PATCHapi-v1-comments--comment_id-">
                                <a href="#endpoints-PATCHapi-v1-comments--comment_id-">Edit a comment inside the 15-minute window.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-comments--comment_id-">
                                <a href="#endpoints-DELETEapi-v1-comments--comment_id-">Delete a comment (tombstoned when it has replies).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-posts--post_id--like">
                                <a href="#endpoints-POSTapi-v1-posts--post_id--like">Like the post as the current actor.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-posts--post_id--like">
                                <a href="#endpoints-DELETEapi-v1-posts--post_id--like">Withdraw the actor's like from the post.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-comments--comment_id--like">
                                <a href="#endpoints-POSTapi-v1-comments--comment_id--like">Like a comment as the current actor.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-comments--comment_id--like">
                                <a href="#endpoints-DELETEapi-v1-comments--comment_id--like">Withdraw the actor's like from the comment.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-feed-suggestions-talents">
                                <a href="#endpoints-GETapi-v1-feed-suggestions-talents">Talent suggestions: ?offset=0&limit=5 → {items, next_offset}.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-feed-suggestions-brands">
                                <a href="#endpoints-GETapi-v1-feed-suggestions-brands">Brand suggestions (empty for brand viewers — the rail hides).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-feed-suggestions-dismiss">
                                <a href="#endpoints-POSTapi-v1-feed-suggestions-dismiss">Permanently dismiss one suggestion: {type: talent|brand, id}.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-posts--post_id--bookmark">
                                <a href="#endpoints-POSTapi-v1-posts--post_id--bookmark">Save the post as the current actor.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-posts--post_id--bookmark">
                                <a href="#endpoints-DELETEapi-v1-posts--post_id--bookmark">Withdraw the actor's save.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-follow--type---id-">
                                <a href="#endpoints-POSTapi-v1-follow--type---id-">Follow the {type}/{id} party as the current actor.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-follow--type---id-">
                                <a href="#endpoints-DELETEapi-v1-follow--type---id-">Withdraw the actor's follow of the {type}/{id} party.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-talents--username--followers">
                                <a href="#endpoints-GETapi-v1-talents--username--followers">Who follows this talent (talents and brands).</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-talents--username--following">
                                <a href="#endpoints-GETapi-v1-talents--username--following">Who this talent follows.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-mentions-search">
                                <a href="#endpoints-GETapi-v1-mentions-search">Search actors by Username / display-name prefix or fragment.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-links-preview">
                                <a href="#endpoints-POSTapi-v1-links-preview">Resolve (find-or-create) the preview for a pasted URL.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-posts--post_id--report">
                                <a href="#endpoints-POSTapi-v1-posts--post_id--report">Report a post.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-comments--comment_id--report">
                                <a href="#endpoints-POSTapi-v1-comments--comment_id--report">Report a comment.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-notifications">
                                <a href="#endpoints-GETapi-v1-notifications">The inbox, newest first.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-notifications-read">
                                <a href="#endpoints-POSTapi-v1-notifications-read">Mark all — or the given IDs — read.</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-notifications-unread-count">
                                <a href="#endpoints-GETapi-v1-notifications-unread-count">The unread badge count.</a>
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
        <li>Last updated: July 22, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>https://fama.test</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-v1-feed-items">One ranked feed page: items in frozen-list order, casting-slot
positions, and the opaque next cursor (D10).</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-feed-items">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/feed/items" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/feed/items"
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

<span id="example-responses-GETapi-v1-feed-items">
            <blockquote>
            <p>Example response (200):</p>
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
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;items&quot;: [
            {
                &quot;id&quot;: 182,
                &quot;post_type&quot;: &quot;project_share&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 8,
                    &quot;username&quot;: &quot;farida-nabil&quot;,
                    &quot;display_name&quot;: &quot;Farida Nabil&quot;,
                    &quot;avatar_url&quot;: &quot;https://fama.test/storage/35/fama_a5ba6cf9e24223bc005ee9c156f6362f.jpg&quot;
                },
                &quot;body&quot;: null,
                &quot;body_html&quot;: null,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 1,
                        &quot;name&quot;: &quot;Modeling&quot;,
                        &quot;slug&quot;: &quot;modeling&quot;,
                        &quot;icon&quot;: &quot;lucide-modeling&quot;
                    }
                ],
                &quot;project&quot;: {
                    &quot;is_live&quot;: true,
                    &quot;id&quot;: 26,
                    &quot;title&quot;: &quot;Decentralized executive artificialintelligence&quot;,
                    &quot;summary&quot;: &quot;Dolores perspiciatis quod perspiciatis aut rerum.&quot;,
                    &quot;cover_url&quot;: null,
                    &quot;year&quot;: 2022,
                    &quot;url&quot;: &quot;https://fama.test/farida-nabil/work/26&quot;
                },
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 1,
                &quot;comments_count&quot;: 7,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-21T08:31:43+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/182&quot;
            },
            {
                &quot;id&quot;: 43,
                &quot;post_type&quot;: &quot;note&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 4,
                    &quot;username&quot;: &quot;yasmin-adel&quot;,
                    &quot;display_name&quot;: &quot;Yasmin Adel&quot;,
                    &quot;avatar_url&quot;: &quot;https://fama.test/storage/18/fama_f6b78ebcdedbf4990a3b07b1384067ec.jpg&quot;
                },
                &quot;body&quot;: &quot;Casting was intense, the energy on set even better. Alhamdulillah.&quot;,
                &quot;body_html&quot;: &quot;Casting was intense, the energy on set even better. Alhamdulillah.&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 5,
                        &quot;name&quot;: &quot;Styling&quot;,
                        &quot;slug&quot;: &quot;styling&quot;,
                        &quot;icon&quot;: &quot;lucide-styling&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 9,
                &quot;comments_count&quot;: 5,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-20T02:23:41+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/43&quot;
            },
            {
                &quot;id&quot;: 107,
                &quot;post_type&quot;: &quot;project_share&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 16,
                    &quot;username&quot;: &quot;karianne-oconnell-2171&quot;,
                    &quot;display_name&quot;: &quot;Karianne O&#039;Connell&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;Casting was intense, the energy on set even better. Alhamdulillah. مع @prof-kelvin-fritsch-md-3377&quot;,
                &quot;body_html&quot;: &quot;Casting was intense, the energy on set even better. Alhamdulillah. مع &lt;bdi&gt;&lt;a href=\&quot;https://fama.test/prof-kelvin-fritsch-md-3377\&quot; class=\&quot;feed-mention\&quot;&gt;⁦@prof-kelvin-fritsch-md-3377⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 5,
                        &quot;name&quot;: &quot;Styling&quot;,
                        &quot;slug&quot;: &quot;styling&quot;,
                        &quot;icon&quot;: &quot;lucide-styling&quot;
                    }
                ],
                &quot;project&quot;: {
                    &quot;is_live&quot;: true,
                    &quot;id&quot;: 7,
                    &quot;title&quot;: &quot;Pre-emptive executive framework&quot;,
                    &quot;summary&quot;: &quot;Quia veritatis aut non aperiam officiis pariatur magni.&quot;,
                    &quot;cover_url&quot;: null,
                    &quot;year&quot;: 2021,
                    &quot;url&quot;: &quot;https://fama.test/karianne-oconnell-2171/work/7&quot;
                },
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 6,
                &quot;comments_count&quot;: 3,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-21T08:31:42+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/107&quot;
            },
            {
                &quot;id&quot;: 94,
                &quot;post_type&quot;: &quot;note&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 20,
                    &quot;username&quot;: &quot;letha-feeney-8951&quot;,
                    &quot;display_name&quot;: &quot;Letha Feeney&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;BTS from the night shoot in Downtown Cairo. Neon everywhere. https://instagram.com/aniyah-russel-v-9998&quot;,
                &quot;body_html&quot;: &quot;BTS from the night shoot in Downtown Cairo. Neon everywhere. &lt;bdi&gt;&lt;a href=\&quot;https://instagram.com/aniyah-russel-v-9998\&quot; class=\&quot;feed-chip\&quot; target=\&quot;_blank\&quot; rel=\&quot;noopener noreferrer nofollow ugc\&quot; data-provider=\&quot;instagram\&quot;&gt;&lt;svg class=\&quot;chip-glyph\&quot; aria-hidden=\&quot;true\&quot;&gt;&lt;use href=\&quot;#glyph-instagram\&quot;&gt;&lt;/use&gt;&lt;/svg&gt;⁦@aniyah-russel-v-9998⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 2,
                        &quot;name&quot;: &quot;Photography&quot;,
                        &quot;slug&quot;: &quot;photography&quot;,
                        &quot;icon&quot;: &quot;lucide-photography&quot;
                    },
                    {
                        &quot;id&quot;: 6,
                        &quot;name&quot;: &quot;Graphic Design&quot;,
                        &quot;slug&quot;: &quot;graphic-design&quot;,
                        &quot;icon&quot;: &quot;lucide-graphic-design&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [
                    {
                        &quot;provider&quot;: &quot;instagram&quot;,
                        &quot;handle&quot;: &quot;aniyah-russel-v-9998&quot;,
                        &quot;resource_id&quot;: null,
                        &quot;url&quot;: &quot;https://instagram.com/aniyah-russel-v-9998&quot;,
                        &quot;canonical_url&quot;: null,
                        &quot;title&quot;: null,
                        &quot;description&quot;: null,
                        &quot;image_url&quot;: null,
                        &quot;status&quot;: &quot;pending&quot;
                    }
                ],
                &quot;likes_count&quot;: 2,
                &quot;comments_count&quot;: 5,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-20T17:06:42+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/94&quot;
            },
            {
                &quot;id&quot;: 29,
                &quot;post_type&quot;: &quot;media&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 27,
                    &quot;username&quot;: &quot;deondre-aufderhar-5616&quot;,
                    &quot;display_name&quot;: &quot;Deondre Aufderhar&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;Testing new looks for SS26. The tailoring on this collection is unreal. https://www.behance.net/aniyah-russel-v-9998&quot;,
                &quot;body_html&quot;: &quot;Testing new looks for SS26. The tailoring on this collection is unreal. &lt;bdi&gt;&lt;a href=\&quot;https://www.behance.net/aniyah-russel-v-9998\&quot; class=\&quot;feed-chip\&quot; target=\&quot;_blank\&quot; rel=\&quot;noopener noreferrer nofollow ugc\&quot; data-provider=\&quot;behance\&quot;&gt;&lt;svg class=\&quot;chip-glyph\&quot; aria-hidden=\&quot;true\&quot;&gt;&lt;use href=\&quot;#glyph-behance\&quot;&gt;&lt;/use&gt;&lt;/svg&gt;⁦@aniyah-russel-v-9998⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [
                    {
                        &quot;id&quot;: 108,
                        &quot;type&quot;: &quot;image&quot;,
                        &quot;original_url&quot;: &quot;https://fama.test/storage/108/fama_ea730af08d4c4844159efb491a8ab2f9.jpg&quot;,
                        &quot;thumb_url&quot;: &quot;https://fama.test/storage/108/fama_ea730af08d4c4844159efb491a8ab2f9.jpg&quot;,
                        &quot;feed_url&quot;: &quot;https://fama.test/storage/108/fama_ea730af08d4c4844159efb491a8ab2f9.jpg&quot;,
                        &quot;full_url&quot;: &quot;https://fama.test/storage/108/fama_ea730af08d4c4844159efb491a8ab2f9.jpg&quot;,
                        &quot;poster_url&quot;: null
                    },
                    {
                        &quot;id&quot;: 109,
                        &quot;type&quot;: &quot;image&quot;,
                        &quot;original_url&quot;: &quot;https://fama.test/storage/109/fama_55e45d2876331a61e2cf54cff6195434.jpg&quot;,
                        &quot;thumb_url&quot;: &quot;https://fama.test/storage/109/fama_55e45d2876331a61e2cf54cff6195434.jpg&quot;,
                        &quot;feed_url&quot;: &quot;https://fama.test/storage/109/fama_55e45d2876331a61e2cf54cff6195434.jpg&quot;,
                        &quot;full_url&quot;: &quot;https://fama.test/storage/109/fama_55e45d2876331a61e2cf54cff6195434.jpg&quot;,
                        &quot;poster_url&quot;: null
                    }
                ],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 4,
                        &quot;name&quot;: &quot;Creative Direction&quot;,
                        &quot;slug&quot;: &quot;creative-direction&quot;,
                        &quot;icon&quot;: &quot;lucide-creative-direction&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [
                    {
                        &quot;provider&quot;: &quot;behance&quot;,
                        &quot;handle&quot;: &quot;aniyah-russel-v-9998&quot;,
                        &quot;resource_id&quot;: null,
                        &quot;url&quot;: &quot;https://www.behance.net/aniyah-russel-v-9998&quot;,
                        &quot;canonical_url&quot;: null,
                        &quot;title&quot;: null,
                        &quot;description&quot;: null,
                        &quot;image_url&quot;: null,
                        &quot;status&quot;: &quot;pending&quot;
                    }
                ],
                &quot;likes_count&quot;: 7,
                &quot;comments_count&quot;: 6,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-18T23:10:41+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/29&quot;
            },
            {
                &quot;id&quot;: 62,
                &quot;post_type&quot;: &quot;note&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 37,
                    &quot;username&quot;: &quot;kathlyn-heathcote-4902&quot;,
                    &quot;display_name&quot;: &quot;Kathlyn Heathcote&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;BTS from the night shoot in Downtown Cairo. Neon everywhere.&quot;,
                &quot;body_html&quot;: &quot;BTS from the night shoot in Downtown Cairo. Neon everywhere.&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 1,
                        &quot;name&quot;: &quot;Modeling&quot;,
                        &quot;slug&quot;: &quot;modeling&quot;,
                        &quot;icon&quot;: &quot;lucide-modeling&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 5,
                &quot;comments_count&quot;: 2,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-20T12:24:41+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/62&quot;
            },
            {
                &quot;id&quot;: 221,
                &quot;post_type&quot;: &quot;project_share&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 25,
                    &quot;username&quot;: &quot;dayna-moore-1677&quot;,
                    &quot;display_name&quot;: &quot;Dayna Moore&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: null,
                &quot;body_html&quot;: null,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 2,
                        &quot;name&quot;: &quot;Photography&quot;,
                        &quot;slug&quot;: &quot;photography&quot;,
                        &quot;icon&quot;: &quot;lucide-photography&quot;
                    },
                    {
                        &quot;id&quot;: 4,
                        &quot;name&quot;: &quot;Creative Direction&quot;,
                        &quot;slug&quot;: &quot;creative-direction&quot;,
                        &quot;icon&quot;: &quot;lucide-creative-direction&quot;
                    }
                ],
                &quot;project&quot;: {
                    &quot;is_live&quot;: true,
                    &quot;id&quot;: 16,
                    &quot;title&quot;: &quot;Synergized systemic standardization&quot;,
                    &quot;summary&quot;: &quot;Quis consequatur autem est nihil nemo rem.&quot;,
                    &quot;cover_url&quot;: null,
                    &quot;year&quot;: 2019,
                    &quot;url&quot;: &quot;https://fama.test/dayna-moore-1677/work/16&quot;
                },
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 7,
                &quot;comments_count&quot;: 0,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-22T08:31:44+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/221&quot;
            },
            {
                &quot;id&quot;: 211,
                &quot;post_type&quot;: &quot;media&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 4,
                    &quot;username&quot;: &quot;yasmin-adel&quot;,
                    &quot;display_name&quot;: &quot;Yasmin Adel&quot;,
                    &quot;avatar_url&quot;: &quot;https://fama.test/storage/18/fama_f6b78ebcdedbf4990a3b07b1384067ec.jpg&quot;
                },
                &quot;body&quot;: &quot;كواليس جلسة تصوير النهاردة في وسط البلد &mdash; الإضاءة كانت خيالية. مع @dayna-moore-1677&quot;,
                &quot;body_html&quot;: &quot;كواليس جلسة تصوير النهاردة في وسط البلد &mdash; الإضاءة كانت خيالية. مع &lt;bdi&gt;&lt;a href=\&quot;https://fama.test/dayna-moore-1677\&quot; class=\&quot;feed-mention\&quot;&gt;⁦@dayna-moore-1677⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [
                    {
                        &quot;id&quot;: 292,
                        &quot;type&quot;: &quot;image&quot;,
                        &quot;original_url&quot;: &quot;https://fama.test/storage/292/fama_bf286b5f03bf5f51e92ff577c4acaead.jpg&quot;,
                        &quot;thumb_url&quot;: &quot;https://fama.test/storage/292/fama_bf286b5f03bf5f51e92ff577c4acaead.jpg&quot;,
                        &quot;feed_url&quot;: &quot;https://fama.test/storage/292/fama_bf286b5f03bf5f51e92ff577c4acaead.jpg&quot;,
                        &quot;full_url&quot;: &quot;https://fama.test/storage/292/fama_bf286b5f03bf5f51e92ff577c4acaead.jpg&quot;,
                        &quot;poster_url&quot;: null
                    },
                    {
                        &quot;id&quot;: 293,
                        &quot;type&quot;: &quot;image&quot;,
                        &quot;original_url&quot;: &quot;https://fama.test/storage/293/fama_8daec549b9179844a50a62a172259a33.jpg&quot;,
                        &quot;thumb_url&quot;: &quot;https://fama.test/storage/293/fama_8daec549b9179844a50a62a172259a33.jpg&quot;,
                        &quot;feed_url&quot;: &quot;https://fama.test/storage/293/fama_8daec549b9179844a50a62a172259a33.jpg&quot;,
                        &quot;full_url&quot;: &quot;https://fama.test/storage/293/fama_8daec549b9179844a50a62a172259a33.jpg&quot;,
                        &quot;poster_url&quot;: null
                    }
                ],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 1,
                        &quot;name&quot;: &quot;Modeling&quot;,
                        &quot;slug&quot;: &quot;modeling&quot;,
                        &quot;icon&quot;: &quot;lucide-modeling&quot;
                    },
                    {
                        &quot;id&quot;: 5,
                        &quot;name&quot;: &quot;Styling&quot;,
                        &quot;slug&quot;: &quot;styling&quot;,
                        &quot;icon&quot;: &quot;lucide-styling&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 12,
                &quot;comments_count&quot;: 2,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-18T16:31:43+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/211&quot;
            },
            {
                &quot;id&quot;: 282,
                &quot;post_type&quot;: &quot;project_share&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 13,
                    &quot;username&quot;: &quot;jocelyn-conn-8666&quot;,
                    &quot;display_name&quot;: &quot;Jocelyn Conn&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: null,
                &quot;body_html&quot;: null,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 4,
                        &quot;name&quot;: &quot;Creative Direction&quot;,
                        &quot;slug&quot;: &quot;creative-direction&quot;,
                        &quot;icon&quot;: &quot;lucide-creative-direction&quot;
                    }
                ],
                &quot;project&quot;: {
                    &quot;is_live&quot;: true,
                    &quot;id&quot;: 31,
                    &quot;title&quot;: &quot;Business-focused static product&quot;,
                    &quot;summary&quot;: &quot;Facere assumenda molestiae assumenda enim.&quot;,
                    &quot;cover_url&quot;: null,
                    &quot;year&quot;: 2019,
                    &quot;url&quot;: &quot;https://fama.test/jocelyn-conn-8666/work/31&quot;
                },
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 0,
                &quot;comments_count&quot;: 6,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-19T08:31:45+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/282&quot;
            },
            {
                &quot;id&quot;: 205,
                &quot;post_type&quot;: &quot;project_share&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 23,
                    &quot;username&quot;: &quot;april-deckow-3527&quot;,
                    &quot;display_name&quot;: &quot;April Deckow&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: null,
                &quot;body_html&quot;: null,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 6,
                        &quot;name&quot;: &quot;Graphic Design&quot;,
                        &quot;slug&quot;: &quot;graphic-design&quot;,
                        &quot;icon&quot;: &quot;lucide-graphic-design&quot;
                    }
                ],
                &quot;project&quot;: {
                    &quot;is_live&quot;: true,
                    &quot;id&quot;: 18,
                    &quot;title&quot;: &quot;Synchronised homogeneous utilisation&quot;,
                    &quot;summary&quot;: &quot;Ad omnis animi dignissimos.&quot;,
                    &quot;cover_url&quot;: null,
                    &quot;year&quot;: 2025,
                    &quot;url&quot;: &quot;https://fama.test/april-deckow-3527/work/18&quot;
                },
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 10,
                &quot;comments_count&quot;: 3,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-18T08:31:43+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/205&quot;
            },
            {
                &quot;id&quot;: 86,
                &quot;post_type&quot;: &quot;note&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 14,
                    &quot;username&quot;: &quot;pattie-collins-9958&quot;,
                    &quot;display_name&quot;: &quot;Pattie Collins&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;الحمد لله، خلصنا حملة كاملة في يومين. فريق أكتر من رائع. with @farida-nabil&quot;,
                &quot;body_html&quot;: &quot;الحمد لله، خلصنا حملة كاملة في يومين. فريق أكتر من رائع. with &lt;bdi&gt;&lt;a href=\&quot;https://fama.test/farida-nabil\&quot; class=\&quot;feed-mention\&quot;&gt;⁦@farida-nabil⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 5,
                        &quot;name&quot;: &quot;Styling&quot;,
                        &quot;slug&quot;: &quot;styling&quot;,
                        &quot;icon&quot;: &quot;lucide-styling&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 5,
                &quot;comments_count&quot;: 6,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-18T03:06:42+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/86&quot;
            },
            {
                &quot;id&quot;: 130,
                &quot;post_type&quot;: &quot;note&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 38,
                    &quot;username&quot;: &quot;prof-kelvin-fritsch-md-3377&quot;,
                    &quot;display_name&quot;: &quot;Prof. Kelvin Fritsch MD&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;Wrapped a two-day lookbook shoot in Zamalek &mdash; the light was pure honey. مع @ms-zelda-steuber-2004&quot;,
                &quot;body_html&quot;: &quot;Wrapped a two-day lookbook shoot in Zamalek &mdash; the light was pure honey. مع &lt;bdi&gt;&lt;a href=\&quot;https://fama.test/ms-zelda-steuber-2004\&quot; class=\&quot;feed-mention\&quot;&gt;⁦@ms-zelda-steuber-2004⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 6,
                        &quot;name&quot;: &quot;Graphic Design&quot;,
                        &quot;slug&quot;: &quot;graphic-design&quot;,
                        &quot;icon&quot;: &quot;lucide-graphic-design&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 12,
                &quot;comments_count&quot;: 4,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-17T12:56:43+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/130&quot;
            },
            {
                &quot;id&quot;: 169,
                &quot;post_type&quot;: &quot;note&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 5,
                    &quot;username&quot;: &quot;omar-khaled&quot;,
                    &quot;display_name&quot;: &quot;Omar Khaled&quot;,
                    &quot;avatar_url&quot;: &quot;https://fama.test/storage/22/fama_c2262b1fb08ddc053d60f43edbdddb77.jpg&quot;
                },
                &quot;body&quot;: &quot;Golden hour on the Corniche never disappoints. Full set soon. مع @kyleigh-littel-iv-4434&quot;,
                &quot;body_html&quot;: &quot;Golden hour on the Corniche never disappoints. Full set soon. مع &lt;bdi&gt;&lt;a href=\&quot;https://fama.test/kyleigh-littel-iv-4434\&quot; class=\&quot;feed-mention\&quot;&gt;⁦@kyleigh-littel-iv-4434⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 3,
                        &quot;name&quot;: &quot;Cinematography&quot;,
                        &quot;slug&quot;: &quot;cinematography&quot;,
                        &quot;icon&quot;: &quot;lucide-cinematography&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 8,
                &quot;comments_count&quot;: 2,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-19T00:18:43+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/169&quot;
            },
            {
                &quot;id&quot;: 204,
                &quot;post_type&quot;: &quot;project_share&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 33,
                    &quot;username&quot;: &quot;cathryn-fay-dds-5582&quot;,
                    &quot;display_name&quot;: &quot;Cathryn Fay DDS&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;شكراً لكل الفريق على المجهود في شوت النهاردة. صور جاية في السكة. with @prof-kelvin-fritsch-md-3377&quot;,
                &quot;body_html&quot;: &quot;شكراً لكل الفريق على المجهود في شوت النهاردة. صور جاية في السكة. with &lt;bdi&gt;&lt;a href=\&quot;https://fama.test/prof-kelvin-fritsch-md-3377\&quot; class=\&quot;feed-mention\&quot;&gt;⁦@prof-kelvin-fritsch-md-3377⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 4,
                        &quot;name&quot;: &quot;Creative Direction&quot;,
                        &quot;slug&quot;: &quot;creative-direction&quot;,
                        &quot;icon&quot;: &quot;lucide-creative-direction&quot;
                    }
                ],
                &quot;project&quot;: {
                    &quot;is_live&quot;: true,
                    &quot;id&quot;: 9,
                    &quot;title&quot;: &quot;Seamless regional focusgroup&quot;,
                    &quot;summary&quot;: &quot;Odit distinctio ad veritatis voluptatem soluta.&quot;,
                    &quot;cover_url&quot;: null,
                    &quot;year&quot;: 2024,
                    &quot;url&quot;: &quot;https://fama.test/cathryn-fay-dds-5582/work/9&quot;
                },
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 11,
                &quot;comments_count&quot;: 7,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-16T08:31:43+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/204&quot;
            },
            {
                &quot;id&quot;: 280,
                &quot;post_type&quot;: &quot;media&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 32,
                    &quot;username&quot;: &quot;haskell-weber-7119&quot;,
                    &quot;display_name&quot;: &quot;Haskell Weber&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;Moodboard to final frame in 48 hours. Proud of this one. مع @adham-yousef&quot;,
                &quot;body_html&quot;: &quot;Moodboard to final frame in 48 hours. Proud of this one. مع &lt;bdi&gt;&lt;a href=\&quot;https://fama.test/adham-yousef\&quot; class=\&quot;feed-mention\&quot;&gt;⁦@adham-yousef⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [
                    {
                        &quot;id&quot;: 357,
                        &quot;type&quot;: &quot;image&quot;,
                        &quot;original_url&quot;: &quot;https://fama.test/storage/357/fama_a30dc8263c733ea61950f87bdf4b1f71.jpg&quot;,
                        &quot;thumb_url&quot;: &quot;https://fama.test/storage/357/fama_a30dc8263c733ea61950f87bdf4b1f71.jpg&quot;,
                        &quot;feed_url&quot;: &quot;https://fama.test/storage/357/fama_a30dc8263c733ea61950f87bdf4b1f71.jpg&quot;,
                        &quot;full_url&quot;: &quot;https://fama.test/storage/357/fama_a30dc8263c733ea61950f87bdf4b1f71.jpg&quot;,
                        &quot;poster_url&quot;: null
                    }
                ],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 2,
                        &quot;name&quot;: &quot;Photography&quot;,
                        &quot;slug&quot;: &quot;photography&quot;,
                        &quot;icon&quot;: &quot;lucide-photography&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 5,
                &quot;comments_count&quot;: 0,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-21T02:33:45+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/280&quot;
            },
            {
                &quot;id&quot;: 222,
                &quot;post_type&quot;: &quot;note&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 5,
                    &quot;username&quot;: &quot;omar-khaled&quot;,
                    &quot;display_name&quot;: &quot;Omar Khaled&quot;,
                    &quot;avatar_url&quot;: &quot;https://fama.test/storage/22/fama_c2262b1fb08ddc053d60f43edbdddb77.jpg&quot;
                },
                &quot;body&quot;: &quot;BTS from the night shoot in Downtown Cairo. Neon everywhere. مع @omar-khaled https://www.tiktok.com/@martin-ohara-6237&quot;,
                &quot;body_html&quot;: &quot;BTS from the night shoot in Downtown Cairo. Neon everywhere. مع &lt;bdi&gt;&lt;a href=\&quot;https://fama.test/omar-khaled\&quot; class=\&quot;feed-mention\&quot;&gt;⁦@omar-khaled⁩&lt;/a&gt;&lt;/bdi&gt; &lt;bdi&gt;&lt;a href=\&quot;https://www.tiktok.com/@martin-ohara-6237\&quot; class=\&quot;feed-chip\&quot; target=\&quot;_blank\&quot; rel=\&quot;noopener noreferrer nofollow ugc\&quot; data-provider=\&quot;tiktok\&quot;&gt;&lt;svg class=\&quot;chip-glyph\&quot; aria-hidden=\&quot;true\&quot;&gt;&lt;use href=\&quot;#glyph-tiktok\&quot;&gt;&lt;/use&gt;&lt;/svg&gt;⁦@martin-ohara-6237⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 3,
                        &quot;name&quot;: &quot;Cinematography&quot;,
                        &quot;slug&quot;: &quot;cinematography&quot;,
                        &quot;icon&quot;: &quot;lucide-cinematography&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [
                    {
                        &quot;provider&quot;: &quot;tiktok&quot;,
                        &quot;handle&quot;: &quot;martin-ohara-6237&quot;,
                        &quot;resource_id&quot;: null,
                        &quot;url&quot;: &quot;https://www.tiktok.com/@martin-ohara-6237&quot;,
                        &quot;canonical_url&quot;: null,
                        &quot;title&quot;: null,
                        &quot;description&quot;: null,
                        &quot;image_url&quot;: null,
                        &quot;status&quot;: &quot;pending&quot;
                    }
                ],
                &quot;likes_count&quot;: 8,
                &quot;comments_count&quot;: 6,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-16T18:06:44+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/222&quot;
            },
            {
                &quot;id&quot;: 98,
                &quot;post_type&quot;: &quot;note&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 29,
                    &quot;username&quot;: &quot;kelsi-simonis-6765&quot;,
                    &quot;display_name&quot;: &quot;Kelsi Simonis&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;الحمد لله، خلصنا حملة كاملة في يومين. فريق أكتر من رائع.&quot;,
                &quot;body_html&quot;: &quot;الحمد لله، خلصنا حملة كاملة في يومين. فريق أكتر من رائع.&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 1,
                        &quot;name&quot;: &quot;Modeling&quot;,
                        &quot;slug&quot;: &quot;modeling&quot;,
                        &quot;icon&quot;: &quot;lucide-modeling&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 6,
                &quot;comments_count&quot;: 6,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-17T00:10:42+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/98&quot;
            },
            {
                &quot;id&quot;: 217,
                &quot;post_type&quot;: &quot;note&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 23,
                    &quot;username&quot;: &quot;april-deckow-3527&quot;,
                    &quot;display_name&quot;: &quot;April Deckow&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;كواليس جلسة تصوير النهاردة في وسط البلد &mdash; الإضاءة كانت خيالية. https://instagram.com/cathryn-fay-dds-5582&quot;,
                &quot;body_html&quot;: &quot;كواليس جلسة تصوير النهاردة في وسط البلد &mdash; الإضاءة كانت خيالية. &lt;bdi&gt;&lt;a href=\&quot;https://instagram.com/cathryn-fay-dds-5582\&quot; class=\&quot;feed-chip\&quot; target=\&quot;_blank\&quot; rel=\&quot;noopener noreferrer nofollow ugc\&quot; data-provider=\&quot;instagram\&quot;&gt;&lt;svg class=\&quot;chip-glyph\&quot; aria-hidden=\&quot;true\&quot;&gt;&lt;use href=\&quot;#glyph-instagram\&quot;&gt;&lt;/use&gt;&lt;/svg&gt;⁦@cathryn-fay-dds-5582⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 6,
                        &quot;name&quot;: &quot;Graphic Design&quot;,
                        &quot;slug&quot;: &quot;graphic-design&quot;,
                        &quot;icon&quot;: &quot;lucide-graphic-design&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [
                    {
                        &quot;provider&quot;: &quot;instagram&quot;,
                        &quot;handle&quot;: &quot;cathryn-fay-dds-5582&quot;,
                        &quot;resource_id&quot;: null,
                        &quot;url&quot;: &quot;https://instagram.com/cathryn-fay-dds-5582&quot;,
                        &quot;canonical_url&quot;: null,
                        &quot;title&quot;: null,
                        &quot;description&quot;: null,
                        &quot;image_url&quot;: null,
                        &quot;status&quot;: &quot;pending&quot;
                    }
                ],
                &quot;likes_count&quot;: 6,
                &quot;comments_count&quot;: 7,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-15T17:18:44+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/217&quot;
            },
            {
                &quot;id&quot;: 233,
                &quot;post_type&quot;: &quot;media&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 30,
                    &quot;username&quot;: &quot;kyleigh-littel-iv-4434&quot;,
                    &quot;display_name&quot;: &quot;Kyleigh Littel IV&quot;,
                    &quot;avatar_url&quot;: null
                },
                &quot;body&quot;: &quot;التفاصيل هي كل حاجة. قصّة القماش دي أخدت مننا أسبوع كامل. مع @omar-khaled https://www.behance.net/ms-zelda-steuber-2004&quot;,
                &quot;body_html&quot;: &quot;التفاصيل هي كل حاجة. قصّة القماش دي أخدت مننا أسبوع كامل. مع &lt;bdi&gt;&lt;a href=\&quot;https://fama.test/omar-khaled\&quot; class=\&quot;feed-mention\&quot;&gt;⁦@omar-khaled⁩&lt;/a&gt;&lt;/bdi&gt; &lt;bdi&gt;&lt;a href=\&quot;https://www.behance.net/ms-zelda-steuber-2004\&quot; class=\&quot;feed-chip\&quot; target=\&quot;_blank\&quot; rel=\&quot;noopener noreferrer nofollow ugc\&quot; data-provider=\&quot;behance\&quot;&gt;&lt;svg class=\&quot;chip-glyph\&quot; aria-hidden=\&quot;true\&quot;&gt;&lt;use href=\&quot;#glyph-behance\&quot;&gt;&lt;/use&gt;&lt;/svg&gt;⁦@ms-zelda-steuber-2004⁩&lt;/a&gt;&lt;/bdi&gt;&quot;,
                &quot;media&quot;: [
                    {
                        &quot;id&quot;: 320,
                        &quot;type&quot;: &quot;image&quot;,
                        &quot;original_url&quot;: &quot;https://fama.test/storage/320/fama_85f6a34bd38bb40e0d2b9cabc45216f4.jpg&quot;,
                        &quot;thumb_url&quot;: &quot;https://fama.test/storage/320/fama_85f6a34bd38bb40e0d2b9cabc45216f4.jpg&quot;,
                        &quot;feed_url&quot;: &quot;https://fama.test/storage/320/fama_85f6a34bd38bb40e0d2b9cabc45216f4.jpg&quot;,
                        &quot;full_url&quot;: &quot;https://fama.test/storage/320/fama_85f6a34bd38bb40e0d2b9cabc45216f4.jpg&quot;,
                        &quot;poster_url&quot;: null
                    }
                ],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 6,
                        &quot;name&quot;: &quot;Graphic Design&quot;,
                        &quot;slug&quot;: &quot;graphic-design&quot;,
                        &quot;icon&quot;: &quot;lucide-graphic-design&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [
                    {
                        &quot;provider&quot;: &quot;behance&quot;,
                        &quot;handle&quot;: &quot;ms-zelda-steuber-2004&quot;,
                        &quot;resource_id&quot;: null,
                        &quot;url&quot;: &quot;https://www.behance.net/ms-zelda-steuber-2004&quot;,
                        &quot;canonical_url&quot;: null,
                        &quot;title&quot;: null,
                        &quot;description&quot;: null,
                        &quot;image_url&quot;: null,
                        &quot;status&quot;: &quot;pending&quot;
                    }
                ],
                &quot;likes_count&quot;: 8,
                &quot;comments_count&quot;: 4,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-16T13:02:44+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/233&quot;
            },
            {
                &quot;id&quot;: 286,
                &quot;post_type&quot;: &quot;media&quot;,
                &quot;author&quot;: {
                    &quot;type&quot;: &quot;talent&quot;,
                    &quot;id&quot;: 9,
                    &quot;username&quot;: &quot;tarek-sobhy&quot;,
                    &quot;display_name&quot;: &quot;Tarek Sobhy&quot;,
                    &quot;avatar_url&quot;: &quot;https://fama.test/storage/39/fama_2b74c7e3e7b9092d50871144e50c6364.jpg&quot;
                },
                &quot;body&quot;: null,
                &quot;body_html&quot;: null,
                &quot;media&quot;: [
                    {
                        &quot;id&quot;: 365,
                        &quot;type&quot;: &quot;image&quot;,
                        &quot;original_url&quot;: &quot;https://fama.test/storage/365/fama_eaf7548a947181157ec3a2fc0b3f1efe.jpg&quot;,
                        &quot;thumb_url&quot;: &quot;https://fama.test/storage/365/fama_eaf7548a947181157ec3a2fc0b3f1efe.jpg&quot;,
                        &quot;feed_url&quot;: &quot;https://fama.test/storage/365/fama_eaf7548a947181157ec3a2fc0b3f1efe.jpg&quot;,
                        &quot;full_url&quot;: &quot;https://fama.test/storage/365/fama_eaf7548a947181157ec3a2fc0b3f1efe.jpg&quot;,
                        &quot;poster_url&quot;: null
                    },
                    {
                        &quot;id&quot;: 366,
                        &quot;type&quot;: &quot;image&quot;,
                        &quot;original_url&quot;: &quot;https://fama.test/storage/366/fama_a48b4178c6fe513ec0224d491dcb2076.jpg&quot;,
                        &quot;thumb_url&quot;: &quot;https://fama.test/storage/366/fama_a48b4178c6fe513ec0224d491dcb2076.jpg&quot;,
                        &quot;feed_url&quot;: &quot;https://fama.test/storage/366/fama_a48b4178c6fe513ec0224d491dcb2076.jpg&quot;,
                        &quot;full_url&quot;: &quot;https://fama.test/storage/366/fama_a48b4178c6fe513ec0224d491dcb2076.jpg&quot;,
                        &quot;poster_url&quot;: null
                    },
                    {
                        &quot;id&quot;: 367,
                        &quot;type&quot;: &quot;image&quot;,
                        &quot;original_url&quot;: &quot;https://fama.test/storage/367/fama_22082b9a2d4a265333b157e7b76d25e7.jpg&quot;,
                        &quot;thumb_url&quot;: &quot;https://fama.test/storage/367/fama_22082b9a2d4a265333b157e7b76d25e7.jpg&quot;,
                        &quot;feed_url&quot;: &quot;https://fama.test/storage/367/fama_22082b9a2d4a265333b157e7b76d25e7.jpg&quot;,
                        &quot;full_url&quot;: &quot;https://fama.test/storage/367/fama_22082b9a2d4a265333b157e7b76d25e7.jpg&quot;,
                        &quot;poster_url&quot;: null
                    }
                ],
                &quot;skills&quot;: [
                    {
                        &quot;id&quot;: 3,
                        &quot;name&quot;: &quot;Cinematography&quot;,
                        &quot;slug&quot;: &quot;cinematography&quot;,
                        &quot;icon&quot;: &quot;lucide-cinematography&quot;
                    }
                ],
                &quot;project&quot;: null,
                &quot;chips&quot;: [],
                &quot;likes_count&quot;: 6,
                &quot;comments_count&quot;: 2,
                &quot;shares_count&quot;: 0,
                &quot;liked_by_viewer&quot;: false,
                &quot;bookmarks_count&quot;: 0,
                &quot;saved_by_viewer&quot;: false,
                &quot;is_pinned&quot;: false,
                &quot;is_editable&quot;: false,
                &quot;edited_at&quot;: null,
                &quot;published_at&quot;: &quot;2026-07-18T04:42:45+00:00&quot;,
                &quot;permalink&quot;: &quot;https://fama.test/posts/286&quot;
            }
        ],
        &quot;casting_positions&quot;: [
            5,
            11,
            17
        ],
        &quot;next_cursor&quot;: &quot;eyJzIjoiMTdlNThjNjgxNjkyZWY1YiIsIm8iOjIwfQ==&quot;,
        &quot;total&quot;: 185
    },
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-feed-items" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-feed-items"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-feed-items"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-feed-items" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-feed-items">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-feed-items" data-method="GET"
      data-path="api/v1/feed/items"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-feed-items', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-feed-items"
                    onclick="tryItOut('GETapi-v1-feed-items');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-feed-items"
                    onclick="cancelTryOut('GETapi-v1-feed-items');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-feed-items"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/feed/items</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-feed-items"
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
                              name="Accept"                data-endpoint="GETapi-v1-feed-items"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-feed-casting">One casting strip: discovery talents with the viewer&#039;s follow-state,
never repeating within the feed session.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-feed-casting">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/feed/casting" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/feed/casting"
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

<span id="example-responses-GETapi-v1-feed-casting">
            <blockquote>
            <p>Example response (200):</p>
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
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;talents&quot;: [
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 2,
                &quot;username&quot;: &quot;nour-elsherif&quot;,
                &quot;display_name&quot;: &quot;Nour El-Sherif&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/8/fama_bb26d0eea69aa81f0d000dcd5ef700a4.jpg&quot;,
                &quot;headline&quot;: &quot;Editorial &amp; runway model &mdash; Cairo&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Modeling&quot;,
                        &quot;slug&quot;: &quot;modeling&quot;,
                        &quot;icon&quot;: &quot;lucide-modeling&quot;
                    }
                ],
                &quot;followers_count&quot;: 7,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 6,
                &quot;username&quot;: &quot;hana-fahmy&quot;,
                &quot;display_name&quot;: &quot;Hana Fahmy&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/27/fama_60c7821fdbb46d3d3745c262079b366c.jpg&quot;,
                &quot;headline&quot;: &quot;Creative director &mdash; Dubai&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Creative Direction&quot;,
                        &quot;slug&quot;: &quot;creative-direction&quot;,
                        &quot;icon&quot;: &quot;lucide-creative-direction&quot;
                    }
                ],
                &quot;followers_count&quot;: 6,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 9,
                &quot;username&quot;: &quot;tarek-sobhy&quot;,
                &quot;display_name&quot;: &quot;Tarek Sobhy&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/39/fama_2b74c7e3e7b9092d50871144e50c6364.jpg&quot;,
                &quot;headline&quot;: &quot;Photographer &amp; DOP &mdash; Cairo&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Photography&quot;,
                        &quot;slug&quot;: &quot;photography&quot;,
                        &quot;icon&quot;: &quot;lucide-photography&quot;
                    },
                    {
                        &quot;name&quot;: &quot;Cinematography&quot;,
                        &quot;slug&quot;: &quot;cinematography&quot;,
                        &quot;icon&quot;: &quot;lucide-cinematography&quot;
                    }
                ],
                &quot;followers_count&quot;: 3,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 5,
                &quot;username&quot;: &quot;omar-khaled&quot;,
                &quot;display_name&quot;: &quot;Omar Khaled&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/22/fama_c2262b1fb08ddc053d60f43edbdddb77.jpg&quot;,
                &quot;headline&quot;: &quot;Cinematographer / DOP &mdash; Giza&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Cinematography&quot;,
                        &quot;slug&quot;: &quot;cinematography&quot;,
                        &quot;icon&quot;: &quot;lucide-cinematography&quot;
                    }
                ],
                &quot;followers_count&quot;: 5,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 4,
                &quot;username&quot;: &quot;yasmin-adel&quot;,
                &quot;display_name&quot;: &quot;Yasmin Adel&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/18/fama_f6b78ebcdedbf4990a3b07b1384067ec.jpg&quot;,
                &quot;headline&quot;: &quot;Model &amp; stylist &mdash; Cairo&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Modeling&quot;,
                        &quot;slug&quot;: &quot;modeling&quot;,
                        &quot;icon&quot;: &quot;lucide-modeling&quot;
                    },
                    {
                        &quot;name&quot;: &quot;Styling&quot;,
                        &quot;slug&quot;: &quot;styling&quot;,
                        &quot;icon&quot;: &quot;lucide-styling&quot;
                    }
                ],
                &quot;followers_count&quot;: 5,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 7,
                &quot;username&quot;: &quot;ziad-rahman&quot;,
                &quot;display_name&quot;: &quot;Ziad Rahman&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/31/fama_8289292746bec7c9e10d7bff7b684e7a.jpg&quot;,
                &quot;headline&quot;: &quot;Graphic designer &mdash; Cairo&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Graphic Design&quot;,
                        &quot;slug&quot;: &quot;graphic-design&quot;,
                        &quot;icon&quot;: &quot;lucide-graphic-design&quot;
                    }
                ],
                &quot;followers_count&quot;: 7,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 3,
                &quot;username&quot;: &quot;karim-mansour&quot;,
                &quot;display_name&quot;: &quot;Karim Mansour&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/13/fama_8dce61690bc37a9e8e27dc24c2a8b102.jpg&quot;,
                &quot;headline&quot;: &quot;Fashion &amp; commercial photographer &mdash; Alexandria&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Photography&quot;,
                        &quot;slug&quot;: &quot;photography&quot;,
                        &quot;icon&quot;: &quot;lucide-photography&quot;
                    }
                ],
                &quot;followers_count&quot;: 4,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 10,
                &quot;username&quot;: &quot;salma-ibrahim&quot;,
                &quot;display_name&quot;: &quot;Salma Ibrahim&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/44/fama_eab3b627b8acc1d1de623d08835247dc.jpg&quot;,
                &quot;headline&quot;: &quot;Fashion stylist &mdash; Cairo&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Styling&quot;,
                        &quot;slug&quot;: &quot;styling&quot;,
                        &quot;icon&quot;: &quot;lucide-styling&quot;
                    }
                ],
                &quot;followers_count&quot;: 3,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 8,
                &quot;username&quot;: &quot;farida-nabil&quot;,
                &quot;display_name&quot;: &quot;Farida Nabil&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/35/fama_a5ba6cf9e24223bc005ee9c156f6362f.jpg&quot;,
                &quot;headline&quot;: &quot;Commercial model &mdash; Alexandria&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Modeling&quot;,
                        &quot;slug&quot;: &quot;modeling&quot;,
                        &quot;icon&quot;: &quot;lucide-modeling&quot;
                    }
                ],
                &quot;followers_count&quot;: 8,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 1,
                &quot;username&quot;: &quot;demo-talent&quot;,
                &quot;display_name&quot;: &quot;Layla Hassan&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/1/fama_452592f6ef73ef3537814895ad921aea.jpg&quot;,
                &quot;headline&quot;: &quot;Model &amp; Photographer &mdash; Cairo&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Modeling&quot;,
                        &quot;slug&quot;: &quot;modeling&quot;,
                        &quot;icon&quot;: &quot;lucide-modeling&quot;
                    },
                    {
                        &quot;name&quot;: &quot;Photography&quot;,
                        &quot;slug&quot;: &quot;photography&quot;,
                        &quot;icon&quot;: &quot;lucide-photography&quot;
                    }
                ],
                &quot;followers_count&quot;: 5,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 11,
                &quot;username&quot;: &quot;adham-yousef&quot;,
                &quot;display_name&quot;: &quot;Adham Yousef&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/48/fama_23df95601ca70b95e05e2478c1a7e9ef.jpg&quot;,
                &quot;headline&quot;: &quot;Model &amp; creative director &mdash; Giza&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Modeling&quot;,
                        &quot;slug&quot;: &quot;modeling&quot;,
                        &quot;icon&quot;: &quot;lucide-modeling&quot;
                    },
                    {
                        &quot;name&quot;: &quot;Creative Direction&quot;,
                        &quot;slug&quot;: &quot;creative-direction&quot;,
                        &quot;icon&quot;: &quot;lucide-creative-direction&quot;
                    }
                ],
                &quot;followers_count&quot;: 9,
                &quot;followed_by_viewer&quot;: false
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 16,
                &quot;username&quot;: &quot;karianne-oconnell-2171&quot;,
                &quot;display_name&quot;: &quot;Karianne O&#039;Connell&quot;,
                &quot;avatar_url&quot;: null,
                &quot;headline&quot;: &quot;CTO&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Styling&quot;,
                        &quot;slug&quot;: &quot;styling&quot;,
                        &quot;icon&quot;: &quot;lucide-styling&quot;
                    }
                ],
                &quot;followers_count&quot;: 3,
                &quot;followed_by_viewer&quot;: false
            }
        ]
    },
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-feed-casting" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-feed-casting"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-feed-casting"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-feed-casting" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-feed-casting">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-feed-casting" data-method="GET"
      data-path="api/v1/feed/casting"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-feed-casting', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-feed-casting"
                    onclick="tryItOut('GETapi-v1-feed-casting');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-feed-casting"
                    onclick="cancelTryOut('GETapi-v1-feed-casting');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-feed-casting"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/feed/casting</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-feed-casting"
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
                              name="Accept"                data-endpoint="GETapi-v1-feed-casting"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-posts--post_id-">The post as JSON (the API mirror&#039;s detail shape).</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-posts--post_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/posts/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1"
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

<span id="example-responses-GETapi-v1-posts--post_id-">
            <blockquote>
            <p>Example response (200):</p>
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
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;post_type&quot;: &quot;project_share&quot;,
        &quot;author&quot;: {
            &quot;type&quot;: &quot;talent&quot;,
            &quot;id&quot;: 16,
            &quot;username&quot;: &quot;karianne-oconnell-2171&quot;,
            &quot;display_name&quot;: &quot;Karianne O&#039;Connell&quot;,
            &quot;avatar_url&quot;: null
        },
        &quot;body&quot;: null,
        &quot;body_html&quot;: null,
        &quot;media&quot;: [],
        &quot;skills&quot;: [
            {
                &quot;id&quot;: 5,
                &quot;name&quot;: &quot;Styling&quot;,
                &quot;slug&quot;: &quot;styling&quot;,
                &quot;icon&quot;: &quot;lucide-styling&quot;
            }
        ],
        &quot;project&quot;: {
            &quot;is_live&quot;: true,
            &quot;id&quot;: 7,
            &quot;title&quot;: &quot;Pre-emptive executive framework&quot;,
            &quot;summary&quot;: &quot;Quia veritatis aut non aperiam officiis pariatur magni.&quot;,
            &quot;cover_url&quot;: null,
            &quot;year&quot;: 2021,
            &quot;url&quot;: &quot;https://fama.test/karianne-oconnell-2171/work/7&quot;
        },
        &quot;chips&quot;: [],
        &quot;likes_count&quot;: 1,
        &quot;comments_count&quot;: 0,
        &quot;shares_count&quot;: 0,
        &quot;bookmarks_count&quot;: 0,
        &quot;is_pinned&quot;: false,
        &quot;is_editable&quot;: false,
        &quot;edited_at&quot;: null,
        &quot;published_at&quot;: &quot;2026-07-11T08:31:40+00:00&quot;,
        &quot;permalink&quot;: &quot;https://fama.test/posts/1&quot;
    },
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-posts--post_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-posts--post_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-posts--post_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-posts--post_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-posts--post_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-posts--post_id-" data-method="GET"
      data-path="api/v1/posts/{post_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-posts--post_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-posts--post_id-"
                    onclick="tryItOut('GETapi-v1-posts--post_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-posts--post_id-"
                    onclick="cancelTryOut('GETapi-v1-posts--post_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-posts--post_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/posts/{post_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-posts--post_id-"
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
                              name="Accept"                data-endpoint="GETapi-v1-posts--post_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="GETapi-v1-posts--post_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-talents--username--posts">A talent&#039;s published posts, newest first (public).</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-talents--username--posts">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talents/1/posts" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talents/1/posts"
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

<span id="example-responses-GETapi-v1-talents--username--posts">
            <blockquote>
            <p>Example response (404):</p>
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
    &quot;message&quot;: &quot;&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talents--username--posts" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talents--username--posts"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talents--username--posts"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talents--username--posts" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talents--username--posts">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talents--username--posts" data-method="GET"
      data-path="api/v1/talents/{username}/posts"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talents--username--posts', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talents--username--posts"
                    onclick="tryItOut('GETapi-v1-talents--username--posts');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talents--username--posts"
                    onclick="cancelTryOut('GETapi-v1-talents--username--posts');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talents--username--posts"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talents/{username}/posts</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talents--username--posts"
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
                              name="Accept"                data-endpoint="GETapi-v1-talents--username--posts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>username</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="username"                data-endpoint="GETapi-v1-talents--username--posts"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-posts">Create a note or media post as the current actor.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-posts">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/posts" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "post_type=note"\
    --form "body=b"\
    --form "skill_ids[]=16"\
    --form "media[]=@/private/var/folders/88/vbp1hp0s0rl7dm90kvjglj940000gn/T/phprbelhkfdekvtcdGvMCX" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('post_type', 'note');
body.append('body', 'b');
body.append('skill_ids[]', '16');
body.append('media[]', document.querySelector('input[name="media[]"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-posts">
</span>
<span id="execution-results-POSTapi-v1-posts" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-posts"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-posts"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-posts" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-posts">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-posts" data-method="POST"
      data-path="api/v1/posts"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-posts', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-posts"
                    onclick="tryItOut('POSTapi-v1-posts');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-posts"
                    onclick="cancelTryOut('POSTapi-v1-posts');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-posts"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/posts</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-posts"
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
                              name="Accept"                data-endpoint="POSTapi-v1-posts"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>post_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="post_type"                data-endpoint="POSTapi-v1-posts"
               value="note"
               data-component="body">
    <br>
<p>Example: <code>note</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>note</code></li> <li><code>media</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="POSTapi-v1-posts"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 2200 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>media</code></b>&nbsp;&nbsp;
<small>file[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="media[0]"                data-endpoint="POSTapi-v1-posts"
               data-component="body">
        <input type="file" style="display: none"
               name="media[1]"                data-endpoint="POSTapi-v1-posts"
               data-component="body">
    <br>
<p>Must be a file.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>skill_ids</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="skill_ids[0]"                data-endpoint="POSTapi-v1-posts"
               data-component="body">
        <input type="number" style="display: none"
               name="skill_ids[1]"                data-endpoint="POSTapi-v1-posts"
               data-component="body">
    <br>
<p>Must match an existing stored value.</p>
        </div>
        </form>

                    <h2 id="endpoints-PATCHapi-v1-posts--post_id-">Edit the post inside the 15-minute window (media immutable).</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-posts--post_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/posts/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"body\": \"b\",
    \"skill_ids\": [
        16
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "body": "b",
    "skill_ids": [
        16
    ]
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-posts--post_id-">
</span>
<span id="execution-results-PATCHapi-v1-posts--post_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-posts--post_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-posts--post_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-posts--post_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-posts--post_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-posts--post_id-" data-method="PATCH"
      data-path="api/v1/posts/{post_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-posts--post_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-posts--post_id-"
                    onclick="tryItOut('PATCHapi-v1-posts--post_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-posts--post_id-"
                    onclick="cancelTryOut('PATCHapi-v1-posts--post_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-posts--post_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/posts/{post_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-posts--post_id-"
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
                              name="Accept"                data-endpoint="PATCHapi-v1-posts--post_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="PATCHapi-v1-posts--post_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="PATCHapi-v1-posts--post_id-"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 2200 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>skill_ids</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="skill_ids[0]"                data-endpoint="PATCHapi-v1-posts--post_id-"
               data-component="body">
        <input type="number" style="display: none"
               name="skill_ids[1]"                data-endpoint="PATCHapi-v1-posts--post_id-"
               data-component="body">
    <br>
<p>Must match an existing stored value.</p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-posts--post_id-">Soft-delete the post (author or moderating admin).</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-posts--post_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/posts/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-posts--post_id-">
</span>
<span id="execution-results-DELETEapi-v1-posts--post_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-posts--post_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-posts--post_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-posts--post_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-posts--post_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-posts--post_id-" data-method="DELETE"
      data-path="api/v1/posts/{post_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-posts--post_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-posts--post_id-"
                    onclick="tryItOut('DELETEapi-v1-posts--post_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-posts--post_id-"
                    onclick="cancelTryOut('DELETEapi-v1-posts--post_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-posts--post_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/posts/{post_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-posts--post_id-"
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
                              name="Accept"                data-endpoint="DELETEapi-v1-posts--post_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="DELETEapi-v1-posts--post_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-posts--post_id--pin">Pin/unpin the post on the author&#039;s presence (max one pinned).</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-posts--post_id--pin">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/posts/1/pin" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1/pin"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-posts--post_id--pin">
</span>
<span id="execution-results-POSTapi-v1-posts--post_id--pin" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-posts--post_id--pin"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-posts--post_id--pin"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-posts--post_id--pin" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-posts--post_id--pin">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-posts--post_id--pin" data-method="POST"
      data-path="api/v1/posts/{post_id}/pin"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-posts--post_id--pin', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-posts--post_id--pin"
                    onclick="tryItOut('POSTapi-v1-posts--post_id--pin');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-posts--post_id--pin"
                    onclick="cancelTryOut('POSTapi-v1-posts--post_id--pin');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-posts--post_id--pin"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/posts/{post_id}/pin</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-posts--post_id--pin"
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
                              name="Accept"                data-endpoint="POSTapi-v1-posts--post_id--pin"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="POSTapi-v1-posts--post_id--pin"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-projects--project_id--share">Share the Project as a project_share post.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-projects--project_id--share">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/projects/1/share" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"body\": \"b\",
    \"skill_ids\": [
        16
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/projects/1/share"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "body": "b",
    "skill_ids": [
        16
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-projects--project_id--share">
</span>
<span id="execution-results-POSTapi-v1-projects--project_id--share" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-projects--project_id--share"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-projects--project_id--share"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-projects--project_id--share" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-projects--project_id--share">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-projects--project_id--share" data-method="POST"
      data-path="api/v1/projects/{project_id}/share"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-projects--project_id--share', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-projects--project_id--share"
                    onclick="tryItOut('POSTapi-v1-projects--project_id--share');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-projects--project_id--share"
                    onclick="cancelTryOut('POSTapi-v1-projects--project_id--share');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-projects--project_id--share"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/projects/{project_id}/share</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-projects--project_id--share"
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
                              name="Accept"                data-endpoint="POSTapi-v1-projects--project_id--share"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>project_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="project_id"                data-endpoint="POSTapi-v1-projects--project_id--share"
               value="1"
               data-component="url">
    <br>
<p>The ID of the project. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="POSTapi-v1-projects--project_id--share"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 2200 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>skill_ids</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="skill_ids[0]"                data-endpoint="POSTapi-v1-projects--project_id--share"
               data-component="body">
        <input type="number" style="display: none"
               name="skill_ids[1]"                data-endpoint="POSTapi-v1-projects--project_id--share"
               data-component="body">
    <br>
<p>Must match an existing stored value.</p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-posts--post_id--comments">Paginated top-level thread with preview replies and viewer state.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-posts--post_id--comments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/posts/1/comments" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1/comments"
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

<span id="example-responses-GETapi-v1-posts--post_id--comments">
            <blockquote>
            <p>Example response (200):</p>
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
    &quot;success&quot;: true,
    &quot;data&quot;: [],
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: {
        &quot;pagination&quot;: {
            &quot;current_page&quot;: 1,
            &quot;last_page&quot;: 1,
            &quot;per_page&quot;: 10,
            &quot;total&quot;: 0,
            &quot;from&quot;: null,
            &quot;to&quot;: null
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-posts--post_id--comments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-posts--post_id--comments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-posts--post_id--comments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-posts--post_id--comments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-posts--post_id--comments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-posts--post_id--comments" data-method="GET"
      data-path="api/v1/posts/{post_id}/comments"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-posts--post_id--comments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-posts--post_id--comments"
                    onclick="tryItOut('GETapi-v1-posts--post_id--comments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-posts--post_id--comments"
                    onclick="cancelTryOut('GETapi-v1-posts--post_id--comments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-posts--post_id--comments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/posts/{post_id}/comments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-posts--post_id--comments"
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
                              name="Accept"                data-endpoint="GETapi-v1-posts--post_id--comments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="GETapi-v1-posts--post_id--comments"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-posts--post_id--comments">Create a comment (or reply) on the post as the current actor.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-posts--post_id--comments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/posts/1/comments" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"body\": \"b\",
    \"parent_id\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1/comments"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "body": "b",
    "parent_id": 16
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-posts--post_id--comments">
</span>
<span id="execution-results-POSTapi-v1-posts--post_id--comments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-posts--post_id--comments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-posts--post_id--comments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-posts--post_id--comments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-posts--post_id--comments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-posts--post_id--comments" data-method="POST"
      data-path="api/v1/posts/{post_id}/comments"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-posts--post_id--comments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-posts--post_id--comments"
                    onclick="tryItOut('POSTapi-v1-posts--post_id--comments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-posts--post_id--comments"
                    onclick="cancelTryOut('POSTapi-v1-posts--post_id--comments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-posts--post_id--comments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/posts/{post_id}/comments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-posts--post_id--comments"
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
                              name="Accept"                data-endpoint="POSTapi-v1-posts--post_id--comments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="POSTapi-v1-posts--post_id--comments"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="POSTapi-v1-posts--post_id--comments"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 2200 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>parent_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="parent_id"                data-endpoint="POSTapi-v1-posts--post_id--comments"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-comments--comment_id--replies">A comment&#039;s replies, paginated separately, oldest first.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-comments--comment_id--replies">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/comments/1/replies" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/comments/1/replies"
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

<span id="example-responses-GETapi-v1-comments--comment_id--replies">
            <blockquote>
            <p>Example response (200):</p>
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
    &quot;success&quot;: true,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 2,
            &quot;is_tombstone&quot;: false,
            &quot;post_id&quot;: 3,
            &quot;parent_id&quot;: 1,
            &quot;body&quot;: &quot;تسلم يا فنان ❤️&quot;,
            &quot;body_html&quot;: &quot;تسلم يا فنان ❤️&quot;,
            &quot;author&quot;: {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 2,
                &quot;username&quot;: &quot;nour-elsherif&quot;,
                &quot;display_name&quot;: &quot;Nour El-Sherif&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/8/fama_bb26d0eea69aa81f0d000dcd5ef700a4.jpg&quot;
            },
            &quot;state&quot;: &quot;published&quot;,
            &quot;chips&quot;: [],
            &quot;likes_count&quot;: 0,
            &quot;replies_count&quot;: 0,
            &quot;liked_by_viewer&quot;: false,
            &quot;is_editable&quot;: false,
            &quot;is_mine&quot;: false,
            &quot;editable_until&quot;: &quot;2026-06-17T23:02:40+00:00&quot;,
            &quot;edited_at&quot;: null,
            &quot;created_at&quot;: &quot;2026-06-17T22:47:40+00:00&quot;
        }
    ],
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: {
        &quot;pagination&quot;: {
            &quot;current_page&quot;: 1,
            &quot;last_page&quot;: 1,
            &quot;per_page&quot;: 10,
            &quot;total&quot;: 1,
            &quot;from&quot;: 1,
            &quot;to&quot;: 1
        }
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-comments--comment_id--replies" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-comments--comment_id--replies"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-comments--comment_id--replies"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-comments--comment_id--replies" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-comments--comment_id--replies">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-comments--comment_id--replies" data-method="GET"
      data-path="api/v1/comments/{comment_id}/replies"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-comments--comment_id--replies', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-comments--comment_id--replies"
                    onclick="tryItOut('GETapi-v1-comments--comment_id--replies');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-comments--comment_id--replies"
                    onclick="cancelTryOut('GETapi-v1-comments--comment_id--replies');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-comments--comment_id--replies"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/comments/{comment_id}/replies</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-comments--comment_id--replies"
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
                              name="Accept"                data-endpoint="GETapi-v1-comments--comment_id--replies"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>comment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="comment_id"                data-endpoint="GETapi-v1-comments--comment_id--replies"
               value="1"
               data-component="url">
    <br>
<p>The ID of the comment. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PATCHapi-v1-comments--comment_id-">Edit a comment inside the 15-minute window.</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-comments--comment_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "https://fama.test/api/v1/comments/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"body\": \"b\",
    \"parent_id\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/comments/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "body": "b",
    "parent_id": 16
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-comments--comment_id-">
</span>
<span id="execution-results-PATCHapi-v1-comments--comment_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-comments--comment_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-comments--comment_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-comments--comment_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-comments--comment_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-comments--comment_id-" data-method="PATCH"
      data-path="api/v1/comments/{comment_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-comments--comment_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-comments--comment_id-"
                    onclick="tryItOut('PATCHapi-v1-comments--comment_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-comments--comment_id-"
                    onclick="cancelTryOut('PATCHapi-v1-comments--comment_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-comments--comment_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/comments/{comment_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-comments--comment_id-"
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
                              name="Accept"                data-endpoint="PATCHapi-v1-comments--comment_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>comment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="comment_id"                data-endpoint="PATCHapi-v1-comments--comment_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the comment. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>body</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="body"                data-endpoint="PATCHapi-v1-comments--comment_id-"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 2200 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>parent_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="parent_id"                data-endpoint="PATCHapi-v1-comments--comment_id-"
               value="16"
               data-component="body">
    <br>
<p>Must match an existing stored value. Example: <code>16</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-comments--comment_id-">Delete a comment (tombstoned when it has replies).</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-comments--comment_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/comments/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/comments/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-comments--comment_id-">
</span>
<span id="execution-results-DELETEapi-v1-comments--comment_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-comments--comment_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-comments--comment_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-comments--comment_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-comments--comment_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-comments--comment_id-" data-method="DELETE"
      data-path="api/v1/comments/{comment_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-comments--comment_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-comments--comment_id-"
                    onclick="tryItOut('DELETEapi-v1-comments--comment_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-comments--comment_id-"
                    onclick="cancelTryOut('DELETEapi-v1-comments--comment_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-comments--comment_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/comments/{comment_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-comments--comment_id-"
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
                              name="Accept"                data-endpoint="DELETEapi-v1-comments--comment_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>comment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="comment_id"                data-endpoint="DELETEapi-v1-comments--comment_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the comment. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-posts--post_id--like">Like the post as the current actor.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-posts--post_id--like">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/posts/1/like" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1/like"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-posts--post_id--like">
</span>
<span id="execution-results-POSTapi-v1-posts--post_id--like" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-posts--post_id--like"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-posts--post_id--like"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-posts--post_id--like" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-posts--post_id--like">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-posts--post_id--like" data-method="POST"
      data-path="api/v1/posts/{post_id}/like"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-posts--post_id--like', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-posts--post_id--like"
                    onclick="tryItOut('POSTapi-v1-posts--post_id--like');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-posts--post_id--like"
                    onclick="cancelTryOut('POSTapi-v1-posts--post_id--like');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-posts--post_id--like"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/posts/{post_id}/like</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-posts--post_id--like"
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
                              name="Accept"                data-endpoint="POSTapi-v1-posts--post_id--like"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="POSTapi-v1-posts--post_id--like"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-DELETEapi-v1-posts--post_id--like">Withdraw the actor&#039;s like from the post.</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-posts--post_id--like">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/posts/1/like" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1/like"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-posts--post_id--like">
</span>
<span id="execution-results-DELETEapi-v1-posts--post_id--like" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-posts--post_id--like"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-posts--post_id--like"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-posts--post_id--like" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-posts--post_id--like">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-posts--post_id--like" data-method="DELETE"
      data-path="api/v1/posts/{post_id}/like"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-posts--post_id--like', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-posts--post_id--like"
                    onclick="tryItOut('DELETEapi-v1-posts--post_id--like');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-posts--post_id--like"
                    onclick="cancelTryOut('DELETEapi-v1-posts--post_id--like');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-posts--post_id--like"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/posts/{post_id}/like</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-posts--post_id--like"
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
                              name="Accept"                data-endpoint="DELETEapi-v1-posts--post_id--like"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="DELETEapi-v1-posts--post_id--like"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-comments--comment_id--like">Like a comment as the current actor.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-comments--comment_id--like">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/comments/1/like" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/comments/1/like"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-comments--comment_id--like">
</span>
<span id="execution-results-POSTapi-v1-comments--comment_id--like" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-comments--comment_id--like"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-comments--comment_id--like"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-comments--comment_id--like" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-comments--comment_id--like">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-comments--comment_id--like" data-method="POST"
      data-path="api/v1/comments/{comment_id}/like"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-comments--comment_id--like', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-comments--comment_id--like"
                    onclick="tryItOut('POSTapi-v1-comments--comment_id--like');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-comments--comment_id--like"
                    onclick="cancelTryOut('POSTapi-v1-comments--comment_id--like');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-comments--comment_id--like"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/comments/{comment_id}/like</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-comments--comment_id--like"
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
                              name="Accept"                data-endpoint="POSTapi-v1-comments--comment_id--like"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>comment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="comment_id"                data-endpoint="POSTapi-v1-comments--comment_id--like"
               value="1"
               data-component="url">
    <br>
<p>The ID of the comment. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-DELETEapi-v1-comments--comment_id--like">Withdraw the actor&#039;s like from the comment.</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-comments--comment_id--like">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/comments/1/like" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/comments/1/like"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-comments--comment_id--like">
</span>
<span id="execution-results-DELETEapi-v1-comments--comment_id--like" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-comments--comment_id--like"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-comments--comment_id--like"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-comments--comment_id--like" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-comments--comment_id--like">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-comments--comment_id--like" data-method="DELETE"
      data-path="api/v1/comments/{comment_id}/like"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-comments--comment_id--like', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-comments--comment_id--like"
                    onclick="tryItOut('DELETEapi-v1-comments--comment_id--like');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-comments--comment_id--like"
                    onclick="cancelTryOut('DELETEapi-v1-comments--comment_id--like');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-comments--comment_id--like"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/comments/{comment_id}/like</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-comments--comment_id--like"
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
                              name="Accept"                data-endpoint="DELETEapi-v1-comments--comment_id--like"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>comment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="comment_id"                data-endpoint="DELETEapi-v1-comments--comment_id--like"
               value="1"
               data-component="url">
    <br>
<p>The ID of the comment. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-feed-suggestions-talents">Talent suggestions: ?offset=0&amp;limit=5 → {items, next_offset}.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-feed-suggestions-talents">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/feed/suggestions/talents" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/feed/suggestions/talents"
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

<span id="example-responses-GETapi-v1-feed-suggestions-talents">
            <blockquote>
            <p>Example response (200):</p>
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
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;items&quot;: [
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 31,
                &quot;username&quot;: &quot;ms-zelda-steuber-2004&quot;,
                &quot;display_name&quot;: &quot;Ms. Zelda Steuber&quot;,
                &quot;avatar_url&quot;: null,
                &quot;headline&quot;: &quot;Human Resources Specialist&quot;,
                &quot;city&quot;: &quot;Dubai&quot;,
                &quot;sector&quot;: null,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Cinematography&quot;,
                        &quot;slug&quot;: &quot;cinematography&quot;
                    }
                ],
                &quot;followers_count&quot;: 10,
                &quot;reason&quot;: null
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 23,
                &quot;username&quot;: &quot;april-deckow-3527&quot;,
                &quot;display_name&quot;: &quot;April Deckow&quot;,
                &quot;avatar_url&quot;: null,
                &quot;headline&quot;: &quot;Landscape Architect&quot;,
                &quot;city&quot;: &quot;Alexandria&quot;,
                &quot;sector&quot;: null,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Graphic Design&quot;,
                        &quot;slug&quot;: &quot;graphic-design&quot;
                    }
                ],
                &quot;followers_count&quot;: 10,
                &quot;reason&quot;: &quot;Active this week&quot;
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 11,
                &quot;username&quot;: &quot;adham-yousef&quot;,
                &quot;display_name&quot;: &quot;Adham Yousef&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/48/fama_23df95601ca70b95e05e2478c1a7e9ef.jpg&quot;,
                &quot;headline&quot;: &quot;Model &amp; creative director &mdash; Giza&quot;,
                &quot;city&quot;: &quot;Giza&quot;,
                &quot;sector&quot;: null,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Modeling&quot;,
                        &quot;slug&quot;: &quot;modeling&quot;
                    },
                    {
                        &quot;name&quot;: &quot;Creative Direction&quot;,
                        &quot;slug&quot;: &quot;creative-direction&quot;
                    }
                ],
                &quot;followers_count&quot;: 9,
                &quot;reason&quot;: &quot;Active this week&quot;
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 18,
                &quot;username&quot;: &quot;dr-webster-doyle-0827&quot;,
                &quot;display_name&quot;: &quot;Dr. Webster Doyle&quot;,
                &quot;avatar_url&quot;: null,
                &quot;headline&quot;: &quot;Earth Driller&quot;,
                &quot;city&quot;: &quot;Giza&quot;,
                &quot;sector&quot;: null,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Creative Direction&quot;,
                        &quot;slug&quot;: &quot;creative-direction&quot;
                    }
                ],
                &quot;followers_count&quot;: 7,
                &quot;reason&quot;: null
            },
            {
                &quot;type&quot;: &quot;talent&quot;,
                &quot;id&quot;: 7,
                &quot;username&quot;: &quot;ziad-rahman&quot;,
                &quot;display_name&quot;: &quot;Ziad Rahman&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/31/fama_8289292746bec7c9e10d7bff7b684e7a.jpg&quot;,
                &quot;headline&quot;: &quot;Graphic designer &mdash; Cairo&quot;,
                &quot;city&quot;: &quot;Cairo&quot;,
                &quot;sector&quot;: null,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Graphic Design&quot;,
                        &quot;slug&quot;: &quot;graphic-design&quot;
                    }
                ],
                &quot;followers_count&quot;: 7,
                &quot;reason&quot;: &quot;Active this week&quot;
            }
        ],
        &quot;next_offset&quot;: 5
    },
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-feed-suggestions-talents" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-feed-suggestions-talents"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-feed-suggestions-talents"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-feed-suggestions-talents" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-feed-suggestions-talents">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-feed-suggestions-talents" data-method="GET"
      data-path="api/v1/feed/suggestions/talents"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-feed-suggestions-talents', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-feed-suggestions-talents"
                    onclick="tryItOut('GETapi-v1-feed-suggestions-talents');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-feed-suggestions-talents"
                    onclick="cancelTryOut('GETapi-v1-feed-suggestions-talents');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-feed-suggestions-talents"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/feed/suggestions/talents</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-feed-suggestions-talents"
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
                              name="Accept"                data-endpoint="GETapi-v1-feed-suggestions-talents"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-feed-suggestions-brands">Brand suggestions (empty for brand viewers — the rail hides).</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-feed-suggestions-brands">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/feed/suggestions/brands" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/feed/suggestions/brands"
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

<span id="example-responses-GETapi-v1-feed-suggestions-brands">
            <blockquote>
            <p>Example response (200):</p>
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
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;items&quot;: [
            {
                &quot;type&quot;: &quot;brand&quot;,
                &quot;id&quot;: 1,
                &quot;username&quot;: &quot;nomad-coffee&quot;,
                &quot;display_name&quot;: &quot;Nomad Coffee Co.&quot;,
                &quot;avatar_url&quot;: &quot;https://fama.test/storage/53/fama_d600005f59d82bdf62bab01894e7fd87.jpg&quot;,
                &quot;headline&quot;: null,
                &quot;city&quot;: &quot;Cairo&quot;,
                &quot;sector&quot;: &quot;food_beverage&quot;,
                &quot;skills&quot;: [
                    {
                        &quot;name&quot;: &quot;Photography&quot;,
                        &quot;slug&quot;: &quot;photography&quot;
                    }
                ],
                &quot;followers_count&quot;: 0,
                &quot;reason&quot;: &quot;Active this week&quot;
            },
            {
                &quot;type&quot;: &quot;brand&quot;,
                &quot;id&quot;: 11,
                &quot;username&quot;: &quot;okeefe-and-sons-4543&quot;,
                &quot;display_name&quot;: &quot;O&#039;Keefe and Sons&quot;,
                &quot;avatar_url&quot;: null,
                &quot;headline&quot;: null,
                &quot;city&quot;: &quot;Giza&quot;,
                &quot;sector&quot;: &quot;food_beverage&quot;,
                &quot;skills&quot;: [],
                &quot;followers_count&quot;: 0,
                &quot;reason&quot;: null
            },
            {
                &quot;type&quot;: &quot;brand&quot;,
                &quot;id&quot;: 16,
                &quot;username&quot;: &quot;brakus-carroll-and-thompson-61806&quot;,
                &quot;display_name&quot;: &quot;Brakus, Carroll and Thompson&quot;,
                &quot;avatar_url&quot;: null,
                &quot;headline&quot;: null,
                &quot;city&quot;: &quot;Giza&quot;,
                &quot;sector&quot;: &quot;lifestyle&quot;,
                &quot;skills&quot;: [],
                &quot;followers_count&quot;: 0,
                &quot;reason&quot;: null
            },
            {
                &quot;type&quot;: &quot;brand&quot;,
                &quot;id&quot;: 15,
                &quot;username&quot;: &quot;sauer-swift-62082&quot;,
                &quot;display_name&quot;: &quot;Sauer-Swift&quot;,
                &quot;avatar_url&quot;: null,
                &quot;headline&quot;: null,
                &quot;city&quot;: &quot;Giza&quot;,
                &quot;sector&quot;: &quot;beauty&quot;,
                &quot;skills&quot;: [],
                &quot;followers_count&quot;: 0,
                &quot;reason&quot;: null
            },
            {
                &quot;type&quot;: &quot;brand&quot;,
                &quot;id&quot;: 5,
                &quot;username&quot;: &quot;kertzmann-weimann-and-koss-94975&quot;,
                &quot;display_name&quot;: &quot;Kertzmann, Weimann and Koss&quot;,
                &quot;avatar_url&quot;: null,
                &quot;headline&quot;: null,
                &quot;city&quot;: &quot;Alexandria&quot;,
                &quot;sector&quot;: &quot;beauty&quot;,
                &quot;skills&quot;: [],
                &quot;followers_count&quot;: 0,
                &quot;reason&quot;: null
            }
        ],
        &quot;next_offset&quot;: 5
    },
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-feed-suggestions-brands" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-feed-suggestions-brands"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-feed-suggestions-brands"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-feed-suggestions-brands" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-feed-suggestions-brands">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-feed-suggestions-brands" data-method="GET"
      data-path="api/v1/feed/suggestions/brands"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-feed-suggestions-brands', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-feed-suggestions-brands"
                    onclick="tryItOut('GETapi-v1-feed-suggestions-brands');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-feed-suggestions-brands"
                    onclick="cancelTryOut('GETapi-v1-feed-suggestions-brands');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-feed-suggestions-brands"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/feed/suggestions/brands</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-feed-suggestions-brands"
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
                              name="Accept"                data-endpoint="GETapi-v1-feed-suggestions-brands"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-feed-suggestions-dismiss">Permanently dismiss one suggestion: {type: talent|brand, id}.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-feed-suggestions-dismiss">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/feed/suggestions/dismiss" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"type\": \"brand\",
    \"id\": 16
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/feed/suggestions/dismiss"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "type": "brand",
    "id": 16
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-feed-suggestions-dismiss">
</span>
<span id="execution-results-POSTapi-v1-feed-suggestions-dismiss" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-feed-suggestions-dismiss"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-feed-suggestions-dismiss"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-feed-suggestions-dismiss" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-feed-suggestions-dismiss">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-feed-suggestions-dismiss" data-method="POST"
      data-path="api/v1/feed/suggestions/dismiss"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-feed-suggestions-dismiss', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-feed-suggestions-dismiss"
                    onclick="tryItOut('POSTapi-v1-feed-suggestions-dismiss');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-feed-suggestions-dismiss"
                    onclick="cancelTryOut('POSTapi-v1-feed-suggestions-dismiss');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-feed-suggestions-dismiss"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/feed/suggestions/dismiss</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-feed-suggestions-dismiss"
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
                              name="Accept"                data-endpoint="POSTapi-v1-feed-suggestions-dismiss"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="type"                data-endpoint="POSTapi-v1-feed-suggestions-dismiss"
               value="brand"
               data-component="body">
    <br>
<p>Example: <code>brand</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>talent</code></li> <li><code>brand</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="POSTapi-v1-feed-suggestions-dismiss"
               value="16"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>16</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-posts--post_id--bookmark">Save the post as the current actor.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-posts--post_id--bookmark">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/posts/1/bookmark" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1/bookmark"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-posts--post_id--bookmark">
</span>
<span id="execution-results-POSTapi-v1-posts--post_id--bookmark" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-posts--post_id--bookmark"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-posts--post_id--bookmark"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-posts--post_id--bookmark" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-posts--post_id--bookmark">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-posts--post_id--bookmark" data-method="POST"
      data-path="api/v1/posts/{post_id}/bookmark"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-posts--post_id--bookmark', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-posts--post_id--bookmark"
                    onclick="tryItOut('POSTapi-v1-posts--post_id--bookmark');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-posts--post_id--bookmark"
                    onclick="cancelTryOut('POSTapi-v1-posts--post_id--bookmark');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-posts--post_id--bookmark"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/posts/{post_id}/bookmark</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-posts--post_id--bookmark"
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
                              name="Accept"                data-endpoint="POSTapi-v1-posts--post_id--bookmark"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="POSTapi-v1-posts--post_id--bookmark"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-DELETEapi-v1-posts--post_id--bookmark">Withdraw the actor&#039;s save.</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-posts--post_id--bookmark">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/posts/1/bookmark" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1/bookmark"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-posts--post_id--bookmark">
</span>
<span id="execution-results-DELETEapi-v1-posts--post_id--bookmark" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-posts--post_id--bookmark"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-posts--post_id--bookmark"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-posts--post_id--bookmark" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-posts--post_id--bookmark">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-posts--post_id--bookmark" data-method="DELETE"
      data-path="api/v1/posts/{post_id}/bookmark"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-posts--post_id--bookmark', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-posts--post_id--bookmark"
                    onclick="tryItOut('DELETEapi-v1-posts--post_id--bookmark');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-posts--post_id--bookmark"
                    onclick="cancelTryOut('DELETEapi-v1-posts--post_id--bookmark');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-posts--post_id--bookmark"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/posts/{post_id}/bookmark</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-posts--post_id--bookmark"
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
                              name="Accept"                data-endpoint="DELETEapi-v1-posts--post_id--bookmark"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="DELETEapi-v1-posts--post_id--bookmark"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-follow--type---id-">Follow the {type}/{id} party as the current actor.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-follow--type---id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/follow/talent|brand/564" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/follow/talent|brand/564"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-follow--type---id-">
</span>
<span id="execution-results-POSTapi-v1-follow--type---id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-follow--type---id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-follow--type---id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-follow--type---id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-follow--type---id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-follow--type---id-" data-method="POST"
      data-path="api/v1/follow/{type}/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-follow--type---id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-follow--type---id-"
                    onclick="tryItOut('POSTapi-v1-follow--type---id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-follow--type---id-"
                    onclick="cancelTryOut('POSTapi-v1-follow--type---id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-follow--type---id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/follow/{type}/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-follow--type---id-"
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
                              name="Accept"                data-endpoint="POSTapi-v1-follow--type---id-"
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
                              name="type"                data-endpoint="POSTapi-v1-follow--type---id-"
               value="talent|brand"
               data-component="url">
    <br>
<p>Example: <code>talent|brand</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTapi-v1-follow--type---id-"
               value="564"
               data-component="url">
    <br>
<p>The ID of the {type}. Example: <code>564</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-DELETEapi-v1-follow--type---id-">Withdraw the actor&#039;s follow of the {type}/{id} party.</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-follow--type---id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "https://fama.test/api/v1/follow/talent|brand/564" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/follow/talent|brand/564"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-follow--type---id-">
</span>
<span id="execution-results-DELETEapi-v1-follow--type---id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-follow--type---id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-follow--type---id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-follow--type---id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-follow--type---id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-follow--type---id-" data-method="DELETE"
      data-path="api/v1/follow/{type}/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-follow--type---id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-follow--type---id-"
                    onclick="tryItOut('DELETEapi-v1-follow--type---id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-follow--type---id-"
                    onclick="cancelTryOut('DELETEapi-v1-follow--type---id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-follow--type---id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/follow/{type}/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-follow--type---id-"
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
                              name="Accept"                data-endpoint="DELETEapi-v1-follow--type---id-"
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
                              name="type"                data-endpoint="DELETEapi-v1-follow--type---id-"
               value="talent|brand"
               data-component="url">
    <br>
<p>Example: <code>talent|brand</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="DELETEapi-v1-follow--type---id-"
               value="564"
               data-component="url">
    <br>
<p>The ID of the {type}. Example: <code>564</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-talents--username--followers">Who follows this talent (talents and brands).</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-talents--username--followers">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talents/1/followers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talents/1/followers"
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

<span id="example-responses-GETapi-v1-talents--username--followers">
            <blockquote>
            <p>Example response (404):</p>
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
    &quot;message&quot;: &quot;&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talents--username--followers" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talents--username--followers"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talents--username--followers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talents--username--followers" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talents--username--followers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talents--username--followers" data-method="GET"
      data-path="api/v1/talents/{username}/followers"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talents--username--followers', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talents--username--followers"
                    onclick="tryItOut('GETapi-v1-talents--username--followers');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talents--username--followers"
                    onclick="cancelTryOut('GETapi-v1-talents--username--followers');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talents--username--followers"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talents/{username}/followers</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talents--username--followers"
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
                              name="Accept"                data-endpoint="GETapi-v1-talents--username--followers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>username</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="username"                data-endpoint="GETapi-v1-talents--username--followers"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-talents--username--following">Who this talent follows.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-talents--username--following">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/talents/1/following" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/talents/1/following"
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

<span id="example-responses-GETapi-v1-talents--username--following">
            <blockquote>
            <p>Example response (404):</p>
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
    &quot;message&quot;: &quot;&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-talents--username--following" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-talents--username--following"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-talents--username--following"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-talents--username--following" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-talents--username--following">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-talents--username--following" data-method="GET"
      data-path="api/v1/talents/{username}/following"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-talents--username--following', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-talents--username--following"
                    onclick="tryItOut('GETapi-v1-talents--username--following');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-talents--username--following"
                    onclick="cancelTryOut('GETapi-v1-talents--username--following');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-talents--username--following"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/talents/{username}/following</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-talents--username--following"
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
                              name="Accept"                data-endpoint="GETapi-v1-talents--username--following"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>username</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="username"                data-endpoint="GETapi-v1-talents--username--following"
               value="1"
               data-component="url">
    <br>
<p>Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-mentions-search">Search actors by Username / display-name prefix or fragment.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-mentions-search">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/mentions/search" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/mentions/search"
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

<span id="example-responses-GETapi-v1-mentions-search">
            <blockquote>
            <p>Example response (200):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
x-ratelimit-limit: 30
x-ratelimit-remaining: 29
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;success&quot;: true,
    &quot;data&quot;: {
        &quot;talents&quot;: [],
        &quot;brands&quot;: []
    },
    &quot;message&quot;: null,
    &quot;errors&quot;: null,
    &quot;meta&quot;: null
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-mentions-search" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-mentions-search"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-mentions-search"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-mentions-search" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-mentions-search">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-mentions-search" data-method="GET"
      data-path="api/v1/mentions/search"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-mentions-search', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-mentions-search"
                    onclick="tryItOut('GETapi-v1-mentions-search');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-mentions-search"
                    onclick="cancelTryOut('GETapi-v1-mentions-search');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-mentions-search"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/mentions/search</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-mentions-search"
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
                              name="Accept"                data-endpoint="GETapi-v1-mentions-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-links-preview">Resolve (find-or-create) the preview for a pasted URL.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-links-preview">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/links/preview" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"url\": \"http:\\/\\/www.bailey.biz\\/quos-velit-et-fugiat-sunt-nihil-accusantium-harum.html\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/links/preview"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "url": "http:\/\/www.bailey.biz\/quos-velit-et-fugiat-sunt-nihil-accusantium-harum.html"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-links-preview">
</span>
<span id="execution-results-POSTapi-v1-links-preview" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-links-preview"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-links-preview"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-links-preview" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-links-preview">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-links-preview" data-method="POST"
      data-path="api/v1/links/preview"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-links-preview', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-links-preview"
                    onclick="tryItOut('POSTapi-v1-links-preview');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-links-preview"
                    onclick="cancelTryOut('POSTapi-v1-links-preview');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-links-preview"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/links/preview</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-links-preview"
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
                              name="Accept"                data-endpoint="POSTapi-v1-links-preview"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="url"                data-endpoint="POSTapi-v1-links-preview"
               value="http://www.bailey.biz/quos-velit-et-fugiat-sunt-nihil-accusantium-harum.html"
               data-component="body">
    <br>
<p>Must be a valid URL. Must not be greater than 2048 characters. Example: <code>http://www.bailey.biz/quos-velit-et-fugiat-sunt-nihil-accusantium-harum.html</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-posts--post_id--report">Report a post.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-posts--post_id--report">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/posts/1/report" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reason\": \"copyright\",
    \"note\": \"b\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/posts/1/report"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reason": "copyright",
    "note": "b"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-posts--post_id--report">
</span>
<span id="execution-results-POSTapi-v1-posts--post_id--report" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-posts--post_id--report"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-posts--post_id--report"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-posts--post_id--report" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-posts--post_id--report">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-posts--post_id--report" data-method="POST"
      data-path="api/v1/posts/{post_id}/report"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-posts--post_id--report', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-posts--post_id--report"
                    onclick="tryItOut('POSTapi-v1-posts--post_id--report');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-posts--post_id--report"
                    onclick="cancelTryOut('POSTapi-v1-posts--post_id--report');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-posts--post_id--report"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/posts/{post_id}/report</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-posts--post_id--report"
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
                              name="Accept"                data-endpoint="POSTapi-v1-posts--post_id--report"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>post_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="post_id"                data-endpoint="POSTapi-v1-posts--post_id--report"
               value="1"
               data-component="url">
    <br>
<p>The ID of the post. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-posts--post_id--report"
               value="copyright"
               data-component="body">
    <br>
<p>Example: <code>copyright</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>spam</code></li> <li><code>harassment</code></li> <li><code>inappropriate</code></li> <li><code>copyright</code></li> <li><code>misleading</code></li> <li><code>other</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>note</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="note"                data-endpoint="POSTapi-v1-posts--post_id--report"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 1000 characters. Example: <code>b</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-comments--comment_id--report">Report a comment.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-comments--comment_id--report">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/comments/1/report" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"reason\": \"other\",
    \"note\": \"b\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/comments/1/report"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "reason": "other",
    "note": "b"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-comments--comment_id--report">
</span>
<span id="execution-results-POSTapi-v1-comments--comment_id--report" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-comments--comment_id--report"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-comments--comment_id--report"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-comments--comment_id--report" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-comments--comment_id--report">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-comments--comment_id--report" data-method="POST"
      data-path="api/v1/comments/{comment_id}/report"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-comments--comment_id--report', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-comments--comment_id--report"
                    onclick="tryItOut('POSTapi-v1-comments--comment_id--report');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-comments--comment_id--report"
                    onclick="cancelTryOut('POSTapi-v1-comments--comment_id--report');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-comments--comment_id--report"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/comments/{comment_id}/report</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-comments--comment_id--report"
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
                              name="Accept"                data-endpoint="POSTapi-v1-comments--comment_id--report"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>comment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="comment_id"                data-endpoint="POSTapi-v1-comments--comment_id--report"
               value="1"
               data-component="url">
    <br>
<p>The ID of the comment. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-comments--comment_id--report"
               value="other"
               data-component="body">
    <br>
<p>Example: <code>other</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>spam</code></li> <li><code>harassment</code></li> <li><code>inappropriate</code></li> <li><code>copyright</code></li> <li><code>misleading</code></li> <li><code>other</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>note</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="note"                data-endpoint="POSTapi-v1-comments--comment_id--report"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 1000 characters. Example: <code>b</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-notifications">The inbox, newest first.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-notifications">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/notifications" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/notifications"
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
      data-authed="0"
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

                    <h2 id="endpoints-POSTapi-v1-notifications-read">Mark all — or the given IDs — read.</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-notifications-read">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://fama.test/api/v1/notifications/read" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/notifications/read"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};


fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-notifications-read">
</span>
<span id="execution-results-POSTapi-v1-notifications-read" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-notifications-read"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-notifications-read"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-notifications-read" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-notifications-read">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-notifications-read" data-method="POST"
      data-path="api/v1/notifications/read"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-notifications-read', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-notifications-read"
                    onclick="tryItOut('POSTapi-v1-notifications-read');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-notifications-read"
                    onclick="cancelTryOut('POSTapi-v1-notifications-read');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-notifications-read"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/notifications/read</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-notifications-read"
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
                              name="Accept"                data-endpoint="POSTapi-v1-notifications-read"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-notifications-unread-count">The unread badge count.</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-notifications-unread-count">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://fama.test/api/v1/notifications/unread-count" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://fama.test/api/v1/notifications/unread-count"
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
      data-authed="0"
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
