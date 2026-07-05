# Decision log (ADRs)

> Lightweight architecture decision records. Every session inherits these. Keep entries short:
> **Context / Decision / Status / Consequences**. When a decision changes the data model, update
> `docs/specs/` too. `Accepted` = build to it; `OPEN` = do not assume — needs an owner's call.

## Accepted

### ADR-1 — Multi-guard web auth
- **Context:** Three distinct login entities (admin, brand, talent) plus a future mobile app.
- **Decision:** Breeze multi-guard session auth — `admin → users`, `brand → brands`, `talent → talents`. Sanctum for the mobile API.
- **Status:** Accepted.
- **Consequences:** Separate guarded route groups/dashboards per entity; a single role-aware login dispatches to the right guard (see ADR-D). Phase 0 default guard is `admin` (only migrated auth table); brand/talent tables land in Phase 1. Sanctum tokens issued in Phase 4.

### ADR-2 — Services + Actions, thin controllers, one envelope
- **Context:** Web-Ajax and API must never drift; logic must be reusable and testable.
- **Decision:** Business logic lives in Services and single-purpose invokable Action classes; controllers are thin and only orchestrate; every action returns a `Response`. Web-Ajax and API share one JSON envelope and the **same** services.
- **Status:** Accepted.
- **Consequences:** `{ success, data, message, errors, meta }` via `ApiResponse` + `response()` macros; DTOs flow Form Request → Service → Resource; controllers hold no queries/logic.

### ADR-3 — State machines for every lifecycle
- **Context:** Many entities have documented, guarded lifecycles.
- **Decision:** `spatie/laravel-model-states` models every documented lifecycle: deal, deal_step, deal_message, campaign, brand, talent profile, availability, review, service, affiliation, media.
- **Status:** Accepted.
- **Consequences:** Transitions are explicit, guarded, and auditable; each stateful model gets a state column + State classes (built with the model in Phase 1+).

### ADR-4 — Configurable deal-flow steps (Strategy/Factory) + snapshot
- **Context:** Admins configure deal flows; in-flight deals must stay stable when a flow is edited.
- **Decision:** One `StepHandler` per `step_type` (form/approval/upload/payment/contract/message/schedule/info) via Strategy + Factory. Flow steps are **snapshotted** into `deal_steps` at deal creation.
- **Status:** Accepted.
- **Consequences:** Editing/archiving a flow affects only **future** deals; adding a step type = a new handler, no schema change; final-payment automation is a handler setting (see ADR-B).

### ADR-5 — Media library is the source of truth for uploads
- **Context:** Uploaded assets vs. external links/embeds.
- **Decision:** `spatie/laravel-medialibrary` (collections + conversions for thumbnails) owns all **uploaded** files. Manual `*_url` columns for uploads are dropped and replaced by accessors resolving from the library. Plain URL columns are kept only for **external** links/embeds (YouTube/Vimeo, social, brand-collab, press).
- **Status:** Accepted.
- **Consequences:** Thumbnails are conversions, not columns; `docs/specs/schema-master.md` documents the original URL columns with an implementation note.

### ADR-6 — Promote query-critical JSON to pivots / indexed columns
- **Context:** Discovery & search must filter efficiently; several spec fields are JSON arrays.
- **Decision:** Promote query-critical arrays to pivot tables / indexed columns — `brand_creative_needs.talent_types`, `brand_aesthetics.mood_tags`, `equipment`, `software_stack`. Non-queried preferences stay JSON.
- **Status:** Accepted.
- **Consequences:** Indexed filtering with `spatie/laravel-query-builder`; a little denormalization; exact pivots defined in Phase 1. (Supersedes the earlier "discovery/search schema additions" open item.)

### ADR-7 — i18n + RTL
- **Context:** Bilingual product (English/Arabic), Egypt-first, RTL required.
- **Decision:** `mcamara/laravel-localization` for locale-prefixed routes (`/en`, `/ar`) + `spatie/laravel-translatable` for translatable attributes; RTL UI.
- **Status:** Accepted.
- **Consequences:** Default locale hidden in URL (`/login` and `/ar/login` both work); language switcher; `dir`-aware layouts with logical utilities; the translatable-attribute policy lives in `docs/conventions.md`.

### ADR-8 — Quality gates
- **Context:** Reliability of a multi-write, money-touching domain.
- **Decision:** Pest unit + feature tests; dedicated fail-log channels; `DB::transaction` on every multi-write operation.
- **Status:** Accepted.
- **Consequences:** Log channels `app`/`auth`/`deals`/`media`; `Service::runInTransaction()` wraps transaction + failure logging (catch → log to channel with context → rethrow / error envelope); green tests are part of the Definition of Done.

### ADR-9 — Docs & process discipline
- **Context:** Keep project knowledge coherent and avoid sprawl.
- **Decision:** Docs are a fixed set, updated **in place**; a descriptive docblock sits above every function; **no git operations, ever**.
- **Status:** Accepted.
- **Consequences:** `docs/` updated in place each phase + a dated `changelog` line; agents never `add`/`commit`/`push`.

### ADR-10 — Tailwind v4 toolchain *(retained from Phase 0)*
- **Context:** Breeze's installer scaffolded a Tailwind **v3** setup into a project whose Vite plugin is v4.
- **Decision:** Standardize on Tailwind **v4** via `@tailwindcss/vite`; dark mode is the **class** strategy.
- **Status:** Accepted.
- **Consequences:** v3 config files removed; `app.css` uses `@import 'tailwindcss'` + `@plugin '@tailwindcss/forms'` + `@custom-variant dark` + `@source`/`@theme`; `@tailwindcss/vite` restored in `vite.config.js`.

## Open — needs owner (surface every session)

### ADR-A — Three brand-side user modes
- **Context:** Brand accounts appear to support multiple user modes with different access.
- **Decision:** TBD — define the modes and their access/authorization logic.
- **Status:** OPEN — needs owner (**product**).
- **Consequences:** Shapes the brand guard/authorization model and possibly a `brand_users` (roles) table; blocks brand team/permission features.

### ADR-B — Final-payment-leg automation boundary
- **Context:** Whether the last payment step auto-completes or requires a manual confirmation.
- **Decision:** TBD — manual confirm vs. auto, expressed as a `PaymentStepHandler` setting.
- **Status:** OPEN — needs owner (**Kanta / billing**).
- **Consequences:** Defines `PaymentStepHandler` config and what triggers deal completion (ADR-4).

### ADR-C — Talent admission flow
- **Context:** Open self-signup vs. an admin-approval gate for talents.
- **Decision:** TBD — choose the admission model.
- **Status:** OPEN — needs owner (**product**).
- **Consequences:** Drives onboarding routing and the default of `talents.is_active` / publish gating.

### ADR-D — Web login UX
- **Context:** One unified login vs. separate per-role login routes.
- **Decision:** Default assumed — a **unified role-aware login** that dispatches to the correct guard. Alternative: separate prefixed login routes (`/talent/login`, …).
- **Status:** OPEN — confirm.
- **Consequences:** Current implementation is unified (`LoginRequest::role()`); switching later is contained to the auth routes/controller.

### ADR-E — Brand-spec completeness
- **Context:** Some brand-side spec sections originally referenced unfetchable Claude artifact URLs and may have gaps.
- **Decision:** Confirm completeness against `docs/specs/brand-spec.md` before building Phase 2.
- **Status:** OPEN — needs owner.
- **Consequences:** Phase 2 (brand) build is gated on this spec confirmation.

### ADR-F — `deals.campaign_id` FK *(retained from Phase 0)*
- **Context:** Brand/campaign workflows reference a deal running under a campaign, but the FK isn't in the `deals` definition.
- **Decision:** TBD — add `campaign_id → campaigns (nullable)` when the campaign⇄deal link is finalised.
- **Status:** OPEN.
- **Consequences:** Update `docs/specs/schema-master.md` §3 at the same time.

### ADR-G — Brand/talent password-reset tables *(retained from Phase 0)*
- **Context:** Phase 0 points the `brands`/`talents` password brokers at the shared `password_reset_tokens` table (inert — unused yet).
- **Decision:** TBD — dedicated per-entity reset tables vs. shared, decided when those auth flows are built.
- **Status:** OPEN.
- **Consequences:** Affects Phase 1 auth flow migrations for brand/talent.
