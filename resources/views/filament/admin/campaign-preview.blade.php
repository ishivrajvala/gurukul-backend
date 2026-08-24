{{--
    THE EMAIL, RENDERED AS THE EMAIL.

    AN IFRAME, and it has to be one. What `Campaign::renderHtml()` returns is a complete HTML
    document — its own <html>, <head> and <body>, laid out in tables with inline styles for Outlook.
    Injected into the panel's DOM it would inherit the admin stylesheet, the Baloo type rules and
    the panel's ground, and show something that renders in no mail client anywhere. Inside an iframe
    it gets its own document and its own cascade, which is exactly what a mail client gives it.

    `srcdoc` rather than a URL, so the preview needs no route, no token and no way to be reached by
    anybody who is not already signed in and looking at this record.

    `sandbox` with nothing granted: the body is HTML somebody typed into an editor, and there is no
    reason for a preview to run scripts, submit forms or navigate the panel away from itself.
--}}
<div class="av-campaign-preview">
    <iframe
        srcdoc="{{ $html }}"
        sandbox=""
        title="Email preview"
        loading="lazy"
    ></iframe>
</div>
