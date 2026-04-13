@extends('site.layout')

@section('content')
<p class="eyebrow">Portfolio highlights</p>
<h2>Selected properties under active operations.</h2>
<p>We currently manage residential and mixed-use assets across key urban centers, with standardized reporting and responsive field support.</p>

<div class="grid grid-3" style="margin-top:20px;">
    <article class="tile reveal">
        <h3>Westlands Apartments</h3>
        <p class="subtle">Nairobi, Kenya</p>
        <p>12 units with monthly rent performance dashboards and maintenance SLA tracking.</p>
    </article>
    <article class="tile reveal">
        <h3>Kileleshwa Residences</h3>
        <p class="subtle">Nairobi, Kenya</p>
        <p>8 units with centralized tenant communication and recurring invoice automation.</p>
    </article>
    <article class="tile reveal">
        <h3>Mombasa Commercial Plaza</h3>
        <p class="subtle">Mombasa, Kenya</p>
        <p>20 units with occupancy trend visibility and coordinated support operations.</p>
    </article>
</div>

<div class="stack" style="margin-top:14px;">
    <a href="/contact" class="btn btn-primary">Request Portfolio Management</a>
</div>
@endsection
