/**
 * Global namespace for the drag and drop uploader.
 * Falls back to an empty object if not defined.
 * @namespace dragDropUploader
 */
const dragDropUploader = window.dragDropUploader || {};

/**
 * Stores registered filters by hook name.
 * @type {Object<string, Array<{callback: Function, priority: number}>>}
 */
dragDropUploader.filters = {};

/**
 * Adds a filter callback to a specified hook.
 *
 * @function
 * @memberof dragDropUploader
 * @param {string} hook - The name of the filter hook.
 * @param {Function} callback - The function to call when the hook is applied.
 * @param {number} [priority=10] - The priority of the callback. Lower numbers run earlier.
 * @returns {void}
 */
dragDropUploader.addFilter = function (hook, callback, priority = 10) {
    if (!dragDropUploader.filters[hook]) {
        dragDropUploader.filters[hook] = [];
    }

    dragDropUploader.filters[hook].push({ callback, priority });
    dragDropUploader.filters[hook].sort((a, b) => a.priority - b.priority);
};

/**
 * Applies all filter callbacks associated with a hook.
 *
 * @function
 * @memberof dragDropUploader
 * @param {string} hook - The name of the filter hook.
 * @param {*} value - The initial value to pass through the filters.
 * @param {...*} args - Additional arguments passed to each callback.
 * @returns {*} The filtered value after all callbacks have been applied.
 */
dragDropUploader.applyFilters = function (hook, value, ...args) {
    if (!dragDropUploader.filters[hook]) {
        return value;
    }

    return dragDropUploader.filters[hook].reduce((acc, filter) => {
        return filter.callback(acc, ...args);
    }, value);
};

export default dragDropUploader;
