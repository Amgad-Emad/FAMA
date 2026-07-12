@php $partial = 'talent.blocks.'.$block->blockType->key; @endphp
@includeWhen(view()->exists($partial), $partial, ['talent' => $talent, 'block' => $block])
@includeUnless(view()->exists($partial), 'talent.blocks.generic', ['talent' => $talent, 'block' => $block])
