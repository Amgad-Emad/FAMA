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

Alpine.start();
