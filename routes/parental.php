<?php

declare(strict_types=1);

use App\Http\Controllers\Parental\ChildAgentApiController;
use App\Http\Controllers\Parental\FamilyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Connectly Parental Control API Routes
|--------------------------------------------------------------------------
|
| Base URL Prefix: /api/v1/parental
|
| Strict separation between:
| 1. Authenticated Parent / Guardian Web & App API (Sanctum)
| 2. Android Child Device Agent API (Cryptographic Bearer Token)
|
*/

// =========================================================================
// 1. Unauthenticated Child Device Pairing Endpoint
// =========================================================================
Route::post('/device/pair', [ChildAgentApiController::class, 'pair'])
    ->middleware('throttle:10,1')
    ->name('parental.device.pair');

// =========================================================================
// 2. Child Device Agent Endpoints (Protected by Device Bearer Token)
// =========================================================================
Route::middleware('auth.child_device')->prefix('device')->group(function () {
    // Rotate authentication token
    Route::post('/rotate-token', [ChildAgentApiController::class, 'rotateToken'])
        ->name('parental.device.rotate_token');

    // Ingest telemetry batch
    Route::post('/usage/sync', [ChildAgentApiController::class, 'syncUsage'])
        ->middleware('throttle:60,1')
        ->name('parental.device.usage.sync');

    // Synchronize installed apps inventory
    Route::post('/apps/inventory', [ChildAgentApiController::class, 'syncAppInventory'])
        ->name('parental.device.apps.inventory');

    // Retrieve active restrictions & policy version
    Route::get('/policies', [ChildAgentApiController::class, 'getPolicies'])
        ->name('parental.device.policies');

    // Poll pending actionable commands
    Route::get('/commands/pending', [ChildAgentApiController::class, 'getPendingCommands'])
        ->name('parental.device.commands.pending');

    // Acknowledge command execution status
    Route::post('/commands/{uuid}/ack', [ChildAgentApiController::class, 'acknowledgeCommand'])
        ->name('parental.device.commands.ack');

    // Request consent / permission escalation
    Route::post('/consent-requests', [ChildAgentApiController::class, 'createConsentRequest'])
        ->name('parental.device.consent.create');

    // Report tamper or security alert
    Route::post('/alerts', [ChildAgentApiController::class, 'submitAlert'])
        ->name('parental.device.alerts.submit');
});

// =========================================================================
// 3. Parent / Guardian Endpoints (Protected by User Auth)
// =========================================================================
Route::middleware(['auth:sanctum'])->group(function () {
    // Family Management
    Route::post('/families', [FamilyController::class, 'store'])
        ->name('parental.families.store');

    Route::get('/families/{family}', [FamilyController::class, 'show'])
        ->name('parental.families.show');

    Route::put('/families/{family}', [FamilyController::class, 'update'])
        ->name('parental.families.update');

    Route::get('/families/{family}/activity', [FamilyController::class, 'activityHistory'])
        ->name('parental.families.activity');

    Route::post('/families/{family}/members', [FamilyController::class, 'addMember'])
        ->name('parental.families.members.add');

    Route::put('/families/{family}/members/{member}', [FamilyController::class, 'updateMember'])
        ->name('parental.families.members.update');

    Route::delete('/families/{family}/members/{member}', [FamilyController::class, 'removeMember'])
        ->name('parental.families.members.remove');

    // Pairing Code Issuance
    Route::post('/families/{family}/pairing-codes', [FamilyController::class, 'generatePairingCode'])
        ->middleware('throttle:15,1')
        ->name('parental.families.pairing_codes.generate');

    // Screen Time Telemetry Analysis
    Route::get('/children/{childMember}/screen-time', [FamilyController::class, 'getScreenTime'])
        ->name('parental.children.screen_time');

    Route::get('/children/{childMember}/screen-time/weekly', [FamilyController::class, 'getWeeklyScreenTime'])
        ->name('parental.children.screen_time.weekly');

    Route::get('/children/{childMember}/screen-time/quota', [FamilyController::class, 'getScreenTimeQuota'])
        ->name('parental.children.screen_time.quota');

    // Policy & Restriction Rules
    Route::post('/families/{family}/restrictions', [FamilyController::class, 'upsertRestriction'])
        ->name('parental.families.restrictions.upsert');

    // Remote Commands
    Route::post('/devices/{device}/commands', [FamilyController::class, 'dispatchCommand'])
        ->name('parental.devices.commands.dispatch');

    Route::post('/devices/{device}/revoke', [FamilyController::class, 'revokeDevice'])
        ->name('parental.devices.revoke');

    // Consent Requests
    Route::get('/families/{family}/consent-requests', [FamilyController::class, 'listConsentRequests'])
        ->name('parental.families.consent_requests.list');

    Route::post('/consent-requests/{consentRequest}/respond', [FamilyController::class, 'respondConsentRequest'])
        ->name('parental.consent_requests.respond');

    // Security Alerts
    Route::get('/families/{family}/alerts', [FamilyController::class, 'listAlerts'])
        ->name('parental.families.alerts.list');
});
