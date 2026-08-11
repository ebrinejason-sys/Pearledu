<?php

namespace App\Services\SchoolPay;

use App\Models\School;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Thin HTTP client for SchoolPay (schoolpay.co.ug) Sync + Adhoc APIs.
 *
 * Auth: MD5(schoolCode + identifyingValue + apiPassword) uppercased hex.
 *
 * @see https://schoolpay.co.ug/apidocumentation
 */
class SchoolPayClient
{
    public function hash(string $schoolCode, string $identifyingValue, string $password): string
    {
        return strtoupper(md5($schoolCode.$identifyingValue.$password));
    }

    public function webhookSignature(string $password, string $receiptNumber): string
    {
        return hash('sha256', $password.$receiptNumber);
    }

    /**
     * @return array{returnCode:int|null,returnMessage:?string,transactions:array<int,array<string,mixed>>,supplementaryFeePayments:array<int,array<string,mixed>>}
     */
    public function syncTransactions(School $school, string $date): array
    {
        [$code, $password] = $this->credentials($school);
        $hash = $this->hash($code, $date, $password);
        $url = $this->baseUrl()."/AndroidRS/SyncSchoolTransactions/{$code}/{$date}/{$hash}";

        return $this->getJson($url);
    }

    /**
     * @return array{returnCode:int|null,returnMessage:?string,transactions:array<int,array<string,mixed>>,supplementaryFeePayments:array<int,array<string,mixed>>}
     */
    public function syncRange(School $school, string $fromDate, string $toDate): array
    {
        [$code, $password] = $this->credentials($school);
        $hash = $this->hash($code, $fromDate, $password);
        $url = $this->baseUrl()."/AndroidRS/SchoolRangeTransactions/{$code}/{$fromDate}/{$toDate}/{$hash}";

        return $this->getJson($url);
    }

    /**
     * @param  array{
     *   amount:int|float,
     *   externalReference:string,
     *   firstName:string,
     *   lastName:string,
     *   reason:string,
     *   callBackUrl?:string,
     *   phoneNumber?:string
     * }  $payload
     * @return array<string, mixed>
     */
    public function registerAdhoc(School $school, array $payload): array
    {
        [$code, $password] = $this->credentials($school);
        $hash = $this->hash($code, (string) $payload['externalReference'], $password);
        $url = $this->baseUrl()."/AndroidRS/AdhocPayments/Register/{$code}/{$hash}";

        return $this->postJson($url, $payload);
    }

    /**
     * @param  array{
     *   amount:int|float,
     *   externalReference:string,
     *   phoneNumber:string,
     *   firstName:string,
     *   lastName:string,
     *   reason:string
     * }  $payload
     * @return array<string, mixed>
     */
    public function requestAdhoc(School $school, array $payload): array
    {
        [$code, $password] = $this->credentials($school);
        $hash = $this->hash($code, (string) $payload['externalReference'], $password);
        $url = $this->baseUrl()."/AndroidRS/AdhocPayments/Request/{$code}/{$hash}";

        return $this->postJson($url, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function checkAdhoc(School $school, string $paymentReference): array
    {
        [$code, $password] = $this->credentials($school);
        $hash = $this->hash($code, $paymentReference, $password);
        $url = $this->baseUrl()."/AndroidRS/AdhocPayments/Check/{$code}/{$hash}/{$paymentReference}";

        return $this->getJson($url);
    }

    /**
     * @return array{0:string,1:string} [schoolCode, apiPassword]
     */
    public function credentials(School $school): array
    {
        if (! $school->schoolpay_enabled) {
            throw ValidationException::withMessages([
                'schoolpay' => 'SchoolPay is not enabled for this school.',
            ]);
        }

        $code = trim((string) $school->schoolpay_school_code);
        $password = (string) ($school->schoolpay_api_password ?? '');

        if ($code === '' || $password === '') {
            throw ValidationException::withMessages([
                'schoolpay' => 'SchoolPay school code and API password must be configured.',
            ]);
        }

        return [$code, $password];
    }

    private function baseUrl(): string
    {
        return (string) config('schoolpay.base_url');
    }

    /**
     * @return array<string, mixed>
     */
    private function getJson(string $url): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout((int) config('schoolpay.timeout', 20))
                ->get($url)
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('SchoolPay request failed: '.$e->getMessage(), previous: $e);
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        return $this->normalizeSyncPayload($data);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function postJson(string $url, array $payload): array
    {
        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout((int) config('schoolpay.timeout', 20))
                ->post($url, $payload)
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('SchoolPay request failed: '.$e->getMessage(), previous: $e);
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        $returnCode = isset($data['returnCode']) ? (int) $data['returnCode'] : null;
        if ($returnCode !== null && $returnCode !== 0) {
            $message = (string) ($data['returnMessage'] ?? 'SchoolPay rejected the request.');
            throw ValidationException::withMessages(['schoolpay' => $message]);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{returnCode:int|null,returnMessage:?string,transactions:array<int,array<string,mixed>>,supplementaryFeePayments:array<int,array<string,mixed>>}
     */
    private function normalizeSyncPayload(array $data): array
    {
        return [
            'returnCode' => isset($data['returnCode']) ? (int) $data['returnCode'] : null,
            'returnMessage' => isset($data['returnMessage']) ? (string) $data['returnMessage'] : null,
            'transactions' => array_values(array_filter(
                (array) ($data['transactions'] ?? []),
                static fn ($row) => is_array($row)
            )),
            'supplementaryFeePayments' => array_values(array_filter(
                (array) ($data['supplementaryFeePayments'] ?? []),
                static fn ($row) => is_array($row)
            )),
        ];
    }
}
