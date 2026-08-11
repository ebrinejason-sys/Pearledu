# SchoolPay Open API integration (PearlEdu)

PearlEdu integrates with [SchoolPay](https://schoolpay.co.ug) (Fincom Technologies / Service Cops; Bank of Uganda–licensed PSP) so Ugandan schools can collect mobile money fees from the parent portal, channel/agent payments, and e-learning/portal surfaces.

Official docs: https://schoolpay.co.ug/apidocumentation

## Credentials (not self-service)

You cannot self-sign into live production. Contact Service Cops / SchoolPay for:

- School code (unique school identifier)
- Transactions / Adhoc API password
- Endpoint confirmation (prod vs UAT)

| | |
|---|---|
| Email | support@schoolpay.co.ug |
| Office | 0200 502 140 |
| WhatsApp | +256 750 923 262 |
| Web | https://www.schoolpay.co.ug |

Store credentials **per school** under **School identity** in PearlEdu (API password is encrypted at rest). Platform env only sets the API base URL:

```env
SCHOOLPAY_BASE_URL=https://schoolpay.co.ug/paymentapi
# UAT example: https://schoolpaytest.servicecops.com/uatpaymentapi
SCHOOLPAY_ADHOC_ENABLED=true
SCHOOLPAY_SYNC_LOOKBACK_DAYS=2
```

## 1. MD5 authentication (every request)

SchoolPay evaluates hashes in **uppercase**. PearlEdu builds them as:

```php
strtoupper(md5($schoolCode . $identifyingValue . $password));
```

| API | `$identifyingValue` |
|---|---|
| `SyncSchoolTransactions` | transaction date `YYYY-MM-DD` |
| `SchoolRangeTransactions` | **fromDate** (not toDate) |
| Adhoc Register / Request | `externalReference` |
| Adhoc Check | `paymentReference` |

Implemented in `App\Services\SchoolPay\SchoolPayClient::hash()`.

Webhook notify payloads use a separate signature:

```text
SHA256(apiPassword + schoolpayReceiptNumber)
```

## 2. Core request types PearlEdu uses

| Endpoint | Purpose in PearlEdu |
|---|---|
| `SyncSchoolTransactions` | Single-day pull (`schoolpay:sync --date=…` or Fees → Sync) |
| `SchoolRangeTransactions` | Lookback window (default daily cron; max 31 days) |
| Adhoc Register + Request | Parent portal “Pay with SchoolPay” MoMo debit |
| Adhoc callback URL | Instant confirm when status is `PAID` |
| Portal webhook notify | Instant apply for `SCHOOL_FEES` / `OTHER_FEES` |

Public webhook routes (CSRF-exempt):

- `POST /webhooks/schoolpay/{schoolId}/callback`
- `POST /webhooks/schoolpay/{schoolId}/notify`

## 3. Student identification (10-digit payment code)

SchoolPay allocates a **unique 10-digit payment code** per student. PearlEdu:

- Stores it on `students.schoolpay_payment_code`
- Validates `^\d{10}$` on create/update
- Matches webhook + sync receipts on that code before applying to the oldest open invoice

Without the code, channel/agent payments cannot be allocated (logged as unmatched). Map codes before go-live.

Adhoc MoMo from the parent portal is invoice-scoped via `externalReference` and does not require the 10-digit code for initiation — but codes are still required for traditional SchoolPay channel reconciliation.

## 4. Reconciliation model

1. **Instant:** webhook / adhoc callback → confirm or create `fee_payments` with `provider_txn_id` = SchoolPay receipt (idempotent).
2. **Backup:** `php artisan schoolpay:sync` daily (SchoolPay webhooks are single-attempt).
3. Manual cash/MoMo “submit for verification” remains available if SchoolPay is off or unavailable.

## 5. School go-live checklist

1. Obtain school code + API password from Service Cops.
2. Enable SchoolPay under School identity; paste credentials.
3. Register the callback + notify URLs shown on that page in the SchoolPay portal.
4. Enter each learner’s 10-digit SchoolPay payment code.
5. Confirm server cron runs `schedule:run` (includes `schoolpay:sync`).
6. Test one small adhoc payment and one channel payment; confirm invoice balance updates.
