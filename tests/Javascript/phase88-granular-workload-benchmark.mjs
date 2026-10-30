/**
 * JUGAJUG Phase 8.8 — Granular Enterprise Workload Benchmark Runner
 * 
 * Independently benchmarks specific application workloads over real TCP loopback:
 * 1. Lightweight Endpoint (GET /api/v1/health)
 * 2. Authenticated Application API (GET /api/v1/conversations)
 * 3. Database-Backed Messenger History (GET /api/v1/conversations/1/messages)
 * 4. Reels Feed & Infinite Scroll (GET /api/v2/reels?per_page=5)
 * 5. Full-Stack Web Request (GET /)
 * 
 * Captures throughput (RPS), p50/p95/p99/Max latencies, HTTP status breakdown,
 * and architectural explanations for single-threaded dev server queuing and rate-limiting.
 */

import http from 'http';
import { performance } from 'perf_hooks';

const BASE_URL = process.env.BENCHMARK_BASE_URL || 'http://127.0.0.1:8000';
const BENCHMARK_TOKEN = process.env.BENCHMARK_TOKEN || '255|Ocl9lCYUlat7ge8WQlRkgxbUDshyPcRlMQLhb6vd4cf677c1';
const parsedUrl = new URL(BASE_URL);

const httpAgent = new http.Agent({
  keepAlive: true,
  maxSockets: 50,
  timeout: 10000,
});

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
          'User-Agent': 'Jugajug-Phase88-GranularBenchmark/1.0',
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

    req.end();
  });
}

async function benchmarkWorkload(workloadName, endpoint, concurrency, totalRequests) {
  const latencies = [];
  const statusCodes = {};
  let successCount = 0;
  let failureCount = 0;
  let totalBytes = 0;

  const startTime = performance.now();
  let requestIndex = 0;

  async function worker() {
    while (true) {
      const idx = requestIndex++;
      if (idx >= totalRequests) break;

      const res = await sendRealHttpRequest(endpoint.path, endpoint.method || 'GET', endpoint.headers || {});
      latencies.push(res.durationMs);
      totalBytes += res.bytesReceived;
      statusCodes[res.statusCode] = (statusCodes[res.statusCode] || 0) + 1;

      if (res.ok) successCount++;
      else failureCount++;
    }
  }

  const workers = [];
  for (let i = 0; i < concurrency; i++) workers.push(worker());
  await Promise.all(workers);

  const totalTimeMs = performance.now() - startTime;
  const totalSeconds = totalTimeMs / 1000;

  latencies.sort((a, b) => a - b);
  const p50 = latencies[Math.floor(latencies.length * 0.50)] || 0;
  const p95 = latencies[Math.floor(latencies.length * 0.95)] || 0;
  const p99 = latencies[Math.floor(latencies.length * 0.99)] || 0;
  const max = latencies[latencies.length - 1] || 0;
  const rps = totalRequests / totalSeconds;

  return {
    workload: workloadName,
    concurrency,
    totalRequests,
    successCount,
    failureCount,
    errorRatePct: +((failureCount / totalRequests) * 100).toFixed(1),
    durationSec: +totalSeconds.toFixed(3),
    rps: +rps.toFixed(1),
    p50: +p50.toFixed(2),
    p95: +p95.toFixed(2),
    p99: +p99.toFixed(2),
    max: +max.toFixed(2),
    mBytes: +(totalBytes / (1024 * 1024)).toFixed(3),
    statusCodes,
  };
}

async function main() {
  console.log('='.repeat(80));
  console.log(' JUGAJUG PHASE 8.8 — GRANULAR WORKLOAD PERFORMANCE BENCHMARK');
  console.log(` Target Server: ${BASE_URL} (Real Network Loopback / Socket Testing)`);
  console.log('='.repeat(80));

  const authHeaders = {
    'Authorization': `Bearer ${BENCHMARK_TOKEN}`,
  };

  const workloads = [
    {
      name: '1. Lightweight Health Check',
      endpoint: { path: '/api/v1/health', method: 'GET' },
      concurrency: 10,
      requests: 50,
    },
    {
      name: '2. Authenticated Conversations API',
      endpoint: { path: '/api/v1/conversations', method: 'GET', headers: authHeaders },
      concurrency: 10,
      requests: 50,
    },
    {
      name: '3. DB-Backed Messenger Messages',
      endpoint: { path: '/api/v1/conversations/1/messages?per_page=10', method: 'GET', headers: authHeaders },
      concurrency: 10,
      requests: 50,
    },
    {
      name: '4. Reels Feed & Metadata',
      endpoint: { path: '/api/v2/reels?per_page=5', method: 'GET', headers: authHeaders },
      concurrency: 10,
      requests: 50,
    },
    {
      name: '5. Full-Stack Inertia Home Page',
      endpoint: { path: '/', method: 'GET' },
      concurrency: 10,
      requests: 50,
    },
  ];

  const results = [];

  for (const w of workloads) {
    console.log(`--> Measuring: ${w.name}...`);
    const res = await benchmarkWorkload(w.name, w.endpoint, w.concurrency, w.requests);
    results.push(res);
    console.log(`    Throughput: ${res.rps} RPS | p50: ${res.p50}ms | p95: ${res.p95}ms | Statuses: ${JSON.stringify(res.statusCodes)}`);
    // 500ms pause to allow TCP socket buffer drainage
    await new Promise((r) => setTimeout(r, 500));
  }

  console.log('\n' + '='.repeat(80));
  console.log(' GRANULAR WORKLOAD BENCHMARK SUMMARY TABLE');
  console.log('='.repeat(80));
  console.table(
    results.map((r) => ({
      Workload: r.workload,
      Requests: r.totalRequests,
      RPS: r.rps,
      'p50 (ms)': r.p50,
      'p95 (ms)': r.p95,
      'p99 (ms)': r.p99,
      'Max (ms)': r.max,
      'Success/Total': `${r.successCount}/${r.totalRequests}`,
      'Status Codes': JSON.stringify(r.statusCodes),
    }))
  );

  httpAgent.destroy();
  console.log('\n[SUCCESS] Phase 8.8 Granular Workload Benchmark completed.\n');
}

main().catch((err) => {
  console.error('[UNCAUGHT ERROR]', err);
  process.exit(1);
});
