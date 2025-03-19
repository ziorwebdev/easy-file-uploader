var wpFilepond = window.wpFilepond || {};

wpFilepond.filters = {};

wpFilepond.addFilter = function (hook, callback, priority = 10) {
	if (!wpFilepond.filters[hook]) {
		wpFilepond.filters[hook] = [];
	}

	wpFilepond.filters[hook].push({ callback, priority });
	wpFilepond.filters[hook].sort((a, b) => a.priority - b.priority);
};

wpFilepond.applyFilters = function (hook, value, ...args) {
	if (!wpFilepond.filters[hook]) {
		return value;
	}

	return wpFilepond.filters[hook].reduce((acc, filter) => {
		return filter.callback(acc, ...args);
	}, value);
};

export default wpFilepond;