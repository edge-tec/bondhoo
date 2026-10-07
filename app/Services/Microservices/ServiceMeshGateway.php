<?php

namespace App\Services\Microservices;

use Illuminate\Support\Facades\Cache;

class ServiceMeshGateway
{
    /**
     * Map of microservices in the Jugajug cluster.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $serviceRegistry = [
        'auth-service' => ['port' => 50051, 'protocol' => 'grpc', 'health_path' => '/health'],
        'social-service' => ['port' => 50052, 'protocol' => 'grpc', 'health_path' => '/health'],
        'media-service' => ['port' => 50053, 'protocol' => 'grpc', 'health_path' => '/health'],
        'notification-service' => ['port' => 50054, 'protocol' => 'grpc', 'health_path' => '/health'],
        'ai-service' => ['port' => 50055, 'protocol' => 'grpc', 'health_path' => '/health'],
        'search-service' => ['port' => 50056, 'protocol' => 'grpc', 'health_path' => '/health'],
        'analytics-service' => ['port' => 50057, 'protocol' => 'grpc', 'health_path' => '/health'],
    ];

    /**
     * Discover registered microservice endpoint.
     *
     * @return array<string, mixed>|null
     */
    public function resolve(string $serviceName): ?array
    {
        if (! isset($this->serviceRegistry[$serviceName])) {
            return null;
        }

        $info = $this->serviceRegistry[$serviceName];
        $info['host'] = env(strtoupper(str_replace('-', '_', $serviceName)).'_HOST', '127.0.0.1');
        $info['status'] = $this->isServiceHealthy($serviceName) ? 'healthy' : 'degraded';

        return $info;
    }

    /**
     * Check if microservice is healthy (with circuit-breaker state cache).
     */
    public function isServiceHealthy(string $serviceName): bool
    {
        $breakerKey = "mesh:breaker:{$serviceName}";

        return Cache::get($breakerKey, 'closed') !== 'open';
    }

    /**
     * List all cluster services and statuses.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getClusterTopology(): array
    {
        $topology = [];
        foreach ($this->serviceRegistry as $name => $spec) {
            $topology[$name] = $this->resolve($name);
        }

        return $topology;
    }
}
