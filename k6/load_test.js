import {check, sleep} from 'k6';
import http from 'k6/http';
import Papa from "https://jslib.k6.io/papaparse/5.1.1/index.js";
import {randomIntBetween, randomItem} from "https://jslib.k6.io/k6-utils/1.4.0/index.js";
import {SharedArray} from "k6/data";

export function load(file) {
    return new SharedArray(file, () => Papa.parse(open(file), {header: true}).data);
}

export const options = {
    scenarios: {
        load: {
            executor: 'ramping-vus',
            // Key configurations for avg load test
            stages: [
                {duration: '5m', target: 100}, // traffic ramp-up from 1 to 100 users over 5 minutes.
                {duration: '30m', target: 100}, // stay at 100 users for 30 minutes
                {duration: '5m', target: 0}, // ramp-down to 0 users
            ],
            gracefulRampDown: '10s',
        },
    }
}

const articles = load('articles.csv');
const categories = load('categories.csv');

export default function () {
    // 40% of the time, we'll hit the homepage
    if (randomIntBetween(0, 100) <= 40) {
        const res = http.get(`${__ENV.HOSTNAME}`);
        sleep(1);

        check(res, {
            'is status 200': (r) => r.status === 200,
        });
    }

    // 10% of the time, we'll hit a category page
    if (randomIntBetween(0, 100) <= 10) {
        const res = http.get(`${__ENV.HOSTNAME}${randomItem(categories).url ?? '/'}`);
        sleep(1);

        check(res, {
            'is status 200': (r) => r.status === 200,
        });
    }

    // 90% of the time, we'll hit an article page
    if (randomIntBetween(0, 100) <= 90) {
        const res = http.get(`${__ENV.HOSTNAME}${randomItem(articles).url ?? '/'}`);
        sleep(1);

        check(res, {
            'is status 200': (r) => r.status === 200,
        });
    }
}
