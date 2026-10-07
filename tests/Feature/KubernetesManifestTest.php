<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * KubernetesManifestTest — কিউবারনেটিস (K8s) প্রোডাকশন ম্যানিফেস্ট টেস্ট
 */
class KubernetesManifestTest extends TestCase
{
    /**
     * সমস্ত প্রয়োজনীয় কিউবারনেটিস ম্যানিফেস্ট ফাইলের উপস্থিতি ও কনফিগারেশন যাচাই।
     */
    public function test_kubernetes_manifests_exist_with_valid_specifications(): void
    {
        $manifests = [
            'k8s/deployment.yaml',
            'k8s/statefulset.yaml',
            'k8s/configmap.yaml',
            'k8s/secret.yaml',
            'k8s/service.yaml',
            'k8s/ingress.yaml',
            'k8s/hpa.yaml',
        ];

        foreach ($manifests as $manifest) {
            $path = base_path($manifest);
            $this->assertFileExists($path, "K8s manifest missing: {$manifest}");

            $content = file_get_contents($path);
            $this->assertStringContainsString('apiVersion:', $content);
            $this->assertStringContainsString('metadata:', $content);
        }

        // Deployment স্পেসিফিকেশন ও HPA যাচাই
        $deploymentContent = file_get_contents(base_path('k8s/deployment.yaml'));
        $this->assertStringContainsString('jugajug-app', $deploymentContent);
        $this->assertStringContainsString('RollingUpdate', $deploymentContent);

        $hpaContent = file_get_contents(base_path('k8s/hpa.yaml'));
        $this->assertStringContainsString('HorizontalPodAutoscaler', $hpaContent);
        $this->assertStringContainsString('maxReplicas: 50', $hpaContent);
    }
}
