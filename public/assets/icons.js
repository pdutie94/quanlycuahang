;(function (window) {
    window.APP_ICONS = {
        'archive-box': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="__CLASS__"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>',
        'check-solid': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="__CLASS__"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.5a1 1 0 0 1-1.438.012L3.29 9.707a1 1 0 0 1 1.42-1.414l3.017 3.03 6.54-6.764a1 1 0 0 1 1.437.03Z" clip-rule="evenodd" /></svg>',
        'x-mark-solid': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="__CLASS__"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" /></svg>',
        'pencil-solid': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="__CLASS__"><path d="M13.586 3.586a2 2 0 0 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 4 13.172V16h2.828l7.38-7.379-2.83-2.828Z" /></svg>',
        'minus': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="__CLASS__"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>',
        'plus': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="__CLASS__"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>',
        'x-mark': '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="__CLASS__"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>'
    };

    window.appIcon = function (name, className) {
        var classes = className || 'size-4';
        var icon = (window.APP_ICONS && window.APP_ICONS[name]) || '';
        return icon ? icon.replace('__CLASS__', classes) : '';
    };
})(window);
