{{-- The sign-in showcase panel (port of the prototype's FAMA.showcase): the one
     purple block — headline + line, the studio photo along the bottom and the
     logo star on the seam. Decorative, so aria-hidden. --}}
<section class="au-show" id="show" aria-hidden="true">
    <div class="au-in">
        <div>
            <h1>{{ __('Your work,') }}<br>{{ __('your people.') }}</h1>
            <p>{{ __('Build a profile the industry actually reads.') }}</p>
        </div>
    </div>
    <div class="au-photo" role="presentation"></div>
    <svg class="hstar" viewBox="10 80 760 690" aria-hidden="true"><polygon points="314.63 635.59 16.74 747.68 217.29 533.15 86.97 370.29 403.26 356.02 701.63 87.21 636.51 355.76 760 356.91 574.68 534.04 422.33 756.79 314.63 635.59"/></svg>
</section>
