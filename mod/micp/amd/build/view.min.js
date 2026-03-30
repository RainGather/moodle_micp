define(['core/ajax'], function(Ajax) {
    var MESSAGE_NAMESPACE = 'mod_micp';
    var MESSAGE_TYPES = {
        INIT: 'init',
        CONTEXT: 'context',
        EVENT: 'event',
        SUBMIT: 'submit',
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
