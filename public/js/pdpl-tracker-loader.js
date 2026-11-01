(function () {
    'use strict';

    function injectHtmlFragment(html, target) {
        if (!html || !target) return;
        var container = document.createElement('div');
        container.innerHTML = html;
        var nodes = Array.prototype.slice.call(container.childNodes);
        for (var i = 0; i < nodes.length; i++) {
            var node = nodes[i];
            if (node.nodeName && node.nodeName.toUpperCase() === 'SCRIPT') {
                var script = document.createElement('script');
                for (var j = 0; j < node.attributes.length; j++) {
                    var attr = node.attributes[j];
                    script.setAttribute(attr.name, attr.value);
                }
                script.textContent = node.textContent || '';
                target.appendChild(script);
            } else {
                target.appendChild(node.cloneNode(true));
            }
        }
    }

    function loadGtm(gtmId) {
        if (!gtmId) return;
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
        var j = document.createElement('script');
        j.async = true;
        j.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(gtmId);
        document.head.appendChild(j);
    }

    function loadGa(gaId) {
        if (!gaId) return;
        window['ga-disable-' + gaId] = false;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId);
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', gaId);
    }

    function loadMetaPixel(pixelId) {
        if (!pixelId || window.fbq) return;
        var n = (window.fbq = function () {
            if (n.callMethod) {
                n.callMethod.apply(n, arguments);
            } else {
                n.queue.push(arguments);
            }
        });
        if (!window._fbq) window._fbq = n;
        n.push = n;
        n.loaded = true;
        n.version = '2.0';
        n.queue = [];
        var t = document.createElement('script');
        t.async = true;
        t.src = 'https://connect.facebook.net/en_US/fbevents.js';
        document.head.appendChild(t);
        window.fbq('init', pixelId);
        window.fbq('track', 'PageView');
    }

    window.__musoftwareLoadTrackers = function (config) {
        if (window.__musoftwareTrackersLoaded || !config) return;
        window.__musoftwareTrackersLoaded = true;
        window.__musoftwareTrackingConfig = config;

        loadGtm(config.gtm_id);
        loadGa(config.ga_id);
        loadMetaPixel(config.meta_pixel_id);
        injectHtmlFragment(config.custom_head_scripts, document.head);
        if (document.body) {
            injectHtmlFragment(config.custom_body_scripts, document.body);
        }
    };

    window.__musoftwareRevokeTrackers = function () {
        var cfg = window.__musoftwareTrackingConfig || {};
        if (cfg.ga_id) {
            window['ga-disable-' + cfg.ga_id] = true;
        }
        var names = ['_ga', '_gid', '_gat', '_fbp', '_fbc', '_ttp'];
        for (var i = 0; i < names.length; i++) {
            document.cookie = names[i] + '=; Max-Age=0; path=/';
        }
    };
})();

