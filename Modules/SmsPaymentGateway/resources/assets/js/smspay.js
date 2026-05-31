/**
 * SmsPay.js — Embeddable Payment Widget
 * Lightweight JavaScript SDK for SMS Payment Gateway integration
 *
 * Usage:
 *   <script src="https://yoursite.com/js/smspay.js"></script>
 *   <script>
 *     const smspay = SmsPay('pk_live_xxxx');
 *     smspay.checkout({ sessionId: 'cs_xxx', onSuccess: (r) => {} });
 *   </script>
 *
 * @version 1.0.0
 * @license MIT
 */
(function (root) {
    'use strict';

    var SDK_VERSION = '1.0.0';
    var DEFAULT_BASE_URL = 'https://www.musoftwares.com';
    var POLL_INTERVAL = 3000; // 3 seconds
    var MODAL_Z_INDEX = 999999;

    /**
     * @param {string} publishableKey - pk_live_xxx or pk_test_xxx
     * @param {object} [options]
     * @param {string} [options.baseUrl]
     */
    function SmsPay(publishableKey, options) {
        if (!(this instanceof SmsPay)) {
            return new SmsPay(publishableKey, options);
        }

        if (!publishableKey || typeof publishableKey !== 'string') {
            throw new Error('SmsPay: A valid publishable key (pk_*) is required.');
        }
        if (publishableKey.indexOf('pk_') !== 0) {
            throw new Error('SmsPay: Key must start with "pk_". Use your publishable key, not your secret key.');
        }

        this._key = publishableKey;
        this._baseUrl = (options && options.baseUrl) || DEFAULT_BASE_URL;
        this._baseUrl = this._baseUrl.replace(/\/+$/, ''); // trim trailing slash
    }

    // ─── Redirect to Hosted Checkout ────────────────
    SmsPay.prototype.redirectToCheckout = function (params) {
        if (!params || !params.sessionId) {
            throw new Error('SmsPay: sessionId is required for redirectToCheckout.');
        }
        window.location.href = this._baseUrl + '/pay/' + params.sessionId;
    };

    // ─── Open Checkout Modal ────────────────────────
    SmsPay.prototype.checkout = function (params) {
        if (!params || !params.sessionId) {
            throw new Error('SmsPay: sessionId is required for checkout.');
        }

        var self = this;
        var sessionId = params.sessionId;
        var onSuccess = params.onSuccess || function () {};
        var onCancel = params.onCancel || function () {};
        var onError = params.onError || function () {};

        // Build iframe URL
        var iframeUrl = this._baseUrl + '/pay/' + sessionId;

        // Create overlay
        var overlay = _createElement('div', {
            style: _styles({
                position: 'fixed',
                top: '0',
                left: '0',
                right: '0',
                bottom: '0',
                background: 'rgba(0, 0, 0, 0.6)',
                backdropFilter: 'blur(4px)',
                WebkitBackdropFilter: 'blur(4px)',
                zIndex: MODAL_Z_INDEX,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '16px',
                opacity: '0',
                transition: 'opacity 0.3s ease',
            }),
        });

        // Create modal container
        var modal = _createElement('div', {
            style: _styles({
                width: '100%',
                maxWidth: '460px',
                maxHeight: '90vh',
                borderRadius: '20px',
                overflow: 'hidden',
                background: '#fff',
                boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.3)',
                transform: 'translateY(20px) scale(0.98)',
                transition: 'transform 0.3s cubic-bezier(0.16, 1, 0.3, 1)',
                position: 'relative',
            }),
        });

        // Close button
        var closeBtn = _createElement('button', {
            innerHTML: '&times;',
            style: _styles({
                position: 'absolute',
                top: '12px',
                right: '12px',
                zIndex: '10',
                width: '32px',
                height: '32px',
                borderRadius: '50%',
                border: 'none',
                background: 'rgba(255, 255, 255, 0.2)',
                backdropFilter: 'blur(10px)',
                color: '#fff',
                fontSize: '20px',
                fontWeight: '700',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                lineHeight: '1',
            }),
        });

        // Loading indicator
        var loader = _createElement('div', {
            style: _styles({
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                height: '500px',
                background: '#f0f2f5',
            }),
        });
        var spinner = _createElement('div', {
            style: _styles({
                width: '36px',
                height: '36px',
                border: '3px solid #e2e8f0',
                borderTopColor: '#4f46e5',
                borderRadius: '50%',
                animation: 'smspay-spin 0.8s linear infinite',
            }),
        });
        loader.appendChild(spinner);

        // Iframe
        var iframe = _createElement('iframe', {
            src: iframeUrl,
            style: _styles({
                width: '100%',
                height: '600px',
                maxHeight: '85vh',
                border: 'none',
                display: 'none',
            }),
            allow: 'clipboard-write',
        });

        iframe.onload = function () {
            loader.style.display = 'none';
            iframe.style.display = 'block';
        };

        modal.appendChild(closeBtn);
        modal.appendChild(loader);
        modal.appendChild(iframe);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        // Inject animation keyframes
        _injectStyles();

        // Animate in
        requestAnimationFrame(function () {
            overlay.style.opacity = '1';
            modal.style.transform = 'translateY(0) scale(1)';
        });

        // ── Close logic ──────────────────────────
        function closeModal() {
            overlay.style.opacity = '0';
            modal.style.transform = 'translateY(20px) scale(0.98)';
            setTimeout(function () {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
                if (pollTimer) clearInterval(pollTimer);
            }, 300);
        }

        closeBtn.onclick = function () {
            closeModal();
            onCancel();
        };

        overlay.onclick = function (e) {
            if (e.target === overlay) {
                closeModal();
                onCancel();
            }
        };

        // ESC key
        var escHandler = function (e) {
            if (e.key === 'Escape') {
                closeModal();
                onCancel();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);

        // ── Poll for completion ──────────────────
        var pollUrl = this._baseUrl + '/api/v1/sms-gateway/checkout/sessions/' + sessionId + '/poll?key=' + encodeURIComponent(this._key);

        var pollTimer = setInterval(function () {
            _httpGet(pollUrl, function (err, data) {
                if (err) return;
                if (data && data.status === 'complete') {
                    clearInterval(pollTimer);
                    closeModal();
                    onSuccess({
                        session: {
                            id: sessionId,
                            status: 'complete',
                            completed_at: data.completed_at,
                        },
                    });
                } else if (data && data.status === 'expired') {
                    clearInterval(pollTimer);
                    closeModal();
                    onError({ type: 'session_expired', message: 'Checkout session has expired.' });
                }
            });
        }, POLL_INTERVAL);

        // Return a handle for external control
        return {
            close: closeModal,
        };
    };

    // ─── Helpers ────────────────────────────────────

    function _createElement(tag, attrs) {
        var el = document.createElement(tag);
        if (attrs) {
            for (var key in attrs) {
                if (key === 'style') {
                    el.setAttribute('style', attrs[key]);
                } else if (key === 'innerHTML') {
                    el.innerHTML = attrs[key];
                } else {
                    el.setAttribute(key, attrs[key]);
                }
            }
        }
        return el;
    }

    function _styles(obj) {
        var s = '';
        for (var k in obj) {
            s += k.replace(/([A-Z])/g, '-$1').toLowerCase() + ':' + obj[k] + ';';
        }
        return s;
    }

    function _httpGet(url, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.timeout = 10000;
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        callback(null, JSON.parse(xhr.responseText));
                    } catch (e) {
                        callback(e);
                    }
                } else {
                    callback(new Error('HTTP ' + xhr.status));
                }
            }
        };
        xhr.onerror = function () { callback(new Error('Network error')); };
        xhr.send();
    }

    var _stylesInjected = false;
    function _injectStyles() {
        if (_stylesInjected) return;
        _stylesInjected = true;
        var style = document.createElement('style');
        style.textContent = '@keyframes smspay-spin { to { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }

    // ─── Export ──────────────────────────────────────
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = SmsPay;
    }
    root.SmsPay = SmsPay;

})(typeof window !== 'undefined' ? window : this);
