# Changelog

Notable changes to the Fama project. Newest first.

## 2026-10-15 — Site root
- `/` (route `home`) no longer shows the Laravel welcome page: it redirects to `/feed` when the viewer's frozen
  candidate list has posts, otherwise to login (a signed-in viewer goes to `route('dashboard')`).
  `FeedController::home`.
- Profile views chart: the "Show as a table" toggle and its table are removed (each bar keeps its aria-label);
  bars grow in from the baseline, staggered left to right, on load and on every range switch
  (`profileViews.grown`/`delay()`; reduced-motion snaps).
- Drop lists on fama-ui pages draw one chevron (app-bridge.css) instead of the browser arrow: 16px from the end,
  vertically centred, RTL-aware, text never runs under it.
- Project editor shows validation errors ON the fields (title, skills, link client-side; every server 422 field —
  client, role, date, specialities, text, results — mapped by `famaProjectEdit.fieldOf()`), scrolls to the first,
  clears each as it is edited; autosave failures land on the fields too.
- Profile structure save bar hides when there is nothing to save; slides up for unsaved changes and stays 2.5s after
  a save ("Saved just now"), then slides away.
- Country + City are searchable drop lists (`<x-fama.combo>`, `ui/combo.js`): Country shows flags (flag-icons SVGs),
  City lists that country's 15k+ cities largest first (`GET /locations/{code}/cities`, GeoNames data) and accepts a
  typed town. `base_country` must be a listed country (a legacy value may ride along unchanged); aliases (UAE, KSA,
  UK, USA) save as the canonical name; migration `2026_10_15_000100` canonicalised existing rows. Factories now pick
  real city/country pairs.
- Go-Live steps: Edit/Add glide smoothly to the exact field and focus it (City and country → Country); same-page
  anchors scroll smoothly site-wide. When all six steps are done they fold into one large "Checked by Fama" card
  (pending: pulsing award; verified: tick + "Go Live whenever you're ready"), with "Review your steps".
- Featured project = the talent's pick (`talents.featured_project_id`, migration `2026_10_15_000200` backfills today's
  cover). `FeaturedProjectService` holds the rule: a talent with projects always has exactly ONE featured; it changes
  by featuring another (no un-feature), so an only project is featured + locked. Set from the project editor
  ("Featured" switch; `featured` on store/update, home owner only) and the Projects list (star →
  `POST /talent/projects/{project}/feature`); create/delete/leave re-check it. Rows carry `is_featured`,
  `can_feature`, `featured_locked`. Public profile cover reads the pick.
- "Steps done" email: the first time all six profile steps are done while Fama's check is pending,
  `ProfileCompletionService::notifyIfComplete()` stamps `talents.steps_completed_at` (atomic, migration
  `2026_10_15_000300`) and queues `App\Mail\ProfileStepsCompleted` (emails/profile-steps-completed). Called from
  updateCore, updateAvatar, setSpecialities and project create.
- Profile structure hero avatar: a real photo now fills the circle (a higher-specificity background shorthand reset
  its size). fama-ui CSS/JS links are versioned by file mtime (`App\Support\FamaAsset::url`) instead of a fixed
  `?v=1`, so style edits reach browsers without a hard refresh.
- Nav status bar has three states: Private (steps left, ink) → "Your profile is under review" (all six done, Fama
  checking, purple, CTA See status) → "checked — Go Live whenever you're ready" (green, CTA Go Live). Server-rendered
  from `ProfileCompletionService::isComplete()` + `is_verified`; Profile structure keeps it live via a `fama-gate`
  window event.
- Admin console wears the fama-ui language: `resources/css/fama-admin.css` (imported by app.css) re-reads the core
  tokens under `<body data-surface="fama-admin">` — cream ground, white cards, ink, Orchid, Oswald + Sofia Sans, 20px
  cards, pill controls, light only — so every admin page restyles token-only. The admin layout chrome is rebuilt
  (fama-ui logo + ADMIN badge, pill sidebar with an ink active item, "Operations console" header, initials chip).
  The staff login is the fama-ui split screen (auth.css) with an INK showcase (distinct from the purple public one).
- Verify / Suspend open an email composer: suggested copy (`AccountNoticeService::defaults`) + a live preview of the
  REAL email view in an iframe (`POST /admin/moderation/{talents|brands}/{id}/notice/{verify|suspend}`), editable
  subject/heading/message, "Send an email" toggle. The decision carries `notify` (validated BEFORE it runs) and
  queues `App\Mail\AccountStatusNotice` (emails/account-status) after commit; no `notify` = the old silent action.
- Admin moderation: account rows show avatar, @username · email · city · joined, Verified / "Ready for review"
  (steps done, unverified) pills; actions = filled primary (Verify → Publish → Reinstate/Restore) + outlined Suspend +
  "⋯" menu (Unpublish, Delete). Queue tabs sit in one card with waiting-count badges (brand tabs only with the
  feature), a larger search, status filter and result count. Sidebar icons are one consistent 24px stroke set in tiles.
- Admin dashboard rebuilt: "Needs a decision" queue cards; Platform insights over 7d/30d/90d/12m
  (`AdminInsightsService`, `GET /admin/dashboard/insights?range=`) — KPI tiles (new talents, verified, posts, profile
  views) with change vs the previous period + sparklines, a large animated column chart for the picked metric, and
  likes/comments/follows totals; "Road to Live" funnel (signed up → six steps → checked → Live); skills mix;
  governance links + recent activity.
- Dashboard detail boxes follow the picked KPI (`details` in the insights payload): new talents → finished steps /
  went Live / per day; verified → verified-and-Live / awaiting / ready (right now); posts → likes / comments /
  active posters; views → talents viewed / views per talent / follows. The Marketplace row is not rendered when
  brands + contracts are off (it left an empty section gap).
- Verify/Suspend email preview is pinned to the LIGHT email (the email's dark-mode block followed the admin's OS in
  the iframe); a "Dark mail app" toggle previews the dark version (`noticeDoc` rewrites the media query).
- Every dashboard figure has an "i" (`<x-admin.info :text>` / `bind=`) explaining how it is calculated: the four KPI
  tiles, the chart (+ its day/week/month bucketing), each detail box, Road to Live, Skills mix and the queue cards.
  KPI tiles are `div role=button` so the info button can nest; clicking it never switches the metric.
- Moderation View (talents) opens a wide side panel with the PUBLIC profile in an iframe, as a visitor sees it, with
  Verify / Suspend / ⋯ in its header. Staff with moderate-content may open private profiles
  (`TalentProfileController::isStaff`; not counted as views); `?staff_preview=1` shows a "Staff preview" strip and
  drops the visitor nav. The details drawer is teleported to <body> (an ancestor clipped its bottom).
- Bulk actions on the talents / brands / reviews / recognitions queues (`ModerationBulkService`): row checkboxes,
  select-all, a sticky bar with Export selected / Delete (and Approve / Reject on reviews), and Export all/filtered
  (CSV, UTF-8 BOM, formula-injection safe). `POST /admin/moderation/bulk/{queue}/delete` (≤200, each row via its
  audited single path), `GET /admin/moderation/export/{queue}?ids=|q=|status=`.
- Media retention: hard deletes already remove files (medialibrary hook, test-proven for projects, gallery items,
  content items). NEW `media:purge-trashed` (daily 03:50) deletes files of soft-deleted owners past
  `media-library.trash_retention_days` (30, env MEDIA_TRASH_RETENTION_DAYS) and orphaned media whose owner row is
  gone (DB cascades fire no model events). `--dry-run` reports.
- Activity log is a day-grouped timeline: readable sentences ("Admin Demo verified a talent · Nadia Samir"), category
  chips, relative times, expandable old → new diff + reason; period filter (24h/7d/30d); subject names resolved.
- Approve / Reject buttons restyled (filled green / outlined red with icons).
- Posts module (sidebar "Posts", was Feed moderation; opens on All posts): rich post/comment rows (author, excerpt,
  photo thumb, likes/comments, open reports, state), Reported tab (reason, note, reporter, target; hide / remove with a
  reason sent to the author / mark actioned / dismiss), single + bulk hide / unhide / delete
  (`FeedModerationService::deletePost|deleteComment|bulk`, audited; impossible moves skipped, not failed;
  `POST /admin/moderation/feed/bulk`, `delete` added to the per-row action routes). The reason dialog replaces
  window.prompt. Sidebar active state fixed (only the moderation index marks a queue item).
- V1 admin shows nothing brand-related while `features.brands` is off: Follows tooltip, Accounts hint, Settings brand
  switches, the Brand account type, brand activity entries (Activity log + dashboard), and the verify email copy.
- "Remove & tell the author" is an email composer: required reason (in-app notice + audit + highlighted in the
  email), "Send an email" toggle, editable To / Subject / Message, live preview of the REAL `emails/content-removed`
  view (`App\Mail\ContentRemovedNotice`, quotes the removed post/comment; Light / Dark-mail-app toggle).
  `POST /admin/moderation/feed/{posts|comments}/{id}/notice` (defaults + preview, sends nothing); remove accepts
  `notify` (validated BEFORE removing).
- Deleting a post now deletes its comments with it (soft, same moment — PostObserver → `App\Feed\PostPurger`;
  migration `2026_10_15_000400` back-fills comments of posts deleted earlier). After the 30-day restore window
  `media:purge-trashed` PURGES the post for good: likes / saves / mentions / link chips of the post and its comments
  (polymorphic rows no cascade reaches), then a force delete (medialibrary removes files; FKs cascade comments, skill
  tags, seen-marks). Reports stay as moderation history.
- Trash module (sidebar → System → Trash; `TrashService`, `TrashController`): every soft-deleted talent / post /
  comment / review / recognition (brands only with the feature), per-type tabs with counts, search, "gone for good in
  N days", Restore / Delete forever singly and in bulk. Rules: restoring a post brings back the comments deleted with
  it; a comment whose post is trashed can't come back alone; deleting a talent forever removes their posts, comments,
  projects (co-owned ones pass to a co-owner via leave), profile content, files, likes, saves, follows, mentions and
  notifications. Restore = moderate-content; Delete forever = manage-settings. Audited (`trash.restored` /
  `trash.purged`). Routes `/admin/trash[/{type}[/bulk|/{id}/restore|/{id}]]`.
- Moderation Talents / Brands lists (and their CSV export) no longer include deleted accounts — a new **Deleted** tab
  on Moderation lists deleted talents with Restore / Delete forever (single + bulk, through the Trash endpoints).
- Posts module: All posts / Comments exclude deleted items; a **Deleted** tab (Posts | Comments switch) lists them
  with Restore / Delete forever, single + bulk, through the Trash endpoints (same rules: a post brings its comments
  back; a comment of a trashed post waits for its post).
- FIX (seen on fama.so): bulk "Delete forever" on deleted talents failed with "No query results for model Talent"
  when a talent had a co-owned project. The hand-over looked the leaving (deleted) creator up with a normal query and
  could pick a co-owner who was deleted too. Now `Project::activeCoOwnerCredits()` picks only live co-owners (none →
  the project is deleted with the talent), transferTo() tolerates a deleted creator, and Trash bulk actions process
  each item in isolation: failures are skipped + reported (`error` in the response), items already removed earlier in
  the batch count as done.
- `media:purge-trashed` now also deletes FOREVER talents left in the trash past the window (same path as "Delete
  forever", so their project/content/post files go too — those rows are not trashed themselves).
- All reviews no longer lists brand reviews while brands are off (their View 404'd → "Request failed").
- Test flake fixed: GeneratesCoverImages wrote a seed-only temp path that parallel processes raced on.
- Block catalog as cards: summary (active / retired / placements), search + All / Profile-wide / Skill-specific /
  Retired filters, per-block icon, "Available to / Holds" facts, usage chip, an Active switch, and the editor in a modal.

## 2026-10-12 — Guest feed + login landing polish
- Guest feed identical to the prototype: the link-chip glyph sprite no longer renders as a 300×150 box (it used
  Tailwind's `hidden`); `i-msg` is the posts.js chat bubble; every prototype `:first-child` rule is re-targeted to
  the first element after Alpine's `<template>` (app-bridge.css) — no stray dividers above first rows.
- Visitor-first actions: follow/like/save/comment-like check `window.famaSignedIn` BEFORE flipping (`asVisitor`).
- Talent login/registration always land on Profile structure; the visitor's pending action is still replayed.
- Demo talent uses the prototype's sample photos (covers, galleries, digitals, post photos; initials avatar).

## 2026-10-12 — Talent password reset, blank-select 500 fixed + demo data for every screen
- Password reset resolves the broker from the account (`App\Auth\PasswordBrokerResolver`): talents (and brands
  when enabled) can reset — previously only admin `users` could. Links carry `account=`; per-broker token tables
  (migration `2026_10_12_000400`).
- Friendly auth copy (fama-ui): failed login → "That email and password don't match. Try again, or reset your
  password." (lang/en/auth.php, also staff login), softer throttle line, plain-words field messages on login
  (LoginRequest::messages) and register (taken email → "This email already has an account. Log in instead.").
- Profile content: a blank select (equipment category and the 4 other NOT NULL enum selects) no longer 500s —
  dropped before insert/update so the column default / stored value applies.
- `ScreensDemoSeeder` (idempotent) fills every fama-ui screen for the demo talent + adds the Private demo talent
  `talent-new@fama.test`; `php artisan demo:freshen` re-dates an aged demo feed.

## 2026-10-12 — The fama-ui design replaces the talent/feed/auth UI (ADR-AB)
- New UI kit from the client prototype (`fama-ui`): CSS/fonts/images verbatim in `public/fama-ui/`; ONE shell
  `<x-fama.layout>` (+ `components/fama/{nav,hero,footer,icons,confirm-dialog,toast}`, `partials/fama-head`,
  `resources/js/ui/*`, `public/fama-ui/js/hero.js`). Tailwind `app.css` is NOT loaded on these pages.
- Ported pages: login/register/forgot/reset/verify/confirm, error pages (standalone, no Vite), feed, post,
  Activity (Saved · Liked · Following), notifications page (NEW `GET /notifications/all`), Profile structure
  (dashboard folded in — `/talent/dashboard` redirects), Profile content, public profile, Projects, the NEW
  project editor page (`/talent/projects/new`, `/talent/projects/{project}/edit`), public project page,
  Credits (+ recognitions), Recognise.
- Product rules (stakeholder, 2026-10-12): no dashboard; no primary skill (order = tab order, `is_primary`
  derived from position 0 — migration `2026_10_12_000100`); Private profiles show no link; Profile structure
  is draft + save bar (Undo); reviews hidden behind `features.reviews` (default off) — Recognitions replace
  them; **co-ownership** (accepted credit = co-owner; any owner edits/leaves; only a sole owner deletes;
  `POST /talent/projects/{project}/leave` transfers on creator exit); project **results** become ≤4
  `{label,value,compare}` rows (migration `2026_10_12_000300`); social links ≤9.
- Feed: one post card (`feed/partials/post`) everywhere, `#SkillTag` linking (`App\Support\SkillTags`),
  `?filter=unread|mention` on notifications, `?format=html` on the collection endpoint; Daylight components
  deleted, brands rail removed from the feed (V1 brands off). `feed/partials/lang` replaces daylight-head.

## 2026-09-22 — Branded email design
- One Fama email shell for ALL mail (`components/email/{layout,button,callout,panel}` + `App\Mail\EmailTheme`):
  cream ground, official logo (PNG), yellow→orchid band, plum ink, preheader, support footer, dark mode, RTL.
- Framework markdown notifications (password reset) now use it via `resources/views/vendor/mail` + the `fama`
  theme (`config/mail.php` `markdown`); warmer notification copy ("Hi there," / "— The Fama team").
- `review-removed` and `mail:test` emails rebuilt on the shell; admin live preview matches; AR copy added.

## 2026-09-22 — Outgoing mail via ZeptoMail
- `config/mail.php` gains env-driven **sender profiles** (`system`/`support`/`info`/`contact`) resolved by
  `App\Mail\MailSender` (From + Reply-To per Mailable/Notification); `.env.example` documents ZeptoMail SMTP.
- New `php artisan mail:test {to} [--sender=] [--queue]` diagnostic command (`App\Mail\TestMail`).
- `ReviewRemovedNotice` is now queued (`ShouldQueue`, after commit) and sends as `system`.

## 2026-08-22 — Projects get collaborators, ordered media and a hidden date (ADR-AA)
Phase 3 of 3 of the client's structure specs — `project.html`. **The client spec is now fully
implemented.**
- **COLLABORATORS** (new). A credit is `person + skill + role`: skill says what someone can do, role
  says what they did *here*. Crediting someone **on Fama** writes the project onto their portfolio, so
  it **requires their confirmation** — it stays invisible everywhere, public project page included,
  until they accept. Crediting someone **not on Fama** is a plain name: display-only, never leaves the
  project, nothing to confirm. New `/talent/credits` page for confirming, declining and withdrawing;
  notifications land in the existing inbox. Declining and withdrawing are the same transition, so being
  publicly associated with a project stays the collaborator's call at any point.
- **A credited project is filed under the CREDIT's skill**, not the project's — you can be credited as a
  stylist on someone else's photography job. `Talent::portfolioProjects()` merges owned + accepted
  credits, and the profile's project count now matches what the page shows.
- **MEDIA** (new). A multi-file gallery of images and video beside the single cover. The **order is the
  edit** — the first frame is doing the most work, so it is chosen rather than defaulted. Drag to
  reorder in the composer; uploads append so a new file never displaces the lead.
- **DATE is recorded, not displayed.** New `projects.occurred_on` orders work by recency; it appears
  nowhere on the public page, so an older strong project is not discounted for its age. `year` is now a
  **derived projection** of it.
- **CLIENT is hidden when empty** — no "personal project" placeholder, no empty label.
- **Two bugs fixed in the same pass.** (1) Confirming a credit could put work in a tab with no
  `projects` block (photography seeds a gallery, not projects), so the confirmation showed nothing —
  accepting now guarantees a block in the target scope, and a credit naming a skill the collaborator
  does not hold falls back to the universal region instead of vanishing. (2) `year` was still
  mass-assignable, so the demo seeder set it directly while the factory set `occurred_on` and the two
  silently disagreed; `year` left `$fillable`, making divergence impossible.
- Suite **711 green** (2573 assertions), build green, `migrate:fresh --seed` exits 0, both directions of
  the credit loop verified live on seeded data.

## 2026-08-22 — The portfolio becomes six sections (ADR-Z)
Phase 2 of 3 of the client's structure specs — `profile.html` implemented onto the existing block system.
- **NOW strip** (new). `will_travel` + `is_available` toggles on `talents`, with
  `availability_changed_at` shown on the public profile as "as of …". The stamp moves **only when
  availability actually flips**, so it never lies. `will_travel` is a yes/no, never a list of cities.
  Both toggles save on flip in the editor — no Save button, because this is the part of the page that
  goes stale fastest. **Supersedes the availability half of ADR-L** (new columns, simpler semantics —
  not a revert); availability remains INFORMATION, never a gate on enquiries.
- **ABOUT** gains `worldview` (translatable, prompted in the editor so it does not become a second bio)
  and `talent_social_handles` (a reorderable list mirroring `brand_social_handles`, one row per platform).
- **RECOGNITIONS** (new). Peer vouches, **talent → talent** — the sibling of reviews, which are
  **brand → talent** and require a completed booking. Because a recognition needs no booking it works
  from day one, when reviews cannot exist yet. Written from a peer's profile
  (`GET|POST /{slug}/recognise`, talent guard — a guest is sent to the talent login), lands pending, and
  goes through the **same admin moderation queue** as reviews (`/admin/moderation?queue=recognitions`).
  Rendered deliberately unlike a review — no stars, quote-led, relationship prominent — so the two do
  not blur into one wall of praise. A peer vouches once; re-submitting replaces and re-moderates.
- **WILLING TO EXPLORE** (new). `talent_interests` — a direction tied to a skill and optionally a kind
  of work, capped at six. Structured rather than free text so brands can filter it, and the skill is
  deliberately **not** restricted to ones the talent already holds.
- **Profile-level blocks are now data**: new `block_types.is_profile_default` marks blocks seeded ONCE
  into the universal region instead of once per skill tab, so the catalog governs it (ADR-T's principle)
  rather than a hardcoded list. Migration backfills existing profiles; shared seeder trait
  `SeedsProfileDefaultBlocks` keeps the hand-rolled demo seeders in step.
- **Discovery** gains `filter[available]`, `filter[will_travel]` and `filter[open_to]` — the last
  surfaces talents who *want* a kind of work but have not done it yet.
- **Bug fixed in the same pass:** the availability writer compared the new value against an attribute
  that may never have been hydrated (fresh insert on a column default), so `(bool) null === false` made
  "not available" silently save nothing. Now assigns first and lets `isDirty()` decide; `TalentFactory`
  sets both toggles explicitly.
- Suite **695 green** (2522 assertions), build green, `migrate:fresh --seed` exits 0, all six sections
  verified rendering live. Two pre-existing tests encoded the old "every seeded block belongs to a skill"
  invariant and were updated to the new one, deriving counts from the catalog rather than hardcoding.

## 2026-08-22 — Skills nest into specialities; the catalog grows to nine (ADR-Y)
Implements the client's structure specs (`profile.html` / `project.html` / `spcialist.html`), phase 1 of 3:
the taxonomy everything else hangs off.
- **Nine skills.** Added **Direction**, **Hair & makeup** and **Retouching & post** (migration
  `2026_08_22_000200` + `TalentTypeSeeder`, idempotent). Applied the spec's orthography — "Modeling" →
  **Modelling**, title-case → sentence-case. **Slugs are unchanged** so no deep link breaks.
- **A second level: specialities.** New `specialities` table owned by one skill, with `speciality_talent`
  and `project_speciality` pivots (migration `2026_08_22_000100`). 45 seeded entries across the nine
  skills, four-to-seven each (`SpecialitySeeder`). The list is **controlled** — talents pick from it and
  can never type into it — because an invented entry would never match on the project side.
- **Regional entries are flagged.** `is_regional` marks **Modest fashion** and **Arabic lettering**: real
  commercial categories here, not filters applied to a broader one.
- **Two rules, one home each.** A talent's picks must belong to a skill they hold
  (`SkillsService::setSpecialities`, 422); a project's must hang off one of its tagged skills
  (`TalentProjectService::assertSkillSpecialities`, 422 — validated against the skill set the project will
  *end up* with, so re-tagging skills and specialities together stays consistent). Re-tagging a project
  without restating its picks prunes the ones that lost their skill.
- **Surfaces.** Profile editor: a speciality pill picker inside each skill section (optimistic, per-skill,
  `PUT /talent/profile/skills/{type}/specialities`). Public profile: the skill tab leads with its
  specialities, linking into discovery where that flag is on. Projects composer: speciality groups revealed
  *by* the skills tagged above, un-tagging a skill takes its picks with it. Admin `/admin/skills`: add,
  retire and reinstate entries — the slug is locked once created, and retiring is grandfathering, never a
  delete.
- **Discovery** gains `filter[speciality]` (slug-matched; combine with `filter[type]` to narrow, since a
  slug like `fashion` deliberately spans three skills).
- Icons for the three new skills; demo + showcase seeders now claim specialities so the layer has real data.
- Suite **677 green** (2464 assertions), build green, `migrate:fresh --seed` exits 0. Three pre-existing
  test fragilities the new catalog exposed were fixed at the root: two assertions compared a sorted actual
  against an unsorted expectation, and one compared a raw skill name against escaped HTML.

## 2026-08-18 — Projects get their own page, and at least one skill (ADR-X)
- **New `/talent/projects` page** — the talent's portfolio of work, on its own premium surface instead of
  a row in the generic content-table editor: a sticky skill filter bar with live counts (All work · one
  pill per skill, primary first · "No skill yet" only when leftovers exist), a drag-to-reorder card grid
  with covers, credits and skill tags, skeleton loaders, distinct empty states, and a portalled composer
  modal (cover drop/replace/remove, EN + AR title, client, role, year, link, rich Summary + full story,
  up to 6 result metrics). Every interaction is Ajax on the shared envelope; nothing reloads.
- **A project must carry at least one skill.** New `project_talent_type` pivot (migration
  `2026_08_18_000100`, backfilled from the existing scope column); `projects.talent_type_id` stays the
  **home tab** and equals tag 0, so every ADR-Q reader is untouched. The rule lives in
  `TalentProjectService::assertOwnedSkills()` (domain 422) *and* `StoreProjectRequest` (field error). The
  composer's Save is disabled until a title and a skill exist, and the footer names what is missing.
- **A project tagged with two skills now appears in both tabs** on the public profile. The tab test
  (`Project::belongsToScope()` / `scopeInSkillScope()`) matches the home column **OR** a tag, so the pivot
  only ever *adds* tabs — a row written straight to the column (factory, a seeder muted by
  `WithoutModelEvents`, un-migrated data) can never fall off the profile. `ProjectFactory::configure()`
  mirrors a factory-set home skill into the pivot so factories and seeders match what the editor writes.
- **Removing a skill re-homes its projects** instead of blanket un-scoping them:
  `SkillsService::untagProjects()` drops the tag, moves the project to its next remaining tag, and only
  un-scopes the ones that ran out (ADR-Q's "content is preserved" still holds).
- **One write path.** `projects` was removed from `ContentRegistry`, so the profile-content page renders a
  signpost panel (kind `link`) pointing at the Projects page rather than a second, weaker editor;
  `/talent/content/projects` redirects there, and the profile editor's "Edit content" link on a projects
  block goes straight to the page. The dead `skill_scoped` machinery in `BlockContentController` and the
  generic content-editor partial went with it.
- **V1 trims:** the composer is **English-only** — the Arabic title input is commented out with the same
  note the profile editor uses for its AR headline/bio (V1 has no AR/EN toggle, ADR-W). New
  `TalentProjectService::dropEmptyForeignTranslations()` strips blank non-English locales before storage, so
  a project never stores `ar => ''` (which spatie would return instead of falling back — a blank title on
  `/ar`); an Arabic value stored earlier survives an edit, while blanking the visible English input still
  clears it. The **cover is optional** — it was never required server-side, and the composer now says so
  (an `Optional` tag + a line explaining the placeholder fallback).
- Also: `Projects` joins `Navigation::talentItems()` (so it appears in the avatar dropdown *and* the
  dashboard's "Where to next" grid); new `<x-…>`-free shared partial `talent/partials/rich-input` extracts
  the lightweight WYSIWYG so the composer and the generic editor share one implementation; AR strings added
  for the whole page. Suite **659 green**, build green, `migrate:fresh --seed` exits 0.

## 2026-08-13 — Block names are admin-governed
- **Talents can no longer rename profile blocks.** The inline title input in the editor's block rows is
  now a read-only label (`title` fallback to the catalog `block_types.name`); `saveTitle` was removed
  from `dashboard.js`, and `FillBlockRequest` dropped its `title` rules so a submitted title never
  reaches the service through `validated()` — `PATCH /talent/profile/blocks/{block}` still accepts
  `content`/`settings`/`layout`. Block naming lives with the admin block catalog (`/admin/blocks`).

## 2026-08-12 — Logo mark in the topbar, dashboard quick links, nav renames
- **The topbar now carries the Fama logo** (the branding SVG) instead of the text wordmark, via a new
  `<x-fama-logo>` component — inlined (no request, sizeable with a height class) with the source file's
  `<style>`/`.st0`/`.st1` classes converted to `fill` attributes, because those generic class names
  would otherwise leak onto anything else on the page. Used by `<x-talent-topbar>` and the guest
  `site-topbar` in `public-layout`. Auth split-screens, the admin/brand layouts, the footer, and the
  e-mail header still use the text wordmark.
- **The talent dashboard links to every page in the nav.** The stale "Manage your profile" cards are
  replaced by a "Where to next" grid built from `Navigation::talentItems()` (minus Dashboard itself)
  plus a Preview-profile card — so a new nav item appears on the dashboard automatically, feature flags
  included. Nav items gained a one-line `description` for those cards.
- **Renames:** the talent nav reads **Profile structure** (was "Edit profile") and **Profile content**
  (was "Content"); the profile editor page title follows. **Reviews left the nav** — they render as a
  block panel inside Profile content (`/talent/reviews` still exists and still serves the list's data
  endpoint, which that panel fetches).

## 2026-08-12 — Profile content is section-first, and Reviews live there too
- **The page is now SECTION-based.** `GET /talent/content/{section?}` (`universal` or a skill slug)
  renders **one section with every one of its content blocks stacked** down the page, in block order.
  The per-type pills are gone; the switcher is a row of **premium section buttons** (skill icon,
  name, Primary badge, block count; active = filled accent). A legacy `/talent/content/{type}` URL
  still resolves — to the section that shows that block — so old links keep working.
- **Reviews joined the page.** The Reviews block renders the talent's reviews (read-only list + star
  filter) inline in its section. The list moved to `talent/partials/reviews-list` and the standalone
  `/talent/reviews` page now includes the same partial — one implementation, two entry points.
- The registry of editable content types moved out of the controller into `App\Support\ContentRegistry`
  (both the content page and the profile editor read it). The editor's **"Edit content"** link is now
  gated on that registry (no more dead link on `comp_card`) and points at the block's own **section +
  anchor** (`/talent/content/photography#block-showreel`), localized via `window.fama.localizeUrl`.
- Blade: `talent/partials/content-editor` (one block's editor) + `talent/partials/reviews-list`;
  `talent/content.blade.php` is now the switcher + the stack.

## 2026-08-12 — The `hero` block type is retired
- The `hero` block did nothing: ADR-O replaced the cover/hero region with the avatar-led header the
  profile renders itself, and every public surface filtered the block out — it survived only as an
  editable row (title + Visible toggle) in the talent editor.
- Migration `2026_08_12_000100_retire_hero_block_type` deletes every `hero` placement, strips the key
  from each skill's `default_blocks`, and sets the catalog entry `is_active = false`. `TalentTypeSeeder`
  no longer lists it; `BlockTypeSeeder` seeds it inactive (new `active` key, defaults true). The
  `block_types` row stays — keys are frozen and deactivated types are grandfathered by design — and the
  defensive `key !== 'hero'` render filters stay for un-migrated data.
- Tests that used `hero` as a stand-in for "an active universal block" now use `brand_collabs`, and the
  picker/demo-seeder tests assert `hero` is **absent**.

## 2026-08-12 — "Profile content" nav label + a profile-shaped content switcher
- The talent nav item `talent.content` is relabelled **Profile content** (`App\Support\Navigation`,
  so the topbar avatar dropdown and every other consumer follow) — "Content" alone read as a generic
  bucket.
- The content page's flat pill bar (Gallery · Looks · Digitals · … · Brand collabs) is replaced by a
  switcher shaped like the profile itself (ADR-Q/ADR-R): the **Profile-wide** section plus **one
  section per skill** (icon + name + Primary badge), each listing **its own table-backed blocks** in
  block order, with the universal slot placed by `meta.universal_position` — the same order the
  editor's pill bar and the public profile use. Hidden blocks stay listed (dimmed, "Hidden" tag) so
  their content remains editable; a skill with no content blocks says so. A content type opened by
  URL but placed on no block gets a trailing **Not on your profile** section, so the page is never
  orphaned from the nav. Built in `BlockContentController::sections()` from the talent's own
  `profile_blocks` (registry-backed keys only, so every pill leads somewhere).

## 2026-08-11 — One drag layer for the unified blocks card (cross-row drops + feedback)
- **Every row in the Blocks card now shares ONE drag system** (`onRowDragStart/Over/Drop/End` in
  `profileEditor`), so dragging works BETWEEN row kinds: block↔block in the same section reorders;
  **dragging a block onto a skill row (or into its expanded list, or onto its pill) MOVES the block
  into that section** — eligibility-gated with a red ring flash when denied; skill↔skill (rows or
  pills) reorders sections; a skill dropped on a universal block row crosses the universal group
  (same as dropping on the universal pill). `dataTransfer` is now fed at dragstart (Firefox refuses
  to start a drag without it).
- **Drag feedback**: the dragged row dims and shrinks slightly, the hovered drop target shows an
  accent ring, denied drops flash a danger ring, and rows transition (150ms) — no more silent,
  cueless drops.

## 2026-08-11 — Skills as single rows inside the unified blocks card
- **The editor's Blocks area is now ONE card** ("Universal · profile-level"): the universal block
  rows plus **each skill rendered as a single collapsed row** ("Skill section · Modeling · Primary ·
  N blocks") with a **Manage blocks** expander revealing its inner block rows inline. Rows sit in the
  true section order (the universal group inserted at `universal_position` among the skill rows), and
  the card's kicker says Above/Below the tabs. Skill rows drag with the same section-reorder
  semantics as the pills; the pill bar stays as the ordering/star/remove/add manager (skill pill
  click now toggles that row's expansion; the "All" pill is gone — everything is always in view).
  `block-scope-card` partial replaced by `talent/partials/block-row.blade.php`; profileEditor gained
  `unifiedRows`/`expanded`/`toggleExpanded` (activeSection is gone).
- Suite 644 green; build green; verified live.

## 2026-08-11 — Smaller feed posts, read-only reviews with rating filter, All-sections view
- **Feed posts back to the standard editorial width**: the V1 no-brand-rail layout no longer widens
  the spine to 1000px — `--dl-feed-col` stays 720px at every breakpoint (grid templates updated to
  match).
- **Talent Reviews page is read-only with a rating filter**: Approve/Reject removed from the talent
  surface (endpoints stay; pending reviews are moderated by the admin queue), the "Moderation queue"
  framing replaced by a "What clients say" list, and new filter chips (All · 5★…1★) drive a `rating`
  param on `GET /talent/reviews/data` (crudList gained a generic `setQuery()` helper).
- **Editor sections gained an "All" pill (the new default)**: shows every section's card stacked —
  universal + Modeling + Photography managed in one view — while picking a pill still focuses one
  section (deselect returns to All; removing the focused skill falls back to All).
- Suite 644 green; build green; verified live.

## 2026-08-11 — Orderable sections (universal among skills) + public header rework
- **The editor's Blocks area is ONE orderable pill list** — the Universal/profile-level section is
  now a pill like the skills (its card opens on selection; it opens by default) and the whole row
  drag-reorders. The universal pill's slot persists to `talents.meta['universal_position']` via the
  new `PATCH /talent/profile/sections/universal-position` (`UpdateSectionOrderRequest`,
  `TalentProfileService::updateUniversalPosition`): **0 = universal renders above the skill tabs on
  the public profile (the ADR-R default); any later slot renders it BELOW the tab region** (a
  deliberate, small extension of ADR-R — in-between slots all mean "below"). Skill relative order
  still persists through the existing reorder endpoint; the universal pill has no star/remove.
- **Public profile header reworked**: spans the full content width (`max-w-3xl` → `max-w-6xl`) and
  lays out as a single row on desktop — avatar · name/@handle/skill/location · stats · CTAs — with
  the bio on its own row below (rate chip + external link under it). New **Share profile** button
  beside Leave a review copies the canonical profile link ("Link copied!" feedback); it replaces the
  old "…" menu.
- Suite 642 green; build green; verified live (order round-trip above ⇄ below).

## 2026-08-11 — Official FAMA branding adopted (PDF): cream ground, Orchid Purple, NO blue
- **Palette re-based on the FAMA — BRANDING PHASE 3 PDF** (supersedes the earlier typed values):
  primary **Orchid Purple #AD50D3**, secondaries **#FFEB73** (yellow → gold family), **#6CEAC2**
  (mint → success family), **#F7E0FF** (lavender → accent wash). The cool blue-grey neutrals became
  the PDF's **warm cream** (`--bg #FAF6E0`, `--surface #FDFBEF`, warm lines/plum ink); dark theme is
  a plum night. Scrim/focus re-tinted; the review-removed email inlines the new hexes.
- **Daylight (feed) de-blued**: `--dl-base` is the brand cream, the indigo `--dl-daylight #3D4FD6` is
  now deep orchid `#8B2FA8`, the veil is lavender, and the signature shadows are **orchid-tinted,
  not blue** (dark: plum equivalents). `CheckFeedContrast`'s formula table updated in lockstep and
  the `@supports` fallback hexes regenerated from the command's output — **all gates pass** (ink
  15:1, ink-faint ≥4.9, daylight ≥6.3).
- **Demo imagery de-blued**: `GeneratesCoverImages` now draws hues from the brand arc only
  (yellows/mints/orchids) and keeps accent circles in the ground's hue family (mint-over-purple
  alpha blends were mixing to cyan). Dev DB re-seeded; 60 live media × 3 sample points → 0 blue.
- Suite 637 green; build green; `migrate:fresh --seed` exits 0.

## 2026-08-11 — Fama brand palette (purple / yellow / mint / lavender)
- **Token-only restyle to the brand colors**: `--accent` is now the brand purple **#BA49DA**
  (`--accent-weak` = brand lavender **#F7E0FF**, `--accent-ink` = darkened #8B2FA8 at 6.5:1,
  `--focus` re-tinted); brand yellow **#FFEA5A** became the `--gold-weak` wash (star/Primary badge;
  `--gold` darkened to #7E6600 for 4.5:1 text on the wash); brand mint **#00EDBD** tints the success
  family (`--success` #0A7A61, `--success-weak` #C9F7EA). Dark-theme equivalents re-derived
  (#CE7BE5 accent etc., all ≥6:1 on the dark surface). Neutrals, warn, danger, and `--primary`
  (graphite buttons) unchanged.
- The Daylight feed system is untouched — it scopes its own `--accent` (`--dl-daylight`), and
  `php artisan feed:check-contrast` still passes. The one inline-styled surface (the review-removed
  email) was updated to the purple values. Contrast on every changed pair verified programmatically;
  white-on-accent buttons sit at 4.16:1 (above the 3:1 UI floor, slightly under 4.5 small-text —
  accepted to keep the exact brand primary).
- Suite 637 green; build green.

## 2026-08-11 — Editor V1 trims: fixed block set + pricing hidden
- **"+ Add skill" is now a solid accent pill** (was a subtle dashed outline).
- **Blocks are a fixed seeded set in V1**: the "Add a block…" picker row and the per-block ✕ remove
  button are gone from the editor's scope cards — blocks can be hidden (Visible toggle), reordered,
  and moved between scopes, but not added or removed. The block endpoints and services are untouched
  (UI-level removal only).
- **Pricing rate hidden in V1** behind a new `pricing` feature flag (`FEATURE_PRICING`, default ON,
  false in the V1 `.env`; pinned ON in phpunit.xml): the editor section disappears
  (`@feature('pricing')`), `PATCH /talent/profile/pricing` 404s (`feature:pricing`), and the public
  profile's rate chip is suppressed. Column data preserved. `FeatureFlagsTest` covers it.
- Suite 637 green; build green; verified live.

## 2026-08-11 — Topbar links left, feed chrome removed, editor consolidation
- **Topbar**: the Feed + Preview-profile links moved to the START side (next to the wordmark),
  replacing the page title in the bar (the `<title>` tag keeps the page name); avatar dropdown stays
  in the corner.
- **Feed chrome header removed** (title, "YOUR WORK · YOUR PEOPLE" eyebrow, inline filter chips);
  the Filters FAB — which opens the same chips in a popover — is now always visible instead of
  appearing only past the header. `feedStream`'s chrome refs were already null-safe.
- **Profile editor is now "Edit profile"** (page title + nav label): the standalone Publishing
  section merged into the Core-details card as its first row (status dot · Live/Not live · verified
  chip · Publish/Unpublish, verification banner above the card).
- **Skill pills = the skills manager**: the standalone Skills section is gone; the pills in the
  Blocks area now drag-to-reorder (same reorder endpoint), carry a ★ star button (set primary) and a
  ✕ remove button (confirmed — deletes the tab's blocks, keeps content), with a dashed "+ Add skill"
  button beside them revealing the picker inline; adding auto-opens the new skill's section.
- Suite 636 green; build green; verified live.

## 2026-08-11 — Slim topbar (Feed + Preview + corner menu) and Discover hidden in V1
- **The talent topbar is now a single row**: two primary links — **Feed** and **Preview profile**
  (opens the public profile in a new tab) — plus an avatar button in the corner that opens a
  dropdown with everything else (Dashboard, Activity, Profile, Content, Reviews, flagged
  marketplace items, and Log out). Alpine-local open state, click-outside/Escape close,
  `aria-haspopup`/`aria-expanded`/`role="menu"`. The bar is h-16 = the default `--topbar-h`, so
  public-layout no longer overrides the token for signed-in talents.
- **Discover is hidden in V1** via a new `discover` feature flag (`FEATURE_DISCOVER`, default ON;
  false in the V1 `.env`): `/discover` + `/discover/search` 404 behind `feature:discover`, and the
  nav item drops out of both the canonical talent set and the guest topbar. `phpunit.xml` pins the
  flag ON so the suite still exercises discovery; `FeatureFlagsTest` covers both states.
- Suite 636 green; build green; verified live.

## 2026-08-11 — One talent topbar everywhere + V1 editor trims
- **`<x-talent-topbar>` is now THE talent chrome**: the dashboard layout renders it, and public-layout
  renders the SAME component for signed-in talents instead of the fixed `site-topbar` (guests/brands/
  admins keep the public bar). On public pages the body padding is dropped and `--topbar-h` is
  overridden to `6.5rem` so the feed's sticky chrome/rails/toasts keep clearing the taller sticky bar.
- **AR/EN locale switcher removed from the talent topbar** (V1 ships without the language toggle;
  `/ar` routes still work, the control is just not offered).
- **Booking method/value removed from the profile editor** (V1): fields gone from the form, payload,
  `UpdateCoreProfileRequest` rules, and the `updateCore` whitelist. Columns and existing data are
  untouched; the public profile still renders an existing external booking link.
- **Profile editor restructure**: the Skills manager moved BELOW the Blocks section; in Blocks, the
  universal section always shows but each skill's section renders only after selecting that skill in
  a new pill bar (hint until then; adding a skill auto-selects its section). The scope card lives in
  `talent/partials/block-scope-card.blade.php`, reused by both spots.

## 2026-08-11 — Standardized talent navigation across top bars
- **One canonical talent link set** — `App\Support\Navigation::talentItems()` (Dashboard · Feed ·
  Discover · Activity · [Brands · Opportunities · Contracts when flagged on] · Profile · Content ·
  Reviews) — consumed by BOTH the talent dashboard topbar (pills + icons) and the public topbar
  (labels + gestural underline) when a talent is signed in, so navigation is identical everywhere.
  This adds the missing **Discover** to the dashboard topbar and the missing **Dashboard / Activity /
  Profile / Content / Reviews** to the public topbar for talents. Guests keep Feed · Discover ·
  Log in; brand/admin viewers keep the Profile→dashboard link. Active detection is `routeIs()` and
  param-carrying items (Content) resolve correctly.

## 2026-08-11 — Feed voice pass + talent dashboard topbar
- **Feed copy no longer speaks in "light" metaphors and now addresses the talent directly** (EN + AR):
  the header eyebrow "WORK · LIGHT · PEOPLE" → "YOUR WORK · YOUR PEOPLE", the empty-feed line
  "THE LIGHT IS BEING SET" → "BE THE FIRST — SHARE YOUR WORK", the end-of-feed line "YOU HAVE SEEN
  TODAY'S LIGHT" → "YOU'RE ALL CAUGHT UP", and the composer placeholder "What did the light do
  today?…" → "What are you working on?…". Mechanics keep their names (`.dl-light-veil`, light-bar,
  `in-the-light`) — copy only.
- **The talent dashboard sidebar is now a topbar** (`components/talent-layout.blade.php`): sticky
  header with wordmark + page title + actions on the first row and a horizontally-scrollable pill nav
  below (icons + labels, active = filled accent like the profile tab bar, `aria-current="page"`).
  The off-canvas drawer, backdrop, and hamburger are gone; group headings flattened (feature-gated
  items unchanged). Main content is full-width under the bar (same `max-w-7xl` container).
- Suite 634 green; build green; verified live (EN + AR).

## 2026-08-10 — Talent dashboard home: visibility switch + views analytics KPI
- **Public/private switch on the dashboard home.** The Profile-status card is now a Profile-visibility
  card with an inline `role="switch"` toggle reusing `PATCH /talent/profile/publish` (same domain
  guards as the editor — an unverified profile gets the friendly 422 surfaced inline and the switch
  stays locked with an *Awaiting verification* hint).
- **Views KPI compares week over week and expands into a chart.** New `talent_profile_views` daily
  buckets (unique `(talent_id, viewed_on)`), written by the new `RecordDailyProfileView` listener on
  `TalentProfileViewed` via an atomic `ON DUPLICATE KEY UPDATE` (lifetime `view_count` unchanged).
  `ProfileViewStatsService` supplies the rolling this-week/last-week delta on the tile and a
  zero-filled, server-localized series for `GET /talent/dashboard/views?range=7d|30d|90d|all`
  ('all' reaches back to the profile's `created_at`; bucket day/week/month picked from the span).
  Clicking the KPI opens an accent column chart (hairline gridlines, 4px-rounded bars, per-bar
  hover/focus tooltip, sr-only table, range pills; refetch dims the previous render in place) —
  `dashboardHome` in `dashboard.js`.
- **KPI row trimmed to two cards.** The Pending-reviews and Blocks·skills cards are gone (reviews stay
  reachable from the sidebar + manage grid); the grid is `sm:grid-cols-2`.
- Suite 634 green; vite build green. WATCH OUT: `foreignId('talent_id')->constrained()` guessed a
  singular `talent` table here — always pass `constrained('talents')` explicitly.

## 2026-08-06 — Premium "TALENT TO FOLLOW" rail (compact editorial rows) + test isolation
- **Redesigned the feed's talent-suggestion rail.** Each talent was a full ~400px portrait card (only ~1.5
  fit); it is now a **compact editorial row** — a small rounded portrait thumb + name (Fraunces) + mono
  handle + one skill/followers line + a ghost **Follow** pill, with hover tint, hairline dividers, and a
  hover-revealed dismiss. 8–9 curated talents now show at once (`suggestion-cards.blade.php` talent branch +
  new `.suggest-row/.suggest-thumb/.suggest-*` CSS in `daylight.css`; the rail skeletons shrank to match).
  Brand cards keep the square frame.
- **Rail enhancement pass.** Names/handles were badly truncated at 260px; the talent rail now widens to
  **340px** (no-brand-rail layout), with bigger portrait thumbnails (58×72, subtle ring), larger display
  names (17px) + handle (12px), more generous row spacing, a hover lift, and the dismiss ✕ moved to an
  absolute top corner so it no longer steals body width. Names are now readable, and the wider rail also
  trims the outer feed margins.
- **Wider feed column (fills the reclaimed V1 space).** With the brand rail gone, the feed sat centred with
  large empty margins on wide monitors. The feed column width is now a CSS variable `--dl-feed-col` (via a
  shared `.feed-col` class on composer/header/posts/frame), default `720px` but **`1000px` in the
  `no-brand-rail` (V1) layout** where the spine widens to match — big, premium posts that fill much more of
  the screen, cutting the side margins by roughly a third. The 3-column (brands-on) layout is untouched.
  The **composer trigger** now tracks `--dl-feed-col` too (it was hard-pinned at 640px) and its middle
  became a full-width faux-input field, so it fills the wider column instead of leaving a dead centre gap;
  it still condenses to 260px on scroll.
- **Test isolation for the release flags.** `phpunit.xml` now forces `FEATURE_BRANDS/CONTRACTS/MESSAGING=true`
  so the suite always runs the full app even while a developer previews V1 locally (`.env FEATURE_*=false`);
  `FeatureFlagsTest` still flips them off per-test via `config()`. Suite 624 green.

## 2026-08-07 — Consolidated talent "Activity" page + sidebar redesign
- **Saved · Liked · Following → one page.** The three separate sidebar entries collapse into a single
  **Activity** page (`/talent/feed/activity`) with a premium segmented tab bar. Top tab = `?view=`; the
  Following tab keeps its own FOLLOWING/FOLLOWERS sub-toggle (`?tab=`, no collision). `FeedActivityController`
  chooses which existing partial to render (`feed-collection` / `feed-follows`) — the Ajax data endpoints are
  unchanged. The old page routes (`feed.saved/liked/follows`) now **redirect** into the matching tab (data
  routes kept). Deep links + the feed's bookmark/like flows still resolve.
- **Talent sidebar redesign.** Flat list → **grouped sections** (Dashboard/Feed · Marketplace · Your studio ·
  Activity) matching the admin pattern, with an inline-start **accent bar** + `aria-current` on the active
  item, a subtle icon hover, and roomier spacing. Empty groups are dropped, so V1 (no Marketplace) stays
  clean.
- **Uniform follow cards.** The Following grid was jagged (brands `aspect-square`, talents `aspect-[4/5]`,
  skill chips wrapping to a second line). All follow-card images now share the 4/5 ratio, the grid
  `align-items: stretch`, each card is a flex column with the UNFOLLOW/FOLLOW-BACK pinned to the bottom, and
  skill chips stay on one line — so every card is the same size. Suite 625 green.

## 2026-08-07 — V1: hide dark mode (light-only release)
- A fourth release flag `features.dark_mode` (`FEATURE_DARK_MODE`, default true). When off, the
  `<x-theme-toggle>` component renders nothing (it is the single toggle used by all layouts + the feed) and
  `partials/design-head` forces `data-theme="light"`, ignoring any stored preference — so V1 ships light-only.
  Added to `.env.example` + `phpunit.xml` (true, so dev/tests keep dark); `FeatureFlagsTest` covers the off
  path. Suite 625 green.

## 2026-08-06 — V1 release scoping: talent + feed + public profile (feature flags, ADR-W)
- **Config-driven feature flags** (`config/features.php`: `brands`, `contracts`, `messaging`) gate whole
  product areas so the V1 release ships only the talent side, the public feed, and the public talent
  profile — brand, contracts, and messaging stay in the codebase, dormant. Flags **default ON** (dev +
  the 624-test suite run the full app); the production `.env` sets `FEATURE_BRANDS/CONTRACTS/MESSAGING=false`.
- **Enforcement:** `App\Support\Features` helper + a `feature:<name>` route middleware (`EnsureFeature`,
  disabled area → 404) + a `@feature('brands')` Blade directive. Routes stay registered and the check is
  runtime, so per-test `config([...])` overrides work.
- **Gated:** the brand dashboard group + public brand/opportunities/messaging routes (`web.php`), talent
  contracts + applications + the enquire funnel (`talent.php`), admin contract/flow/template consoles +
  brand/project moderation queues (`admin.php`), the feed brand suggestion endpoint (`feed.php`).
  Registration/login become talent-only; the public + talent + admin nav drop brand/contract items; the
  feed hides the BRANDS rail + brand follow chips; the public profile hides the Message CTA. `config/feed.php`
  `allow_following_brands` now tracks `FEATURE_BRANDS`, so talent→brand follows are refused off.
- **Kept in V1:** Reviews (standalone) and the talent "Brands I've worked with" portfolio block. Admin stays
  fully functional (mandatory talent verification + feed moderation). New `tests/Feature/FeatureFlagsTest`
  proves the OFF gating; suite 624 green.

## 2026-08-04 — Timeline phase cards with details + template "applies to" removed
- **Phase events now show their details.** Step-completion system events carry a `details` list (built in
  `ContractProgression::stepEventMeta` from the step payload — scope/dates/budget for the brief, amount +
  pricing unit + note for the quote, signatory for a signature, deposit for a payment, dates for a
  schedule, file count for a delivery). Exposed via `ContractMessageResource.details`.
- **Distinct phase UI in the timeline.** System events render as a bordered **phase card** (✦ badge +
  "PHASE" label + summary + a details list), clearly not a chat bubble — talent + brand rooms.
- **Contract templates: "Applies to" removed** from the builder + create form + list (moving templates
  toward version-based, un-scoped selection). Suite 616 green.

## 2026-08-04 — Contract document: PDF download + inbox "See contract"
- **Download as PDF.** The contract-document modal (talent + brand) now has a **Download PDF** button;
  new `GET /contracts/{id}/document.pdf` renders a print-styled, self-contained (inline-CSS, table-based)
  PDF via `barryvdh/laravel-dompdf` — a real contract document, not a screenshot. A `View contract` button
  also sits below the Phases stepper.
- **Inbox: completed = green + "See contract".** In the contracts inbox, a `completed` status now reads in
  **green**, and completed rows get a **See contract** button that opens the document in a modal (fetched
  via `GET /contracts/{id}/document` → `{html, pdf_url}`) with its own Download PDF.
- New dependency: `barryvdh/laravel-dompdf`. Suite 615 green.

## 2026-08-04 — Contract document in the room: live, phase-click modal, priced, prior-step data
- **Flow template picker fix.** The flow workspace's "Contract document template" `<select>` now
  preselects the linked template — options are server-rendered so `x-model` can sync (an `x-for` option
  list is built after `x-model` inits and never preselected; the value was saving all along).
- **The contract document is now rendered live in the contract room.** The `/contracts/{id}/thread`
  payload carries a freshly-rendered `document_html` (talent + brand), so it always reflects the latest
  completed steps. Clicking any phase in the **Phases** stepper opens the document in a **modal**; a
  `contract`-type step shows the document inline above the sign field.
- **The document builds itself from prior steps.** `FormStepHandler` gained a `writes` map (field →
  contract column): the **brief** step writes `scope → brief` and `start_date`/`end_date`, the **quote**
  step writes the amount and a new **pricing unit**. `validated()` strips unruled fields, so every
  declared field now gets a passthrough rule.
- **Priced by hour / day / item.** New `contracts.pricing_unit` (migration `2026_08_04_000100`); the quote
  step captures it (a select in the room), and the document's fee reads e.g. **"4,500 EGP / day"**.
- Demo seeders updated (brief dates + priced quotes). Suite 614 green.

## 2026-08-03 — Admin contract-template builder with a live document preview (ADR-V)
- **New "contract schema" authoring surface.** `/admin/contract-templates` (gated `manage-flows`) lets an
  admin author a reusable **contract template** — the standard-terms parameters (deposit %, deposit
  holder, cancellation forfeit %, revision rounds) plus a **fully custom, reorderable clause list** with
  numeric tokens (`{deposit_pct}`, `{rest_pct}`, `{deposit_holder}`, `{forfeit_pct}`, `{revisions}`,
  `{brand}`, `{talent}`, …). The builder is two-column: author on the left, a **live contract-document
  preview** on the right that renders the *same* canonical partial the contract room uses (debounced
  `POST /admin/contract-templates/preview` → `{html}`), so "how it will look" is WYSIWYG-accurate.
- **Applied to contracts via flows.** Each contract flow can pick a template
  (`contract_flows.contract_template_id`, nullable). At contract creation the resolved terms are **frozen
  onto `contracts.terms_snapshot`** (new JSON column), so later template edits never rewrite a live
  contract (same immutability rule as flow-step snapshots). The talent + brand contract rooms now render a
  collapsible **Contract** document from that snapshot + the engagement's live values.
- **Rendering is one source of truth.** `App\Contracting\ContractDocument` normalizes a template (preview)
  or a contract (room) into the `$doc` structure consumed by `contracts/partials/document.blade.php`.
- **Model/engine.** `ContractTemplate` (`contract_templates` table, migration `2026_08_03_000100`) with a
  one-default-per-`applies_to` rule; default templates are protected from deactivation/deletion.
  `ContractTemplateService` (transactional, audited on the `contract_flow` log). Registered in the
  enforced morph map as `contract_template`. Default "Fama Standard" template seeded + linked to the
  Standard Booking flow.

## 2026-08-02 — Mandatory admin verification before a talent/brand can go public
- **Verification is now a hard gate on publishing (ADR-U).** No talent or brand reaches the public
  surfaces until an admin verifies it. Talents gained an `is_verified` column (migration
  `2026_08_02_000100`, mirroring brands); the publish transitions are guarded — `ToLive` (talent) now
  requires `display_name` **and** `is_verified`, and a new `ToPublished` (brand) requires `is_verified`
  on `Complete→Published` / `Unpublished→Published`.
- **Self-serve publish is blocked with a clear message.** `TalentProfileService::publish` and the brand
  `AccountController::publish` pre-check `is_verified` and return a friendly 422 ("awaiting verification")
  when unset. The talent/brand profile editors show an *Awaiting verification* banner and disable the
  Publish button until approved.
- **Admin verify flow.** New `TalentModerationService::verify` + the `verify` moderation action (audited
  `talent.verified`); the shared moderation `account-actions` partial now shows **Verify** for both
  talents and brands and hides **Publish** until the account is verified. Brand verify already existed;
  it is now the publish prerequisite rather than a cosmetic trust badge.
- **Grandfathering.** The migration backfills `is_verified = true` for already-`live` talents and
  already-`published` brands, so the gate only affects future publish transitions. Factory defaults for
  the public/published states are `is_verified = true` (with `unverified()` states for negative tests).

## 2026-07-31 — Admin: delete a review with an emailed notice (live-preview modal)
- **Reviews are now soft-deletable by an admin, with a reason + email notice.** `reviews` gained
  `deleted_at` (migration `2026_07_31_000100_add_soft_deletes_to_reviews`); `Review` uses `SoftDeletes`.
  A moderator opens the review drawer → **Delete…** → a composer modal with **recipient email**,
  **subject**, and **reason** fields beside a **live preview** that mirrors the actual email
  (`emails/review-removed`). Submitting hits `POST /admin/moderation/reviews/{review}/delete`
  (`ReviewModerationService::deleteWithNotice`): the review snapshot is captured, the delete + an audited
  `review.deleted` activity (reason + notified address) run in a transaction, then
  `App\Mail\ReviewRemovedNotice` is mailed to the recipient (defaulting to the reviewed talent, since
  reviews are anonymous). Gated on `moderate-content`; validated (email/subject/reason required).
  Comment authors already delete their own comments (CommentPolicy + the feed `is_mine` Delete control);
  reviews have no reviewer account, so the admin path is the removal route for them.

## 2026-07-23 — Brands page redesign, share fixes, overflow-menu icon
- **Brands cards, gallery-plate creative pass + covers.** Reworked each card into an editorial "plate":
  the header now shows the brand's real **cover image** (new `cover_url` on the card resource — the
  accessor is `cover_image_url`; covers are seeded for every brand + backfilled), falling back to the
  deterministic gradient when absent. Added film grain, a top-light sheen + bottom scrim for legibility,
  an editorial index number (01, 02, … across pages), a verified pill, a larger rotating ring-framed
  mark, a big two-line display name, and a circular arrow that fills with accent on hover (RTL-mirrored).
- **Brands cards, premium pass.** Removed the per-card **Message** button (messaging lives on the brand
  profile). Each card is now a single quiet link to the profile — no nested anchors — with the brand
  initial as a large watermark on the colour band, a soft top-light sheen + bottom scrim, a lifted
  ring-framed mark, and a mono **"View profile →"** affordance whose arrow slides on hover (RTL-mirrored).


- **Discover brands — "super creative" redesign.** `public/brands.blade.php` rebuilt as an editorial
  wall: an oversized display hero with a live count and a gradient orb; brand cards now carry a vivid
  deterministic colour band (tint hashed from the name), an overlapping ring-framed mark, the name in
  the display face, a mono industry pill + verified check, a location chip on the band, and a
  **prominent full-width Message CTA** (primary) beside a quiet View. Every `brandsDiscover` Alpine hook
  (filters, industry chips, active summary, advanced-filters modal, pagination) is unchanged.
- **Message links localized.** `View`/`Message` now build through `window.fama.localizeUrl` (`profileUrl`
  / `messageUrl` helpers) so they keep the active locale on `/ar` instead of dropping to English. The
  messaging flow itself was already wired (ADR-P): a talent lands in the brand↔talent contract room; a
  guest is sent to talent login and returned.
- **Overflow-menu icon fix.** The teleported ⋯ menu's Save glyph ballooned — `.action-icon` is only
  sized inside `.action-btn`, and the menu item isn't one. Gave it an explicit `h-4 w-4`.
- **Instagram share.** Added Instagram to the post share popover. Instagram has NO web share-intent URL,
  so the honest behaviour is: copy the link, toast the user, and open Instagram to paste into a
  story/bio/DM (mobile users get Instagram directly via the native "Share…" item).
- **Link unfurls (LinkedIn/X/WhatsApp/Facebook).** The "Cannot display preview" was the target crawler
  finding no Open Graph tags (and `fama.test` being unreachable in dev). Added a `head` slot to
  `<x-public-layout>` and Open Graph + Twitter-card meta to the post page (title, description from the
  body, `og:image` from the post's feed-size media or author avatar, canonical URL). Previews will
  render once the post URL is on a publicly reachable host.
- Feed + public suites green (259). AR strings added.

## 2026-07-23 — Fix: "BRANDS TO FOLLOW" rail vanished (shown-rest emptied a small pool)

- **Bug.** With only ~15 brands, the suggestion engine's "shown-but-unacted" set was a HARD exclusion:
  once a viewer had scrolled past every brand once, all of them were excluded for 7 days and the
  BRANDS TO FOLLOW rail collapsed to nothing (the talents rail survived only because the talent pool is
  large). Confirmed live — all 15 brands were in the demo talent's excluded set.
- **Fix.** The shown-rest is now a soft **deprioritization**, not an exclusion: `excludedIds` keeps only
  the hard exclusions (followed + dismissed), a new `shownIds` feeds `scoreAndSort` a
  `feed.suggestions.shown_penalty` that ranks shown candidates BELOW every fresh one — so variety is
  preserved when the pool is large, but a small pool still fills the rail instead of vanishing. The
  `SuggestionEngineTest` exclusion test was split accordingly (a new test asserts shown items rank last
  yet still appear when they're all that remain). Feed suite 210 green.

## 2026-07-23 — Feed UX fixes: sticky filter chrome, back-to-top, overflow menu, dashboard access
- **Filter chrome → Filters FAB handoff.** The sticky filter chips now hand off to a floating
  **Filters** button in the bottom corner once you scroll past the header: `feedStream` tracks
  `pastHeader` (scroll beyond the chrome height), which hides the sticky chrome (`.chrome-handed-off`)
  and reveals a Filters FAB in the teleported fab-stack. Tapping it opens the same ALL / FOLLOWING /
  Skill chips in an upward popover (no clip trap — the stack is teleported); picking one applies via the
  existing `setFilter` and smooth-scrolls to the top (which brings the sticky chrome back). Reachable
  because the fab-stack is teleported from inside the `feedStream` scope, so it reads
  `pastHeader`/`skill`/`following`/`setFilter` directly.


- **Sticky filter chrome.** The feed header (title + eyebrow + filter chips) has
  `position: sticky; top: var(--topbar-h)` but wasn't sticking: the shared `<main class="animate-fade-in-up">`
  animates a `transform`, and a transformed ancestor breaks `position: sticky` (and `fixed`) for
  descendants. `<x-public-layout>` gained an `:animate` prop; the feed passes `:animate="false"` (it runs
  its own 5Y motion layer), so `<main>` no longer transforms and the chrome sticks under the topbar. This
  also hardens the teleported FABs.
- **Back to top.** `toTop()` now scrolls `window` + `document.scrollingElement` + `documentElement`
  (engines disagree on which owns the scroll) and moves focus with `preventScroll: true` — the old
  `h1.focus()` was snapping the page back down and cancelling the scroll-to-top.
- **Overflow (⋯) menu.** It opened invisibly: the Daylight frame clips with `clip-path`, so the in-frame
  dropdown was clipped away. New `overflowMenu` Alpine component teleports the menu to `<body>` and
  positions it fixed from the trigger rect (flips up near the viewport bottom, RTL-aware) — the same
  escape the mention-pop uses. **Save moved into this menu** (with Report, and author Edit/Delete); the
  standalone Save button is gone from the action bar.
- **Feed access from dashboards.** Added a **Feed** nav link to the admin and brand dashboard sidebars
  (both already had it on talent) so staff and brands can reach the public feed.
- Regression tests added (FAB teleport already guarded; overflow-menu teleport + Save relocation now
  asserted). Feed suite **208 green**; admin + brand dashboard suites green. Live browser pass pending
  (extension disconnected).

## 2026-07-23 — Fix: feed floating actions (New post + Back to top) invisible; teleport to <body>

- **Bug.** The feed's FAB stack (New post + Back to top) was rendered inside
  `<main class="animate-fade-in-up">`, whose `fadeInUp` keyframes animate a `transform`. A
  transformed ancestor becomes the containing block for `position: fixed`, so the stack was pinned to
  the bottom of the full-height `<main>` (far below the fold) instead of the viewport — the buttons
  effectively never appeared on screen.
- **Fix.** The stack is now `x-teleport`'d to `<body>` inside a `data-surface="daylight"` host
  (`.fab-host { display: contents }` so it carries the token scope without painting a ground layer) —
  the same escape-the-transformed-ancestor pattern the composer modal already uses. The buttons sit at
  bottom `inset-inline-start`: **bottom-left in English, bottom-right in Arabic**. Guarded by a
  `RoundThreePolishTest` assertion.

## 2026-07-23 — Dashboard follow management (Prompt 5-follow): FOLLOWING / FOLLOWERS tabs; 587 tests green

- **The surface.** A FOLLOWING sub-tab (and its near-free twin FOLLOWERS) on BOTH the talent and
  brand dashboards, reusing the 5X collection shell — same layout, pagination, empty-state pattern and
  voice — over a new `FollowManagementService`. Rendered on a `data-surface="daylight"` region inside
  the dashboard shell so the rail card language applies exactly: **portrait talents, square brands**
  (shape is the type signal — no colour), display name in Fraunces, lowercase handle in mono, top two
  Skills for talents / sector + city for brands, followed-date in mono.
- **Two management signals** that make it a pruning surface, not a list: **FOLLOWS YOU BACK** (mono
  badge when mutual) and **QUIET SINCE <month>** (`--ink-faint`, when a talent has not published in 90
  days; "Never posted" when they never have).
- **`talents.last_posted_at`** (migration `2026_07_22_000700`, indexed, backfilled): a denormalized
  last-publish timestamp maintained by `CounterService::applyPostPublishDelta` on the publish boundary
  (publish stamps the later value; leaving published recomputes from remaining posts, so deleting the
  last post clears it) and reconciled nightly with a NULL-safe `<=>` repair. No per-row subquery.
- **Controls, all server-side + pagination-correct (SQL, never in-memory over a fetched page):**
  search over display name + Username (debounced 200 ms); a talents/brands type filter (hidden for
  brands, who follow only talents); a Skill filter drawn from Skills PRESENT in the set
  (`presentSkills`, not the catalog); sorts — recently followed (default), alphabetical, and
  least-active (`last_posted_at` ASC, NULLs first, so prune candidates surface first).
- **Optimistic unfollow** — the row dims and slides out over 280 ms, counters move, a 5 s undo toast
  holds; undo restores the row IN PLACE (not re-sorted). The FOLLOWERS tab offers **FOLLOW BACK** where
  not mutual, and never unfollow. Both reuse the existing `/follow/{type}/{id}` POST/DELETE endpoints.
- **Privacy.** The mutual badge and quiet-since signal are dashboard-only and never enter the public
  `GET /talents/{username}/followers|/following` payload (Prompt 5H) — asserted.
- **Performance.** The polymorphic other party is eager-loaded with **`MorphTo::morphWith`** (talents
  with Skills + media, brands with logo — a plain `with('followable')` N+1s across the mixed types);
  mutuality is ONE bulk query via `FollowService::isFollowing` / the new `followsBack`. A test asserts a
  fixed query count for 3 vs 15 followed accounts.
- **Tests.** `FollowManagementTest` (13): mixed-type cards, the two signals, `last_posted_at`
  maintenance + reconcile, search / least-active / present-skills, the followers-tab control, the
  brand type-filter-hidden case, the privacy boundary, and the fixed-query-count contract. Full suite
  **587 green** (2,113 assertions); build green; verified end-to-end on the demo talent (quiet-since +
  least-active on real data). AR strings added (21). Docs: feed-spec (public-vs-dashboard follow
  surfaces + `last_posted_at`), architecture (dashboard follow-management subsection + the counter
  note), schema-master + schema.md (`talents.last_posted_at`).

## 2026-07-22 — Feed round 3 (5AB–5AI + polish): chrome, action bar, media geometry, comments, composer; 574 tests green

- **5AB — topbar & z-scale.** A single z-index scale now lives in `app.css` `:root`
  (`--z-base` 0 → `--z-toast` 500, plus `--topbar-h`); no raw z-indexes remain in the feed.
  The public topbar is **fixed**, fully opaque (`--bg`), grows a hairline seam after 8px of
  scroll, and hides/reveals on scroll direction under 1100px (`resources/js/topbar.js`).
  Primary nav is **Feed, Discover, Brands, Opportunities** (Feed-first per the playbook
  default — flip the array order in `public-layout.blade.php` for Discover-first); the active
  item carries `aria-current="page"` + a gestural SVG underline, never the underline alone.
- **5AC — floating actions.** `.fab-stack` (NEW POST + back-to-top) fades in past a 150vh
  scroll marker via IntersectionObserver; NEW POST opens the composer (`open-composer`
  window event) or the guest gate; back-to-top honours reduced motion and focuses the h1.
- **5AD — action bar.** Rebuilt `<x-feed.actions>`: 40px hit targets / 18px icons, hover
  wash tokens (`--dl-hover*`), the lit like with count, comment toggle, a **share popover**
  (Copy link / X / WhatsApp / LinkedIn / native share), and an end cluster of SAVE + an
  overflow `role=menu` (Report; Edit/Delete when author). Every control's `aria-label`
  names the author.
- **5AE — media geometry.** New generic `<x-feed.media-grid>` (single with container-query
  aspect clamp; two-up; 3+ leads with a double-row tile and a +N overlay) + a teleported
  **lightbox** (arrows, swipe, `01 / 08` counter, Escape, caption from ALT). `media_alt[]`
  now persists as a medialibrary custom property per file (order-matched), rendered as
  real `alt`/captions.
- **5AF — report flow.** Report reasons widened to seven config-driven keys
  (`feed.report_reasons`; migration `2026_07_22_000600` converts the old enum + maps
  legacy rows); "Something else" requires a note (≤500); the shared report dialog confirms
  receipt honestly — including on the duplicate-report 403 — and quotes reported comments.
  A toast rack (`$store.feedToasts`, cap 3, hover-pause) serves save/report feedback.
- **5AG — comment thread.** Author lines are 14px normal-case names + mono handle/time +
  a BRAND badge that never shrinks; 32px avatars (24px in replies); the composer is a
  collapsed row that expands on focus (guests → gate); optimistic sends show a pending row
  with Retry on failure; inline edit keeps the mm:ss countdown.
- **5AH — mention autocomplete, diagnosed and fixed.** The 5U dropdown had never been
  visible in the live build: it was clipped by `.frame { overflow: hidden }` (comment
  fields) and the modal's scroll container (composer). The list is now **portalled to
  `<body>`** (`.mention-pop`, `--z-autocomplete`, fixed-position from the field rect,
  repositioned on capture-phase scroll/resize, bottom-sheet under 640px). Browser-verified
  live (feed + composer + comment field). Pick events dispatch from the component **root**
  — `$dispatch` from inside a teleported node bubbles from `<body>` and the parent's
  listener never hears it.
- **5AI — New post modal.** Portalled on the z-scale with pinned header (title +
  posting-as identity) and footer (CLOSE ghost + PUBLISH that shows a hover hint naming
  what's missing instead of sulking); scrolling body; whole-modal drag-and-drop with a
  full-width dashed dropzone; media preview uses the REAL `.media-grid` classes with
  per-tile reorder / ALT / remove; live credits preview; char counter appears at 1,900 and
  turns danger at the limit; Escape/backdrop/CLOSE confirm before discarding visible work
  (the draft still survives in memory).
- **Round-3 polish.** Rail follower counts read as words ("3 FOLLOWERS", zero = "NEW");
  brand sector enums humanized; reason lines clamp to two lines so the full @handle
  survives; deterministic avatar fallbacks (display-face initial on a username-hash tint);
  FeedSeeder now attaches generated avatars (talents) and logos (brands) — dev DB
  backfilled; the design-tokens preview gained the action bar, comment typography, and
  avatar fallback.
- **Live-found fixes (browser QA).** (1) `@js()` on a **component-tag attribute** is not
  compiled by Blade — the postFrame `x-data` shipped it literally and broke every post
  frame's Alpine tree; replaced with `{{ Js::from(...) }}` + a regression test asserting
  the rendered feed never contains a literal directive. (2) A string `:style` binding on
  the same element as `x-show` rewrites the style attribute and wipes `display:none` —
  removed from the composer counter. (3) Rails hydrated via bare `requestAnimationFrame`,
  which never fires in hidden tabs — now rAF with a 250ms timeout fallback. (4) The
  `$confirm` dialog markup was only in dashboard layouts; the public layout now mounts it.
  (5) NEW `window.famaLang` bridge (daylight-head) — feed.js's runtime strings were
  silently English-only; all bridge keys + every round-3 Blade string translated in
  `lang/ar.json` (+124 keys).
- **Tests.** `RoundThreePolishTest` (14 tests: topbar structure/order/aria-current, the
  literal-directive regression, famaLang bridge, all seven report reasons + other-requires-
  note + config lockstep, ALT persistence order + length cap). Full suite **574 green**
  (2,076 assertions); build green; live /feed 200 with clean console.

## 2026-07-22 — Feed rails 5Z+5AA: suggestion engine + the two sidebars; 560 tests green

- **5Z — the engine.** `feed.allow_following_brands` flipped **true** (decision 3): the
  talent→brand edge is first-class — **`brands.followers_count`** landed (migration
  `2026_07_22_000500`, FollowObserver + reconcile; `ToggleFollow` reports it), and the old
  "config off" test now sets the flag explicitly. New **`suggestion_dismissals`** (double morph,
  unique ×4 — a dismissal never returns). **`SuggestionService`**: tiered ranking per viewer kind
  exactly per the brief (talent: second-degree → mentioned-in-liked → shared Skill → same city →
  high-signal; brand→talent: sought-Skills proxy → contract history → city → signal; talent→brand:
  Skill overlap via Project roles → contract history → network-followed → city → active;
  **brands never see brands**; guests: seeded high-signal, stable per session via a session-stored
  seed). "High signal" = config-weighted completeness/recent-posts/log-damped followers/account age
  (`feed.suggestions` — zero magic numbers). **Truthful reasons** stored as {key, params} and
  translated at serve time (en + ar): Followed by @handle (+N), Works in X like you, You worked
  together on Title, New in City, Active this week — or omitted, never fabricated. **All mandatory
  exclusions** enforced (self, followed, dismissed, unpublished/suspended, shown-unacted-7-days —
  the last cache-tracked, not a table). Cached 5 min per actor (plain `{id, reason}` arrays only);
  invalidated on follow, dismiss, and own-profile update; fixed query count regardless of limit
  (the followed-by sample handles resolve in ONE batched query per page). Endpoints
  GET /feed/suggestions/{talents,brands} (offset cursor) + POST /feed/suggestions/dismiss
  (actor-only, 60/min), mirrored under /api/v1; Scribe regenerated — **200 endpoints** (was 190;
  saves + rails). 9 new tests.
- **5AA — the rails.** The **per-post meta rail is deleted** (decision 2 — its third-duplication
  date/counts/skills all live elsewhere; the frame's rail slot went with it). New three-column
  grid: **TALENT TO FOLLOW** inline-start (portrait 4:5 notched cards), the 720px spine,
  **BRANDS TO FOLLOW** inline-end (square cards on surface-sunk with the mono BRAND label —
  shape carries the marketplace side, not colour); ≥1400px three columns, 1100–1399 spine+right
  (talent falls back to the casting strip), <1100 single column; a brand viewer gets no brand rail
  and the grid **rebalances** (`no-brand-rail`). With a rail visible the inline strip halves to
  every 12 slots. Rails are sticky/self-scrolling (hidden scrollbar, `overscroll-contain`), hydrate
  AFTER first paint via `format=html` fragments (server-rendered `suggestion-cards` partial),
  SHOW MORE appends via the offset cursor, and the **empty rail collapses** — never a placeholder
  box. Interactions: optimistic Follow → "FOLLOWING" for 2s → 280ms slide-out → replacement pulled;
  dismiss slides immediately and **only POSTs after the 5s undo window** (server dismissal is
  permanent); both undoable; guests get the inline sign-in chips with intent replay (ADR-F8).
  Card entrances rise 8px staggered (cap 5) — no light response (that stays exclusive to frames).
  Logical properties throughout — RTL swaps sides with zero overrides; asides + list semantics,
  named controls, live-region announcements. **Suite: 560 passed (2033 assertions)**; build green;
  live /feed 200 with both rails serving.

## 2026-07-22 — Revision batch 5Y: the motion layer — REVISION BATCH COMPLETE; 551 tests green

## 2026-07-22 — Revision batch 5Y: the motion layer — REVISION BATCH COMPLETE; 551 tests green

- **Entrances** (JS-applied, so a no-JS page hides nothing; skipped entirely under
  `prefers-reduced-motion`): frames rise 12px + fade over 280ms on the house curve, staggered 40ms
  capped at 6, **direction-aware** (down = from below + .985→1 scale, up = from above; leaving
  reverses subtler at 60%), first-paint frames never animate, `will-change` applied on start and
  removed on end. Ajax batches and the composer's optimistic insert re-arm the observers
  (`.dl-insert` expands the new frame with content fading in behind).
- **The light response** — the signature: crossing the middle 40% viewport band lifts the frame
  into the light. Implemented as a **pre-rendered notched `::after` shadow layer crossfaded by
  opacity** (compositor-friendly — never an animated box-shadow) plus a 2% surface veil, driven by
  one IntersectionObserver with `-30%` root margins — **no scroll listeners, no gBCR loops**. The
  like sweep composes on the base layer beneath it.
- **Chrome on scroll:** the filter row is sticky and **condenses on scroll-down** (eyebrow hides,
  padding halves, chips collapse to one scrollable line) and expands on scroll-up; the composer
  trigger collapses to a compact pill; the 2px light bar keeps tracking depth. One passive scroll
  listener feeds direction + progress. `content-visibility: auto` + `contain-intrinsic-size` on
  every frame. Deviation noted: there was no pre-existing "global scroll-motion layer" to reuse
  (the playbook assumed one) — the feed's own layer in feed.js is that layer now, re-armed after
  every swap. The 60fps / 50-frame profiler run is queued with the manual RTL/dark QA pass.
- **Suite: 551 passed (1998 assertions)**, build green, live /feed 200. The revision batch
  (5S–5Y) is complete.

## 2026-07-22 — Revision batch 5X: bookmarks + private dashboard collections; 551 tests green

- **Schema:** `bookmarks` (polymorphic BOTH sides — saving talents/Projects later is a policy flip,
  not a migration; unique ×4 = the idempotency backstop) + `posts.bookmarks_count` (in-transaction
  via `BookmarkObserver`→`CounterService`, covered by `feed:reconcile-counters`). Morph alias
  `bookmark` registered.
- **`ToggleBookmark`** (idempotent, race-safe; **posts-only this phase** — anything else answers
  "Only posts can be saved for now."), `BookmarkPolicy` (any Actor), POST|DELETE
  /posts/{post}/bookmark on web AND /api/v1, `feed-bookmarks` limiter 60/min, `bookmark` added to
  the PendingIntent replay set — guests get the same inline gate + replay as like/comment.
- **UI:** a Save control in the action bar — the mark is a **folded corner echoing the frame's
  notch** (sprite glyphs `save`/`save-filled`), outline → filled ink, SAVE → SAVED, optimistic with
  rollback. `viewerBookmark` constrained-eager everywhere (`bookmarker_*` columns — NOT the liker
  closure), `saved_by_viewer` + `bookmarks_count` on PostResource.
- **Dashboards:** talent **SAVED** + **LIKED** and brand **SAVED** (sidebar entries + routes +
  `FeedCollectionService`): newest interaction first, paginated 12, compact cards (cover/author/
  date/skills), **server-side skill filter + body search**, inline unsave/unlike with optimistic
  removal and a 6-second **UNDO toast**, guard-scoped with no way to request another actor's
  collection, fixed-query-count asserted, empty states in the feed's voice.
- **ADR-F9:** saved and liked collections are **private** — no public likes; the polymorphic table
  is the documented extension point for brand talent-shortlists.
- **8 new tests** (idempotent toggle + counter, type-gate message, guest gate + replay, reconcile
  coverage, talent saved/liked scoping + filters + search, brand collection, guest lockout,
  fixed query count). **Suite: 551 passed (1998 assertions).**

## 2026-07-22 — Revision batch 5U+5V+5W: one mention field, chips replace URLs, richer spreads; 163 feed tests green

- **5U — ONE mention component.** New `<x-feed.mention-field>` (+ `mentionField` in feed.js,
  `x-modelable` binding) mounted by BOTH the post composer and the comment/reply composer — the
  composer's inline autocomplete was extracted, not duplicated. Queries after 1 character
  (debounced 200ms), TALENT/BRAND type badges, loading + "No one found" states, arrows/Enter/Tab
  insert (**Enter never submits while open** — submit rides a `submit-intent` event), Escape/space/
  blur close, drop-up flip when out of room, combobox ARIA (`aria-expanded`,
  `aria-activedescendant`, listbox/option roles) + a live result count. Server: `/mentions/search`
  now **excludes the current actor** and ranks **followed (4) > prefix (2) > substring (0)** via
  one bulk follow map; fixed query count proven by test. Caret anchoring is field-anchored with
  viewport flip (a textarea has no DOM caret — documented approximation).
- **5V — chips REPLACE raw URLs.** `BodyRenderer` now locates URL ranges by **re-parsing the raw
  body** and pairing parsed offsets with `linked_urls.position` (string-searching the stored
  NORMALIZED url missed trailing slashes/case — the bug class the batch's edge cases target); a
  long URL is never visible. Chips: inline SVG **glyph sprite** (`feed/partials/glyphs.blade.php`,
  14 monochrome marks, no CDN) + `ChipLabel` rules (profile → @handle; content → enriched title
  when landed else "Instagram post"/"TikTok video"/…; generic → registrable domain), 13 brand
  glint colours on hover only, and a delayed (400ms) enriched hover preview card — remote HTML
  still never renders. API adds a structured `chips` array to Post/CommentResource. 7 edge-case
  tests (tracking queries, mobile subdomains, trailing slashes, Arabic bdi, adjacent + start/end
  URLs, permanent enrichment failure, domain labels).
- **5W — the spread is now the strongest frame + honest credits.** Project shares rebuilt:
  full-bleed cover under the notch, eyebrow, Fraunces title, mono client · year meta line,
  thumbnail strip (+N chip) when the Project has further media, 2-line description clamp **with a
  fade**, skill chips, VIEW THE PROJECT, and a stretched whole-frame click target with inner
  targets layered above. `shared_snapshot` **widened** (title/cover/client/year/media_count/
  skill_ids — ADR-F2 updated) so a dead Project renders complete, marked unavailable. **Credits
  fixed:** the author gets ONE line with skills middot-joined (`MODELING · STYLING —
  @darwin-carter`), other mentions get their own WITH lines, **handles are never uppercased**
  (`.credit-handle` cancels the transform), empty block never renders. **Counters de-duplicated:**
  the rail carries date + skills only. Query test proves project shares add zero per-post queries.
  Suite 163 feed tests green; build green.

## 2026-07-22 — Revision batch 5S+5T: derived palette + control colours; 148 feed tests green

- **5S — the palette is re-grounded in the topbar colour.** The batch asked for the exact hex: it is
  core `--bg` — `#EDEFF3` light / `#0D0F13` dark (the screenshot's `#F4F2EE` was the 85%-alpha bar
  blended over the old sand). `daylight.css` now derives the ENTIRE neutral ramp from `--dl-base`
  via `color-mix(in oklch)`: ground/ground-sunk/surface/surface-sunk/seam/ink/ink-soft/ink-faint;
  bar and ground are the same colour (the bar dissolves); frames float on `--dl-surface` via the
  blue shadow. `sand`/`sand-deep`/`chalk` retired and every usage migrated (views, css, preview,
  Tailwind names → `bg-ground`, `bg-ground-sunk`, `bg-surface-sunk`, `text-ink-faint`). Static hex
  fallbacks in `@supports not (color-mix in oklch)`. **`php artisan feed:check-contrast`** re-
  computes the formulas (sRGB→OKLab→LCH) and gates WCAG (ink 7:1, soft/faint/daylight 4.5:1, both
  themes, both grounds) — `ink-faint` tuned 48%→**56%** (light) and 44%→**56%** (dark) to pass;
  the command runs in the Pest suite and prints the fallback hexes.
- **5T — controls stop spending the accent.** New `daylight.css` classes: `.well` (surface-sunk,
  seam border, **soft focus halo** — 1px daylight border + 3px 14%-alpha ring, `padding-inline-end:
  48px` for extension widgets, `--grow` variant with JS `autogrow` 1→cap rows, no resize handle);
  `.dl-btn` (ghost when empty → **ink fill** when ready, oklch hover/active lightness steps, 40%-
  alpha busy state + inline spinner) applied to SEND / PUBLISH / SEND REPORT; `.dl-chip` (surface-
  sunk + seam, hover border ink-faint, **active = ink on surface, never blue**) applied to filter
  pills, composer mode/skills chips, report reasons, gate chips and the casting FOLLOW toggle;
  thread classes (`.thread-row` seam hairlines, `.thread-replies` 32px indent + rule,
  `.is-mine` 2px daylight inline-start rule — the only accent in threads, `.thread-tombstone`
  faint italic on surface-sunk). All states added to `/design/feed-tokens`. Suite 148 feed tests
  (528 total) green; build green; live 200.

## 2026-07-22 — Prompt 5R: hardening + close-out — PHASE 5 COMPLETE; 527 tests green

- **Sweep tests:** every public feed read answers guests with 200 (12 routes); every write answers
  guests with the 401 envelope (15 routes); post-detail (< 15 queries) and warm casting strip
  (< 10) join the feed-page (< 25) and size-independent comment-thread budgets.
- **`docs/performance.md`** (new, part of the fixed docs set): the test-enforced query budgets,
  EXPLAIN evidence from the seeded DB (candidate query → `posts_author_type_author_id_published_at`
  range; comment listing → `comments_post_id_parent_id_created_at` ref; likes → likeable composite),
  the full cache inventory with the **deliberate no-write-invalidation** note on the frozen
  candidate list (ADR-F5), and the queue/schedule/ffmpeg operational notes.
- **Docs reconciled in place:** feed-spec open questions restated with close-out status (Q1 project-
  share snapshot model and Q3 video scope remain stakeholder/product calls — both isolated
  changes); architecture.md carries the full feed plane (actor, ranking, rich text, notifications,
  moderation, UI); README states the phase complete. **Final counts: 190 endpoints (was 130) ·
  45 domain tables (was 35) + the `notifications` infra table.**
- **Final verification:** suite **527 passed (1901 assertions)**, Vite build green,
  `php artisan migrate:fresh --seed` exits 0 (FeedSeeder ~14 s at full scale), live `/feed` 200.
  Known follow-ups for stakeholders: ADR-F2 confirmation, video-scope decision, AR copy pass over
  the new feed UI strings (notification + validation strings are translated; some Blade labels are
  first-pass), and RTL/dark verification in-browser per the standing convention.

## 2026-07-22 — Prompt 5Q: feed moderation console + auto-hide + feed:prune; 523 tests green

- **`FeedModerationService`** — every action through the state machines in a transaction,
  activity-logged (`feed_moderation`, causer = the acting admin, reason in properties):
  hide/remove/restore for posts AND comments (remove is terminal and **notifies the author with
  the reason** — new `ContentRemoved` notification), `resolveReport` (actioned/dismissed with
  resolver + timestamp), and `registerReport` — the **auto-hide tripwire**: at
  `config('feed.auto_hide_reports')` open reports (default 5) a published item transitions to
  Hidden and logs `auto_hidden`; `wasAutoHidden()` flags it in the queue. Wired into the public
  report endpoint.
- **Console** (`/admin/moderation/feed`, permission `moderate-content`, sidebar entry added):
  Reports queue (status/reason/target filters, content in context, auto-hidden badge, remove/hide/
  actioned/dismiss), Posts + Comments indexes (state filter + body search, hide/remove/restore
  through `$confirm`, remove prompts for the author-facing reason), and the **talent activity
  timeline** (`/admin/moderation/feed/talents/{talent}` — posts ∪ comments ∪ reports, one stream).
  Blade + inline Alpine on the shared fetch wrapper, matching the admin queue pattern
  (server-validated `?queue=` deep links, `$pill`/`$statusLabel`).
- **`feed:prune`** (scheduled 03:40): drops `feed_seen` rows > 60 days and failed, expired,
  unreferenced link previews.
- **8 new tests** (state-machine walk + audit + author notice, report resolution stamping,
  the 3-report auto-hide → public 404, moderator access + talent redirect + permission-less 403,
  end-to-end comment removal, prune correctness incl. the referenced-preview guard).
  **Suite: 523 passed (1854 assertions).**

## 2026-07-22 — Prompt 5P: brand-side discovery + filtered feed modes; 515 tests green

- **Filtered feed modes:** `/feed?skill={slug}` (posts tagged with that Skill — both tiers
  filtered) and `/feed?following=1` (tier 1 only). The filters ride **inside the opaque cursor**
  (`{s,o,k,f}`) and in the candidate-cache key, so a filtered stream keeps the frozen-list
  stability contract across pages; the first page reads query params, every later page reads the
  cursor. Filter pills on the feed header swap the stream over Ajax (spine cleared, URL synced via
  pushState); FOLLOWING shows only for actors.
- **Discovery entry point:** the public `/discover` results row gains "See their work in the feed →",
  carrying the currently-selected skill into the filtered feed (a single selected skill →
  `/feed?skill={slug}`).
- Brand capability boundaries were already in place (5C–5O): PostPolicy rejects brand posting
  server-side, the composer renders for talents only, the casting strip is the brand-side rail
  (12 unfollowed talents, no repeats, follow toggle), and brand comment authors carry the mono
  BRAND label — never colour alone.
- **3 new tests** (skill filter restricts + cursor carries the filter across pages with no dupes,
  following-only restricts to followed authors, filtered page loads render). **Suite: 515 passed
  (1825 assertions)**; live `/feed?skill=…` + `/discover` verified 200.

## 2026-07-22 — Prompt 5O: inline comments, interactions, full guest gating; 512 tests green

- **Inline comment threads** (`feed/partials/comments.blade.php` + `commentThread` in feed.js —
  nested in postFrame, lazy-loads on first open, never a modal): seam divider, paginated 10 +
  Load more, 3 preview replies + "Show N more replies" (separate pagination), tombstones (D12),
  optimistic comment likes, **reply pre-fills @username and focuses the field** (depth-two
  re-attach stays server-side), author inline edit (well swap, PATCH, quiet mono `mm:ss` countdown
  from `editable_until`) and delete via `$confirm` (tombstone when replies survive). `CommentResource`
  gains `is_mine` + `editable_until`. Live region announces thread updates; BRAND label on brand
  authors (never colour alone).
- **Interactions:** double-tap on a media set likes with the same glint flare; the report flow is
  now a reason sheet (radio chips + optional note → confirmation toast; duplicate → "already
  reported" toast).
- **The guest loop closed (ADR-F8):** `require.actor` now also stores `feed.return_to` (the exact
  post/comment anchor, else the same-origin referer) alongside the intent; the role-aware login
  honours it — a **deliberately separate slot from `url.intended`**, which the multi-guard login
  ignores on purpose (the feed return URL is public and guard-safe). E2E test: guest ✦ → 401 intent
  → login → redirected to `/posts/{id}#post-{id}` with the like already landed; ordinary logins
  still go to the dashboard.
- Deflaked the followers-listing test (identical `created_at` made page-1 membership
  nondeterministic). **Suite: 512 passed (1815 assertions)**; build green; /feed + /posts/{id}
  verified live.

## 2026-07-22 — Prompt 5N: the composer; 510 green, build green, live verified

- **`feed/partials/composer.blade.php`** (talents only — the trigger and panel render only when the
  actor is a Talent; `FeedController::index` ships the talent's Skills + shareable Projects
  server-side): teleported to `<body>` (class-driven enter/leave, no x-transition — the discovery-
  modal lesson), centred panel on desktop / bottom sheet on mobile, scrim + ESC + body scroll lock,
  focus moved in and restored out.
- **Plain-text body in a `<textarea>`** — a deliberate deviation from the playbook's
  contenteditable: native caret/RTL/bidi behaviour is exactly the "plain-text model" the prompt
  wants, without a caret-management layer (documented in docs/design-system.md). `@`-token under
  the caret → debounced 200ms `/mentions/search`, keyboard-navigable (arrows/Enter/ESC), grouped
  Talents/Brands with avatars; picking completes the token and records the mention. Pasted/typed
  URLs → `/links/preview` → **instant chips from the parse** (enrichment optional); chips prune
  when their URL leaves the body. Counter appears at 1900 and turns daylight at 2200.
- **Media**: drag-drop + picker, object-URL previews, reorder (‹ ›), remove, client validation
  mirroring the server rules (types, per-kind size caps, max 10). **Skills**: own-skills chip
  multi-select. **Share a Project**: mode toggle listing the talent's Projects as notched cards.
- **Live credits preview** — the signature moment: the editorial credit line assembles under the
  draft as skills and mentions are added (`SKILL — @username / WITH — @mention`).
- **Submit**: POST /posts (FormData) or /projects/{id}/share, then the server-rendered frame
  (`/posts/{id}/fragment`) prepends onto the spine; failures keep the draft with an inline error;
  the draft persists in memory across close/reopen within the page session.
- **Fix:** `DiscoverTalentsService` cached Eloquent model instances, which unserialized as
  `__PHP_Incomplete_Class` on the live site — the cache now stores the ranked **ID list** only and
  rehydrates in ranked order. Live /feed verified 200; suite 510; build green.

## 2026-07-22 — Prompt 5M: the Daylight feed shell + post frames; 510 green, build green

- **Components** (`resources/views/components/feed`): `frame` (notched shell + blue drop-shadow +
  the desktop meta rail slot — absolute `start-full`, mirrors under RTL), `media` (1 full-bleed /
  2–3 editorial grid / 4+ horizontal snap strip with tick marks + mono `03/08` counter; videos with
  poster; alt text from the body), `spread` (FROM THE PROJECT eyebrow, Fraunces title, live
  link-through or dead-but-intact snapshot frame — ADR-F2), `credits` (the editorial credit line:
  each Skill — @author, mentions as WITH — @handle; skills deep-link to filtered discovery),
  `actions` (glint/comment/share/overflow + the INLINE guest gate — never a modal, ADR-F8),
  `casting` (notched talent cards, Fraunces names, mono skills, follow toggle), `skeleton`
  (notched, locale-aware sweep).
- **`feed/partials/post.blade.php`** is now the canonical Daylight frame (used by the server page,
  the Ajax stream and /posts/{post}/fragment); `feed/index` rebuilt as the Daylight spine —
  variable widths (520/640/780) centred on one line, light bar, grain, skeletons, sentinel;
  `feed/post` detail rebuilt. `FeedController::index` server-renders **page 1 of the ranked
  stream** (same frozen list the cursor slices — D10) with the casting strip injected at its slot;
  `/feed/items` (web only) now also returns `html[]` frames + `casting_html`, keeping the API
  mirror pure JSON.
- **`resources/js/feed.js`** (registered in app.js): `feedStream` (IntersectionObserver sentinel,
  600px rootMargin, double-fetch guard, casting injection at `casting_positions`, light-bar
  `--scroll-progress`), `postFrame` (optimistic glint + shadow-sweep restart trick, rollback on
  failure, 401 → inline sign-in prompt with talent/brand chips, share-copy, author delete via
  `$confirm`), `castingCard` (optimistic follow toggle). Comments open a placeholder mount — the
  full inline thread lands in 5O.
- Verified live: `/feed` renders 200 with the Daylight surface, notched frames and the casting
  strip. Suite 510, Vite build green.

## 2026-07-22 — Prompt 5L: the Daylight design tokens (feed-scoped); suite still 510 green

- **`resources/css/daylight.css`** — the feed's second design system, scoped under
  `[data-surface='daylight']`: the scope **re-reads the core tokens** (--bg/--surface/--ink/
  --accent/fonts…) from the Daylight palette so every existing utility renders Daylight inside the
  feed and cloud/graphite/teal everywhere else; Daylight-only values live under `--dl-*`. Light
  ("Daylight") + dark ("Night Shoot") palettes exactly per playbook §3; **shadows are blue, never
  grey** (`--dl-shadow-frame/raised/lifted`, and `.frame-shadow` uses `filter: drop-shadow` so the
  shadow follows the **notched silhouette** — clip-path clips box-shadow, hence the two-class
  compose documented in docs/design-system.md).
- **Signature classes:** `.frame` (6px radius, 14px 45° inline-end notch, RTL-mirrored),
  `.frame-shadow(--raised)`, `.glint` + `dl-glint-pop` + `dl-shadow-sweep` (the 300ms light-source
  move), `.credits`, `.feed-chip` (`--chip-glint` hover-only provider colour) / `.feed-mention`,
  `.grain` (2.5% feTurbulence), `.light-bar` (`--scroll-progress`), `.skeleton` (inline-axis sweep
  → locale-aware), `.well`, `.seam`; type roles `.t-meta/.t-body/.t-comment/.t-note/.t-display`
  (Fraunces `SOFT`+`WONK` axes; Amiri display in RTL; Latin lining numerals in both locales); 2px
  daylight focus ring; blanket `prefers-reduced-motion` collapse. Tailwind names registered:
  `bg-sand/sand-deep/chalk/veil`, `text-daylight`, `border-seam`.
- **Fonts** load from `feed/partials/daylight-head.blade.php` on feed pages only (Fraunces,
  Instrument Sans, Geist Mono, Amiri, IBM Plex Sans Arabic — Google Fonts `display=swap`, matching
  the app-wide pattern; the playbook's self-hosting is documented as a deploy-time swap).
- **`GET /design/feed-tokens`** renders every token/type/frame/state in both themes × both
  directions. **`docs/design-system.md`** created (the fixed docs set gains the file the playbook
  mandates). Vite build green; suite unchanged at 510.

## 2026-07-22 — Prompt 5K: full HTTP surface + /api/v1 Sanctum mirror + Scribe; 510 tests green

- **Web surface completed:** GET /posts/{post}/fragment (`{html}` envelope, shared partial
  `feed/partials/post` — the swap contract 5M builds on), GET /talents/{username}/posts (published,
  pinned first, paginated 12), POST /posts/{post}/report + /comments/{comment}/report
  (`StoreReportRequest`, once-per-target via PostReportPolicy). `LinkChipResource` +
  `NotificationResource` formalized and wired into their controllers.
- **`/api/v1` mirror** (`routes/api.php`, registered in bootstrap): every feed read/write through
  the SAME controllers/requests/actions/Resources — reads public, writes `auth:sanctum`;
  `CurrentActor` now also resolves the sanctum tokenable; `RequireActor` skips session capture on
  stateless requests; the feed seed derives from actor/fingerprint+hour when no session exists
  (mobile clients keep their seed via the cursor). 29 API routes.
- **Scribe regenerated** (`php artisan scribe:generate`): /docs UI, `openapi.yaml`,
  `collection.json` under storage/app/private/scribe. `docs/api.md` gains the full feed route table
  and reports **190 endpoints** (130 pre-feed + 31 feed web + 29 API).
- **7 new tests** (fragment envelope, talent-posts pinned-first, report accept/duplicate-403/bad-
  reason-422 incl. comments, guest API reads, Sanctum-token write + unauthenticated 401, shared
  validation pipeline web↔API). **Suite: 510 passed (1808 assertions).**

## 2026-07-22 — Prompt 5J: database notifications with aggregation + suppression; 503 tests green

- **`App\Feed\FeedNotifier`** — the single dispatch point owning every rule: never self-notify;
  repeat likes on one target inside 60 min (config `feed.notifications.aggregate_minutes`)
  **collapse into one record updated in place** (actor count + newest actor, re-marked unread);
  a mention **suppresses** the duplicate comment/reply notification for the same event; refollow
  inside the window stays silent; every payload deep-links to its post/comment anchor; failures log
  to the feed channel and never block the write. Wired post-commit into ToggleLike (created-flag),
  CreateComment, CreatePost, ShareProjectAsPost (mentions + the follower fan-out, chunked) and
  ToggleFollow (new-edge detection).
- **Eight queued classes** (`app/Notifications/Feed`, database channel, `afterCommit()`): PostLiked,
  CommentLiked, PostCommented, CommentReplied, MentionedInPost, MentionedInComment, NewFollower,
  ProjectShared — thin subclasses of `FeedNotification`; payloads store an **English message key +
  params** (never pre-rendered text) and render through `__()` in the viewer's locale at read time;
  ~40 feed strings added to `lang/ar.json`.
- **Endpoints** (require.actor): GET /notifications (paginated 20, localized rows), POST
  /notifications/read (all or `ids[]`), GET /notifications/unread-count. `notifications` table
  migration (uuid + notifiable-morph indexes) — dev DB migrated.
- Debugging note: a fatal (`Queueable` already defines `$afterCommit`) was invisible because the
  agent output wrapper swallowed it — `PAO_DISABLE=1 ./vendor/bin/pest` is the escape hatch when a
  run goes silent.
- **7 new tests** (self-guard + two-liker aggregation into one record, mention-suppression, reply
  fan to post+parent authors, new-follow-only, Project-share follower fan-out, en/ar rendering,
  inbox/badge/mark-read endpoints + guest 401). **Suite: 503 passed (1786 assertions).**

## 2026-07-22 — Prompt 5I: the feed ranking engine + casting strip; 496 tests green

- **`FeedService`** (ADR-F5, D9/D10/D14): the candidate ID list is computed once per feed session
  and cached 10 min keyed viewer+seed; pages are slices of the frozen list. Tier 1 = followed
  authors (30 d) in a **seeded shuffle** (`md5(post_id . seed)` — reproducible); tier 2 = discovery
  scored by skill affinity (talent: own Skills; brand: skills of followed talents; guest: none),
  log-damped engagement velocity, new-author freshness, and half-life recency decay, seed-jittered
  tie-breaks. Interleave injects one tier-2 item after every 4 tier-1; the **author diversity cap**
  (≤2 consecutive; displaced posts move down via a deferred queue, never dropped) runs last. Every
  6th global position is a casting slot. Hydration is ONE whereIn + `FIELD()` with the full eager
  graph + constrained `viewerLike`. Cursor = opaque base64 `{seed, offset}`. All knobs in
  `config/feed.php` `ranking` — zero magic numbers.
- **`DiscoverTalentsService`:** casting strips of 12 published, unfollowed talents ranked by skill
  overlap + activity + completeness; candidate pool cached 5 min; shown IDs tracked per
  viewer+seed so a strip never repeats within a session.
- **Endpoints:** GET /feed/items (items + casting_positions + next_cursor + total) and
  GET /feed/casting (strip rows with `followed_by_viewer` via the 5H bulk map). Guest seed derives
  from the session ID — two visitors differ, one visitor's refresh is stable (D14). New
  **`PostResource`** (author, safe body_html, media derivative URLs with pre-conversion fallbacks,
  skills, the ADR-F2 live-Project-or-snapshot payload, counters, viewer state, permalink).
- **7 new tests:** seed determinism (same seed = same list, other seed = same SET reordered),
  discovery-injection invariant, guest/zero-follow = full discovery set, diversity cap (runs > 2
  only at an unavoidable tail; nothing dropped), **pagination stability — 50 posts paged with
  mid-scroll inserts, zero dupes/omissions**, casting no-repeat, and the < 25-query page budget.
  **Suite: 496 passed (1762 assertions).**

## 2026-07-22 — Prompt 5H: follow system — bulk state map + audience listings; 489 tests green

- **`FollowService::isFollowing`** — THE bulk follow-state check: one query for any mixed
  talent/brand set, returned as a `"type:id" => bool` map (guests get an all-false map, same keys).
  No feed surface resolves a follow button per-row.
- **Audience listings:** GET /talents/{username}/followers and /following — paginated 24, actor
  identity rows (`ActorResource`) each with `followed_by_viewer` resolved through the bulk map;
  unpublished talents 404. `ToggleFollow` + POST|DELETE /follow/{type}/{id} + edge rules + the
  self-follow policy landed in 5C; follow notifications join in 5J.
- **4 new tests** (repeated-call idempotency with counter accuracy both directions, the mixed-set
  keyed map incl. config-flag brand follow + guest map, followers pagination + viewer flag,
  following list + unpublished 404). **Suite: 489 passed (1735 assertions).**

## 2026-07-22 — Prompt 5G: comment threads, replies, likes on both, rate limits; 485 tests green

- **Actions:** `ReplyToComment` (thin — delegates to `CreateComment`, the single write path owning
  the depth-two re-attach), `UpdateComment` (window + full re-parse + `edited_at`), `DeleteComment`
  (soft delete → tombstone when replies survive). `ToggleLike` already handled posts AND comments
  polymorphically (5C) — comment endpoints now exposed: POST|DELETE /comments/{comment}/like.
- **Thread listing** (`CommentController`): top-level comments paginated 10 newest-first, each with
  up to 3 preview replies + `remaining_replies`; **tombstones included** (trashed parents with
  surviving replies stay; childless deletions drop out); replies paginate separately
  (GET /comments/{comment}/replies, oldest first); PATCH|DELETE /comments/{comment}. Every row
  carries `liked_by_viewer` via a constrained `viewerLike` eager-load (guest constraint `1=0`) —
  **nothing per-row**, proven by a same-shape/different-size fixed-query-count test.
- **Resources:** `app/Http/Resources/Feed/{ActorResource, CommentResource}` — safe `body_html`
  through BodyRenderer, tombstone branch, edit-window flag, viewer state. 5K reuses both.
- **Named per-actor rate limiters** (AppServiceProvider, keyed actor-morph:id else IP):
  feed-posts 10/h, feed-comments 10/min, feed-likes 60/min, feed-follows 60/min, feed-links 20/min,
  feed-mentions 30/min — all feed routes switched to them; `ThrottleRequestsException` now renders
  the friendly 429 envelope.
- **8 new tests** (listing order/previews/remaining, tombstone survival + childless drop, reply
  pagination, bulk viewer-like state, fixed query count, comment like counters, brand edit window,
  the 10/min comment cap → friendly 429). **Suite: 485 passed (1719 assertions).**

## 2026-07-22 — Prompt 5F: post write path, Project share, media, FeedSeeder; 477 tests green

- **Five author Actions** (`app/Actions/Feed`, all transactional): `CreatePost` (draft → skills
  own-only → ordered `post_media` uploads → parse/sync mentions+links → stamp `published_at` →
  transition Published; PostObserver counts), `ShareProjectAsPost` (ownership + published-profile
  gates, frozen `shared_snapshot` {title translations, cover path, shared_at} — ADR-F2),
  `UpdatePost` (window re-check, full re-parse, `edited_at`; **accepts no files — media immutable**),
  `DeletePost` (soft delete; comments survive), `TogglePinPost` (max one — pinning unpins the
  previous in-transaction).
- **Validation:** `StorePostRequest` (substance rule: body or media; 1–10 items;
  `PostMediaFile` per-file rule — jpg/png/webp ≤ 8 MB, mp4/mov ≤ 100 MB, 60 s via ffprobe **when the
  binary exists** — an explicit operational note, not a silent skip), `UpdatePostRequest`,
  `ShareProjectRequest`; skill ownership validated inside the actions (shared web/API/replay).
- **Endpoints:** POST /posts (throttle 10/h), PATCH|DELETE /posts/{post}, POST /posts/{post}/pin,
  POST /projects/{project}/share. `Post` gains the queued `poster` video-frame conversion (ffmpeg on
  the worker).
- **`FeedSeeder`** — the UI-evaluation dataset: tops up to 40 published talents / 15 brands (config
  `feed.seeder`, tests shrink it), 300 posts ≈ 40/45/15 note/media/share with **plausible Arabic and
  English bodies**, @mentions + Instagram/Behance/TikTok links through the real parser/sync
  pipeline, GD-generated editorial imagery, a brand→talent + talent→talent follow graph, likes and
  threaded comments. Counters are **reconciled at the end** — DatabaseSeeder's `WithoutModelEvents`
  mutes the observers, so the seeder cannot trust them. Registered in DatabaseSeeder; dev DB seeded
  (300 posts / 247 follows / 549 comments / 1782 likes).
- **11 new tests** (note publish pipeline incl. counters, media post via medialibrary, brand 403,
  substance/oversize/foreign-skill 422s, share + snapshot + refusals, window edit/lock, delete
  uncounting with surviving comments, single-pin invariant, seeder consistency — a post-seed
  reconcile finds zero drift). **Suite: 477 passed (1681 assertions).**

## 2026-07-22 — Prompt 5E: link provider registry + preview enrichment with SSRF guard; 466 tests green

- **`App\Feed\Links`:** `LinkProvider` contract (key / matches / parse — parse works from the URL
  string alone, zero network: the chip IS the parse, ADR-F4), `AbstractProvider` (host matching
  tolerant of www/m/mobile subdomains, clean segmentation), and **14 drivers** — Instagram, TikTok
  (+vm short links), YouTube (+youtu.be), Behance, Vimeo, X/Twitter, LinkedIn, Pinterest, Dribbble,
  Spotify (canonicalized), SoundCloud, Facebook (profile.php/watch/pages), Snapchat, Generic
  (matches everything, registrable-domain label). `LinkProviderRegistry` resolves first-match from
  `config/feed.php` (`providers` — adding one is a class + a config line), generic always last.
- **`ResolveLinkPreview` upgraded in place** (same signature): normalize → SHA-256 dedupe →
  driver parse → pending row → `FetchLinkPreviewJob::dispatch()->afterCommit()` on first sight only.
- **`FetchLinkPreviewJob`** (queued, 3 tries, 10/60/300s backoff): oEmbed for trusted providers
  (config `enrichment.oembed`) else Open Graph scrape; success → title/description/image + `ok` +
  `expires_at` +30d; terminal failure → `failed`, chip stays, logged quietly — never user-facing.
  **SSRF guard (`UrlGuard`)**: http/https only, DNS-resolved with private/loopback/link-local/
  reserved ranges rejected **before connecting and on every redirect hop** (manual redirects, max
  3), 5 s timeout, 512 KB hard cap, remote HTML parsed never rendered, `embed_html` stored only for
  the youtube/vimeo allowlist and reduced to a single vetted https iframe.
- **`POST /links/preview`** (require.actor + throttle 20/min): instant chip payload on paste;
  deduplicates and queues enrichment once.
- **Tests never touch the network:** phpunit queue connection switched `sync` → `database` (a sync
  queue executed the enrichment job inline mid-test and fetched behance.net for real). 39 new
  assertions across 33 driver dataset cases, registry order, 7 SSRF rejections, job-never-connects,
  OG + oEmbed enrichment (guard stubbed), sanitized-iframe proof, terminal failure, endpoint dedupe/
  guest/invalid. **Suite: 466 passed (1641 assertions).**

## 2026-07-22 — Prompt 5D: rich-text parsing — mentions, links, bidi-safe rendering; 419 tests green

- **DTOs** (`app/Data/Feed`): `ParsedBodyData { raw, mentions[], urls[] }`, `MentionData`
  (morph + id + parse-time username + `startOffset`/`length`), `UrlData` — the typed contract
  between parser, sync actions and renderer.
- **`RichTextParser`** (`app/Services`): extracts `@username` tokens (never from e-mail addresses —
  lookbehind guard) and http/https URLs (trailing sentence punctuation trimmed) with
  **multibyte-correct character offsets** (byte→char conversion; proven against Arabic bodies).
  Usernames resolve `talents.slug` first then `brands.slug` in two whereIn queries; unresolved
  tokens stay plain text. The parser never throws.
- **`SyncMentions` / `SyncLinks`** (`app/Actions/Feed`): idempotent delete-and-re-insert on create
  AND edit (offsets shift — no diffing, ADR-F3); enforce the limits (10 mentions/post, 5/comment,
  5 URLs/body) as validation errors naming the limit. `SyncLinks` joins each URL to the deduplicated
  `link_previews` row via **`ResolveLinkPreview`** (normalize → SHA-256 `url_hash` → firstOrCreate
  pending; 5E swaps in the driver registry + enrichment job behind the same signature). Both wired
  into `CreateComment` inside its transaction.
- **`BodyRenderer`**: escape-first, then inject — mentions render the mentioned entity's **current**
  Username (rename-proof, ADR-F3; dead entities degrade to plain '@'), links render as chip anchors
  (`rel="noopener noreferrer nofollow ugc"`, provider data-attribute, handle-or-truncated-URL
  label). **Bidi is first-class:** every injected token is `<bdi>`-wrapped with LRI…PDI Unicode
  isolates around emitted raw text; covered by a mixed Arabic/Latin test asserting isolate
  positions and XSS-escape.
- **`GET /mentions/search?q=`** (throttle 30/min): talents + brands matched on Username/display
  name, published only, 8 results grouped by type (talents first), each with type/id/username/
  display_name/avatar_url.
- **9 new tests** (offsets Latin + Arabic, precedence, e-mail guard, sync idempotency + edit
  re-parse, both limits as 422s, renderer safety/isolates/rename, search shape). **Suite: 419
  passed (1509 assertions).**

## 2026-07-22 — Prompt 5C: Actor resolution, feed policies, guest read + intent replay; 410 tests green

- **One concept of "who is acting".** New `App\Feed\Actor` interface (morph alias, key, Username,
  display name, avatar) implemented by `Talent` and `Brand`; `App\Feed\CurrentActor` resolves the
  acting identity across guards in priority talent → brand (admins/guests → null), deliberately
  stateless (guard user caches suffice; instance state would leak across requests in tests/Octane).
  Nothing in the feed calls `Auth::guard()` directly — controllers extend `FeedBaseController`
  (actor + a Gate bound to actor / admin / guest-null).
- **Middleware:** `resolve.actor` (never rejects; shares `$currentActor` with views + request) and
  `require.actor:<action>` (guest → **401 envelope carrying `data.intent` {action, target_type,
  target_id, payload}**, intent stored in session; admin-only session → 403 — admins moderate, never
  participate). Registered in `bootstrap/app.php`.
- **PendingIntent replay (ADR-F8):** `App\Feed\PendingIntent` captures the rejected write (comment
  body included) and replays it **exactly once** on the `Login` event (`ReplayPendingIntent`
  listener, auto-discovered) — cleared before execution, failures log to the feed channel and never
  disturb the login.
- **Policies (auto-discovered):** `PostPolicy` (guests view published; create = talents only —
  ADR-F1's one-line flip lives here; update = author in the 15-min window; delete = author anytime
  or moderating admin — deleting your own content is deliberately NOT windowed, deviating from the
  playbook's 5C wording in favour of D12/5F; pin = author), `CommentPolicy`, `LikePolicy`,
  `FollowPolicy` (self-follow denied), `PostReportPolicy` (once per reportable per reporter).
- **First live endpoints** (`routes/feed.php`, inside the locale group before the `/{slug}`
  catch-all): GET /feed + /posts/{post} (interim Blade shells — Daylight replaces them in 5L–5M;
  unpublished 404s, never 403 — existence not leaked), GET|POST /posts/{post}/comments,
  POST|DELETE /posts/{post}/like, POST|DELETE /follow/{type}/{id}. Backed by minimal-but-final
  `app/Actions/Feed` Actions — `ToggleLike` (race-safe on the unique index, onlyLike/onlyUnlike),
  `ToggleFollow` (edge rules + config flag, 422 via InvalidArgumentException), `CreateComment`
  (depth-two re-attach + auto-@mention) — which 5D/5G/5H enrich in place.
- **14 new tests** (guest reads, 401-intent envelopes, admin 403, idempotent like/unlike,
  follow edges + self-follow, reply re-attach, and end-to-end replay of like/follow/comment incl.
  the once-only guarantee). **Suite: 410 passed (1469 assertions).**

## 2026-07-22 — Prompt 5B: feed schema, models, state machines, counters, enforced morph map; 396 tests green

- **Migrated the ten feed tables** (`2026_07_22_000100`) exactly per schema-master §7 — posts /
  post_skill / comments / likes / follows / mentions / link_previews / linked_urls / post_reports /
  feed_seen, every mandated composite index included — plus the counter columns on `talents` and
  `brands` (ADR-F6).
- **Nine models + two state machines.** `Post` (HasMedia `post_media`, queued WebP conversions,
  `scopePublished`, `isEditable` 15-min window), `Comment` (two-level, tombstone-aware), `Like`,
  `Follow` (append-only), `Mention` (offset-addressed, ADR-F3), `LinkPreview`/`LinkedUrl` (ADR-F4),
  `PostReport`, `FeedSeen`; `PostState` (draft→published⇄hidden→removed) and `CommentState` under
  `app/States`. `Talent` + `Brand` gained the shared `HasFeedIdentity` concern (posts/followers/
  following/likesGiven + feed identity readers). Factories for Post/Comment/Like/Follow.
- **Counters are transactional (ADR-F6):** new `CounterService` (sole mutator, zero-clamped) driven by
  `#[ObservedBy]` observers on Like/Comment/Follow/Post — `posts_count` tracks the published boundary
  in both directions, matching the reconcile definition. New `feed:reconcile-counters` command
  (scheduled daily 03:10, drift logged to the new `feed` log channel; the self-referential
  `replies_count` reconciles via a derived-table join around MySQL error 1093), covered by a
  corruption-and-repair test.
- **Enforced aliased morph map (ADR-F1):** `App\Support\MorphMap` (every model + media/role/
  permission, snake_case aliases) enforced in `AppServiceProvider`; data migration
  `2026_07_22_000200` rewrote legacy FQCN morph values across media, activity_log, permission pivots,
  sanctum tokens and contract morphs (reversible). Two activity-log tests updated from FQCN literals
  to `getMorphClass()`. Dev DB converted + `permission:cache-reset`; `migrate:fresh --seed` exits 0.
- **Suite: 396 passed (1427 assertions)** — 16 new feed tests (schema/relations/morphs, state
  transitions, counter integrity, reconcile repair + clean pass, edit-window locking, unique-index
  idempotency backstops).

## 2026-07-22 — Phase 5 kickoff (Prompt 5A): feed spec, schema doc, ADR-F1…F8 — docs only, no code

- **Canonical feed spec written.** New `docs/specs/feed-spec.md`: a public feed where talents publish
  posts (`note` / `media` / `project_share`), and talents + brands follow, like, comment (two-level,
  ADR-F7) and reply; guests read everything, write intent is captured and replayed after auth
  (ADR-F8). Covers the entity model, post/comment lifecycles as spatie state machines, the full
  guest/talent/brand/admin permission matrix, the two-tier seeded ranking spec with cached
  candidate-ID pagination (ADR-F5), mention resolution (ADR-F3), link-chip rules (ADR-F4), the
  notification matrix, moderation/reporting (auto-hide threshold), and open questions Q1–Q7.
- **`docs/specs/schema-master.md` updated in place — 35 → 45 tables.** New §7 Feed: `posts`,
  `post_skill`, `comments`, `likes`, `follows`, `mentions`, `link_previews`, `linked_urls`,
  `post_reports`, `feed_seen` with every column, type, nullability, default, index and FK, plus the
  counter columns on `talents` (followers/following/posts) and `brands` (following). Also reconciled
  §5 with implemented reality (ADR-F: `brand_projects.talent_type_id` + `budget_is_public`; the
  `brand_project_talent_types` pivot was never built) and added the feed edges to the quick-map.
- **ADR-F1…F8 appended to `docs/decisions.md`** (polymorphic author gated by policy; live-reference +
  `shared_snapshot`; mentions as ID+offset; zero-network link parse with async enrichment + SSRF
  guard; cached candidate-ID pagination; in-transaction counters + nightly reconcile; two-level
  comments; guest intent replay). ADR-F2 carries the stakeholder-confirmation flag.
- **No application code, no migrations, no tests touched** — suite stays green as of the last run
  (378). Next: Prompt 5B (migrations, models, states, counters).

## 2026-07-21 — Moderation toggles, locale-aware Ajax, translated statuses, drawer polish; 378 tests green

A batch of moderation + i18n improvements from live review.

- **Discovery redesigned as an editorial talent mosaic.** The public `/discover` grid went from uniform
  4:3 text-under-image cards to an image-forward, varied-height **masonry** (`columns-1/2/3`,
  `break-inside-avoid`): each talent is a full-bleed portrait with a gradient scrim, the name/craft/
  location overlaid at the foot, a frosted primary-skill pill + a views stat up top, and a cinematic
  hover (portrait zoom, deepening scrim, card lift, "View profile →" reveal). Card heights vary
  deterministically by `id` (stable — no layout shift) for a lookbook rhythm; the no-avatar case is a
  rich graphite placeholder with a watermark initial so it reads identically to photo cards. Skeleton
  loaders mirror the new mosaic. **Blade + tokens only** — no controller/query/resource changes (the card
  resource already exposed every field), so no new queries or N+1. Filter machinery untouched. AR added;
  dark/light + RTL supported via tokens + logical properties. Verify RTL live.
- **State-aware moderation toggles.** Suspend and Unpublish are now toggles that reflect the account's
  actual state: a suspended talent/brand shows **Reinstate** (→ unpublished), a hidden one shows
  **Publish**, a live one shows **Unpublish** (Publish hides while suspended — reinstate first). New
  `publish()`/`unsuspend()` on both moderation services (audited: `talent.published`/`talent.unsuspended`
  + brand equivalents); controller actions added. This fixes the bug where an already-suspended row still
  offered "Suspend". Every toggle — plus Approve/Reject/Verify — now goes through the confirm modal.
  Shared `partials/account-actions` keeps the row and drawer identical.
- **Project visibility is now a state-aware toggle too.** In Projects oversight, "Make private" is paired
  with a **Make public** reverse (new `ProjectOversightService::makePublic()`, audited
  `project.made_public`; `moderation.projects.action` gains the `public` case) — the two swap on
  `is_public`, and both now go through the confirm modal (previously "Make private" fired with no
  confirmation and had no way back). **Cancel** stays one-way — `Cancelled` is a terminal state with no
  reverse transition — so it now only shows while the project is active (draft/open/in_progress). Applies
  to both the row and the detail drawer. Also disambiguated the budget-visibility tag: the row's bare
  "private" (the `budget_is_public` flag, which sat confusingly next to the "Make private" project toggle)
  now reads **"budget hidden"**, and the drawer's "(private)" reads **"(hidden from public)"** — so the
  budget flag can't be mistaken for the project's own listing state.
- **Locale-aware Ajax (root-cause fix).** `http.js` now prefixes same-origin requests with the active
  locale (a `<meta name="locale-prefix">` from `design-head`), so a page opened under `/ar` gets Arabic
  content back instead of English. This fixes two reports: opening a contract from the Arabic admin list
  no longer drops to the English page (nav links localize via `window.fama.localizeUrl`), and Ajax-loaded
  content (skill/block names, etc.) comes back localized.
- **Contract timeline system-events now localize.** System-event messages ("Brand submitted Project
  brief.", "Talent delivered the work for …", "Contract completed.") carried frozen English bodies. They
  now store a structured `meta` ({key, params}) on `contract_messages` (new nullable column);
  `ContractMessageResource` renders the body in the VIEWER's locale from that meta (English `body` kept as
  the fallback for pre-meta rows). All generation stays in `ContractProgression` (a step-type→verb map)
  plus the skip/reject/complete call sites — no step-handler churn. Actor + step labels come from a shared
  `App\Support\ContractLabels` used by both the resource and `design-head` (one source of truth).
  Reseeded so the demo timeline reads Arabic on `/ar`. +2 tests.
- **Contract-flow names, applies-to categories & settings flags translated.** `$flowLabel()` localizes
  the three seeded flow names (Standard Booking → الحجز القياسي, …; custom flows keep their stored name);
  `$categoryLabel()` translates `applies_to` (model/crew/creative/all) on the flow cards; and the three
  Settings feature-flag labels got Arabic. Also: the global "All reviews" row now names the entity being
  reviewed (brand for a brand review, talent for a talent review) with a "by <reviewer>" note.
- **Translated contract steps, actors & categories.** `$stepLabel()` / `$actorLabel()` /
  `$categoryLabel()` magics (label maps from `design-head`) localize the contract-intervention stepper
  (Project brief → موجز المشروع, Deposit → الدفعة المقدمة, …), the BRAND/TALENT/SYSTEM actor tags, the
  contracts-list current-step chip, and the skills-catalog category badge. Step labels key off the stable
  step `key` with a fallback to the stored name for custom flows. Verified live on `/ar`. (Stored
  system-message bodies in the timeline remain in the language they were written — translating historical
  audit text would need a structured key+params store, noted as future work.)
- **Translated statuses.** A `$statusLabel()` Alpine magic (backed by a Blade-provided label map, since
  JS can't call `__()`) localizes every admin status pill — moderation, contracts, flows. Block catalog
  now shows localized block names + availability ("By category: Modeling") and content source.
- **Search + filters on every moderation queue.** A debounced search box (name/email/username for
  accounts; body/reviewer for reviews; title/brand for projects) and a queue-aware status filter, all
  server-side with pagination preserved and a Clear control.
- **Detail drawer redesigned** (premium): a gradient hero with avatar/logo + status pills + "Open public
  page", sectioned body (About / Ratings / Skills / Budget / Contact / Details), and a divided fact grid.
  **Email/phone/website moved to a dedicated Contact section** — mailto/tel links, LTR, icons, truncated
  (fixes the mid-word email wrap). The whole row is now clickable to open the drawer (buttons stop
  propagation; keyboard-accessible).
- **Block catalog locked-field UX.** The key/content-source fields that lock once a block is in use now
  show a 🔒 + a "Locked because talents already use this block" hint and a clearly disabled style — they
  no longer look broken (this was mistaken for an Arabic bug).
- i18n: ~55 EN/AR strings. **Verified live** (EN + `/ar`): toggles reflect state, row-click opens the
  drawer, the Contact section renders the email cleanly, the contract link keeps `/ar`, and status pills
  render in Arabic. Tests +5 (toggles + search); full suite **378/378 green**. No git.

## 2026-07-20 — Confirmation dialog for every destructive action; 373 tests green

Every delete / remove / cancel now goes through ONE professional confirmation modal instead of firing
immediately (or via ad-hoc inline confirms).

- **`$confirm` — a promise-based global dialog** (`resources/js/confirm.js`): an Alpine store + `$confirm`
  magic; `resources/views/components/confirm-dialog.blade.php` renders it once per dashboard layout
  (admin/talent/brand), teleported to `<body>`. Backdrop fade + panel scale animation, `role="dialog"` +
  `aria-modal`, ESC/backdrop dismiss, focus moves to the safer **Cancel** button on open and is restored
  on close, a minimal Tab loop keeps focus inside, danger vs accent tone, RTL-aware, `motion-reduce` safe.
- **Wiring pattern keeps copy localized:** each destructive button is
  `@click="$confirm({ title, message, confirmLabel }).then(ok => ok && <existingAction>())"` — the action
  methods are untouched, and the `__()` strings live in Blade. Two ad-hoc inline confirms (talent
  skill-removal, brand project-delete) were replaced with the unified modal.
- **Covered:** admin — flow-step remove, account remove, contract cancel, moderation account delete
  (row + drawer) and project cancel (row + drawer); talent — avatar / block / skill / content-item
  remove; brand — gallery image, social handle, project media, project delete. The Breeze account-delete
  keeps its own password-confirm modal (stronger); staging-only "remove pending file" is not gated.
- i18n: +29 EN/AR strings. **Verified in-browser:** the modal opens with the specific item name
  ("Cancel this project? — Autumn Menu Launch"), "Keep it" dismisses leaving the project untouched, and
  the promise resolves true on confirm / false on dismiss. Full suite **373/373 green** (+2 render tests).
  No git.

## 2026-07-20 — Account-type registration, admin account creation, premium auth redesign; 371 tests green

**Self-registration now creates the right entity.** The public `/register` asks the applicant to choose
**Talent or Brand** (admin self-signup stays forbidden — ADR-I). A shared `AccountCreationService` is the
single source of truth for creating all three entities (talent → draft, brand → registered, both
unpublished so they enter the moderation/onboarding funnel; admin → User + roles). `RegisteredUserController`
creates via the service, scopes email-uniqueness to the chosen entity's table, and logs the new account
into its own guard. `Brand` gained the same guarded auto-slug `booted()` hook `Talent` already had.

**Admins can provision any account type.** `/admin/users` (now "Accounts") gained an account-type control
on its create form: Admin (roles + listed here), or Brand/Talent (via the shared service, surfacing in the
moderation queues). `AdminUserController::store` routes by type, scopes email uniqueness, only accepts
admin-guard roles for the admin type, and audits every create as `account.created` with the type in
properties. The nav/dashboard label is now "Accounts".

**Premium auth redesign.** Login and register are now standalone **split-screen** pages (their own
layouts, not the guest card): a graphite `bg-primary` showcase panel with accent glows + value copy on
one side, the form on the other.
- **Register**'s showcase and the "Full name/Brand name" label **adapt live** to the selected type, which
  is a pair of **rich selectable cards** (icon + title + description, accent ring when checked); passwords
  sit side-by-side; arrow-nudge submit.
- **Login** keeps the role-aware contract intact — native radios posting `role`, **Talent | Brand only**
  (staff use `/admin/login`), `?role=brand` pre-checks Brand (ADR-P), absent role defaults to **talent**,
  no-JS-safe. Forgot-password inline with the label; show-password toggle throughout.
- The secondary flows (forgot/reset/confirm/verify) keep the guest-layout card — transactional, still
  on-brand.
- i18n: +38 EN/AR strings. Verified live: EN + `/ar` RTL render, `?role=brand` pre-checks Brand, and real
  talent/brand register + login POSTs land on the right dashboard.
- **Tests:** `RegistrationTest` rewritten (talent/brand self-register lands on the right guard with the
  right starting state; admin type + missing type rejected; per-entity email uniqueness); `AdminUserTest`
  gains talent/brand provisioning + audit + permission cases; existing `/admin/users` posts updated with
  `account_type`. Full suite **371/371 green**. No git.

## 2026-07-18 — Dedicated staff login (/admin/login) split from the public login; 364 tests green

- **Staff console sign-in is now its own surface (ADR-D updated):** `GET|POST /admin/login`
  (`admin.login` / `admin.login.store`, `guest:admin`), `Admin\Auth\AdminLoginController`, and
  `AdminLoginRequest` — a subclass of `LoginRequest` that inherits the shared pipeline (rate limiting,
  single-active-identity across guards) with the guard pinned to `admin` and **no role field**. Lands on
  `admin.dashboard`.
- **Its own view, deliberately unlike the public login:** an enterprise split screen
  (`admin/auth/login`) — graphite `bg-primary` brand panel with accent glows, "Operations console"
  framing, "Restricted area" eyebrow, work-email + show-password field, `noindex`, theme/locale toggles,
  and a "Not staff? Go to the Fama login" escape hatch. Token-only, dark/light + RTL verified live.
- **The public login no longer serves admin:** the segmented control is Talent | Brand (2 segments),
  absent role now defaults to **talent**, and `LoginRequest` rejects `role=admin` server-side
  (validation error) — the public form cannot authenticate the admin guard at all. `?role=brand`
  (ADR-P) unchanged.
- **Area-aware guest redirects:** `redirectGuestsTo` sends `/admin/*` guests (locale-prefixed included,
  matched on path segments so a public slug containing "admin" can't be misrouted) to the staff login;
  everyone else to `/login`. Verified live: `/admin/dashboard` guest → `/admin/login`,
  `/talent/dashboard` guest → `/login`.
- **Tests:** Breeze's User-based `AuthenticationTest` now exercises `/admin/login`; `MultiGuardTest`
  gains public-rejects-admin + staff-login cases; new `AdminLoginTest` (own view, no role field, guest
  redirect, authed redirect away, invalid-credential errors, toggle). Full suite **364/364 green**.
  Live: staff POST → 302 → admin dashboard; AR staff login renders RTL with Arabic copy. No git.

## 2026-07-18 — Premium login + show-password toggle; 357 tests green

- **`<x-password-input>`** — shared password field with a show/hide eye toggle, now on every password
  form (login, register ×2, reset ×2, confirm). No-JS-safe (static `type="password"` is the default;
  Alpine's `:type` takes over), announces state via `aria-pressed` + a translated `aria-label`, toggle
  sits at the inline-end (RTL-correct), focus-visible ring.
- **Login redesigned premium:** the role select became a **segmented control of native radios** — no JS,
  still posts `role` with `old('role', request('role', 'admin'))`, so `?role=brand` (ADR-P) still
  pre-checks Brand and a live POST verified the flow end-to-end. Mono eyebrow + display heading,
  forgot-password moved inline with the password label, arrow-nudge submit (RTL-flipped), and a
  "New to Fama? Create an account" footer CTA.
- **Guest layout ambience:** soft token-only accent-weak glows (blurred, `pointer-events-none`) behind a
  `rounded-2xl` card — reads premium in light and dark without new colors.
- i18n: +5 EN/AR strings. Tests updated for the radio control (`checked` not `selected`) + a
  show-password render test; full suite **357/357 green**; `/ar/login` verified RTL. No git.

## 2026-07-16 — Auth + error pages on the Fama design system; 356 tests green

Presentation only — no auth logic, routes, guards, validation, or request classes were touched (the
untouched Breeze feature suites passing unchanged is the proof).

- **Breeze low-level partials retokened once, fixing every form:** `input-label`, `text-input` (new
  `error` prop → `aria-invalid` + danger border), `input-error` (`role="alert"`), `auth-session-status`
  (accent-weak banner, `role="status"`), `primary-button` (matches `x-ui.button` primary — rounded-pill,
  `bg-primary`/`text-on-primary`, focus-visible ring). All stock indigo/gray classes gone from auth.
- **Guest layout rebuilt on the Fama design:** slim public-style header (wordmark + `x-theme-toggle` +
  `x-public-locale-switcher` now available on auth pages), centered `bg-surface` card (`shadow-e2`),
  mono footer strap, and a reduce-motion-aware entrance via the existing `animate-fade-in-up` layer.
- **All six auth views restyled** (login, register, forgot/reset password, confirm password, verify
  email): display-face headings + subcopy, token inputs with inline error states, full-width primary
  submit. **Login's role-aware control is preserved exactly** — same `role` select posting the guard,
  `old('role', request('role', 'admin'))`, so `?role=brand` (ADR-P) still pre-selects Brand and absent
  role defaults to admin (verified live + by test).
- **Branded error pages the app now owns:** `errors/{403,404,419,429,500,503}` on a shared
  `errors/layout` — deliberately dependency-light (no Alpine, no data queries; the dashboard link
  resolves via `rescue()` because sessions may be unavailable mid-failure). Each: mono eyebrow + big
  display code + human title + one line of guidance + "Back to home" (+ "Go to dashboard" when any guard
  is authenticated). Verified live: real 404 (EN + AR/RTL) and a real `artisan down` 503 render branded.
- **i18n:** 24 EN strings added to `lang/ar.json`; `/ar/login` verified RTL with Arabic copy.
- **Tests:** +6 (`AuthViewsTest` — role control renders, brand pre-selects on `?role=brand`, validation
  errors render, no stock-Breeze grays; `ErrorPagesTest` — HTTP 404 serves the branded page, all six
  views render code + home link). Full suite **356/356 green**. No git.

## 2026-07-16 — Moderation detail drawers + admin UI polish; 350 tests green

- **Drawer open/close is animated** (follow-up, same day): backdrop fades (with a 2px blur) and the
  panel slides from the inline-end edge (`translate-x-full`, flipped `rtl:-translate-x-full` for Arabic)
  via the house pattern — mount → `$nextTick` flips `drawerOpen` → CSS transitions run; on close,
  `detail` is cleared only after the leave so content doesn't blank mid-slide, and the overlay goes
  `pointer-events-none` while leaving (a departing overlay must never trap clicks — the CLAUDE.md
  teleport-modal lesson). **Motion spec:** the panel runs the sheet curve
  `cubic-bezier(0.32, 0.72, 0, 1)` (fast launch, long settle) with an asymmetric **500ms enter / 300ms
  exit**; the header/body/footer layer trails the panel by 80ms (450ms fade + 12px inline drift) for
  depth, and exits as a fast no-delay 150ms fade. `will-change-transform` keeps the slide composited.
  All of it collapses under `motion-reduce`. Tailwind v4 note: translate utilities set the native
  `translate` property, and v4's `transition-transform` covers `transform, translate, scale, rotate` —
  verified in the compiled bundle.
- **Detail drawers for every moderation queue.** Each queue row (talents, brands, reviews,
  brand-reviews, projects — and the global queue, which resolves per row kind) gains a **View** action
  opening an end-side drawer with the FULL record: talent (avatar, bio, skills, pricing, publish state,
  content counts, public link), brand (logo, description, credibility, counts), review (complete body +
  reviewer), brand review (three sub-ratings + average + contract reference), project (description, role,
  dates, budget — always visible to admins, tagged private). Five new `show*` endpoints on
  `ModerationController` (`GET /admin/moderation/{queue}/{id}`, `moderate-content`, withTrashed for
  talents/brands/projects), each a single eager-loaded query. The drawer carries the SAME kind-aware
  moderation actions (act → drawer closes → list reloads), ESC/backdrop close, `role="dialog"` +
  `aria-modal`, RTL-aware (`end-0` + logical borders). +5 tests in `AdminModerationTest`.
- **UI polish across every admin page.** New shared components `<x-admin.skeleton>` (pulsing loaders
  replacing every "Loading…" text) and `<x-admin.pagination>` (envelope-driven, `meta.pagination`) now on
  flows / moderation / contracts / blocks / activity / users. A shared Alpine magic **`$pill(status)`**
  maps every status to one semantic tone (success / warn / danger / neutral) so the same state reads the
  same colour on every list. Contract rows gain talent avatar (initials fallback), mono reference chip,
  and a current-step chip; users rows gain initials avatars + pill states; activity rows show the subject
  basename with hover; every page has an intro subtitle. **Bug found while polishing:** admin views used
  `text-ok`/`bg-ok`, tokens that do not exist in the design system (`success`/`success-weak` are the real
  ones) — "green" states had never rendered green; all swapped to real tokens.
- **i18n:** 48 more EN strings added to `lang/ar.json` (drawer labels + pre-existing admin gaps).
  Verified live: `/ar/admin/*` renders RTL with the Arabic strings.
- Full suite **350/350 green**; bundle rebuilt; `migrate:fresh --seed` exit 0. No git.

## 2026-07-16 — Admin reachability + block governance split (ADR-T); 345 tests green

**Audit (per target page, before changes):** Talent moderation — **exists** (suspend/unpublish/
soft-delete/restore, audited). Brand moderation — **partial** (verify/suspend/unpublish/delete all
existed in `BrandModerationService`, but the view never offered Unpublish). Brand-reviews queue —
**exists**. Global review queue — **missing** (only per-kind tabs). Projects oversight — **partial**
(status filter existed server-side with no UI; budget absent from the admin payload). Flow builder —
**exists** (snapshot semantics verified by tests). Contracts overview — **partial** (status filter only,
no current-step filter). Single contract — **exists** (thread + override/advance/nudge/reassign/cancel).
Navigation — **partial**: one generic Moderation link, no per-queue links, no Projects-oversight or
Skills/Activity/Settings/Users dashboard cards. Block catalog manager — **missing entirely**. And one
critical find: **`resources/js/admin.js` was never imported by `app.js`**, so every admin page shipped
as a dead shell in the browser (Alpine components unresolved) — invisible to HTTP-level tests and curl.

- **`admin.js` bundled** (`app.js` imports it; Vite rebuild verified the components are in the bundle).
- **Block Catalog Manager** (`/admin/blocks`, new seeded permission **`manage-blocks`**, super-admin):
  full CRUD over `block_types` — translatable name/description, icon, `availability`
  universal/by_category/by_type with pivot gates synced (stale gates removed on mode switch),
  `is_active` toggle with **grandfathering** (existing `profile_blocks` keep rendering; the talent picker
  already filters), `is_repeatable`, `default_layout`, `content_source`, `settings_schema` (must be
  well-formed JSON). **Guard rails:** `key`/`content_source` locked once in use (422 + locked inputs).
  `BlockCatalogService` re-authorizes + audits every mutation (`catalog` log).
- **Skills Template Manager refactored** (ADR-T): `/admin/skills` now edits ONLY the ordered
  preselection per skill, offering exactly the catalog-eligible blocks
  (`ProfileBlockService::isEligibleForScope` — same predicate as the talent picker); drag to reorder;
  stale preselections flagged "no longer eligible" and removable. Server-side,
  `updateDefaultBlocks` rejects ADDING an ineligible key, allows reorder/removal around a stale one.
- **Global review queue** (`/admin/moderation/all-reviews` + an "All reviews" tab): pending talent
  `reviews` ∪ `brand_reviews` in one honestly-paginated chronological list (UNION drives the page, two
  kind-hydration queries, no N+1); each row's approve/reject routes to its kind's endpoint.
- **Projects oversight**: status filter UI (draft/open/in_progress/completed/cancelled) over the existing
  `forStatus`; the admin payload now always carries the budget, tagged **private** when
  `budget_is_public` is false.
- **Contracts overview**: filter by the CURRENT step's key (whereHas on `currentStep`), fed by one
  distinct query over the snapshotted `contract_steps`.
- **Brand moderation**: the Unpublish action the service always had is now offered in the queue UI.
- **Navigation rewritten** (grouped, permission-gated, real routes only): Dashboard · Moderation (Talent
  profiles / Brands / Brand reviews / Global review queue — each deep-links its tab via `?queue=`,
  server-validated, URL kept in sync via replaceState) · Marketplace (Projects oversight / Contracts) ·
  Configuration (Contract flows / Skills templates / Block catalog) · System (Activity log / Settings /
  Admins). Active item marked (`aria-current`), queue-aware.
- **Dashboard home rebuilt** as the reachable landing: moderation queue counts (pending talent profiles =
  created/draft, pending talent/brand reviews, brands awaiting verification) each linking its queue with
  "All clear." empty states; contracts card (active + whose-turn breakdown); projects card (open /
  in_progress / completed); governance quick links (flows, skills, blocks, settings, admins); recent
  activity (causer eager-loaded) linking the activity log. Every section computed only when the admin
  holds its permission, via single aggregate queries.
- **Seeder consistency fix:** `DatabaseSeeder` now creates the demo admin as **`admin-demo@fama.test`**
  (matching talent-demo/brand-demo), but `RolesAndPermissionsSeeder` still granted super-admin to the
  old `test@example.com` — the demo admin seeded with **no role**. Grant updated; login + permissions
  verified live over HTTP.
- **i18n:** 87 EN strings added to `lang/ar.json` (including pre-existing gaps like Suspend/Verify/
  Delete).
- **Tests:** +15 (345 total, green) — `AdminNavigationTest` (super-admin sees a link/card for every page,
  hrefs built via `route()` so dead links fail loudly; moderator sees only its subset; counts render;
  `?queue=` deep-link + active marking) and `BlockCatalogTest` (CRUD, gate sync, settings_schema JSON
  validation, key/content_source locks, grandfathering, BOTH authz layers, activity log, skills
  eligibility offering/flagging/rejection, global queue kinds + pagination).
- Docs: ADR-T (block governance split), admin QA checklist refreshed, CLAUDE.md state, README status.
  No git.

## 2026-07-16 — Admin phase reconciled with the renames; `migrate:fresh --seed` green

The admin slice (Phase 3) was authored before the Campaign → Project and Deal → Contract renames and had
never been re-run end-to-end. Reconciled it, fixed the bugs the renames exposed **and** several that
predated them.

- **`migrate:fresh --seed` exits 0.** `DatabaseSeeder` never called `RolesAndPermissionsSeeder` or
  `SettingsSeeder`, so `AdminDemoSeeder` authorized against permissions that did not exist and settings
  had no default flow to point at. Both now run, in an order the docblock explains
  (RBAC → catalogs → `ContractFlowSeeder` → `SettingsSeeder` → demo data).
- **Activity log actually records now (pre-existing bug).** `ContractFlow` and `ContractFlowStep`
  *imported* `LogsActivity`/`HasStates` but never applied the traits, and no model defined
  `getActivitylogOptions()` — so the audit trail `ContractFlowStep`'s own docblock promised was silently
  dead. Traits applied + options added (log name `contract_flow`, `logOnlyDirty()`,
  `dontLogEmptyChanges()`). Causer resolves from the `admin` guard. **Note:** the `LogOptions` API in
  activitylog 5.0 is `dontLogEmptyChanges()`; there is no `dontSubmitEmptyLogs()`.
- **`ContractFlow::contracts()`** hasMany added (the flow console counts usage through it).
- **Namespace collision fixed:** `ContractInterventionService` pointed at `App\Contracts\ContractProgression`;
  `App\Contracts` is Laravel's interface namespace — the engine lives at `App\Contracting\`.
- **Stale eager-loads removed:** the contract console loaded `Contract::service`, a relation ADR-K deleted.
- **N+1 in the admin contract console:** `ContractResource` reads `talent.avatar_url`, a medialibrary
  accessor — one media query per row. Medialibrary uses `loadMissing` internally, so `preventLazyLoading`
  never surfaced it. Now eager-loads `talent.media`; query count is flat as rows grow.
- **Deposit is mandatory** — `ContractFlowSeeder` marks the payment step `is_skippable => false`; it locks
  the booking, so it can never be skipped. `ContractFlowFactory::standard()` reuses `STANDARD_STEPS`.
- **Stale test fixtures:** admin tests looked up `TalentType` slug `'model'`, retired by ADR-S in favour of
  the discipline slug `'modeling'`. (`applies_to`/`category` legitimately keep the `model` **enum** — the
  category enum was not renamed.)
- **Self-contradicting test fixed:** `ProfileEditorTest` asserted `assertSee(__('Skills'))` and
  `assertDontSee(__('Skills'))` four lines apart — a casualty of the ADR-N `Professions`→`Skills` sed.
  Git history confirms the intent was `assertDontSee('Professions')`; restored.
- **Docs reconciled with the code (they had never been updated for either rename).** `architecture.md`,
  `schema.md`, `api.md`, `conventions.md`, `decisions.md` and `docs/specs/*` renamed in place, and three
  claims that were factually wrong — not merely misnamed — corrected:
  - `contracts.brand_project_id` (ADR-F) is **landed**, not "deferred"/"open".
  - **One project = one role = one position** — the `brand_project_talent_types` pivot (many roles ×
    `quantity`) and `positions_count` are gone; `brand_projects.talent_type_id` replaces them.
  - `budget_is_public` (default **false**) documented: private budgets are withheld from every
    non-owning viewer, not just hidden in the view.
  - Admin authoring/moderation UI moved out of "Not yet built" (it exists and is tested); admin routes
    corrected to the real ones (`/admin/skills`, moderation `/projects`) and verified against
    `route:list`.
  - `changelog.md` is a dated historical record and was **left as written** (a mechanical rename there
    produced nonsense self-renames like `` `contract_enquiries`→`contract_enquiries` ``).
- **Verified:** full Pest suite **330/330 green**; `migrate:fresh --seed` exit 0; audit trail confirmed
  live (causer + old→new); `/admin/dashboard` guarded (302 → `/login`). Deposit non-skippable in seeded
  data. No git.

> Note: entries below this one predate the Deal → Contract rename and still say "deal" — they are a
> historical record and are intentionally left as written.

## 2026-07-15 — Repo-wide rename: Deal → Contract

- **Every "deal" is now a "contract"** — 122 files, ~1,555 occurrences, zero `deal` identifiers left in
  `app/ database/ routes/ resources/ tests/ config/ lang/ docs/`.
- **Models/tables:** `Deal`→`Contract`, `DealStep`→`ContractStep`, `DealMessage`→`ContractMessage`,
  `DealFlow`→`ContractFlow`, `DealFlowStep`→`ContractFlowStep`, `DealEnquiry`→`ContractEnquiry`; tables
  `deals`→`contracts`, `deal_steps`→`contract_steps`, `deal_messages`→`contract_messages`,
  `deal_flows`→`contract_flows`, `deal_flow_steps`→`contract_flow_steps`,
  `deal_enquiries`→`contract_enquiries`; FKs `deal_id`→`contract_id`, `deal_step_id`→`contract_step_id`,
  `deal_flow_id`→`contract_flow_id`. Migrations edited in place → `php artisan migrate:fresh --seed`.
- **Two naming collisions resolved** (both would have broken conventions):
  `app/Deals/` → **`app/Contracting/`** and `app/Actions/Deals/` → **`app/Actions/Contracting/`** —
  *not* `App\Contracts`, which is Laravel's convention for interfaces and already holds
  `App\Actions\Contracts\Action`. The **`'contract'` step_type is unchanged** (it's the signing step, a
  sub-type — so a Contract legitimately contains a contract-signing step).
- Also renamed: `ContractService`, `ContractProgression`, `ContractCompleted`, the Initiate/Advance/
  Convert actions, all three states, resources, both controllers, factories, `ContractFlowSeeder`,
  routes (`/brand/contracts`, `/talent/contracts`, `*.contracts.*`), views (`*/contracts/`),
  `resources/js/contracts.js` + its Alpine components (`contractsInbox`, `contractRoom`,
  `brandContractRoom`, `brandContractsInbox`), tests (`tests/Feature/Contracts/`), and the **`contracts`
  log channel** (`config/logging.php` + `storage/logs/contracts.log`).
- **i18n:** keys renamed (Deal→Contract) *and* the Arabic values retuned from صفقة/صفقات → عقد/عقود,
  including the gender agreement (صفقة is feminine, عقد masculine → "انتهى هذا العقد").
- Docs + CLAUDE.md project-state updated in place. **Full Pest suite green (277)** — first run, no
  regressions. No git.

## 2026-07-15 — Apply to a project (rich-text brief + @mentions + attachments)

- **The public project CTA is now "Apply", not "Message brand".** A talent opens a modal, writes a
  **rich-text brief** (why they're a fit), can **@-mention their own portfolio projects**, and **attaches
  files**; submitting opens (or reuses) the talent↔brand deal scoped to that project and posts the brief as its
  opening message, then lands them in the deal room. Guests/brands get a talent-login link instead.
- **Backend** (`Talent\ApplicationController`, talent-guarded): `GET /talent/applications/mentions` (the
  talent's projects for the @-picker, filtered in PHP to dodge MySQL's case-sensitive JSON collation);
  `POST /talent/applications/{brandProject}` (open+public+published only) → `DealService::applyToProject`
  (reuse/open deal, post a rich message, attach media in one transaction).
- **Security:** the brief is the ONLY user HTML Fama renders un-escaped, so it's sanitized server-side to a
  strict allowlist (`App\Support\Html\BriefSanitizer`, DOMDocument): keeps p/br/b/i/u/ul/ol/li + mention
  spans, strips every attribute (except `class="mention"`) and every disallowed tag (script/img/anchors
  unwrapped). Messages carry an `is_rich` flag — rich briefs render via `x-html`, plain chat stays `x-text`.
- **Attachments:** `DealMessage` is now `HasMedia` (an `attachments` collection); the deal-room timeline
  (both sides) renders the brief HTML + downloadable file chips (media eager-loaded, no N+1).
- **Editor:** a lightweight `contenteditable` rich editor (bold/italic/bullets via execCommand) with a
  caret-anchored @-mention dropdown (keyboard-navigable), teleported + scroll-locked + focus-trapped modal.
- **+6 tests** (mentions filter, application creates a talent-initiated deal + rich message + attachment,
  sanitization strips scripts/handlers/images, empty-brief 422, closed/private 404, re-apply reuses the deal).
  **Full Pest suite green (277).** No git.

## 2026-07-15 — "Campaigns" → "Projects" rename, single-role projects, budget privacy

- **Repo-wide rename Campaign → BrandProject** (the brand "Campaigns" feature is now "Projects" everywhere
  users see it). The literal name `Project`/`projects` was already taken by the talent portfolio model/table, so
  the brand code uses **`BrandProject` / `brand_projects`** (tables `brand_project_media`, FK
  `brand_project_id`; the roles pivot was dropped — see below). All URLs/routes are `projects`
  (`/brand/projects`, `/projects`, `/brands/{brand}/projects/{project}`), route names `*.projects.*` /
  `projects.browse` / `brand.project.public`, Alpine components `brandProjects` / `brandProject` /
  `projectBrowse`, and all UI text + Arabic are "Project(s)". The `type` enum keeps its `campaign`/`shoot`
  values (a project's *kind*), and talent-portfolio "Campaign" sample strings are untouched (a different
  concept). Migrations edited in place → run `php artisan migrate:fresh --seed`.
- **Each project = one role, one position.** Dropped the multi-role `brand_project_talent_types` pivot +
  `positions_count`; added `brand_projects.talent_type_id` (a single discipline). Service/requests/resources/
  views/JS simplified from a roles editor to one discipline select.
- **Budget public/private flag (ADR).** New `brand_projects.budget_is_public` (**private by default**). The
  owning brand always sees the budget (with a Public/Private tag); the public project detail, opportunities
  cards, and profile cards expose the budget **only when the brand opts in** (`PublicProjectCardResource`
  nulls it otherwise; the detail view gates it server-side).
- **"Message brand" removed from the public brand profile** (per request) — the CTA stays on the Opportunities
  cards + project detail.
- **Full Pest suite green (271).** No git.

## 2026-07-14 — Filters on the talent-facing discovery pages

- **Discover brands** and **Opportunities** now filter **the same way as Discover talent**: a sticky primary
  chip bar (Brands → **Industry**; Campaigns → **Discipline** chips grouped by scope via the shared
  `skill-filter-chips` partial) + an **Advanced filters** modal (teleported to `<body>`, scroll-locked,
  focus-trapped, staged draft applied only on "Apply"), an active-filter summary row (removable chips + Clear
  all), a live result count, and skeleton loaders.
  - Brands advanced facets: **stage · reach · verified-only**. Campaigns advanced facets: **type · budget
    min/max · city**.
- **Backend:** `BrandDiscoveryController@feed` gained `industry`/`brand_stage`/`geographic_reach`/`verified`;
  `CampaignDiscoveryController@feed` gained `type` (talent_type slugs, `whereHas`), `campaign_type`,
  `budget_min`/`budget_max` (null-safe overlap), and `city`. Both stay paginated + eager-loaded (no N+1).
- **JS:** factored the modal machinery into a shared `filterModal()` mixin (mirrors talentSearch — teleport,
  scroll-lock, focus-trap; no x-transition on the teleported node) + a `disciplineIcon()` helper; `brandsDiscover`
  and `campaignBrowse` rewritten around it. **+2 filter tests; full Pest suite green (270).** No git.

## 2026-07-14 — Premium redesign of the brand campaign workspace

- **Campaign detail page redesigned** from a flat stack of white cards into a premium workspace: a **hero**
  (type eyebrow + coloured status pill + title + a 4-stage lifecycle stepper Draft→Open→In progress→Completed,
  with a cancelled banner), a **KPI strip** (Budget · Deals · Positions · Location), and a **two-column body** —
  main column (roles as cards, gallery, deals) + a sticky **Summary** sidebar (type/dates/currency, visibility
  toggle, "View public listing" link) and a **Danger zone** (inline-confirm delete). The contextual lifecycle
  CTA is the filled primary; Edit/Cancel are secondary.
- **Gallery gained removal.** New `DELETE /brand/campaigns/{campaign}/media/{media}`
  (`CampaignController@removeMedia`, ownership-checked + scoped to the campaign) with a hover remove control and
  a dashed empty-state uploader. `brandCampaign` gained `removeMedia`, `destroy` (inline-confirm), `statusIndex`
  + `totalPositions` getters. **+3 media tests.**
- **i18n kept complete** — 13 new keys translated (lifecycle labels, Danger zone, Positions, …). **Full Pest
  suite green (268).** No git.

## 2026-07-14 — Talent-facing discovery, unread indicators, profile consolidation, i18n

- **Unread-message indicators (both sides).** `DealResource` now exposes `unread_count` (the counterparty's
  unread free-messages, via the `humanUnreadFor` scope); the brand + talent `DealController@data` set it with
  `withCount`. Both inboxes badge unread deals (accent dot + count + ring) and now **poll every 20s** so the
  badge appears live. Message ordering is deterministic on same-second sends — `Deal::messages()` already sorts
  by `created_at` then the auto-increment `id`, and neither deal room re-sorts.
- **Campaign editing.** The campaign detail page was read-only; the Edit button led nowhere editable. Added an
  in-place **Edit details** form (title, type, budget, location, dates, roles, visibility) wired to the existing
  `PATCH /brand/campaigns/{campaign}` endpoint, plus a read-only Details section. Editing is gated to
  non-complete/cancelled campaigns.
- **Profile consolidation continues (ADR-N pattern).** **Creative needs** folded into the Profile editor (like
  Account before it): its section now lives in `brand/profile.blade.php` (talent types / project types /
  frequency / budget tier), the nav item is gone, and `GET /brand/creative-needs` redirects to the profile
  (the `PATCH` endpoint stays). Orphan view + `brandCreativeNeeds` component removed.
- **Brand topbar** gained a **View public profile** link (published brands only → `brand.public`).
- **Talent-facing discovery (new).** `GET /brands` (published-brand discovery) + `GET /campaigns` (open, public
  campaigns = the "opportunities" board), each a Blade shell + paginated, eager-loaded Ajax feed
  (`BrandDiscoveryController`, `CampaignDiscoveryController` + `BrandCardResource`, `PublicCampaignCardResource`).
  Added to the talent sidebar + public header nav.
- **Talent→brand messaging (ADR-P mirror).** `GET /brands/{brand:slug}/message` (`BrandMessageController`)
  mirrors the brand→talent flow: guest → talent login (return URL kept); talent → the latest brand↔talent deal
  or a fresh talent-initiated one (optionally tagged to the campaign via `?campaign=`), then the talent deal
  room. "Message brand" CTAs added on the public brand profile + campaign detail + campaign cards.
- **i18n.** Full `ar.json` audit (script-driven): every `__()` key across views/JS/PHP now has an Arabic value
  — 95 keys added (incl. `Public`/`Private`/`View public profile`/`Message brand`/`Opportunities`), file
  re-sorted case-insensitively (447 → 542 entries). Only `auth.password` resolves from `lang/ar/auth.php`.
- **Full Pest suite green (265, +9 new tests for the discovery + messaging routes).** No git.

## 2026-07-14 — Brand deal room shows full deal details

- **Root cause of the empty brand pages: a stale JS build.** The bundle `fama.test` loaded predated the brand
  Alpine components, so none of them initialised (empty Ajax lists, blank forms, dead buttons). Rebuilt
  (`npm run build`) — the fresh bundle contains all 10 brand components; all data endpoints return correct
  data; every button handler maps to a real route. **Fix = rebuild + hard-refresh.**
- **`DealResource` never exposed the `talent` counterparty** — so the brand deal room header and deals inbox
  both rendered a blank counterparty (`deal.talent?.display_name`). Added `talent` (name/slug/avatar) and
  `campaign` (title/slug) via `whenLoaded`; the brand `DealController@thread` now eager-loads `campaign`.
- **Deal room now renders the details:** a richer header (reference, title, campaign chip, counterparty with
  avatar, agreed amount, status), a new **Deal details** card (brief, dates, initiated-by), plus the existing
  phases stepper + timeline. `brandDealRoom` gained a `detailRows` getter (translatable labels passed from the
  view).
- **Demo deal made real:** the seeded campaign deal (`NOMAD-AUTUMN-01`) was created raw (0 steps) → the
  stepper was empty. It's now created **through the deal engine** and walked to completed, so it has 7
  snapshotted steps + 8 messages. Its credibility counters are set **after** completion so the "recalc
  credibility" side effect doesn't overwrite the curated demo numbers. **Full Pest suite green (255).** No git.

## 2026-07-12 — Reconcile the brand slice with the talent-side edits (post-merge fixes)

After merging `main` (talent side) into `brand-phase`, the brand code broke against changes it predated.
Fixed so `migrate:fresh --seed` and the full suite are green again (255 tests):

- **Skill rename (ADR-S).** The brand slice referenced the old person-noun `talent_types` slugs, which no
  longer resolve → `BrandDemoSeeder` inserted an empty `campaign_talent_types.talent_type_id` (`1366`).
  Updated the seeder (`model`→`modeling`, `photographer`→`photography`, `cinematographer`→`cinematography`)
  and every brand test slug lookup + the `assertSee('Model')` → `'Modeling'` role-name assertion.
- **Availability removed (ADR-L).** `App\Queries\BrandTalentFeed` filtered on the dropped
  `talents.availability_status` column — removed that `AllowedFilter`.
- **Services removed (ADR-K).** `Brand\DealController` eager-loaded the deleted `Deal::service` relationship
  (`data()` + `thread()`) → 500. Dropped `'service'` from both `with()`/`load()` calls.
- **Merge artifacts (missing `use` imports).** `routes/web.php` used `BrandProfileController::class` without
  importing it → `ReflectionException` (500 on every public brand/campaign page). And
  `App\Listeners\SyncStateProjections` referenced `Brand`/`BrandReview` without importing them, so
  `instanceof` silently returned false and the brand/review projections (`is_complete`, `is_published`,
  `is_approved`) never synced → onboarding/publish/review assertions failed. Added both imports.
- **Verified:** `migrate:fresh --seed` completes (campaign roles resolve to `modeling`/`photography`, zero
  orphan pivots); **full Pest suite green (255)**; brand public profile + campaign detail render 200
  in-browser with the new discipline role names. No git.

## 2026-07-12 — Talent profile image (avatar) uploader

- **Added the missing profile-image uploader** to the Profile editor's Identity/Core-details section. A
  talent can now **Upload / Change / Remove** their avatar — Ajax, no reload: the preview updates in place
  and falls back to the initials avatar when removed. The image goes to the existing `avatar` single-file
  media collection (ADR-O — only the circular avatar; no cover/hero).
- **Endpoints:** `POST /talent/profile/avatar` (update) and `DELETE /talent/profile/avatar` (remove), both
  returning `{ avatar_url }`. Thin controller (`ProfileEditorController::updateAvatar/removeAvatar`) →
  `TalentProfileService::updateAvatar/removeAvatar` (media ops, fail-logged to the `media` channel).
  Validation via `UpdateAvatarRequest` (`image|mimes:jpg,jpeg,png,webp|max:5120`).
- **Front-end:** `profileEditor` (dashboard.js) gains `avatarUrl` + `uploadAvatar()`/`removeAvatar()` and an
  `avatarInitials` getter; a reactive avatar preview + hidden file input in the editor blade.
- **Tests:** `ProfileEditorTest` gains 7 cases — the uploader renders; upload returns a URL + adds media;
  re-upload replaces (single-file); remove clears it; non-image / >5 MB → 422; guests are redirected.
  **Full Pest suite green (199).** Verified in-browser (uploader renders with the seeded avatar; Remove →
  initials reactively, no reload; DELETE endpoint works) — console clean. Docs updated. No git.

## 2026-07-12 — Fix: MariaDB deploy failure on the Looks functional-index migration

- **Migration `2026_07_11_000100_add_look_types_name_index` failed on MariaDB** (production) with a
  `1064` syntax error: MariaDB has **no functional/expression indexes**, and the old
  `getDriverName() !== 'mysql'` guard didn't catch it because **Laravel reports MariaDB's PDO driver as
  `mysql`**. The migration now inspects `VERSION()` and creates the functional index **only on genuine
  MySQL 8.0.13+**, **skipping it on MariaDB / older MySQL** (the model-scope **Looks** filter still works
  unindexed — `look_types` is a tiny lookup table). `down()` drops the index only if it exists. No change on
  MySQL 8 (dev/CI): the index is still created; **full Pest suite green (193)**. docs/schema.md updated. No git.

## 2026-07-12 — Discovery: scoped filters appear only after a skill is selected

- **Scoped filters are now skill-gated.** In the Advanced-filters modal, a skill-specific filter shows **only
  once its related skill is selected** (crew → Equipment, creative → Software, modeling → Looks) — previously
  they all showed when nothing was selected. With **no skill selected** the Skill-specific section shows a hint
  ("Select a skill to reveal its filters."); picking a skill reveals the filter that narrows it further. The JS
  getters dropped the `|| draft.type.length === 0` clause (`showEquipment = draftScopes.has('crew')`, etc.), and
  a new `hasScopedFilters` getter gates the grid-vs-hint. **Full Pest suite green (193).** Verified in-browser
  (no skill → hint; Photography → Equipment only) — console clean. No git.

## 2026-07-12 — Discovery: scoped filters restored to the modal (shown by selected skill)

- **Reverted the "hide scoped filters" change.** The **Skill-specific** section (Equipment / Software / Looks)
  is back in the (still `max-w-3xl`) Advanced-filters modal, **shown based on the selected skills** — with no
  skill selected all three show; a crew skill reveals **Equipment**, a creative skill **Software**, modeling
  **Looks**. The JS visibility getters (`showEquipment/Software/Looks`, `draftScopes`) were already intact, so
  only the modal markup + subtitle + the two translation strings + `DiscoveryTest` were restored. **Full Pest
  suite green (193).** Verified in-browser (no-skill → all groups; Modeling → Looks only) — console clean. No git.

## 2026-07-12 — Discovery: wider Advanced-filters modal + scoped filters hidden

- **Wider modal.** The Advanced-filters dialog grew from `max-w-2xl` to **`max-w-3xl`**.
- **Skill-specific scoped filters hidden.** The Equipment / Software / Looks group (the filters that show
  based on the selected skill's category) is **removed from the modal UI** — the modal now holds only a
  **Skills** section and a **Location** section (subtitle updated to "Refine by skill and location."). The
  scoped filters remain valid `TalentSearch`/URL filters and stay covered by the backend scoping test; the
  `showEquipment/draftScopes` JS getters remain but are now UI-unused. `DiscoveryTest`'s modal-groups test now
  asserts the modal shows Skills + Location and **does not** render the scoped selects. **Full Pest suite green
  (193).** Verified dark + RTL; console clean. No git.

## 2026-07-12 — Discovery: bigger, polished Advanced-filters modal + "All" no longer a default

- **"All" is a neutral reset, not a default selection.** The "All" chip no longer shows a filled/selected
  state and is **disabled while nothing is chosen** — an unfiltered view highlights no chip (in both the
  sticky bar and the modal, via the shared partial).
- **Bigger, enhanced modal.** The Advanced-filters dialog is now a **large** card (`sm:max-w-2xl`,
  `rounded-2xl`) with a **title + subtitle**, a Skills section (with its own selected-count badge), a
  divider, a **Location** section, and a **Skill-specific** section whose scoped `<select>`s sit in a
  **2-column grid** (using the extra width). Roomier padding, larger rounded inputs/selects, and a larger
  "Apply filters" button. Verified dark/light + RTL; console clean; **full Pest suite green (193)**. No git.

## 2026-07-12 — Discovery: skills groups side-by-side, skills-in-modal, staged modal

- **Scope groups side by side, on one line.** The primary Skills filter lays its scope groups (Modeling /
  Crew / Creative) out **horizontally** as divider-separated **columns** (was a vertical stack), and the
  sticky bar now keeps them all on **one line** (`nowrap` + `overflow-x-auto`, hidden scrollbar) so they sit
  beside each other. The **"All"** reset (renamed from "All skills") sits **beside** the groups, not above.
  The single-chip **Modeling** group keeps an **invisible** label placeholder so its chip stays aligned.
  RTL-mirrored (logical `border-s`). Chips extracted to a shared partial
  `public/partials/skill-filter-chips` (`$nowrap`, `$staged` flags).
- **Skills selector inside the Advanced-filters modal.** The modal now includes the same grouped skill chips
  at the top, so a visitor can pick skills there and the scoped groups below react. With **no skill selected**
  ("All"), **every** scoped group shows (Equipment · Crew, Software · Creative, Looks · Modeling); once skills
  are selected only the groups **matching those categories** remain. The obsolete "Choose … skills for …"
  hint was removed.
- **Modal is now a staging area.** The sticky bar still applies **live**, but the modal edits a **draft**
  snapshot (`draft.*`) and **nothing commits to the results until "Apply filters"**: `applyFilters()` copies
  the draft into `filters` then searches; ×/backdrop/ESC **discards** the draft; **"Clear filters"** resets
  the draft in place without applying. The trigger's filter-count badge reflects the **applied** filters, not
  the unsaved draft. `pruneScopedFilters` (live) was made self-contained on `selectedScopes` so it no longer
  depends on the now-draft-based `showEquipment/Software/Looks` getters.
- **Docs/tests:** talent-spec + conventions + CLAUDE.md updated; `DiscoveryTest`'s scoped-groups test asserts
  the by-scope groups. **Full Pest suite green (193).** Verified in-browser: side-by-side one-line groups, "All"
  beside the groups, skills-in-modal narrowing the scoped groups, and staging (toggling a skill / typing a city
  in the modal leaves the grid unchanged until Apply; discard on close) — dark + RTL, console clean. No git.

## 2026-07-12 — Public profile: skill tabs elevated to primary navigation (presentation-only)

- **Prominent, sticky tab bar.** The skill tabs are now the profile's main navigation: a **sticky** (under
  the site header, `top-16`), horizontally-scrollable **pill / segmented** control, separated from the
  identity region by a divider. Each tab shows the skill's **`icon`** (new self-contained `<x-skill-icon>`
  component, mirroring the discovery chips), its **name**, and a **count badge** (visible blocks in that
  skill).
- **Unmistakable active state.** The active tab is **filled** (`bg-accent` + `text-on-accent` +
  `font-semibold` + shadow), not a faint underline; inactive tabs stay legible (surface + border) with
  **hover** and **`focus-visible`** rings.
- **Accessibility.** Proper `role="tablist"` / `role="tab"` / `role="tabpanel"` with `aria-selected`,
  `aria-controls` / `aria-labelledby`, **roving `tabindex`** (only the active tab is tabbable), and
  **arrow / Home / End** keyboard navigation (RTL-aware; activation follows focus). Visible focus ring.
- **Panel.** Renders the **active skill's name as a heading** (context when the bar scrolls out on mobile)
  and **fades on switch** (reduced-motion-aware — a forced-reflow opacity swap, no `requestAnimationFrame`
  so it's background-tab-safe). **Mobile:** the tabs scroll horizontally with **snap + edge fades** and
  never wrap.
- **De-duplicated:** the header's **skill chips were removed** (the tab bar is the navigation); the
  **primary-skill line** in the header stays.
- **Prompt-H behaviour preserved:** primary tab active by default; tabs **lazy-load** via Ajax on first
  click and cache; `?skill=` deep-link + back-button sync; single-skill talents show **no tab bar**; skills
  with no visible blocks show **no tab**; `view_count` bumps **once** per profile view (server-side), not
  per switch. No schema/query changes.
- **JS:** `profileTabs` (`resources/js/dashboard.js`) gains `labels`, `onTabKey` (keyboard nav), and
  `swapPanel` (fade); the now-unused `jump()` (header chips) was removed.
- **Docs/tests:** talent-spec (prominent tab bar, chips removed) + conventions (QA checklist) + CLAUDE.md
  updated. `ProfileTabsTest` gains: the accessible tab bar with the primary tab active + panel heading; the
  keyboard-nav / roving-tabindex wiring; and the header-no-longer-renders-chips check; the single-skill test
  now asserts no `role="tablist"`. **Full Pest suite green (193, 625 assertions).** Verified in-browser:
  tab switch updates panel + URL without reload, sticky-under-header, arrow-key nav, filled active state —
  dark / light / RTL. No git.

## 2026-07-12 — Discovery: primary skills filter + teleported viewport-centred modal

- **Modal positioning bug fixed (Part 1):** the "Advanced filters" dialog is now **teleported to `<body>`**
  (`x-teleport`) so no transformed/`overflow` ancestor (the filter card, or `main.animate-fade-in-up`'s
  transform) can trap its `position: fixed`. It **always opens centred in the viewport** regardless of scroll,
  over a token **scrim** (`--scrim`, added to the design tokens). Body scroll is **locked** (position preserved
  and restored on close); it closes on **×, backdrop click, and ESC**; **focus is trapped** and returns to the
  trigger; `role="dialog"` + `aria-modal` + `aria-labelledby`; on small screens it's a **bottom sheet whose body
  scrolls** (not the page). Enter/leave use the motion tokens and honour `prefers-reduced-motion`.
  - **Gotcha recorded:** Alpine's `x-transition` **leave** never completes for an `x-teleport`'d node, leaving a
    `display:flex` overlay that traps clicks. Fix: plain `x-show` for display + enter/leave via `:class` + CSS
    transitions on a **mount → `$nextTick`(activate) → (leave) → unmount** cycle, with **`pointer-events-none`
    while inactive** so the page is never trapped during the fade-out. (`$nextTick`, not `requestAnimationFrame`,
    because rAF is paused in background tabs.)
- **Primary skills filter (Part 2):** the Skills selector is now **the** primary control — a **sticky** bar
  (`top-16`) with a **Skills** heading, **selected-count**, and an **"All skills"** clear affordance. Skills are
  **multi-select chips grouped by scope** (Modeling / Crew / Creative) rendering `talent_types.icon` with real
  **states** (hover, `focus-visible` ring, filled-accent **selected + check**); accessible **`aria-pressed`
  toggle buttons** in a labelled `role="group"`. Below: an **active-filter summary row** of removable chips
  ("Modeling ×", "Cairo ×") + **"Clear all"**, and a live **result count** ("N talents"). Applying filters is
  **Ajax** with **skeleton loaders** and an **empty state** (Clear filters); active filters **sync to the URL**
  (shareable + **back/forward** via `popstate`; discrete changes push, typing replaces, **pagination holds
  filters**). `filter[type]` is **multi-select** (comma-separated slugs). The free-text `q` search stays a small
  **secondary** control.
- **Scoped filters (Part 3):** Location stays always-visible; the bare "select a skill" line is replaced by a
  hint that **names** what each selection reveals ("Choose Crew skills for Equipment · Creative for Software ·
  Modeling for Looks"); selecting skills reveals exactly those scoped groups (crew → Equipment, creative →
  Software, modeling → Looks). Apply / Clear re-run the search.
- **Docs/tests:** talent-spec (discovery) + conventions (QA checklist incl. "modal opens in the viewport when
  scrolled") + CLAUDE.md updated. `DiscoveryTest` gains multi-select-type, pagination-holds-multi-filter, the
  primary-control render (sticky, `aria-pressed`, `role="group"`, summary, count), the teleported-modal a11y
  wiring (`x-teleport`, `role=dialog`, `aria-modal`, focus trap, `var(--scrim)`), and the named scoped-groups
  empty state. **Full Pest suite green (190, 608 assertions).** Verified in-browser: modal centred-when-scrolled,
  scroll-lock, ESC/backdrop close, multi-select + summary removal, URL sync + back-button, scoped groups per
  category — dark/light + RTL. No git.

## 2026-07-11 — Skills named as disciplines, not people (ADR-S)

- **Renamed the six `talent_types` (the Skills catalog)** from person-nouns to disciplines/activities:
  Model → **Modeling** (`modeling`), Photographer → **Photography** (`photography`), Cinematographer (DOP)
  → **Cinematography** (`cinematography`), Creative Director → **Creative Direction** (`creative-direction`),
  Stylist → **Styling** (`styling`), Graphic Designer → **Graphic Design** (`graphic-design`). Both `name`
  (en+ar), `slug`, `icon` (`lucide-<slug>`), and `description` change.
- **Migration `2026_07_11_000300_rename_talent_types_to_disciplines`** renames existing rows in place and
  is a **no-op on a fresh DB** (the table is empty when it runs; `TalentTypeSeeder` seeds the new values),
  so `migrate:fresh --seed` and an in-place `migrate` converge. **IDs are unchanged**, so every FK
  (`talent_talent_type`, `block_type_talent_type`, `campaign_talent_types`,
  `brand_creative_need_talent_type`, `profile_blocks.talent_type_id`, `projects.talent_type_id`) is
  untouched — verified on the dev DB (pivot counts identical before/after; `down()` reverses to the
  person-nouns).
- **`category` enum unchanged** (`model | crew | creative` — it gates blocks and scopes discovery filters);
  only its **display labels** are now Modeling / Crew / Creative (`discover.blade.php` + `dashboard.js`
  `scopeLabels`, plus the editor's per-skill category label). A single-chip category group whose label
  duplicates its lone chip (Modeling) **suppresses the redundant header**.
- **Ripples (all data-driven, auto-updated):** talent-card kicker, public-profile secondary line + skill
  chips, admin Skills catalog, brand creative-needs, API lookup now read the new names. Deep links change:
  `?skill=photographer` → `?skill=photography`; **old links break — accepted pre-launch, no redirects.**
- **Seeders/factories/tests:** `TalentTypeSeeder`, `TalentTypeFactory`, `TalentDemoSeeder`,
  `TalentShowcaseSeeder` and every test referencing an old slug/name literal were updated; nothing
  references `photographer` / `stylist` / etc. as a slug literal. `lang/ar.json` gains `Modeling`.
- **⚠ Arabic copy is a first pass — stakeholders should confirm the AR names** (`عرض الأزياء` /
  `التصوير الفوتوغرافي` / `التصوير السينمائي` / `الإدارة الإبداعية` / `تنسيق الأزياء` / `التصميم الجرافيكي`).
- **Docs:** ADR-S (decisions.md); schema-master + conventions + api.md + README + CLAUDE.md updated.
  `brand-spec.md` needed no change (it references the Skills catalog only abstractly). **Full Pest suite
  green (185, 579 assertions).** No `composer api-docs` (mobile API is Phase 4). No git.

## 2026-07-11 — Public profile: identity/universal region + lazy, deep-linked skill tabs (ADR-R)

- **Two stacked regions** (`talent/profile.blade.php`): **Region 1** (always visible) = the IG identity
  header + universal/meta (location, **Pricing rate**, and **clickable skill chips** that activate a tab)
  + the **profile-level blocks** (`talent_type_id = NULL`, visible, in position order). **Region 2** =
  **skill tabs** — one tab per linked skill **with visible blocks** (ordered primary-first, primary active
  by default); a block-less skill has **no tab**; a **single-skill** talent shows no tab bar (renders
  directly). A tab shows only that skill's visible blocks (via the existing partials; grandfathered types
  still render), and its **Projects** block lists only that skill's projects.
- **Lazy + deep-linked:** the active (primary, or `?skill=`) tab renders **server-side**; other tabs are
  fetched on first click via **`GET /{slug}/tab/{skill}`** (`TalentProfileController@tab` → envelope
  `{ html }`, eager-loaded, no N+1) and **cached client-side** (re-click is instant). The active tab is
  mirrored in the URL (`?skill={slug}`) so it's shareable and the **back button** works (`popstate`).
  `view_count` bumps once per profile view (not per tab switch). New `profileTabs` Alpine component; a
  reusable `talent/partials/skill-blocks` partial renders a tab (used server-side + by the lazy endpoint).
- **API contract:** `App\Http\Resources\PublicProfileResource` defines the Phase-4 public-profile shape —
  `identity` (header + universal/meta + pricing rate + skills), `universal_blocks`, and `skills[]` each
  with `blocks[]` (mirrors the two regions). Documented in `docs/api.md`.
- **Docs/tests:** ADR-R; talent-spec + api.md + conventions (QA checklist) + CLAUDE.md + README updated.
  New `ProfileTabsTest` covers: identity + universal + a tab per skill with the primary active server-side;
  a lazy tab returns ONLY that skill's visible blocks (projects scoped); the Projects link to
  `/{slug}/work/{project}`; single-skill → no tab bar; a block-less skill → no tab (its lazy endpoint
  404s); deep-link `?skill=` opens the right tab; unpublished/foreign/unknown-skill 404; and the resource
  shape. Verified in-browser (lazy load, `?skill=` URL, back button, dark/light + `/ar` RTL with
  locale-aware fetch). **Full Pest suite green (185).** No `composer api-docs` (mobile API Phase 4). No git.

## 2026-07-11 — Skill-scoped blocks & projects — one tab per skill (ADR-Q)

- **Schema (`2026_07_11_000200`):** added nullable `talent_type_id` FKs (nullOnDelete) to `profile_blocks`
  and `projects` — NULL = profile-level / universal (above the tabs), NOT NULL = that skill's tab.
  `profile_blocks.position` is now ordered **within a scope** (index `(talent_id, talent_type_id,
  position)`; `projects.talent_type_id` indexed). The migration **backfills** (projects → primary skill;
  blocks → the single skill their gate matches, else NULL) and reports the counts.
- **Seeding is now per-skill (behavioural change):** adding a skill seeds **that skill's** `default_blocks`
  into its own tab (`SeedBlocksForSkill`, replacing the global-merge `MergeDefaultBlocksForTypes` /
  `SeedProfileBlocks`). Dedupe is scoped to (talent, skill, block_type) — a model-photographer gets its
  own **gallery in both tabs** (each with its own `portfolio_items` via `block_id`). `is_repeatable` now
  means "once **per scope**".
- **`ProfileBlockService` is scope-aware:** the picker is per-scope (`availableBlockTypes($talent,
  ?$scope)` — universal blocks in any tab or the universal section; gated blocks only in an eligible
  skill's tab; per-scope repeatability). `addBlock`/`reorder` take a scope; new **`moveBlock`** re-stamps
  `talent_type_id` (validated). New route `PATCH /talent/profile/blocks/{block}/move`; add/reorder accept
  `talent_type_id`; `ProfileBlockResource` + the block-type catalog expose the scope + gate metadata.
- **Removing a skill preserves content:** `SkillsService::removeType` deletes the skill's tab blocks but
  un-links gallery items (`block_id` → NULL) and un-scopes its projects (`talent_type_id` → NULL); the
  editor confirms first and the removal is logged.
- **Profile editor (tab-aware):** blocks are grouped into a Universal / profile-level section + one
  section per skill (primary first) — per-scope add picker, scoped drag-reorder, move-between-scopes
  (only eligible targets), and a confirm-to-remove skill. The **projects editor** gains a Skill selector
  (defaults to the primary skill).
- **Public profile:** renders the universal section, then **one Alpine tab per skill** (no reload) — each
  tab shows its own blocks + gallery. Gallery/projects partials are now block-/skill-scoped. Demo &
  showcase seeders curate a clean tabbed layout (universal talent-level blocks at profile level, gated +
  gallery blocks per tab).
- **Docs/tests:** ADR-Q; schema-master + talent-spec + schema.md + architecture + conventions + api.md +
  CLAUDE.md updated. Tests cover per-skill seeding (2nd gallery, no cross-skill dedupe), per-scope picker
  eligibility + repeatability, scoped reorder, move-between-scopes (+ validation), skill-removal content
  preservation, projects-carry-a-skill, and the backfill via a clean `migrate:fresh`. Verified in-browser
  (profile tabs switch galleries; editor scope groups + move-eligibility). **Full Pest suite green (175).**
  No mobile-API doc regeneration (Phase 4; no `composer api-docs`). No git.

## 2026-07-11 — Discovery: skills-first with an advanced-filters modal + scoped filters

- **Skills-first UI** (`resources/views/public/discover.blade.php`, `talentSearch` Alpine component): the
  primary filter is **Skills** (the `talent_types` catalog), a prominent selector **grouped by scope**
  (Models / Crew / Creative) from the skills' `category`. Selecting skill(s) filters live (Ajax, envelope,
  paginated, eager-loaded — no reload).
- **"Advanced filters" modal**: a button (with an **active-filter count** badge) opens a modal holding the
  full filter set, **scoped by the selected skills' category** — **Location** (city, country) always,
  **crew → Equipment**, **creative → Software**, **model → Looks**; out-of-scope groups are hidden (and
  their values pruned). **Apply filters** re-runs the paginated search. The free-text `q` search is
  demoted to a small **secondary** control.
- **New `looks` filter (model scope)**: `App\Queries\TalentSearch` gains a whitelisted `filter[looks]`
  matching `look_types.name` on the English path (`name->en`). Because `name` is a translatable **JSON**
  column, a plain index is impossible — migration `2026_07_11_000100` adds a **functional index**
  `look_types_name_en_index` on `CAST(name->>'$.en' AS CHAR(191))` (MySQL-only, guarded by driver).
  Confirmed the removed **availability** filter is gone (ADR-L). Everything stays eager-loaded + paginated
  (no N+1). `country` is now also surfaced in the UI (was already whitelisted).
- **Docs/tests.** talent-spec (skills-first discovery, advanced-filters modal, scoped filters, secondary
  search, Looks filter + a comp-card-ranges future note), `docs/schema.md` (the functional index),
  `docs/conventions.md` (refreshed discovery QA checklist), `docs/api.md` (`looks` filter), CLAUDE.md.
  `DiscoveryTest` gains scoped-filter coverage (crew→equipment, creative→software, **model→looks**),
  country + secondary-`q` filtering, and filtered-pagination; the render test asserts the skills-first +
  advanced-filters entry. Verified in-browser (dark, light, `/ar` RTL — skills groups + modal mirror).
  **Full Pest suite green (171).** No mobile-API doc regeneration (Phase 4; no `composer api-docs`). No git.

## 2026-07-11 — Public profile primary CTA: "Message" (interim brand-auth redirect, ADR-P)

- **CTA swap.** The public profile's primary CTA is now **Message** (was **Contact** — Prompt C/ADR-O);
  "Leave a review" stays as the secondary CTA. Message points at a reserved, talent-scoped named route
  **`brand.talents.message`** (`GET /brand/talents/{talent:slug}/message`,
  `App\Http\Controllers\Brand\TalentMessageController`).
- **Interim behaviour (no chat wiring, no new tables).** The route is public and branches on brand auth:
  a visitor **not** authenticated as a brand is redirected to brand auth (`route('login', ['role' =>
  'brand'])` — the login view now pre-selects the Brand role from `?role`) with this talent's public
  profile stored as `url.intended`; an **authenticated brand** gets a **"Messaging coming soon"** flash
  back on the profile. A clearly-marked `TODO(brand-messaging)` hook marks where the real brand↔talent
  chat (`deal_messages`) / deal initiation attaches later. `talent.enquire` (deal_enquiries) is untouched.
- **Supporting bits.** Added a token-based flash banner to `public-layout` (renders `session('status')`);
  new `Message` / `Messaging coming soon` / `Dismiss` strings in `ar.json`.
- **Docs/tests.** ADR-P added to `docs/decisions.md`; talent-spec (public-profile CTA), CLAUDE.md, and this
  changelog updated. `TalentProfileTest` now asserts the **Message** CTA (and no "Contact") and that it
  links to `brand.talents.message`; new `tests/Feature/Brand/TalentMessageTest` covers the guest→brand-auth
  redirect (return URL preserved), the authed-brand "coming soon" stub, and a 404 for an unknown slug.
  Verified in-browser (Message CTA → `/login?role=brand` with Brand pre-selected). **Full Pest suite green
  (168).** No git.

## 2026-07-11 — Deal room: timeline-first layout (message thread central)

- **Layout-only swap** of the talent deal room (`resources/views/talent/deals/show.blade.php`): the
  **message timeline** is now the primary, central, wide column (conversation view — messages +
  system_events interleaved, newest at the bottom — with the composer), and the **current-step action
  panel** + **phases stepper** moved into the narrower **side panel** (action panel on top, stepper
  below). The header stays on top (reference, title, counterparty, status badge, amount) and now carries
  the "← All deals" link. Added `Phases` / `Current step` section labels (+ `ar.json`).
- **No behaviour change:** the `dealRoom` Alpine component, all endpoints
  (`thread`/`advance`/`reject`/`skip`/`message`), and `DealService` are untouched — sending a message and
  acting on a step still update the timeline + stepper via **Ajax with no reload**. No schema, no
  state-machine, no service changes.
- **Responsive + themed:** the side panel stacks under the timeline on narrow screens; verified in-browser
  in **dark, light, and `/ar` RTL** (the whole layout mirrors correctly).
- **Docs/tests:** talent-spec (deal-room: timeline-primary, phases in the side panel), conventions
  (refreshed deal-room QA checklist), CLAUDE.md. `DealRoomTest` gains a structural render assertion
  (`assertSeeInOrder`: All deals → Timeline → composer → Current step → Phases); message-send + step-action
  tests unchanged and green. **Full Pest suite green (164).** No git. *(Only the talent deal room exists;
  the brand deal room is Phase 2C and should reuse this layout.)*

## 2026-07-10 — Instagram-style public profile header + Pricing rate shown (ADR-O)

- **Cover/hero image removed.** Deprecated the `hero` media collection + `hero_image_url` accessor on
  `Talent`; removed the editor's hero uploader (`ProfileEditorController::uploadHero`, the `profile.hero`
  route, `TalentProfileService::setHeroImage`, and the Alpine hero state in `dashboard.js`). No column
  drop (hero was a media-library accessor). The **`avatar`** collection stays. The `hero` **block type**
  in the catalog/`default_blocks` is a separate concept and is untouched (still skipped in the render
  loop — the header replaces it). Demo/showcase seeders no longer attach a hero cover.
- **Instagram-style header** (`resources/views/talent/profile.blade.php`, token-only, dark + light +
  RTL): a thin brand accent strip; a large **circular avatar** (`avatar` collection; initials/gradient
  fallback — a new `2xl` size on `x-ui.avatar`); **display_name** + **@username** (the `slug`) + an
  optional "…" menu (copy profile link); a secondary line (primary **Skill** or headline); a three-item
  **stats row** — **Projects** (count of `projects`) · **Views** (`view_count`) · **Rating** (avg of
  approved reviews, hidden when none); the **bio**; an optional external-link row; and **Contact** /
  **Leave a review** CTAs. Header stats read from already eager-loaded relations — no N+1.
- **Pricing rate shown** (ADR-N) near the identity as a "From {currency} {amount} / {unit}" chip
  (formatted, e.g. "From EGP 5,000 / day"), hidden entirely when no rate is set.
- **Consistency:** confirmed the header carries no availability badge and no services/affiliations/press
  (ADR-K/L/M); remaining blocks still render in position order.
- **Docs/tests:** talent-spec (IG header, no cover, pricing shown), conventions (media map: `hero`
  removed; refreshed QA checklist), decisions (ADR-O), CLAUDE.md, README, changelog. `TalentProfileTest`
  gains header / @username / stats / pricing / rating-visibility coverage; the `Talent` media-collection
  test drops `hero`. Verified in-browser (dark, light, `/ar` RTL). **Full Pest suite green (163).** No git.

## 2026-07-10 — Profile consolidation, "Professions → Skills" rename & Pricing rate (ADR-N)

- **Skills rename.** "Professions" is now **Skills** across all user-facing copy, Blade, translation keys
  and routes (route segment `professions` → `skills`; names `talent.professions*` →
  `talent.profile.skills*`). Talent-side symbols renamed to match: `ProfessionsService` → `SkillsService`,
  `ProfessionController` → `SkillController`, `StoreProfessionRequest` → `StoreSkillRequest`. The
  `talent_types` table + `TalentType` model + `talent_type_id` FKs are **kept** as the physical layer —
  `talent_types` is the **Skills catalog** (a physical rename would cascade into brands/campaigns; noted
  as a deliberate future migration in `schema-master.md` + ADR-N).
- **Editor consolidation.** The standalone **Professions** and **Account** tabs are folded into the
  **Profile editor**, so the talent sidebar is now **Home · Profile · Content · Reviews · Deals**. The
  Profile editor is the single profile surface: Identity, **Skills** (`SkillController` under
  `/talent/profile/skills*`), **Username** (the public `slug`, relabelled "Username" in UI + validation
  via `UpdateCoreProfileRequest::attributes()`; column unchanged), **Publish**
  (`PATCH /talent/profile/publish`, moved from Account), **Pricing rate**, and Blocks. Removed
  `AccountController` + `UpdateAccountRequest` + the `account`/`professions` routes, views and the
  `professionsManager` Alpine component (folded into `profileEditor`).
- **Pricing rate (replaces the removed rate card).** Migration `2026_07_10_000500` adds `rate_unit`
  ENUM(project, day, hour) / `rate_amount` DECIMAL(10,2) / `rate_currency` CHAR(3) to `talents` (nullable,
  NOT translatable). `PATCH /talent/profile/pricing` via `TalentProfileService::updatePricingRate` +
  `UpdatePricingRateRequest` — **all-or-nothing** (`required_with` makes any one field require the other
  two; a blank amount clears the whole rate; currency upper-cased). Demo talent seeded with a sample rate.
- **Docs.** talent-spec (Skills section + Account/Publish merged + Pricing rate), schema-master
  (`talent_types` = Skills catalog note + `rate_*` on `talents`), `docs/schema.md`, `docs/architecture.md`,
  `docs/api.md` (routes: skills under profile, publish/pricing, Account removed), `docs/conventions.md`
  (rate not translatable, "slug shown as Username", refreshed QA checklist), `docs/decisions.md` (ADR-N),
  CLAUDE.md and README updated in place.
- **Tests.** `ProfessionsServiceTest` → `SkillsServiceTest`, `ProfessionsTest` → `SkillsTest` (routes under
  `/talent/profile/skills*`); publish + username now tested via the profile routes; new pricing-rate CRUD
  + all-or-nothing 422 + username-relabel-message coverage; consolidated-editor render test. No
  "profession" left in user-facing copy or routes. **Full Pest suite green (160).** *(No `composer
  api-docs` run — the mobile API is Phase 4 and no `api-docs` script exists yet; `docs/api.md` updated by
  hand. The admin "Skills" relabel + `ProfessionCatalogService` → `SkillCatalogService` apply when the
  admin side is built — not present in this talent-slice codebase.)*

## 2026-07-10 — Remove three talent features (rate card, availability & travel, affiliations & press)

- **Removed entirely (ADR-K/L/M):** the rate card / **services**, **availability & travel**, and
  **affiliations & press** talent features — every trace across the codebase.
  - **Rate card / services (ADR-K):** dropped the `services` table, `Service` model/factory/state
    machine/policy, the `services` block (catalog row + content editor + public partial + its key in
    every `default_blocks`), and the rate-card routes/controller/request/resource. **Deal-engine ripple:**
    `service_id` dropped from `deals` + `deal_enquiries` (FK + column); `service()` relations,
    `DealResource`/deal payloads, `InitiateDeal`, `ConvertEnquiryToDeal`, `StoreEnquiryRequest`, and the
    enquiry Blade stripped. Deal amount now comes from the flow's form/quote step; the single Pricing rate
    (`booking_type`/`booking_value`) replaces the rate card.
  - **Availability & travel (ADR-L):** dropped `availability_status` (+ its discovery index and the
    `Availability` state machine), the public-profile availability badge, the enquiry availability gate
    (**enquiries are always allowed now**), and `willing_to_travel` / `travel_regions` / `rate_tier`
    (superseded by the Pricing rate). Removed the dashboard route/controller/request/resource, sidebar
    entry, Alpine handler, discovery filter, and dashboard-home badge.
  - **Affiliations & press (ADR-M):** dropped `agency_affiliations` + `press_features` (tables, models,
    factories, the `Affiliation` state machine, both policies), the `press` block (catalog row + content
    editor + public partial + its key in `default_blocks`), the affiliations section, and the
    routes/controllers/requests/resources.
- **Migrations (append-only):** `2026_07_10_0001` drops `deals`/`deal_enquiries` `service_id`; `_0002`
  drops the availability/travel columns (index first); `_0003` drops the three tables; `_0004` removes the
  three block types + their category/type gates + any existing `profile_blocks` rows (no-op on fresh).
- **Seeders:** stripped `services`/`press` from every `default_blocks`, removed the three block-type rows,
  and cleaned the demo/showcase seeders of all availability/travel/rate-card/affiliation references.
- **Docs:** ADR-K/L/M added to `docs/decisions.md`; talent-spec, schema-master, `docs/schema.md`,
  `docs/architecture.md`, `docs/api.md`, `docs/conventions.md` (media map + translatable list + QA
  checklist), CLAUDE.md, and README updated in place.
- **Tests:** removed/adjusted the removed-features' tests; **full Pest suite green (155)**; migrations run
  clean on `fama_test`; `migrate:fresh --seed` completes; app boots; public profile + talent dashboard
  render with the features gone (no dead routes/blocks/badge/imports). No git.

## 2026-07-08 — Talent slice: production-grade sign-off

- **Audit (no defects found):** `preventLazyLoading` + `preventSilentlyDiscardingAttributes` on (suite
  green = no N+1); every list endpoint paginated + eager-loaded; every service multi-write wrapped in
  `runInTransaction` with fail-logging to the right channel (deals → `deals`, hero upload → `media`,
  rest → `app`).
- **Coverage +2:** dashboard active-deals/whose-turn render test; engine auto-completes an automatic
  (auto-confirm payment) step with no human turn. Suite: 168 green.
- **Demo dataset:** the demo talent now carries **three deals at different steps** (awaiting_talent /
  awaiting_brand / completed) alongside full blocks/content/images; 10 showcase talents unchanged.
- **Docs:** manual QA checklist added to `docs/conventions.md`; README gains a Status section; CLAUDE.md
  marks the **talent phase COMPLETE** and points to **Phase 2A**.

## 2026-07-08 — Rename case studies → projects (full)

- Completed the earlier label-only change into a full identifier rename across the codebase: model
  `CaseStudy` → `Project`, table `case_studies` → `projects`, relation `Talent::caseStudies()` →
  `projects()`, block-catalog key `case_studies` → `projects` (+ partial `talent/blocks/projects.blade.php`),
  content-type key + registry, public controller `CaseStudyController` → `ProjectController`, route param
  `{caseStudy}` → `{project}` (URL `/{slug}/work/{project}`), factory, seeders (`default_blocks`,
  `BlockTypeSeeder` key), the public view, and the feature test. Docs (schema/architecture/api/conventions/
  specs) updated to match. Route name `talent.work` and the `/work/` segment kept. Full DB rebuilt +
  re-seeded; suite green.

## 2026-07-07 — Showcase demo data: 10 talents with images

- **`TalentShowcaseSeeder`** — ten published talents spanning all six professions (single- and
  multi-type: model, photographer, cinematographer, creative director, stylist, graphic designer, plus
  combinations), each with curated headline/bio/city/services/reviews and category-appropriate content,
  so discovery and profiles render with varied structures (5–9 blocks, rich vs lean).
- **Real images** — hero, avatar and gallery covers are generated locally with GD (deterministic
  gradient covers, no external assets/CDN) via the shared `Concerns\GeneratesCoverImages` trait, and
  attached through medialibrary. `TalentDemoSeeder` (Layla) now gets images too. Registered in
  `DatabaseSeeder`. Verified in-browser: discovery lists 11 talents with images; profiles show
  hero/avatar/gallery.

## 2026-07-06 — QA pass: discovery filter, dashboard deals, real demo data

- **Discovery profession filter showed `[object Object]`** (fixed): the filter printed the raw
  translations map; now resolves via `t(type.name)`.
- **Dashboard "Active deals" was a stale Phase-1E placeholder** (fixed): `DashboardController` loads the
  talent's live (non-terminal) deals and the home renders them with whose-turn highlighting + links to
  the deal room; empty state links to the inbox.
- **`<title>` doubled the suffix** ("Dashboard — Fama — Fama") — fixed in `talent-layout`.
- **Real demo data:** `TalentDemoSeeder` now seeds curated content (gallery captions, two Projects,
  rate-card services, four named reviews, brand collaborations) instead of Lorem-ipsum, plus a **live
  in-progress deal** (brand submitted the brief → talent's turn to quote) so the deal inbox/room and the
  dashboard demo the full lifecycle. New `ar.json` string for the empty-deals state.

## 2026-07-06 — New theme: cloud / graphite / teal + Bricolage + Sora

- Replaced the design-token palette and font pairing across every page (token-only change in
  `resources/css/app.css` + font `<link>` in `partials/design-head`): cool **cloud** surfaces,
  **graphite** ink, **teal** accent — for both light (`:root`) and dark (`[data-theme="dark"]`).
  (Surfaces were briefly peach, then swapped to cool cloud; only the surface/line tokens differ.)
- Fonts: **Bricolage Grotesque** (display) + **Sora** (UI/body) + **IBM Plex Sans Arabic** + **IBM Plex
  Mono**. The `font-display` utility now resolves through the dir-aware `--font-head`, so RTL/Arabic
  headings use IBM Plex Sans Arabic (Bricolage is Latin-only). Derived tokens (primary/on-primary/
  on-accent) re-tuned to the new palette; radii/shadows/motion unchanged. Verified in light, dark, RTL.

## 2026-07-06 — Content upload fix + "Projects" rename

- **Gallery/content upload broken (fixed):** the drop-zone and add-item form post the blank form which
  includes `position: null`; `position` is a NOT NULL column, so the insert 500'd. `BlockContentController::store`
  now appends (`position ?? count()`) instead of inserting null, and `createAndUpload` (dashboard.js)
  infers `media_type` from the dropped file. Regression test added.
- **"Case studies" → "Projects":** renamed the user-facing label (content-editor tab) and the
  `case_studies` block-type catalog name (en "Projects" / ar "المشاريع") + `lang/ar.json`. Internal
  identifiers (table `case_studies`, model `CaseStudy`, route `talent.work`) are unchanged.

## 2026-07-06 — RTL fixes + Arabic UI translations

- **Sidebar hidden in RTL (fixed):** the talent-layout `<aside>` mixed a static `sm:translate-x-0`
  desktop override with a dynamic `rtl:translate-x-full`; the `rtl:` rule ordered after `sm:` in the
  cascade and shoved the sidebar off-screen on desktop RTL. Scoped the off-canvas transform to
  `max-sm:` (mobile only) + added `start-0`, so desktop always uses the static in-flow column.
- **Couldn't switch back to EN (fixed):** with `hideDefaultLocaleInURL=true`, the `localeSessionRedirect`
  middleware bounced the prefix-less EN URL back to `/ar` (session locale trapped). Removed
  `localeSessionRedirect` from the locale route group — the URL prefix is now the single source of truth.
- **Arabic UI translations:** added `lang/ar.json` (313 strings — the full talent dashboard + public
  pages + auth) and `lang/{ar,en}/auth.php` for the dotted `auth.*` keys. `__()` now renders Arabic on
  `/ar` and English on the default locale.

## 2026-07-06 — Deal engine (Phase 1E, shared infrastructure)

- **Schema:** `deal_flows`, `deal_flow_steps`, `deals`, `deal_steps`, `deal_messages`, `deal_enquiries`
  (+ models, factories, `DealFlowSeeder` "Standard Booking"). A **minimal `brands` stub** table lands
  too (auth + name/slug + `is_complete` gate) so `deals.brand_id` FKs; Phase 1B extends it. `deal_steps`
  snapshots `settings`/`is_required`/`is_skippable` (ADR-4).
- **Engine:** StepHandler Strategy + `StepHandlerFactory` — one handler per step_type
  (form/approval/upload/payment/contract/message/schedule/info); `PaymentStepHandler` manual-vs-auto
  per ADR-B (default **manual**). Actions `SnapshotDealFlowSteps` / `InitiateDeal` / `AdvanceDeal` /
  `RejectStep` (loop-back) / `ConvertEnquiryToDeal`, sharing the `DealProgression` engine (one active
  step, status mirrors actor, auto system steps, system_events). State machines Deal / DealStep /
  DealMessage. `DealService` orchestrates in transactions with `deals`-channel fail-logging.
- **Booking CTA:** public `/{slug}/enquire` → `deal_enquiries` (availability-checked, no login),
  replacing the fire-and-forget enquiry; converts to a deal after brand auth (Phase 2).
- **Talent UI:** deal room (turn-aware stepper + `step_type` action panel + chat/system-event timeline
  + free messaging) and deals inbox (status/current-step/whose-turn, filter, paginate) — Blade shells +
  Alpine (`resources/js/deals.js`) on http.js, JSON envelopes, ownership 403 / out-of-turn 422.
- **Tests green:** 165 passed / 493 assertions (+29) — handlers, deal/step/message transitions (incl.
  illegal, reject-loop, skip), full initiate→advance→complete loop, convert, and the talent deal room
  (render, thread, advance, out-of-turn, message, ownership, inbox filter). docs (architecture engine +
  handler map, schema, api) updated. No git.

## 2026-07-06 — Public pages: profile, case study, reviews, discovery

- **Case-study detail** `GET /{slug}/work/{caseStudy}` (`CaseStudyController` + `public/case-study`
  view) — one `case_studies` record expanded; 404 unless published & owned. Profile case-study cards now
  link here.
- **Public review submission** `GET|POST /{slug}/review` (`PublicReviewController` + `StoreReviewRequest`
  + Alpine form) — writes a pending review (`is_approved = false`); "Leave a review" CTA added to the
  profile header.
- **Discovery / search** (ADR-6 applied, talent side): migration
  `add_discovery_search_indexes` (talents availability/published/city/country, `talent_talent_type.
  talent_type_id`, `equipment.category`, `software_stack.software_name`); `App\Queries\TalentSearch`
  (spatie/laravel-query-builder — filters type/category/availability/city/country/equipment/software/q,
  paginated, eager-loaded); `DiscoveryController` (page + Ajax) + `public/discover` view with the
  `talentSearch` Alpine component; `TalentCardResource`. "Discover" link in the public header.
- **Talent profile** `GET /{slug}` was already live (Phase 1 front-end); confirmed it renders the header
  from `talent_talent_type` (primary leading), visible blocks in position order, bumps `view_count` via
  event, and eager-loads everything (no N+1).
- **Tests green:** 136 passed / 431 assertions (+12) — profile & case-study render (+404s), review
  submission writes pending (+validation/unpublished 404), discovery filters (type/availability/
  location/equipment/software) + pagination. docs/schema.md (indexes) + api.md updated. No git.

## 2026-07-06 — Talent dashboard (talent guard)

- **Full talent dashboard** under `auth:talent` (`routes/talent.php`, `app/Http/Controllers/Talent/*`):
  home (draft/live, view_count, pending reviews, deals slot), profile editor (core fields + reorderable
  blocks + eligibility picker + hero upload), professions manager, block content editors (registry-driven
  controller for gallery/digitals/showreel/equipment/case-studies/software/brand-collabs/looks with
  medialibrary upload), rate card, availability & travel, reviews moderation, affiliations & press, and
  account/settings (slug + publish toggle).
- **Thin controllers → Phase 1B services**; Form Requests (`app/Http/Requests/Talent`) validate,
  Resources (`app/Http/Resources`) shape output, everything returns the JSON envelope. Own-resource
  access enforced (403 on foreign); domain-rule/illegal-transition errors return 422 envelopes
  (`bootstrap/app.php` renders).
- **Front-end**: Blade shells + Alpine (`resources/js/dashboard.js`: `profileEditor`, `professionsManager`,
  `crudList`) on the shared `http.js` wrapper — no page reloads, optimistic drag-reorder, inline
  validation errors, medialibrary uploads with skeleton/loading, dark/light + RTL. New `x-talent-layout`.
- **Tests green:** 124 passed / 400 assertions (+23) — each page's happy path, guest→login, admin
  rejection, ownership 403s, eligibility/duplicate 422s, and a media upload. No git.

## 2026-07-05 — Talent domain logic: block engine, services & state machines

- **Block engine.** Actions `MergeDefaultBlocksForTypes` (merge + de-dupe) and `SeedProfileBlocks`
  (idempotent seeding, Created → Draft). `ProfileBlockService` with an eligibility-aware picker
  (active + universal/by-category/by-type − non-repeatable already present) and add/fill/reorder/
  show-hide/remove; rendering still resolves via `block_type_id` so grandfathered blocks render.
- **Services.** `ProfessionsService` (add/remove types, primary, reorder; seeds missing blocks; blocks
  duplicates) and `TalentProfileService` (core fields, hero, availability, publish/unpublish, rate-card
  CRUD, reviews moderation, affiliations & press). All multi-write ops are `DB::transaction` +
  fail-logged via the service base.
- **State machines** (spatie/laravel-model-states) for 7 lifecycles — talent profile, availability,
  block, review, service, affiliation, portfolio media — with explicit transitions + a guarded `ToLive`
  publish. Added `status` columns (backfilled); the old booleans (`is_published`/`is_visible`/
  `is_approved`/`is_active`/`is_current`) are kept as projections synced by `SyncStateProjections`.
- **Events/listeners** (auto-discovered): `TalentProfileViewed` → view-count; `StateChanged` →
  projection sync + `published_at`; media added → log (media channel); conversion completed → advance
  portfolio media state. Controller now dispatches the view event.
- **Policies:** a talent may only manage its own resources (7 policies via `BasePolicy::owns`).
- **Tests green:** 101 passed / 294 assertions (+32) — every action, service path, and state transition
  incl. illegal ones (`CouldNotPerformTransition`), plus events and policy ownership. No git.

## 2026-07-05 — Design system foundation + live Talent Profile

- **Adopted the Fama design system** (from `public/fama-front`) into the real stack. Ported the design
  tokens (light + `[data-theme='dark']`) into `resources/css/app.css`, mapped them to Tailwind via
  `@theme inline` (theme-aware `bg-surface`/`text-ink`/`bg-accent`/`font-display`/`shadow-e2`…), added
  the Bodoni Moda + IBM Plex Sans/Arabic/Mono fonts, and switched dark mode from the `.dark` class to
  the `data-theme` attribute (layouts, nav toggle, and the `dark:` variant all reconciled).
- **Base UI components** (`resources/views/components/ui/*`): button, card, chip, badge, avatar
  (image/initials), eyebrow, stat, section — token-bound, dark + RTL aware. Plus a public layout,
  `x-theme-toggle`, and a DS locale switcher.
- **Live public Talent Profile** — `GET /{slug}` (`TalentProfileController`) renders a published talent
  (hero, identity header, then `profile_blocks` in order via `talent/blocks/{key}` partials) bound to
  the seeded demo talent. Verified in light, dark, LTR and Arabic/RTL against the running app; missing
  media degrades to gradient/initials placeholders.
- Removed a stray `es` (and duplicate `en`) locale from `config/laravellocalization.php` — supported
  locales are now **en, ar** only.
- **Tests green:** 69 passed / 206 assertions (+4 profile tests: published→200, draft/unknown→404,
  view-count increments). Existing Breeze pages re-themed without breakage. No git.

## 2026-07-05 — Test suite runs on MySQL

- Switched the Pest suite from in-memory SQLite to **MySQL** against a dedicated `fama_test` database
  (`phpunit.xml`: `DB_CONNECTION=mysql`, `DB_DATABASE=fama_test`), so tests exercise the same engine as
  dev/prod and never touch the dev `fama` data. Created `fama_test`. Full suite green (65/200) on MySQL.

## 2026-07-05 — Phase 1A: talent side + block system

- **Migrations** (4 files): `talents` (soft deletes); `talent_types` + `talent_talent_type` pivot; the
  block system (`block_types`, `block_type_category`, `block_type_talent_type`, `profile_blocks`); and
  13 talent content tables (portfolio_items, brand_collabs, reviews, services, comp_cards[1:1],
  look_types, digitals, showreels, equipment, case_studies, software_stack, agency_affiliations,
  press_features).
- **Models** (18): full `Talent` (SoftDeletes + HasMedia + HasTranslations, slug auto-gen), TalentType,
  BlockType, BlockTypeCategory, ProfileBlock, and the 13 content models. Relationships both directions,
  casts, sensible default eager-loads (`ProfileBlock` → `blockType`).
- **Media** (ADR-5): uploaded-asset `*_url`/`thumbnail_url` columns dropped and replaced by media-library
  collections + `thumb` conversions + accessors; external links/embeds kept as plain columns (+ new
  `portfolio_items.embed_url`).
- **Translatable** (ADR-7): content fields stored as per-locale JSON; final list recorded in
  `docs/conventions.md`.
- **Factories** for every model + seeders: `TalentTypeSeeder` (six professions), `BlockTypeSeeder`
  (block catalog with category gates), and `TalentDemoSeeder` — a multi-type (model + photographer)
  talent with merged/deduped blocks and populated content, wired into `DatabaseSeeder`.
- **Notes:** profile fields made nullable for progressive onboarding; `Talent` needs an explicit
  `$table` (the inflector leaves "talent" unpluralized); dev DB is MySQL, tests run on in-memory SQLite.
- **Tests green:** 65 passed / 200 assertions (relationships, casts, media collections, translatable,
  soft deletes, pivot uniqueness, demo seeder). Migrations run clean. No git.

## 2026-07-05 — Decision log formalized

- **`docs/decisions.md` rewritten as a lightweight ADR log** (Context / Decision / Status /
  Consequences). Recorded 10 **Accepted** ADRs — multi-guard auth; Services + Actions with one shared
  JSON envelope; state machines for every lifecycle; configurable deal-flow steps (Strategy/Factory,
  snapshotted into `deal_steps`); media library as source of truth for uploads; promoting
  query-critical JSON to pivots/indexed columns for discovery & search; i18n + RTL; quality gates;
  docs/process discipline; and the retained Tailwind v4 toolchain choice.
- Recorded **OPEN** decisions (needs owner) so they surface every session: A) three brand-side user
  modes (product); B) final-payment-leg automation boundary as a `PaymentStepHandler` setting
  (Kanta/billing); C) talent admission flow — self-signup vs admin approval (product); D) web login UX
  (unified role-aware assumed); E) confirm `brand-spec.md` completeness before Phase 2; plus retained
  F) `deals.campaign_id` FK and G) brand/talent password-reset tables.
- No code changes; no git.

## 2026-07-01 — Phase 0: foundation

- **App & packages.** Laravel 13 app with Breeze (Blade + Alpine + Tailwind + Vite, dark mode, Pest).
  Installed & configured: sanctum, spatie medialibrary, model-states, laravel-data, translatable,
  mcamara/laravel-localization, activitylog, query-builder, knuckleswtf/scribe, Pest (+ laravel plugin).
  Published vendor configs/migrations. Ran only vendor/auth migrations (users/sessions, sanctum
  personal_access_tokens, media, activity_log; model-states has none).
- **Multi-guard auth.** Three guards/providers in `config/auth.php` — admin→users, brand→brands,
  talent→talents. Breeze auth adapted into a DRY, guard-aware structure (`UserRole` enum, `Support\Auth\Guards`,
  role-aware `LoginRequest`/`AuthenticatedSessionController`), guarded dashboard groups per entity,
  public pages unguarded. Single role-aware login (recorded as an open decision). Sanctum reserved for
  the mobile API. Added `Brand`/`Talent` Authenticatable stubs (tables land in Phase 1).
- **i18n + RTL.** Locale-prefixed routes (`/en`, `/ar`, default hidden) + `x-language-switcher`;
  middleware aliases registered in `bootstrap/app.php`. `dir`-aware layouts; Tailwind dark mode = class
  strategy. Reconciled the front-end back to Tailwind v4 (Breeze had scaffolded v3).
- **Shared spine.** JSON envelope (`ApiResponse` + `response()->success|error|paginated` macros,
  `meta.pagination`); base `Service` (with `runInTransaction` + failure logging), `Action` contract,
  `BaseData`, `BasePolicy`, `BaseResource`; dedicated log channels (app/auth/deals/media); strict models
  in non-production; `resources/js/http.js` fetch wrapper.
- **Docs.** Wrote `docs/architecture.md`, `docs/schema.md`, `docs/api.md`, `docs/conventions.md`,
  `docs/decisions.md`; refreshed `CLAUDE.md` (standing rules + pattern map) and `README.md`.
- **Tests green.** Auth scaffolding + envelope covered by Pest; no git operations.

## 2026-07-01

- **Canonical specs installed.** Added the single source of truth for the data model, pages, workflows, and lifecycles under [`docs/specs/`](specs/):
  - `docs/specs/schema-master.md` — canonical database schema (all tables, columns, constraints, relationships).
  - `docs/specs/talent-spec.md` — talent-side pages, workflows, lifecycles, and the shared deal engine.
  - `docs/specs/brand-spec.md` — brand-side pages, onboarding, workflows, and lifecycles.
  - `docs/specs/README.md` — one-page index and the rule that all schema/feature work must be checked against these specs.
  - Root `CLAUDE.md` updated to point every future session to `docs/specs/` as the canonical reference before building anything.
  - No migrations or application code scaffolded yet — that is Phase 0.
