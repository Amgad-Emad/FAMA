{{-- /llms.txt (ADR-AO, llmstxt.org): Markdown for AI assistants. Params: $profiles, $skills, $discover. Plain text — no HTML. --}}
# {!! config('seo.site_name') !!}

> {!! config('seo.description') !!}

Fama is a portfolio and community platform for creative professionals, starting in Egypt and the Arab world. Every talent has a public profile at {!! url('/') !!}/{username}. A profile is organised by **skill** (for example Photography, Styling, Modelling, Makeup, Direction); each skill has **specialities** (for example Fashion, Beauty, Portrait) and a tab of **projects**.

A **project** is a case study of real work: the client (unless confidential), the talent's role, the skills and specialities it used, the pictures and video, results, and the **team** — everyone credited on it. People credited from Fama confirm their credit before it shows, so the team on a project page is verified by the people on it.

## Key pages

- [Feed]({!! route('feed') !!}): what creatives on Fama are posting and working on now
@if ($discover)
- [Discover]({!! route('discover') !!}): search talent by skill, speciality and city
@endif
- [Sitemap]({!! route('seo.sitemap') !!}): every public profile and project
- [Join Fama]({!! route('register') !!}): create a talent profile

## How URLs work

- Profile: {!! url('/') !!}/{username}
- A skill tab on a profile: {!! url('/') !!}/{username}?skill={skill-slug}
- A project: {!! url('/') !!}/{username}/work/{project-id}

## Skills on Fama

@foreach ($skills as $skill)
- {!! $skill->name !!} (`{!! $skill->slug !!}`)
@endforeach

## Recently updated profiles

@forelse ($profiles as $talent)
- [{!! $talent->display_name ?: $talent->slug !!}]({!! route('talent.public', ['slug' => $talent->slug]) !!}){!! $talent->talentTypes->isNotEmpty() ? ': '.$talent->talentTypes->pluck('name')->implode(', ') : '' !!}{!! collect([$talent->base_city, $talent->base_country])->filter()->isNotEmpty() ? ' — '.collect([$talent->base_city, $talent->base_country])->filter()->implode(', ') : '' !!}
@empty
- No public profiles yet.
@endforelse

## Optional

- Private profiles, drafts and signed-in pages are never public and are not listed here.
