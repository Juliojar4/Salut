<div class="block-name-block">
    <h3>{{ $title }}</h3>
    <div class="block-content">
        {!! wp_kses_post($content) !!}
    </div>
</div>