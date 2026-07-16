import Alpine from 'alpinejs';

// Shared Ajax wrapper (parses the JSON envelope, surfaces validation errors).
import './http';

window.Alpine = Alpine;

// Talent dashboard Alpine components (register on alpine:init before start()).
import './dashboard';
import './contracts';

// Brand dashboard Alpine components.
import './brand';

// Apply-to-project modal (public project detail).
import './apply';

Alpine.start();
