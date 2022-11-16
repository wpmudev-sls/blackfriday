/**
 * Make a fetch request with POST data.
 *
 * @param {string} url
 * @param {object} data
 *
 * @returns {Promise}
 */
const postRequest = async (url, data) => {
	// Setup data.
	const formData = new FormData()
	// Set values.
	if (Object.keys(data).length > 0) {
		Object.keys(data).forEach((key) => {
			formData.append(key, data[key])
		})
	}

	// Extend the notice.
	return await fetch(url, {
		method: 'POST',
		body: formData,
	})
}

export { postRequest }
