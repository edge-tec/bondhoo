# PHASE 15.2 — Backend Fix Report

| Bug ID | File Changed | Description of Fix | Regression Test | Status |
|---|---|---|---|---|
| **P15-BE-01** | `app/Services/Messenger/EnterpriseMessengerService.php` | Deduplicated `$optionIds` before single-choice validation and enforced strict count equality (`$validOptions->count() === count($uniqueOptionIds)`) to prevent cross-poll option injection | `Phase15DatabaseConcurrencyAndSecurityTest::test_poll_voting_option_id_and_option_ids_compatibility_cross_poll_injection_and_owner_close` | **PASS** |
| **P15-BE-02** | `app/Services/Messenger/EnterpriseMessengerService.php` | Allowed group `'owner'` role in addition to `'admin'` and poll creator to close a group poll | `Phase15DatabaseConcurrencyAndSecurityTest` | **PASS** |
| **P15-BE-03** | `app/Http/Controllers/Api/v1/MessengerEnterpriseApiController.php` | Caught `InvalidArgumentException` in `votePoll` and `closePoll` to return structured HTTP 422 JSON responses | `Phase15DatabaseConcurrencyAndSecurityTest` | **PASS** |
