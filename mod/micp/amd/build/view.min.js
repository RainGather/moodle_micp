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
        iframe: null,
        iframeOrigin: null,
        listenerAttached: false
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

        return 'Submission failed. Please try again.';
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

    function updateResultSummary(summary) {
        var activity;
        var container;
        var submittedAt;
        var detailsRegion;
        var detailsList;

        if (!isPlainObject(summary)) {
            return;
        }

        activity = document.querySelector('[data-region="mod-micp-activity"]');

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
            submittedAt.style.display = summary.showsubmittedat ? '' : 'none';
        }

        detailsRegion = container.querySelector('[data-region="mod-micp-result-details"]');
        detailsList = container.querySelector('[data-region="mod-micp-result-details-list"]');
        if (detailsRegion && detailsList) {
            detailsList.innerHTML = '';

            if (summary.showdetails && Array.isArray(summary.details) && summary.details.length) {
                summary.details.forEach(function(detail) {
                    var item = document.createElement('li');
                    item.textContent = String(detail.label || 'Interaction') + ' — ' + String(detail.scorelabel || '');
                    detailsList.appendChild(item);
                });
                detailsRegion.style.display = '';
            } else {
                detailsRegion.style.display = 'none';
            }
        }
    }

    function applyIframeHeight(height) {
        var resolvedHeight = Number(height) || 0;

        if (!state.iframe || resolvedHeight <= 0) {
            return;
        }

        state.iframe.style.height = Math.max(480, resolvedHeight) + 'px';
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

    return {
        init: function(args) {
            var config = isPlainObject(args) ? args : {};

            state.iframe = document.getElementById(config.iframeId || '');

            if (!state.iframe) {
                return false;
            }

            state.iframeOrigin = resolveOrigin(config.iframeSrc || state.iframe.getAttribute('src') || '');
            ensureListener();

            return true;
        }
    };
});
