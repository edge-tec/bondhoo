<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductionDeploymentAndDockerTest extends TestCase
{
    /**
     * Test that Dockerfile exists with production PHP extensions and configurations.
     */
    public function test_production_dockerfile_is_properly_configured(): void
    {
        $dockerfilePath = base_path('Dockerfile');
        $this->assertTrue(File::exists($dockerfilePath), 'Dockerfile must exist in application root.');

        $content = File::get($dockerfilePath);
        $this->assertStringContainsString('php:8.4-fpm-alpine', $content);
        $this->assertStringContainsString('install-php-extensions', $content);
        $this->assertStringContainsString('pdo_mysql', $content);
        $this->assertStringContainsString('redis', $content);
        $this->assertStringContainsString('opcache', $content);
        $this->assertStringContainsString('pcntl', $content);
        $this->assertStringContainsString('bcmath', $content);
        $this->assertStringContainsString('gd', $content);
        $this->assertStringContainsString('zip', $content);
        $this->assertStringContainsString('exif', $content);
        $this->assertStringContainsString('intl', $content);
        $this->assertStringContainsString('supervisor', $content);
        $this->assertStringContainsString('composer', $content);
        $this->assertStringContainsString('EXPOSE 9000', $content);
        $this->assertStringContainsString('USER www-data', $content);
    }

    /**
     * Test that docker-compose defines all enterprise services and shared PHP image.
     */
    public function test_docker_compose_configuration_is_complete(): void
    {
        $composePath = base_path('docker-compose.yml');
        $this->assertTrue(File::exists($composePath), 'docker-compose.yml must exist.');

        $content = File::get($composePath);
        $this->assertStringContainsString('nginx:', $content);
        $this->assertStringContainsString('php-fpm:', $content);
        $this->assertStringContainsString('redis:', $content);
        $this->assertStringContainsString('mysql-primary:', $content);
        $this->assertStringContainsString('mysql-replica:', $content);
        $this->assertStringContainsString('reverb:', $content);
        $this->assertStringContainsString('horizon:', $content);
        $this->assertStringContainsString('scheduler:', $content);
        $this->assertStringContainsString('meilisearch:', $content);
        $this->assertStringContainsString('livekit:', $content);
        $this->assertStringContainsString('coturn:', $content);
        $this->assertStringContainsString('jugajug-network', $content);

        // Ensure workers use the unified built application image
        $this->assertStringContainsString('image: jugajug-app:latest', $content);
    }

    /**
     * Test that docker nginx configuration routes upstream traffic to PHP-FPM and Reverb.
     */
    public function test_docker_nginx_configuration_is_valid(): void
    {
        $nginxPath = base_path('docker/nginx.conf');
        $this->assertTrue(File::exists($nginxPath), 'docker/nginx.conf must exist.');

        $content = File::get($nginxPath);
        $this->assertStringContainsString('upstream php_backend', $content);
        $this->assertStringContainsString('server php-fpm:9000;', $content);
        $this->assertStringContainsString('upstream reverb_websocket', $content);
        $this->assertStringContainsString('server reverb:8080;', $content);
        $this->assertStringContainsString('client_max_body_size 500M;', $content);
    }

    /**
     * Test that deploy.sh script is executable and contains atomic release and rollback steps.
     */
    public function test_zero_downtime_deploy_script_is_configured(): void
    {
        $deployScriptPath = base_path('deploy.sh');
        $this->assertTrue(File::exists($deployScriptPath), 'deploy.sh must exist.');
        $this->assertTrue(is_executable($deployScriptPath), 'deploy.sh must be executable.');

        $content = File::get($deployScriptPath);
        $this->assertStringContainsString('APP_DIR=', $content);
        $this->assertStringContainsString('RELEASE_DIR=', $content);
        $this->assertStringContainsString('SHARED_DIR=', $content);
        $this->assertStringContainsString('config:cache', $content);
        $this->assertStringContainsString('route:cache', $content);
        $this->assertStringContainsString('view:cache', $content);
        $this->assertStringContainsString('event:cache', $content);
        $this->assertStringContainsString('migrate --force', $content);
        $this->assertStringContainsString('horizon:terminate', $content);
        $this->assertStringContainsString('queue:restart', $content);
        $this->assertStringContainsString('api/v1/health', $content);
        $this->assertStringContainsString('automatic rollback', $content);
    }

    /**
     * Test that system health endpoint returns HTTP 200 for deployment verification.
     */
    public function test_system_health_endpoint_responds_ok_for_deployment(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'status',
                'services',
            ],
        ]);
        $this->assertTrue(in_array($response->json('data.status'), ['operational', 'degraded'], true));
    }
}
