/**
 * JUGAJUG Phase 8.7 — Enterprise Real Network HTTP Load & Performance Benchmark Runner
 * 
 * Executes REAL HTTP requests over the network loopback to the live Laravel application
 * (PHP 8.5 / Laravel 12 on http://127.0.0.1:8000).
 * 
 * Replaces in-process simulated benchmarks with real HTTP round-trip timing, measuring:
 * - Real TCP connection and request-response round-trip latency
 * - p50, p95, p99, and Max latency percentiles
 * - Requests per second (RPS) throughput
 * - HTTP status code distribution (2xx, 3xx, 4xx, 5xx)
 * - Error rate & socket timeout tracking
 */

import http from 'http';
import { performance } from 'perf_hooks';

const BASE_URL = process.env.BENCHMARK_BASE_URL || 'http://127.0.0.1:8000';
const BENCHMARK_TOKEN = process.env.BENCHMARK_TOKEN || '255|Ocl9lCYUlat7ge8WQlRkgxbUDshyPcRlMQLhb6vd4cf677c1';
const parsedUrl = new URL(BASE_URL);

// Dedicated HTTP Agent with connection reuse / keep-alive
const httpAgent = new http.Agent({
  keepAlive: true,
  maxSockets: 100,
  timeout: 10000,
});

/**
 * Perform a single real HTTP request and record its timing and status.
 */
function sendRealHttpRequest(path, method = 'GET', headers = {}) {
  return new Promise((resolve) => {
    const start = performance.now();
    const req = http.request(
      {
        hostname: parsedUrl.hostname,
        port: parsedUrl.port || 80,
        path: path,
        method: method,
        agent: httpAgent,
        headers: {
          'Accept': 'application/json, text/html',
          'User-Agent': 'Jugajug-Phase87-HttpBenchmark/1.0',
          ...headers,
        },
      },
      (res) => {
        let body = '';
        res.on('data', (chunk) => {
          body += chunk;
        });
        res.on('end', () => {
          const duration = performance.now() - start;
          resolve({
            ok: res.statusCode >= 200 && res.statusCode < 400,
            statusCode: res.statusCode,
            durationMs: duration,
            bytesReceived: Buffer.byteLength(body),
            error: null,
          });
        });
      }
    );

    req.on('error', (err) => {
      const duration = performance.now() - start;
      resolve({
        ok: false,
        statusCode: 0,
        durationMs: duration,
        bytesReceived: 0,
        error: err.message,
      });
    });

    req.on('timeout', () => {
      req.destroy();
      const duration = performance.now() - start;
      resolve({
        ok: false,
        statusCode: 408,
        durationMs: duration,
        bytesReceived: 0,
        error: 'Socket timeout',
      });
    });

    req.end();
  });
}

/**
 * Run a concurrency pool of workers sending real HTTP requests.
 */
async function runConcurrencyTier(tierName, concurrency, totalRequests, endpoints) {
  const latencies = [];
  const statusCodes = {};
  let successCount = 0;
  let failureCount = 0;
  let totalBytes = 0;

  const startTime = performance.now();
  let requestIndex = 0;

  async function worker() {
    while (true) {
      const currentIdx = requestIndex++;
      if (currentIdx >= totalRequests) {
        break;
      }

      const endpoint = endpoints[currentIdx % endpoints.length];
      const result = await sendRealHttpRequest(endpoint.path, endpoint.method || 'GET', endpoint.headers || {});

      latencies.push(result.durationMs);
      totalBytes += result.bytesReceived;

      statusCodes[result.statusCode] = (statusCodes[result.statusCode] || 0) + 1;
      if (result.ok) {
        successCount++;
      } else {
        failureCount++;
      }
    }
  }

  // Launch concurrent workers
  const workers = [];
  for (let i = 0; i < concurrency; i++) {
    workers.push(worker());
  }
  await Promise.all(workers);

  const totalTimeMs = performance.now() - startTime;
  const totalSeconds = totalTimeMs / 1000;

  latencies.sort((a, b) => a - b);
  const p50 = latencies[Math.floor(latencies.length * 0.50)] || 0;
  const p95 = latencies[Math.floor(latencies.length * 0.95)] || 0;
  const p99 = latencies[Math.floor(latencies.length * 0.99)] || 0;
  const max = latencies[latencies.length - 1] || 0;
  const min = latencies[0] || 0;
  const avg = latencies.reduce((sum, v) => sum + v, 0) / (latencies.length || 1);
  const rps = totalRequests / totalSeconds;

  return {
    tier: tierName,
    concurrency,
    totalRequests,
    successCount,
    failureCount,
    errorRatePct: +((failureCount / totalRequests) * 100).toFixed(2),
    totalDurationSec: +totalSeconds.toFixed(3),
    requestsPerSecond: +rps.toFixed(1),
    latencyMinMs: +min.toFixed(2),
    latencyAvgMs: +avg.toFixed(2),
    latencyP50Ms: +p50.toFixed(2),
    latencyP95Ms: +p95.toFixed(2),
    latencyP99Ms: +p99.toFixed(2),
    latencyMaxMs: +max.toFixed(2),
    totalMBytesReceived: +(totalBytes / (1024 * 1024)).toFixed(3),
    statusCodes,
  };
}

async function main() {
  console.log('='.repeat(78));
  console.log(' JUGAJUG PHASE 8.7 — REAL NETWORK HTTP LOAD & PERFORMANCE BENCHMARK');
  console.log(` Target Server: ${BASE_URL} (Real Network Loopback / Socket Benchmark)`);
  console.log('='.repeat(78));

  // Verify baseline reachability
  const probe = await sendRealHttpRequest('/api/v1/health');
  if (probe.statusCode !== 200) {
    console.error(`[FATAL] Target server returned status ${probe.statusCode}: ${probe.error || 'Unhealthy'}`);
    process.exit(1);
  }
  console.log(`[INFO] Server verified online and responsive: HTTP ${probe.statusCode} in ${probe.durationMs.toFixed(2)}ms\n`);

  const endpoints = [
    { path: '/api/v1/health', method: 'GET' },
    { path: '/api/v2/reels?per_page=10', method: 'GET', headers: { 'Authorization': `Bearer ${BENCHMARK_TOKEN}` } },
    { path: '/api/v1/conversations', method: 'GET', headers: { 'Authorization': `Bearer ${BENCHMARK_TOKEN}` } },
    { path: '/', method: 'GET' },
  ];

  const tiers = [
    { name: 'Tier 1 — Baseline Smoke (10 Concurrency)', concurrency: 10, totalRequests: 150 },
    { name: 'Tier 2 — Steady Load (25 Concurrency)', concurrency: 25, totalRequests: 300 },
    { name: 'Tier 3 — Stress Peak (50 Concurrency)', concurrency: 50, totalRequests: 500 },
  ];

  const results = [];

  for (const tier of tiers) {
    console.log(`--> Running ${tier.name}...`);
    const res = await runConcurrencyTier(tier.name, tier.concurrency, tier.totalRequests, endpoints);
    results.push(res);
    console.log(`    Throughput: ${res.requestsPerSecond} RPS | p50: ${res.latencyP50Ms}ms | p95: ${res.latencyP95Ms}ms | p99: ${res.latencyP99Ms}ms | Errors: ${res.failureCount} (${res.errorRatePct}%)`);
    // Brief cool-down between tiers
    await new Promise((r) => setTimeout(r, 500));
  }

  console.log('\n' + '='.repeat(78));
  console.log(' REAL NETWORK BENCHMARK SUMMARY MATRIX');
  console.log('='.repeat(78));
  console.table(
    results.map((r) => ({
      Tier: r.tier.split(' — ')[0],
      Concurrency: r.concurrency,
      Requests: r.totalRequests,
      RPS: r.requestsPerSecond,
      'p50 (ms)': r.latencyP50Ms,
      'p95 (ms)': r.latencyP95Ms,
      'p99 (ms)': r.latencyP99Ms,
      'Max (ms)': r.latencyMaxMs,
      'Error %': `${r.errorRatePct}%`,
    }))
  );

  console.log('\nStatus Code Breakdown:');
  results.forEach((r) => {
    console.log(`  ${r.tier}: ${JSON.stringify(r.statusCodes)}`);
  });

  httpAgent.destroy();
  console.log('\n[SUCCESS] Phase 8.7 Real Network HTTP Load Benchmark completed successfully.\n');
}

main().catch((err) => {
  console.error('[UNCAUGHT ERROR]', err);
  process.exit(1);
});
