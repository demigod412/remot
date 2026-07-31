@php
$tabs = [
    ['general',      'General',      'sliders',      route('admin.settings.general')],
    ['mail',         'Mail',         'mail',         route('admin.settings.mail')],
    ['notification', 'Notification', 'bell',         route('admin.settings.notification')],
    ['logo',         'Logo & Icon',  'image',        route('admin.settings.logo')],
    ['social',       'Social Login', 'log-in',       route('admin.settings.social')],
    ['links',        'Links & Social','link',        route('admin.settings.links')],
    ['firebase',     'Firebase',     'flame',        route('admin.settings.firebase')],
    ['css',          'Custom CSS',   'code',         route('admin.settings.css')],
    ['license',      'License',      'shield-check', route('admin.settings.license')],
];
@endphp
<nav style="display:flex;flex-direction:column;gap:2px;font-size:13px;">
    @foreach($tabs as [$key, $label, $icon, $url])
    <a href="{{ $url }}"
       style="display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:8px;text-decoration:none;
              background:{{ ($active ?? '') === $key ? 'var(--surface-2)' : 'transparent' }};
              color:{{ ($active ?? '') === $key ? 'var(--fg)' : 'var(--fg-2)' }};
              font-weight:{{ ($active ?? '') === $key ? '500' : '400' }};"
       onmouseover="this.dataset.hovered='1';if(!this.dataset.active){this.style.background='var(--surface)';this.style.color='var(--fg)';}"
       onmouseout="this.dataset.hovered='';if(!this.dataset.active){this.style.background='transparent';this.style.color='var(--fg-2)';}"
       @if(($active ?? '') === $key) data-active="1" @endif>
        <i data-lucide="{{ $icon }}" style="width:14px;height:14px;flex-shrink:0;color:{{ ($active ?? '') === $key ? 'var(--accent)' : 'inherit' }};"></i>
        {{ $label }}
    </a>
    @endforeach
</nav>
