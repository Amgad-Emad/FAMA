import Alpine from 'alpinejs';

// Shared Ajax wrapper (parses the JSON envelope, surfaces validation errors).
import './http';

window.Alpine = Alpine;

// Talent dashboard Alpine components (register on alpine:init before start()).
import './dashboard';

Alpine.start();
