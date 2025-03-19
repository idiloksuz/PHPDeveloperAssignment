// I am using axios an HTTP client for making API requests.
const axios = require('axios');


async function fetchAPIs(urls) {
    console.log('Fetching API...');

    // I decided use Promises for a couple of reasons:
    // To make multiple API requests in parallel.
    // To handle both successful and failed requests.
    // or else i would have to handle each request with a callback function which would be a lot of code.
    try {
        const results = await Promise.allSettled(
            urls.map(url => axios.get(url, { timeout: 5000 }))
            //Maps each URL to an Axios GET request.
            // 5s timeout per request
            //Promise.allSettled runs all requests parallelly. It does not stop in failure.
        );
        const combinedData = results
            .filter(result => result.status === 'fulfilled') // Filters out failed requests.
            .map(result => result.value.data); // Gets the JSON data from each successful request.

        // Log errors for failed requests.
        results
            .filter(result => result.status === 'rejected')
            .forEach(result => console.error(`Error fetching API: ${result.reason.config?.url} - ${result.reason.message}`));

        return combinedData.flat(); // Merges all data arrays into a single array.
    } catch (error) {
        console.error('Error:', error);
        return [];
    }
}

const apiUrls = [
    'https://jsonplaceholder.typicode.com/posts', // Valid API
    'https://jsonplaceholder.typicode.com/comments', //  Valid API
    'https://httpstat.us/500', //  Simulated server error (500)
    'https://httpstat.us/404', //  Simulated not found error (404)
    'https://httpstat.us/200?sleep=6000', // Will timeout (longer than 5s)
    'https://invalid-api-url.com' // Fake URL (will fail with ENOTFOUND)
];

fetchAPIs(apiUrls).then(data => console.log('Data combined:', data));
