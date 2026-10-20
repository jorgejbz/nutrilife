<div class="auth-notice-body">
    <span class="auth-notice-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>

    <span class="welcome-label">{{ $eyebrow }}</span>
    <h1>{{ $title }}</h1>

    @foreach($lines as $line)
        <p>{{ $line }}</p>
    @endforeach

    @if(! empty($steps))
        <ol class="auth-notice-steps">
            @foreach($steps as $step)
                <li>
                    <span aria-hidden="true"><i class="{{ $step['icon'] }}"></i></span>
                    {{ $step['text'] }}
                </li>
            @endforeach
        </ol>
    @endif

    <a href="{{ $action['url'] }}" class="btn-login auth-notice-action">
        <span>{{ $action['label'] }}</span>
        <div class="login-arrow"><i class="fas fa-arrow-right"></i></div>
    </a>

    @if($note)
        <p class="auth-notice-note"><i class="fas fa-info-circle" aria-hidden="true"></i> {{ $note }}</p>
    @endif
</div>
