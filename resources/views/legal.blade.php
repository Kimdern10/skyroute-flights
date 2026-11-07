<x-layouts::site :title="$title">
    <section class="page-hero">
        <div class="container">
            <h1>{{ $title }}</h1>
            <p>{{ \App\Models\Setting::get('business_name') }}</p>
        </div>
    </section>

    <div class="container" style="padding-bottom: 60px">
        <article class="panel prose-legal">
            {!! \Illuminate\Support\Str::markdown($markdown, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
        </article>
    </div>
</x-layouts::site>
