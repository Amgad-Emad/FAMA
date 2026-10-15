{{-- One section by its block type (talent/blocks/{key}), else the generic fallback. --}}
@php $partial = 'talent.blocks.'.$block->blockType->key; @endphp
@includeWhen(view()->exists($partial), $partial, ['talent' => $talent, 'block' => $block, 'isOwner' => $isOwner ?? false])
@includeUnless(view()->exists($partial), 'talent.blocks.generic', ['talent' => $talent, 'block' => $block, 'isOwner' => $isOwner ?? false])
