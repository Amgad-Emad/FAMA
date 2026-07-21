/**
 * `$statusLabel(value)` — localizes a raw status string (e.g. 'in_progress' →
 * "In progress", or its Arabic equivalent). The label map is provided per page
 * by <x-status-labels> (Blade, so __() localizes it); unknown values fall back
 * to a humanized form. Used by every admin list pill.
 */
document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;
    const humanize = (s) => (s ?? '').toString().replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

    const statusMap = window.__famaStatusLabels || {};
    const actorMap = window.__famaActorLabels || {};
    const stepMap = window.__famaStepLabels || {};
    const categoryMap = window.__famaCategoryLabels || {};
    const flowMap = window.__famaFlowLabels || {};

    Alpine.magic('statusLabel', () => (value) => statusMap[value] ?? humanize(value));
    Alpine.magic('actorLabel', () => (value) => actorMap[value] ?? humanize(value));
    Alpine.magic('categoryLabel', () => (value) => categoryMap[value] ?? humanize(value));
    // Localize a seeded flow name by slug; custom flows keep their stored name.
    Alpine.magic('flowLabel', () => (flow) => (flow && (flowMap[flow.slug] ?? flow.name)) ?? '');
    // Localize a contract step by its stable key; fall back to the stored name
    // (custom flows) or a humanized key.
    Alpine.magic('stepLabel', () => (step) => {
        if (!step) return '';
        return stepMap[step.key] ?? step.name ?? humanize(step.key);
    });
});
