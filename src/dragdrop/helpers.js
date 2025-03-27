var easyDragDropFileUploader = window.easyDragDropFileUploader || {};

easyDragDropFileUploader.filters = {};

easyDragDropFileUploader.addFilter = function (hook, callback, priority = 10) {
	if (!easyDragDropFileUploader.filters[hook]) {
		easyDragDropFileUploader.filters[hook] = [];
	}

	easyDragDropFileUploader.filters[hook].push({ callback, priority });
	easyDragDropFileUploader.filters[hook].sort((a, b) => a.priority - b.priority);
};

easyDragDropFileUploader.applyFilters = function (hook, value, ...args) {
	if (!easyDragDropFileUploader.filters[hook]) {
		return value;
	}

	return easyDragDropFileUploader.filters[hook].reduce((acc, filter) => {
		return filter.callback(acc, ...args);
	}, value);
};

export default easyDragDropFileUploader;