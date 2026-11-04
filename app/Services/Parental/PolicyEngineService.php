<?php

declare(strict_types=1);

namespace App\Services\Parental;

use App\Models\Parental\ChildDevice;
use App\Models\Parental\DeviceRestriction;
use App\Models\Parental\Family;
use App\Models\Parental\FamilyMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Class PolicyEngineService
 *
 * Manages device usage restrictions, daily screen-time quotas, bedtime curfews, and app blacklists.
 */
class PolicyEngineService
{
    public function __construct(
        protected FamilyService $familyService
    ) {}

    /**
     * Create or update a device restriction rule.
     *
     * @param  array<string, mixed>  $configuration
     */
    public function upsertRestriction(
        Family $family,
        FamilyMember $childMember,
        string $restrictionType,
        array $configuration,
        User $creator,
        ?string $applicationPackage = null,
        ?ChildDevice $device = null,
        bool $enabled = true
    ): DeviceRestriction {
        if ($childMember->family_id !== $family->id) {
            throw new InvalidArgumentException('Child member does not belong to the target family.');
        }

        return DB::transaction(function () use (
            $family,
            $childMember,
            $restrictionType,
            $configuration,
            $creator,
            $applicationPackage,
            $device,
            $enabled
        ): DeviceRestriction {
            // Find existing restriction or create new
            $query = DeviceRestriction::where('family_id', $family->id)
                ->where('child_member_id', $childMember->id)
                ->where('restriction_type', $restrictionType);

            if ($applicationPackage !== null) {
                $query->where('application_package', $applicationPackage);
            } else {
                $query->whereNull('application_package');
            }

            if ($device !== null) {
                $query->where('child_device_id', $device->id);
            } else {
                $query->whereNull('child_device_id');
            }

            /** @var DeviceRestriction|null $existing */
            $existing = $query->first();

            $nextVersion = $existing ? ($existing->policy_version + 1) : 1;

            $restriction = DeviceRestriction::updateOrCreate(
                [
                    'id' => $existing?->id,
                ],
                [
                    'family_id' => $family->id,
                    'child_device_id' => $device?->id,
                    'child_member_id' => $childMember->id,
                    'restriction_type' => $restrictionType,
                    'application_package' => $applicationPackage,
                    'configuration_json' => $configuration,
                    'enabled' => $enabled,
                    'policy_version' => $nextVersion,
                    'created_by' => $creator->id,
                ]
            );

            $this->familyService->logAudit(
                family: $family,
                actorUserId: $creator->id,
                role: 'parent',
                action: 'policy.updated',
                resourceType: 'device_restriction',
                resourceId: (string) $restriction->id,
                metadata: [
                    'restriction_type' => $restrictionType,
                    'package' => $applicationPackage,
                    'version' => $nextVersion,
                    'enabled' => $enabled,
                ]
            );

            return $restriction;
        });
    }

    /**
     * Retrieve active policies applicable to a given child device.
     *
     * @return array{
     *     policy_version: int,
     *     daily_screen_time_limit_minutes: int|null,
     *     bedtime_curfew: array{start: string, end: string}|null,
     *     blacklisted_packages: array<string>,
     *     whitelisted_packages: array<string>,
     *     custom_rules: array<int, array<string, mixed>>
     * }
     */
    public function getActivePoliciesForDevice(ChildDevice $device): array
    {
        $restrictions = DeviceRestriction::where('family_id', $device->family_id)
            ->where('child_member_id', $device->child_member_id)
            ->where('enabled', true)
            ->where(function ($q) use ($device) {
                $q->whereNull('child_device_id')
                    ->orWhere('child_device_id', $device->id);
            })
            ->get();

        $maxVersion = (int) $restrictions->max('policy_version') ?: 1;
        $dailyLimitMinutes = null;
        $bedtimeCurfew = null;
        $blacklistedPackages = [];
        $whitelistedPackages = [];
        $customRules = [];

        foreach ($restrictions as $r) {
            $config = $r->configuration_json ?? [];

            switch ($r->restriction_type) {
                case 'daily_time_limit':
                    $dailyLimitMinutes = $config['daily_minutes'] ?? null;
                    break;
                case 'bedtime_curfew':
                    $bedtimeCurfew = [
                        'start' => $config['start_time'] ?? '21:00',
                        'end' => $config['end_time'] ?? '07:00',
                    ];
                    break;
                case 'app_blacklist':
                    if ($r->application_package) {
                        $blacklistedPackages[] = $r->application_package;
                    }
                    if (! empty($config['packages']) && is_array($config['packages'])) {
                        $blacklistedPackages = array_merge($blacklistedPackages, $config['packages']);
                    }
                    break;
                case 'app_whitelist':
                    if ($r->application_package) {
                        $whitelistedPackages[] = $r->application_package;
                    }
                    break;
                default:
                    $customRules[] = [
                        'type' => $r->restriction_type,
                        'package' => $r->application_package,
                        'config' => $config,
                    ];
            }
        }

        return [
            'policy_version' => $maxVersion,
            'daily_screen_time_limit_minutes' => $dailyLimitMinutes,
            'bedtime_curfew' => $bedtimeCurfew,
            'blacklisted_packages' => array_values(array_unique($blacklistedPackages)),
            'whitelisted_packages' => array_values(array_unique($whitelistedPackages)),
            'custom_rules' => $customRules,
        ];
    }
}
