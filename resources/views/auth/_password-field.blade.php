<div class="field {{ $errors->has($name) ? 'has-error' : '' }}">
    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    <input type="password" id="{{ $id ?? $name }}" name="{{ $name }}" placeholder="{{ $placeholder ?? '••••••••' }}"
           autocomplete="{{ $autocomplete }}" required @if (! empty($autofocus)) autofocus @endif>
    <button type="button" class="toggle-pass" aria-label="Show password" data-toggle-pass>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
    </button>
</div>
