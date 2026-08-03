<?php

// ----- 5S: the palette contrast gate is part of the suite --------------------

it('passes every Daylight contrast gate in both themes', function () {
    $this->artisan('feed:check-contrast')
        ->expectsOutputToContain('All Daylight contrast gates pass.')
        ->assertSuccessful();
});
