import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 50 },  // ramp-up
    { duration: '1m', target: 200 },   // high concurrency
    { duration: '30s', target: 0 },    // ramp-down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],  // 95% requests must complete within 500ms
    http_req_failed: ['rate<0.01'],    // less than 1% failure rate
  },
};

const BASE_URL = __ENV.TARGET_URL || 'http://localhost';

export default function () {
  // 1. Health Probe
  let res = http.get(`${BASE_URL}/api/v1/health`);
  check(res, { 'status is 200': (r) => r.status === 200 });

  // 2. Metrics Endpoint
  res = http.get(`${BASE_URL}/api/v1/metrics`);
  check(res, { 'metrics status is 200': (r) => r.status === 200 });

  // 3. Social Feed
  res = http.get(`${BASE_URL}/api/v1/feed`);
  check(res, { 'feed status is 200': (r) => r.status === 200 });

  sleep(1);
}
