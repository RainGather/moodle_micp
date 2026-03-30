// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

(function() {
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
        context: {},
        initialised: false,
        messageQueue: [],
        parentWindow: null,
        targetOrigin: null,
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

    function postMessage(message) {
        if (!state.initialised || !state.parentWindow || !state.targetOrigin) {
            state.messageQueue.push(message);
            return;
        }

        state.parentWindow.postMessage(message, state.targetOrigin);
    }

    function flushQueue() {
        var queue = state.messageQueue.slice(0);

        state.messageQueue = [];

        queue.forEach(function(message) {
            postMessage(message);
        });
    }

    function receiveMessage(event) {
        var data = event ? event.data : null;

        if (event.source !== state.parentWindow || event.origin !== state.targetOrigin || !isPlainObject(data)) {
            return;
        }

        if (data.namespace !== MESSAGE_NAMESPACE || data.type !== MESSAGE_TYPES.CONTEXT || !isPlainObject(data.payload)) {
            if (data && data.namespace === MESSAGE_NAMESPACE && isPlainObject(data.payload)) {
                if (data.type === MESSAGE_TYPES.SUBMIT_RESULT) {
                    window.dispatchEvent(new CustomEvent('micp:submit-success', {
                        detail: cloneValue(data.payload)
                    }));
                }

                if (data.type === MESSAGE_TYPES.SUBMIT_ERROR) {
                    window.dispatchEvent(new CustomEvent('micp:submit-error', {
                        detail: cloneValue(data.payload)
                    }));
                }
            }

            return;
        }

        state.context = cloneValue(data.payload.context || {});
    }

    function ensureListener() {
        if (state.listenerAttached) {
            return;
        }

        window.addEventListener('message', receiveMessage, false);
        state.listenerAttached = true;
    }

    window.MICP = {
        init: function(options) {
            var settings = isPlainObject(options) ? options : {};

            state.parentWindow = window.parent;
            state.targetOrigin = resolveOrigin(settings.targetOrigin || document.referrer || window.location.href);
            state.context = cloneValue(settings.context || state.context || {});
            state.initialised = true;

            ensureListener();
            postMessage(buildMessage(MESSAGE_TYPES.INIT, {
                requestedContext: true
            }));
            flushQueue();

            return this.getContext();
        },

        sendEvent: function(type, data) {
            var eventType = String(type || '');

            if (!eventType) {
                return false;
            }

            postMessage(buildMessage(MESSAGE_TYPES.EVENT, {
                type: eventType,
                data: cloneValue(data || {}),
                clientts: Date.now()
            }));

            return true;
        },

        submit: function(result) {
            postMessage(buildMessage(MESSAGE_TYPES.SUBMIT, {
                result: cloneValue(result || {}),
                clientts: Date.now()
            }));

            return true;
        },

        getContext: function() {
            return cloneValue(state.context || {});
        }
    };
})();
