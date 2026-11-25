import Alpine from 'alpinejs';

// Shared Ajax wrapper (parses the JSON envelope, surfaces validation errors).
import './http';

// Localized status labels ($statusLabel).
import './status';

// Global confirmation dialog ($confirm) for destructive actions.
import './confirm';

window.Alpine = Alpine;

// Talent dashboard Alpine components (register on alpine:init before start()).
import './dashboard';
import './contracts';

// Brand dashboard Alpine components.
import './brand';

// Admin dashboard Alpine components.
import './admin';

// Apply-to-project modal (public project detail).
import './apply';

// Public Daylight feed (stream, frames, casting strip).
import './feed';

// Fixed topbar scroll behaviour (5AB).
import './topbar';

// fama-ui design (2026-10-12): shell (nav, bell, toast) + per-area page components.
import './ui/shell';
import './ui/combo';
import './ui/feed';
import './ui/profile';
import './ui/projects';
import './ui/project-editor';
import './ui/onboarding';
import './ui/auth';

Alpine.start();
