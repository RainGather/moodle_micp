define(['core/ajax'], function(Ajax) {
    var MESSAGE_NAMESPACE = 'mod_micp';
    var MESSAGE_TYPES = {
        INIT: 'init',
        CONTEXT: 'context',
        EVENT: 'event',
        SUBMIT: 'submit',
        RESIZE: 'resize',
        SUBMIT_RESULT: 'submit_result',
        SUBMIT_ERROR: 'submit_error'
    };
    var OBJECT_PROTOTYPE = Object.prototype;
    var state = {
        activity: null,
        iframe: null,
        iframeOrigin: null,
        maximizeButton: null,
        strings: {},
        listenerAttached: false,
        uiAttached: false,
        iframeLoadAttached: false,
        isIframeFullscreen: false,
        lastMeasuredHeight: 0,
        iframeResizeObserver: null
    };

    function isPlainObject(value) {
        return OBJECT_PROTOTYPE.toString.call(value) === '[object Object]';
    }

    function cloneValue(value) {
        if (value === null || typeof value === 'undefined') {
            return value;
        }

        return JSON.parse(JSON.stringify(value));
    }

    function resolveOrigin(url) {
        var link = document.createElement('a');

        link.href = url || window.location.href;

        if (!link.protocol || !link.host) {
            return window.location.origin;
        }

        return link.protocol + '//' + link.host;
    }

    function buildMessage(type, payload) {
        return {
            namespace: MESSAGE_NAMESPACE,
            type: type,
            payload: payload || {}
        };
    }

    function postToIframe(type, payload) {
        if (!state.iframe || !state.iframe.contentWindow || !state.iframeOrigin) {
            return;
        }

        state.iframe.contentWindow.postMessage(buildMessage(type, payload), state.iframeOrigin);
    }

    function callAjax(methodname, args) {
        return Ajax.call([{
            methodname: methodname,
            args: args
        }])[0];
    }

    function extractErrorMessage(error) {
        if (error && typeof error.message === 'string' && error.message) {
            return error.message;
        }

        if (error && error.error && typeof error.error === 'string' && error.error) {
            return error.error;
        }

        return state.strings.submiterror || '';
    }

    function setText(container, selector, value) {
        var node;

        if (!container) {
            return;
        }

        node = container.querySelector(selector);

        if (node) {
            node.textContent = String(value || '');
        }
    }

    function toggleClass(node, className, shouldEnable) {
        if (!node) {
            return;
        }

        if (shouldEnable) {
            node.classList.add(className);
        } else {
            node.classList.remove(className);
        }
    }

    function getViewportHeight() {
        return Math.max(window.innerHeight || 0, document.documentElement ? document.documentElement.clientHeight : 0);
    }

    function getIframeMinHeight() {
        if (state.isIframeFullscreen) {
            return Math.max(720, getViewportHeight() - 112);
        }

        return 640;
    }

    function updateMaximizeButton() {
        var label;

        if (!state.maximizeButton) {
            return;
        }

        label = state.isIframeFullscreen ?
            state.maximizeButton.getAttribute('data-label-restore') :
            state.maximizeButton.getAttribute('data-label-maximize');

        state.maximizeButton.textContent = label || '';
        state.maximizeButton.setAttribute('aria-pressed', state.isIframeFullscreen ? 'true' : 'false');
    }

    function getRestoreLabel() {
        if (!state.maximizeButton) {
            return state.strings.restoreinline || '';
        }

        return state.maximizeButton.getAttribute('data-label-restore') || state.strings.restoreinline || '';
    }

    function getIframeDocument() {
        if (!state.iframe || !state.iframe.contentWindow) {
            return null;
        }

        try {
            return state.iframe.contentDocument || state.iframe.contentWindow.document || null;
        } catch (error) {
            return null;
        }
    }

    function measureIframeDocumentHeight() {
        var iframeDocument = getIframeDocument();
        var body;
        var doc;

        if (!iframeDocument) {
            return 0;
        }

        body = iframeDocument.body;
        doc = iframeDocument.documentElement;

        return Math.max(
            body ? body.scrollHeight : 0,
            body ? body.offsetHeight : 0,
            body ? body.clientHeight : 0,
            doc ? doc.scrollHeight : 0,
            doc ? doc.offsetHeight : 0,
            doc ? doc.clientHeight : 0
        );
    }

    function ensureIframeDocumentStyle() {
        var iframeDocument = getIframeDocument();
        var styleNode;

        if (!iframeDocument || !iframeDocument.head) {
            return;
        }

        styleNode = iframeDocument.getElementById('mod-micp-host-style');

        if (!styleNode) {
            styleNode = iframeDocument.createElement('style');
            styleNode.id = 'mod-micp-host-style';
            iframeDocument.head.appendChild(styleNode);
        }

        styleNode.textContent = [
            'html, body {',
            '  overflow-x: hidden !important;',
            '  overflow-y: visible !important;',
            '  max-width: 100% !important;',
            '}',
            '#mod-micp-exit-fullscreen {',
            '  position: fixed;',
            '  top: 16px;',
            '  right: 16px;',
            '  z-index: 2147483647;',
            '  display: inline-flex;',
            '  align-items: center;',
            '  justify-content: center;',
            '  padding: 10px 14px;',
            '  border: 0;',
            '  border-radius: 999px;',
            '  background: rgba(15, 23, 42, 0.88);',
            '  color: #ffffff;',
            '  font: 600 14px/1.2 sans-serif;',
            '  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.22);',
            '  cursor: pointer;',
            '}',
            '#mod-micp-exit-fullscreen[hidden] {',
            '  display: none !important;',
            '}'
        ].join('\n');
    }

    function ensureIframeExitButton() {
        var iframeDocument = getIframeDocument();
        var button;

        if (!iframeDocument || !iframeDocument.body) {
            return;
        }

        button = iframeDocument.getElementById('mod-micp-exit-fullscreen');

        if (!button) {
            button = iframeDocument.createElement('button');
            button.id = 'mod-micp-exit-fullscreen';
            button.type = 'button';
            button.textContent = getRestoreLabel();
            button.setAttribute('hidden', 'hidden');
            button.addEventListener('click', function() {
                if (document.fullscreenElement === state.iframe && typeof document.exitFullscreen === 'function') {
                    document.exitFullscreen();
                }
            }, false);
            iframeDocument.body.appendChild(button);
        }

        button.textContent = getRestoreLabel();

        if (state.isIframeFullscreen) {
            button.removeAttribute('hidden');
        } else {
            button.setAttribute('hidden', 'hidden');
        }
    }

    function disconnectIframeObservers() {
        if (!state.iframeResizeObserver) {
            return;
        }

        state.iframeResizeObserver.disconnect();
        state.iframeResizeObserver = null;
    }

    function observeIframeDocument() {
        var iframeDocument = getIframeDocument();

        disconnectIframeObservers();

        if (!iframeDocument || typeof ResizeObserver === 'undefined') {
            return;
        }

        state.iframeResizeObserver = new ResizeObserver(function() {
            applyIframeHeight(measureIframeDocumentHeight());
        });

        if (iframeDocument.documentElement) {
            state.iframeResizeObserver.observe(iframeDocument.documentElement);
        }

        if (iframeDocument.body) {
            state.iframeResizeObserver.observe(iframeDocument.body);
        }
    }

    function syncIframeHeight() {
        var resolvedHeight;
        var measuredHeight = measureIframeDocumentHeight();

        if (!state.iframe) {
            return;
        }

        if (measuredHeight > 0) {
            state.lastMeasuredHeight = Math.max(state.lastMeasuredHeight, measuredHeight);
        }

        resolvedHeight = Math.max(getIframeMinHeight(), state.lastMeasuredHeight || 0, measuredHeight);
        state.iframe.style.minHeight = getIframeMinHeight() + 'px';
        state.iframe.style.height = resolvedHeight + 'px';
    }

    function syncLayoutState() {
        updateMaximizeButton();
        ensureIframeExitButton();
        syncIframeHeight();
    }

    function updateResultSummary(summary) {
        var activity;
        var container;
        var submittedAt;
        var detailsRegion;
        var detailsList;

        if (!isPlainObject(summary)) {
            return;
        }

        activity = state.activity || document.querySelector('[data-region="mod-micp-activity"]');

        if (!activity) {
            return;
        }

        container = activity.querySelector('[data-region="mod-micp-result-summary"]');

        if (!container) {
            return;
        }

        setText(container, '[data-field="status"]', summary.statuslabel);
        setText(container, '[data-field="score"]', summary.scorelabel);
        setText(container, '[data-field="rawgrade"]', summary.rawgradelabel);
        setText(container, '[data-field="grademax"]', summary.grademaxlabel);
        setText(container, '[data-field="interactions"]', summary.interactionslabel);
        setText(container, '[data-field="submittedat"]', summary.submittedatlabel);

        submittedAt = container.querySelector('[data-region="mod-micp-submitted-at"]');
        if (submittedAt) {
            toggleClass(submittedAt, 'mod-micp-activity__region--hidden', !summary.showsubmittedat);
        }

        detailsRegion = container.querySelector('[data-region="mod-micp-result-details"]');
        detailsList = container.querySelector('[data-region="mod-micp-result-details-list"]');
        if (detailsRegion && detailsList) {
            detailsList.innerHTML = '';

            if (summary.showdetails && Array.isArray(summary.details) && summary.details.length) {
                summary.details.forEach(function(detail) {
                    var item = document.createElement('li');
                    item.textContent = String(detail.label || state.strings.interactionfallbacklabel || '') + ' — ' + String(detail.scorelabel || '');
                    detailsList.appendChild(item);
                });
                toggleClass(detailsRegion, 'mod-micp-activity__region--hidden', false);
            } else {
                toggleClass(detailsRegion, 'mod-micp-activity__region--hidden', true);
            }
        }
    }

    function applyIframeHeight(height) {
        var resolvedHeight = Number(height) || 0;
        var measuredHeight = measureIframeDocumentHeight();

        if (resolvedHeight > 0) {
            state.lastMeasuredHeight = resolvedHeight;
        }

        if (measuredHeight > 0) {
            state.lastMeasuredHeight = Math.max(state.lastMeasuredHeight, measuredHeight);
        }

        if (!state.iframe) {
            return;
        }

        syncIframeHeight();
    }

    function handleMaximizeClick(event) {
        var fullscreenRequest;

        event.preventDefault();

        if (!state.iframe) {
            return;
        }

        if (document.fullscreenElement === state.iframe) {
            if (typeof document.exitFullscreen === 'function') {
                document.exitFullscreen();
            }
            return;
        }

        if (typeof state.iframe.requestFullscreen === 'function') {
            fullscreenRequest = state.iframe.requestFullscreen();
            if (fullscreenRequest && typeof fullscreenRequest.catch === 'function') {
                fullscreenRequest.catch(function() {
                    return null;
                });
            }
        }
    }

    function handleFullscreenChange() {
        state.isIframeFullscreen = document.fullscreenElement === state.iframe;
        syncLayoutState();
    }

    function attachIframeLoadHandler() {
        if (!state.iframe || state.iframeLoadAttached) {
            return;
        }

        state.iframe.addEventListener('load', function() {
            ensureIframeDocumentStyle();
            ensureIframeExitButton();
            observeIframeDocument();
            state.lastMeasuredHeight = 0;
            applyIframeHeight(measureIframeDocumentHeight());
        }, false);
        state.iframeLoadAttached = true;
    }

    function handleEvent(payload) {
        if (!payload.type) {
            return;
        }

        callAjax('mod_micp_report_event', {
            cmid: Number(window.MICP_CONTEXT && window.MICP_CONTEXT.cmid) || 0,
            eventtype: payload.type,
            payload: JSON.stringify(payload.data || {}),
            clientts: payload.clientts || null
        });
    }

    function handleSubmit(payload) {
        return callAjax('mod_micp_submit_attempt', {
            cmid: Number(window.MICP_CONTEXT && window.MICP_CONTEXT.cmid) || 0,
            rawjson: JSON.stringify(payload.result || {}),
            clientmeta: JSON.stringify({
                clientts: payload.clientts || null
            })
        }).then(function(result) {
            if (result && isPlainObject(result.resultsummary)) {
                updateResultSummary(result.resultsummary);
            }

            postToIframe(MESSAGE_TYPES.SUBMIT_RESULT, {
                result: cloneValue(result || {})
            });

            return result;
        }).catch(function(error) {
            postToIframe(MESSAGE_TYPES.SUBMIT_ERROR, {
                message: extractErrorMessage(error)
            });
        });
    }

    function validateIncomingMessage(event) {
        var data = event ? event.data : null;
        var payload = data ? data.payload : null;

        if (!state.iframe || event.source !== state.iframe.contentWindow || event.origin !== state.iframeOrigin) {
            return null;
        }

        if (!isPlainObject(data) || data.namespace !== MESSAGE_NAMESPACE || !isPlainObject(payload)) {
            return null;
        }

        if (data.type === MESSAGE_TYPES.INIT) {
            return {
                type: MESSAGE_TYPES.INIT,
                payload: payload
            };
        }

        if (data.type === MESSAGE_TYPES.EVENT && typeof payload.type === 'string' && payload.type) {
            return {
                type: MESSAGE_TYPES.EVENT,
                payload: payload
            };
        }

        if (data.type === MESSAGE_TYPES.SUBMIT) {
            return {
                type: MESSAGE_TYPES.SUBMIT,
                payload: payload
            };
        }

        if (data.type === MESSAGE_TYPES.RESIZE && typeof payload.height !== 'undefined') {
            return {
                type: MESSAGE_TYPES.RESIZE,
                payload: payload
            };
        }

        return null;
    }

    function onMessage(event) {
        var message = validateIncomingMessage(event);

        if (!message) {
            return;
        }

        if (message.type === MESSAGE_TYPES.INIT) {
            postToIframe(MESSAGE_TYPES.CONTEXT, {
                context: cloneValue(window.MICP_CONTEXT || {})
            });
            return;
        }

        if (message.type === MESSAGE_TYPES.EVENT) {
            handleEvent(message.payload);
            return;
        }

        if (message.type === MESSAGE_TYPES.SUBMIT) {
            handleSubmit(message.payload);
            return;
        }

        if (message.type === MESSAGE_TYPES.RESIZE) {
            applyIframeHeight(message.payload.height);
        }
    }

    function ensureListener() {
        if (state.listenerAttached) {
            return;
        }

        window.addEventListener('message', onMessage, false);
        state.listenerAttached = true;
    }

    function ensureUi() {
        if (state.uiAttached) {
            return;
        }

        if (state.maximizeButton) {
            state.maximizeButton.addEventListener('click', handleMaximizeClick, false);
        }

        window.addEventListener('resize', syncIframeHeight, false);
        document.addEventListener('fullscreenchange', handleFullscreenChange, false);
        state.uiAttached = true;
    }

    return {
        init: function(args) {
            var config = isPlainObject(args) ? args : {};

            state.activity = document.querySelector(config.activitySelector || '[data-region="mod-micp-activity"]');
            state.iframe = document.getElementById(config.iframeId || '');
            state.strings = isPlainObject(config.strings) ? cloneValue(config.strings) : {};
            state.maximizeButton = state.activity ? state.activity.querySelector('[data-action="toggle-maximized"]') : null;

            if (!state.activity || !state.iframe) {
                return false;
            }

            state.iframeOrigin = resolveOrigin(config.iframeSrc || state.iframe.getAttribute('src') || '');
            ensureListener();
            attachIframeLoadHandler();
            ensureUi();
            ensureIframeDocumentStyle();
            ensureIframeExitButton();
            observeIframeDocument();
            applyIframeHeight(measureIframeDocumentHeight());
            syncLayoutState();

            return true;
        }
    };
});
