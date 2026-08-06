<?php

namespace App\Services;

use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Admin contract-template builder (ADR-V). Authors the reusable "contract schema"
 * (standard-terms parameters + the custom clause list) that flows render their
 * contract document from. Sibling to ContractFlowBuilderService — gated on the same
 * `manage-flows` permission; every mutation is transactional and audited (the
 * template's own LogsActivity trait tracks field changes on the `contract_flow` log).
 */
class ContractTemplateService extends AdminService
{
    private const FIELDS = [
        'name', 'description', 'applies_to', 'deposit_pct', 'deposit_holder',
        'cancellation_forfeit_pct', 'revision_rounds', 'preview_currency', 'intro', 'clauses',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $admin, array $data): ContractTemplate
    {
        $this->authorizeAdmin($admin, 'manage', ContractTemplate::class);

        return $this->runInTransaction(function () use ($data): ContractTemplate {
            return ContractTemplate::create(Arr::only($data, self::FIELDS) + [
                'clauses' => $data['clauses'] ?? ContractTemplate::defaultClauses(),
                'is_active' => true,
                'is_default' => false,
            ])->refresh();
        }, ['admin_id' => $admin->getKey()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $admin, ContractTemplate $template, array $data): ContractTemplate
    {
        $this->authorizeAdmin($admin, 'manage', $template);

        return $this->runInTransaction(function () use ($template, $data): ContractTemplate {
            $template->update(Arr::only($data, self::FIELDS));

            return $template->refresh();
        }, ['template_id' => $template->getKey()]);
    }

    /**
     * Make this template the default for its `applies_to` scope — at most one
     * default per scope (null scope = "all"), so the picker always has a clear
     * fallback. Also activates it (a default must be usable).
     */
    public function setDefault(User $admin, ContractTemplate $template): ContractTemplate
    {
        $this->authorizeAdmin($admin, 'manage', $template);

        return $this->runInTransaction(function () use ($admin, $template): ContractTemplate {
            ContractTemplate::query()
                ->where('id', '!=', $template->getKey())
                ->where(fn ($q) => $template->applies_to === null
                    ? $q->whereNull('applies_to')
                    : $q->where('applies_to', $template->applies_to))
                ->update(['is_default' => false]);

            $template->update(['is_default' => true, 'is_active' => true]);
            $this->record($admin, $template, 'contract_flow', 'contract_template.default');

            return $template->refresh();
        }, ['template_id' => $template->getKey()]);
    }

    /**
     * Toggle a template active/inactive. An inactive template is hidden from the
     * flow picker; existing links (and snapshotted contracts) are unaffected. The
     * default template cannot be deactivated (it would leave a scope without one).
     */
    public function setActive(User $admin, ContractTemplate $template, bool $active): ContractTemplate
    {
        $this->authorizeAdmin($admin, 'manage', $template);

        if (! $active && $template->is_default) {
            throw ValidationException::withMessages([
                'is_active' => __('The default template cannot be deactivated. Set another default first.'),
            ]);
        }

        return $this->runInTransaction(function () use ($admin, $template, $active): ContractTemplate {
            $template->update(['is_active' => $active]);
            $this->record($admin, $template, 'contract_flow', $active ? 'contract_template.activated' : 'contract_template.deactivated');

            return $template->refresh();
        }, ['template_id' => $template->getKey()]);
    }

    /**
     * Delete a template. Flows referencing it are detached (nullOnDelete); a
     * default template is protected from deletion.
     */
    public function delete(User $admin, ContractTemplate $template): void
    {
        $this->authorizeAdmin($admin, 'manage', $template);

        if ($template->is_default) {
            throw ValidationException::withMessages([
                'template' => __('The default template cannot be deleted. Set another default first.'),
            ]);
        }

        $this->runInTransaction(function () use ($admin, $template): void {
            $this->record($admin, $template, 'contract_flow', 'contract_template.deleted', ['name' => $template->name]);
            $template->delete();
        }, ['template_id' => $template->getKey()]);
    }
}
