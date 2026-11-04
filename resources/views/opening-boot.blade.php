{{--
    The opening's house, as plain HTML and SVG in the first response, so it paints
    and starts to animate before any JavaScript has run (and whether or not the
    Inertia SSR server is up). It mirrors the top of OpeningScene: the greeting's
    line, the house, the wordmark and the edition (the opening product's name,
    from the page's `opening` prop). The register, the rooms and
    the window buttons need data and script, so they arrive with React.

    When the Opening component mounts it copies this layer's animation clock onto
    its own markup, so nothing restarts, and then removes this layer.
    The CSS is the same: resources/css/opening.css.
--}}
@php
    $openingArt = resource_path('js/components/maison/opening/art/');
@endphp
<div id="op-boot" class="ma-opening" aria-hidden="true">
    <div class="op-scroll">
        <div class="op-sun"></div>

        <div class="op-stage">
            <div>
                <section class="op-scene">
                    {{-- Holds the greeting's line, so the house sits where React puts it. --}}
                    <p class="op-greeting op-in op-t0">&nbsp;</p>

                    <div class="op-house">
                        <svg class="mh-house" viewBox="958 142 662 796" focusable="false">{!! file_get_contents($openingArt.'door-house.html') !!}</svg>
                    </div>

                    <div class="op-identity">
                        <svg class="ma-wordmark op-in op-t1" viewBox="672.727 496.492 1001.83 87.0156" focusable="false">{!! file_get_contents($openingArt.'wordmark.html') !!}</svg>

                        {{-- The opening product's name, as React prints it; none when no offer is active. --}}
                        @if (filled($openingEdition = $page['props']['opening']['name'] ?? null))
                            <p class="op-edition op-in op-t2">{{ $openingEdition }}</p>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
