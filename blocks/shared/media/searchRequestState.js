const createSearchRequestState = () => {
	let activeRequest = null;
	let requestSequence = 0;

	return {
		begin() {
			activeRequest?.controller.abort();

			const requestId = requestSequence + 1;
			const controller = new AbortController();
			requestSequence = requestId;
			activeRequest = { controller, requestId };

			return { requestId, signal: controller.signal };
		},

		isCurrent( requestId ) {
			return (
				requestId === undefined ||
				activeRequest?.requestId === requestId
			);
		},

		finish( requestId ) {
			if (
				requestId !== undefined &&
				activeRequest?.requestId !== requestId
			) {
				return false;
			}

			activeRequest = null;
			return true;
		},

		cancel() {
			activeRequest?.controller.abort();
			activeRequest = null;
		},
	};
};

module.exports = createSearchRequestState;
