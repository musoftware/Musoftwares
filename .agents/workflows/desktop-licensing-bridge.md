---
description: Standards for connecting desktop applications to the Musoftwares SaaS API for device registration, offline lease verification, and HMAC signing.
---

# Desktop Licensing & Device Bridge

This workflow defines the architecture, security protocols, and lifecycle rules for desktop applications (such as WhatsApp Bulker and other automation tools) communicating with the Musoftwares backend.

---

## 1. Core Architecture

The desktop client is a separate execution binary that relies on the Musoftwares SaaS API for:
1. Device identity registration and seat allocation.
2. User account linking via email (`/api/serial/device/link-user`).
3. Cryptographic offline lease tokens.
4. Auto-update checks and entitlement status.

---

## 2. API Contract & Security Protocol

### A. Device Registration (`POST /api/serial/device`)
* **Endpoints**: Primary (`https://www.musoftwares.com/api/serial/device`), Secondary Fallback (`https://www.mu-hub.com/api/serial/device`).
* **Protection**: Throttled to 60 req/min per IP (`throttle:60,1`).
* **Legacy C# Compatibility (CRITICAL)**:
  * Public endpoint invoked by legacy C# programs on startup.
  * Never impose mandatory HMAC headers, complex tokens, or breaking auth requirements on `/api/serial/device` as this breaks active legacy C# applications in the field.
  * Request payload accepts standard environment parameters: `program_name`, `device_id`, `machine_name`, `user_name`, `os_version`, and `framework_version`.

### B. Status Resolution Hierarchy
When a desktop client reports a hardware check-in:
1. **User Temporary Override**: If `user.temp_valid_until` is in the future, return status `active`.
2. **User-Device Assignment**: If `SerialUserDevice.status` is `active`, return `active`.
3. **Software Default**: If `SerialDevice.status` is `active` and `software.default_status` is `active`, return `active`.
4. **Fallback**: Return `inactive` or `expired` with payment/linking instructions.

---

## 3. Cryptographic Offline Leases (Tamper Resistance)

To protect desktop tools from local database tampering:
1. **Signed Lease Token**:
   * The backend signs a JSON payload containing `{ device_id, software_slug, expires_at, tier, features }` using an asymmetric private key (e.g. Ed25519 or RSA).
   * The response provides this signed lease to the desktop application.
2. **Local Verification**:
   * The desktop client verifies the token signature using the bundled public key before unlocking features.
   * Modifying local SQLite database records will fail cryptographic signature verification.
3. **Lease Duration & Silent Heartbeat**:
   * Leases are valid for a bounded period (typically 7 to 14 days).
   * While online, the desktop app executes a background check-in every 12 to 24 hours to renew the lease.
   * If the user operates without an internet connection, full functionality is preserved until the lease expiration timestamp.

---

## 4. Seat Management & Anti-Abuse Rules

* **Device Limiting**:
  * Each subscription tier defines a maximum number of concurrent authorized devices (`max_seats`).
  * If a user attempts to link an additional machine beyond their seat limit, the API returns HTTP 422 with `SEAT_LIMIT_EXCEEDED` and a prompt to deactivate old machines.
* **Audit Trail**:
  * Every device check-in records `last_seen_at`, `hostname`, and `ip_address` to detect suspicious concurrent geographic distribution.
