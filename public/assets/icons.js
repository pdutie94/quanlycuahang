;(function (window) {
    window.APP_ICONS = {
        'archive-box': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="__CLASS__"><rect width="20" height="5" x="2" y="3" rx="1" /><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" /><path d="M10 12h4" /></svg>',
        'check-solid': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="__CLASS__"><path d="M20 6 9 17l-5-5" /></svg>',
        'x-mark-solid': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="__CLASS__"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>',
        'pencil-solid': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="__CLASS__"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" /><path d="m15 5 4 4" /></svg>',
        'minus': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="__CLASS__"><path d="M5 12h14" /></svg>',
        'plus': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="__CLASS__"><path d="M5 12h14" /><path d="M12 5v14" /></svg>',
        'x-mark': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="__CLASS__"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>'
    };

    window.appIcon = function (name, className) {
        var classes = className || 'size-4';
        var icon = (window.APP_ICONS && window.APP_ICONS[name]) || '';
        return icon ? icon.replace('__CLASS__', classes) : '';
    };
})(window);
