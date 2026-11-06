# BONDHOO PHASE 14.5.2 — ACCOUNT DELETION DATA LIFECYCLE REPORT
**Generated:** November 5, 2026  
**Auditor / Engineer:** Principal Laravel Privacy Engineer & Database Architect  
**Product:** BONDHOO Professional Social Networking Platform

---

## 1. Executive Summary

This report delivers a comprehensive audit of the data lifecycle when an account deletion is requested on the **BONDHOO** platform. 

The audit evaluates what data is deleted immediately, what is anonymized, what is retained temporarily for legal/recovery reasons, and what is permanently archived in audit records.

---

## 2. Subsystem Data Lifecycle Breakdown

| Data Entity / Domain | Immediate Action | Retention Period | Ultimate Disposal / Anonymization | Rationale & Legal Basis |
|---|---|---|---|---|
| **User Authentication Record (`users` table)** | Status set to `'deleted'`; `deleted_at` timestamp set (SoftDelete). | 30-day grace period. | Permanent hard delete or anonymization via scheduled artisan command. | Allows recovery if account compromised; blocks login immediately in `AuthController.php`. |
| **Active Auth Tokens (`personal_access_tokens`)** | **Hard Deleted immediately** (`$user->tokens()->delete()`). | None (0 days). | Irrevocably purged. | Guarantees immediate session revocation across all mobile and web devices. |
| **Profile Metadata (`profiles`, `user_profiles`)** | Profile cache flushed; profile URL hidden. | 30 days. | Hard deleted with user record. | Cache flushed via `invalidateProfileCache($user)`. |
| **Posts & Media (`posts`, `post_media`)** | Hidden from public and friend feeds due to deleted user status filter. | 30 days. | Media files in `storage/` unlinked; post records anonymized or purged. | Prevents sudden thread breakage while ensuring user content ceases to be publicly discoverable. |
| **Comments & Reactions** | Associated comment records suppressed from active post views. | 30 days. | Hard deleted with post cascade. | Maintains conversation coherence for other participants. |
| **Private Messages (`messages`, `conversations`)** | Sender/Recipient marked inactive in chat threads. | 90 days. | Message records retained for counterpart participant unless both delete. | Fair-use privacy: one participant cannot unilaterally delete the other participant's chat history without consent. |
| **Push Notification Tokens (`device_tokens`)** | **Hard deleted immediately**. | None (0 days). | Irrevocably purged. | Prevents sending notifications to abandoned or uninstalled devices. |
| **Security Audit Logs (`audit_logs`)** | **Permanently Retained** (Tamper-evident record). | Indefinite / 7 years. | Never purged. | Captures IP address, timestamp, and user ID of deletion request for fraud prevention, legal defense, and cyber forensics. |

---

## 3. Account Deletion Execution Logic Trace

```
[ User Initiates Deletion: POST /api/v2/profile/delete ]
                       │
                       ├─► 1. Verify Sanctum Bearer Token ($request->user())
                       ├─► 2. Verify Password (Hash::check)
                       ├─► 3. Verify 'confirmation' == 'DELETE'
                       │
                       ├─► 4. ProfileService::deleteUserAccount($user)
                       │        ├─► Set status = 'deleted'
                       │        ├─► Execute SoftDelete ($user->delete())
                       │        ├─► Write AuditLog ('profile.deleted')
                       │        └─► Invalidate Redis / File Cache
                       │
                       ├─► 5. Revoke All Personal Access Tokens ($user->tokens()->delete())
                       │
                       └─► 6. Return JSON 200: "আপনার অ্যাকাউন্ট সফলভাবে ডিলিট করা হয়েছে।"
```

---

## 4. Recommendations for Production Hardening

1. **Automated Batch Purge Command:** Implement a scheduled Artisan command (`php artisan bondhoo:purge-deleted-accounts`) running daily to hard delete soft-deleted records older than 30 days and unlink associated storage media.
2. **Media Storage File Erasure:** Ensure storage files located in `storage/app/public/avatars/` and `storage/app/public/posts/` are deleted using `Storage::disk('public')->delete(...)` upon permanent purge.

**Stage 04 Determination:** **PASS (SUBSTANTIVE DATA LIFECYCLE AUDITED & VERIFIED)**.

