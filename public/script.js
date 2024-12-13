    var _paq = window._paq = window._paq || [];
    /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
    _paq.push(["setDocumentTitle", document.domain + "/" + document.title]);
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (function() {
    var u="//matomo.deliccaa.com/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '1']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
})();

(function rewriteUrlsWithProxy() {
    // Dynamically derive proxyHost from the current domain
    const proxyHost = window.location.hostname.split('.').slice(-2).join('.');

    /**
     * Rewrite a given URL to use the proxy.
     * @param {string} url - The original URL.
     * @returns {string} - The rewritten URL with the proxy.
     */
    function rewriteUrl(url) {
        try {
            // Handle protocol-relative URLs (e.g., //example.com)
            if (url.startsWith('//')) {
                url = window.location.protocol + url;
            }

            const parsedUrl = new URL(url, window.location.href);

            // Check if the URL is already rewritten to the proxy
            if (parsedUrl.hostname.endsWith('.' + proxyHost)) {
                return url; // Skip rewriting for already rewritten URLs
            }

            // If the URL has a host, rewrite it for the proxy
            if (parsedUrl.hostname) {
                if (parsedUrl.hostname === proxyHost) {
                    return url;
                }
                const proxySubdomain = parsedUrl.hostname.replace(/\./g, 'xix') + '.' + proxyHost;
                return `${parsedUrl.protocol}//${proxySubdomain}${parsedUrl.pathname}${parsedUrl.search}${parsedUrl.hash}`;
            }
        } catch (e) {
            console.error('Failed to rewrite URL:', url, e);
        }

        // Return original URL if rewriting fails
        return url;
    }

    /**
     * Check and rewrite a URL attribute if necessary.
     * @param {HTMLElement} element - The element to process.
     * @param {string} attribute - The attribute to check and rewrite.
     */
    function checkAndRewriteAttribute(element, attribute) {
        if (element.hasAttribute(attribute)) {
            const originalUrl = element.getAttribute(attribute);
            const rewrittenUrl = rewriteUrl(originalUrl);

            if (originalUrl !== rewrittenUrl) {
                element.setAttribute(attribute, rewrittenUrl);
            }
        }
    }

    /**
     * Rewrite URLs in all matching attributes and inline styles of the given element.
     * @param {HTMLElement} element - The element to process.
     */
    function processElement(element) {
        const attributes = ['href', 'src', 'action'];

        // Rewrite attributes
        attributes.forEach(attr => {
            checkAndRewriteAttribute(element, attr);
        });

        // Rewrite inline styles with background-image URLs
        const style = element.style;
        if (style && style.backgroundImage) {
            const urlMatch = style.backgroundImage.match(/url\((['"]?)(.*?)\1\)/);
            if (urlMatch && urlMatch[2]) {
                const originalUrl = urlMatch[2];
                const rewrittenUrl = rewriteUrl(originalUrl);
                if (originalUrl !== rewrittenUrl) {
                    style.backgroundImage = `url("${rewrittenUrl}")`;
                }
            }
        }
    }

    /**
     * Process CSS rules in <style> tags and linked CSS files.
     */
    function processStylesheets() {
        Array.from(document.styleSheets).forEach(stylesheet => {
            try {
                if (!stylesheet.cssRules) return; // Skip if no access to rules

                Array.from(stylesheet.cssRules).forEach(rule => {
                    if (rule.style && rule.style.backgroundImage) {
                        // Rewrite background-image URLs
                        const urlMatch = rule.style.backgroundImage.match(/url\((['"]?)(.*?)\1\)/);
                        if (urlMatch && urlMatch[2]) {
                            const originalUrl = urlMatch[2];
                            const rewrittenUrl = rewriteUrl(originalUrl);
                            if (originalUrl !== rewrittenUrl) {
                                rule.style.backgroundImage = `url("${rewrittenUrl}")`;
                            }
                        }
                    } else if (rule instanceof CSSImportRule) {
                        // Rewrite @import rules
                        const originalUrl = rule.href;
                        const rewrittenUrl = rewriteUrl(originalUrl);
                        if (originalUrl !== rewrittenUrl) {
                            rule.href = rewrittenUrl;
                        }
                    }
                });
            } catch (e) {
                console.error('Error processing stylesheet:', e);
            }
        });
    }

    /**
     * Ensure URLs are rewritten on hover or click.
     */
    function setupEventListeners() {
        document.body.addEventListener('mouseover', event => {
            const target = event.target;
            if (target instanceof HTMLElement) {
                processElement(target);
            }
        });

        document.body.addEventListener('click', event => {
            const target = event.target;
            if (target instanceof HTMLElement) {
                processElement(target);
            }
        });
    }

    /**
     * Process all existing and future elements in the document.
     * @param {Node} node - The node to process.
     */
    function processNode(node) {
        if (node.nodeType === Node.ELEMENT_NODE) {
            processElement(node);
        }

        if (node.querySelectorAll) {
            node.querySelectorAll('a, img, script, link, form, [style]').forEach(processElement);
        }
    }

    // Process the initial document
    processNode(document);

    // Process stylesheets for background-image and @import rules
    processStylesheets();

    // Ensure URLs are rewritten on hover or click
    setupEventListeners();

    // Observe for dynamically added content
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(processNode);
        });

        // Re-process stylesheets in case new <style> or <link> elements are added
        processStylesheets();
    });

    // Use MutationObserver to monitor DOM changes
    // This script avoids deprecated events like DOMSubtreeModified or DOMNodeInserted
    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
})();
