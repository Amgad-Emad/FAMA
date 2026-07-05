# Fama — Canonical Specs

This folder holds the **single source of truth** for Fama's data model, pages, workflows, and lifecycles. Everything else in the codebase is an implementation of what these documents describe.

## The rule

> **All schema and feature work must be checked against these specs before it is built or changed.**
>
> If the code and a spec disagree, the spec wins — or the spec is updated *first*, deliberately, and the change is noted in [`../changelog.md`](../changelog.md). Do not silently diverge: no new table, column, page, workflow step, or lifecycle state should be introduced without tracing it back to one of the files below.

## What each file covers

| File | Covers |
|---|---|
| [`schema-master.md`](schema-master.md) | The complete database schema — every table, column, type, constraint, enum, and the relationships between them. Grouped by domain: talent core & block system, talent content tables, the deal engine, brand core & satellites, campaigns, and platform/admin. Ends with a relationship quick-map. |
| [`talent-spec.md`](talent-spec.md) | The **talent** side: public and authenticated pages, admin/system/brand/talent workflows, the shared **deal engine** (the deal loop both parties walk through), and every talent-facing lifecycle (deal, deal step, deal message, profile, availability, block, review, service, profession & agency affiliation, media, onboarding). |
| [`brand-spec.md`](brand-spec.md) | The **brand** side: public and dashboard pages, the 6-step onboarding wizard, brand workflows (onboarding, discovery, campaigns, trust & reputation, admin), and every brand-facing lifecycle (brand, aesthetics, images, creative needs, credibility, reviews, social handles, signals, campaign, campaign media) plus how they connect. |

## How they fit together

- `schema-master.md` defines the **structures**; `talent-spec.md` and `brand-spec.md` describe how those structures are **used** across pages, workflows, and lifecycles.
- The **deal engine** is shared: it is defined in the schema and documented in full in `talent-spec.md`; `brand-spec.md` consumes it from the brand side.
- The three files cross-reference each other by name and are meant to be read together.

## Editing these files

- Treat the technical content as authoritative. Fix only genuine typos/formatting; **do not change logic, table shapes, enums, or workflow semantics** without an explicit, recorded decision.
- Known open items are called out inline in the specs (e.g. `deals.campaign_id`, discovery/search schema additions). Resolve them by updating the spec first, then the code.
- Any change to a spec should be reflected in [`../changelog.md`](../changelog.md).
