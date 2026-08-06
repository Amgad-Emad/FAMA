<?php

namespace App\Http\Controllers\Admin;

use App\Contracting\ContractDocument;
use App\Http\Requests\Admin\PreviewContractTemplateRequest;
use App\Http\Requests\Admin\StoreContractTemplateRequest;
use App\Http\Resources\ContractTemplateResource;
use App\Models\ContractTemplate;
use App\Services\ContractTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Admin contract-template builder (ADR-V). Authors the reusable "contract schema"
 * (standard terms + custom clause list) rendered into a live contract document.
 * Thin — delegates mutations to ContractTemplateService; the live preview renders
 * the SAME canonical partial the contract room uses. `can:manage-flows` gates the
 * routes.
 */
class ContractTemplateController extends AdminController
{
    public function __construct(private readonly ContractTemplateService $service) {}

    public function index(): View
    {
        return view('admin.contract-templates.index');
    }

    public function data(): JsonResponse
    {
        $paginator = ContractTemplate::query()->withCount('flows')->latest()->paginate(20);

        return response()->paginated($paginator, ContractTemplateResource::collection($paginator->getCollection()));
    }

    public function store(StoreContractTemplateRequest $request): JsonResponse
    {
        $template = $this->service->create($this->admin(), $request->validated());

        return response()->success(
            ['id' => $template->id, 'redirect' => route('admin.contract-templates.show', $template)],
            __('Template created.'),
            status: 201,
        );
    }

    public function show(ContractTemplate $template): View
    {
        return view('admin.contract-templates.show', ['template' => $template]);
    }

    public function showData(ContractTemplate $template): JsonResponse
    {
        $template->loadCount('flows');

        return response()->success(['template' => new ContractTemplateResource($template)]);
    }

    public function update(StoreContractTemplateRequest $request, ContractTemplate $template): JsonResponse
    {
        $this->service->update($this->admin(), $template, $request->validated());

        return response()->success(null, __('Template saved.'));
    }

    public function setDefault(ContractTemplate $template): JsonResponse
    {
        $this->service->setDefault($this->admin(), $template);

        return response()->success(null, __('Default template set.'));
    }

    public function toggle(ContractTemplate $template): JsonResponse
    {
        $this->service->setActive($this->admin(), $template, ! $template->is_active);

        return response()->success(null, __('Template updated.'));
    }

    public function destroy(ContractTemplate $template): JsonResponse
    {
        $this->service->delete($this->admin(), $template);

        return response()->success(null, __('Template deleted.'));
    }

    /**
     * Live preview: render the canonical contract document from the (unsaved) draft
     * terms + sample negotiation inputs. Returns the same {html} the contract room
     * shows, so "how it will look" is WYSIWYG-accurate.
     */
    public function preview(PreviewContractTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();

        $template = new ContractTemplate([
            'deposit_pct' => $data['deposit_pct'] ?? 50,
            'deposit_holder' => $data['deposit_holder'] ?? 'Fama',
            'cancellation_forfeit_pct' => $data['cancellation_forfeit_pct'] ?? 50,
            'revision_rounds' => $data['revision_rounds'] ?? 1,
            'preview_currency' => $data['preview_currency'] ?? 'EGP',
            'intro' => $data['intro'] ?? null,
            'clauses' => $data['clauses'] ?? [],
        ]);

        $doc = ContractDocument::fromTemplate($template, $data['sample'] ?? []);

        return response()->success(['html' => view('contracts.partials.document', ['doc' => $doc])->render()]);
    }
}
