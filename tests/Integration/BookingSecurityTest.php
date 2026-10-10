<?php

namespace Tests\Integration;

use Tests\TestCase;

/**
 * Integration tests for the public booking endpoint security (regression guard).
 *
 * These tests exercise the real HTTP endpoint "booking/register" against a running
 * Easy!Appointments instance and verify the database state through the framework
 * database connection.
 *
 * They are opt-in: set the EA_BASE_URL environment variable to the base URL of a
 * running instance (e.g. http://127.0.0.1:8080) whose database matches this app's
 * config.php. Without it the tests are skipped, so the default (database-less) CI
 * "Unit" suite is unaffected.
 *
 * Covered risks (see docs/TECHNICAL-AUDIT.md):
 *  - R1: Booking::register() must not update an arbitrary appointment/customer just
 *        because the client sends its numeric id (IDOR / broken access control).
 *  - R2: Concurrent bookings for the same provider/slot must not both succeed
 *        (double-booking race).
 *
 * No real customer data or secrets are used; all records are synthetic.
 */
class BookingSecurityTest extends TestCase
{
    private static ?string $baseUrl = null;

    private object $db;

    private int $providerId = 2; // "Jane Doe", installed by the setup wizard.

    private int $serviceId = 1; // "Service" (30 min), offered by the provider above.

    protected function setUp(): void
    {
        self::$baseUrl = getenv('EA_BASE_URL') ?: null;

        if (!self::$baseUrl) {
            $this->markTestSkipped('Set EA_BASE_URL to a running instance to run booking integration tests.');
        }

        $ci = get_instance();
        $this->db = $ci->db;

        // Keep the test deterministic regardless of the instance configuration.
        $this->db->update('settings', ['value' => '0'], ['name' => 'require_captcha']);
        $this->db->update('settings', ['value' => '0'], ['name' => 'disable_booking']);

        // Guard: the baseline provider must offer the baseline service.
        $offersService =
            $this->db
                ->get_where('services_providers', [
                    'id_users' => $this->providerId,
                    'id_services' => $this->serviceId,
                ])
                ->num_rows() > 0;

        if (!$offersService) {
            $this->markTestSkipped('Baseline provider/service not present; run "php index.php console install" first.');
        }
    }

    /**
     * R1: an anonymous new booking that carries a victim appointment id (but no valid
     * appointment hash) must not overwrite that appointment. A fresh appointment must
     * be created instead and the victim must stay intact.
     */
    public function test_new_booking_cannot_overwrite_existing_appointment_by_id(): void
    {
        $victimId = $this->createVictimAppointment('2026-10-20 11:00:00', '2026-10-20 11:30:00', 'original-notes');

        $attackSlotStart = '2026-10-20 13:00:00';
        $attackSlotEnd = '2026-10-20 13:30:00';

        // Keep the attack slot free regardless of earlier test runs.
        $this->db->delete('appointments', [
            'start_datetime' => $attackSlotStart,
            'id_users_provider' => $this->providerId,
        ]);

        $response = $this->registerBooking([
            'manage_mode' => false,
            'appointment' => [
                // The attacker targets the victim's record id without knowing its hash.
                'id' => $victimId,
                'start_datetime' => $attackSlotStart,
                'end_datetime' => $attackSlotEnd,
                'notes' => 'HACKED',
                'is_unavailability' => false,
                'id_users_provider' => $this->providerId,
                'id_services' => $this->serviceId,
            ],
            'customer' => [
                'first_name' => 'Mallory',
                'last_name' => 'Attacker',
                'email' => 'mallory.attacker@example.invalid',
                'phone_number' => '000',
            ],
        ]);

        $this->assertArrayHasKey('appointment_id', $response, 'Booking should still succeed as a new appointment.');
        $this->assertNotEquals(
            $victimId,
            (int) $response['appointment_id'],
            'The booking must create a NEW appointment, not reuse the victim id.',
        );

        $victim = $this->db->get_where('appointments', ['id' => $victimId])->row_array();
        $this->assertNotEmpty($victim, 'Victim appointment must still exist.');
        $this->assertSame('2026-10-20 11:00:00', $victim['start_datetime'], 'Victim start time must be unchanged.');
        $this->assertSame('original-notes', $victim['notes'], 'Victim notes must not be overwritten.');
        $this->assertSame(
            (string) $this->victimCustomerId(),
            (string) $victim['id_users_customer'],
            'Victim appointment must still belong to its original customer.',
        );
    }

    /**
     * R1: an anonymous booking that carries a victim customer id must not overwrite
     * that customer's record.
     */
    public function test_new_booking_cannot_overwrite_existing_customer_by_id(): void
    {
        $this->createVictimAppointment('2026-10-21 11:00:00', '2026-10-21 11:30:00', 'original-notes');
        $customerId = $this->victimCustomerId();

        $before = $this->db->get_where('users', ['id' => $customerId])->row_array();

        // Keep the attack slot free regardless of earlier test runs.
        $this->db->delete('appointments', [
            'start_datetime' => '2026-10-21 14:00:00',
            'id_users_provider' => $this->providerId,
        ]);

        $this->registerBooking([
            'manage_mode' => false,
            'appointment' => [
                'start_datetime' => '2026-10-21 14:00:00',
                'end_datetime' => '2026-10-21 14:30:00',
                'notes' => '',
                'is_unavailability' => false,
                'id_users_provider' => $this->providerId,
                'id_services' => $this->serviceId,
            ],
            'customer' => [
                'id' => $customerId, // attacker tries to hijack an existing customer record
                'first_name' => 'Mallory',
                'last_name' => 'Attacker',
                'email' => 'mallory.hijack@example.invalid',
                'phone_number' => '000',
            ],
        ]);

        $after = $this->db->get_where('users', ['id' => $customerId])->row_array();
        $this->assertSame($before['first_name'], $after['first_name'], 'Victim customer name must be unchanged.');
        $this->assertSame($before['email'], $after['email'], 'Victim customer email must be unchanged.');
    }

    /**
     * R2: firing several concurrent bookings for the exact same provider/slot must
     * result in at most one booked appointment for that slot.
     */
    public function test_concurrent_bookings_do_not_double_book_the_same_slot(): void
    {
        $slotStart = '2026-10-22 10:00:00';
        $slotEnd = '2026-10-22 10:30:00';

        // Make sure the slot starts empty.
        $this->db->delete('appointments', ['start_datetime' => $slotStart, 'id_users_provider' => $this->providerId]);

        $payloads = [];
        for ($i = 0; $i < 5; $i++) {
            $payloads[] = [
                'manage_mode' => false,
                'appointment' => [
                    'start_datetime' => $slotStart,
                    'end_datetime' => $slotEnd,
                    'notes' => '',
                    'is_unavailability' => false,
                    'id_users_provider' => $this->providerId,
                    'id_services' => $this->serviceId,
                ],
                'customer' => [
                    'first_name' => 'Race' . $i,
                    'last_name' => 'Tester',
                    'email' => 'race' . $i . '.' . uniqid() . '@example.invalid',
                    'phone_number' => '000',
                ],
            ];
        }

        $this->registerBookingsConcurrently($payloads);

        $booked = $this->db
            ->get_where('appointments', [
                'start_datetime' => $slotStart,
                'id_users_provider' => $this->providerId,
                'is_unavailability' => 0,
            ])
            ->num_rows();

        $this->assertLessThanOrEqual(1, $booked, 'The same provider/slot must not be booked more than once.');
    }

    // --- helpers -----------------------------------------------------------------

    private function victimCustomerId(): int
    {
        $row = $this->db
            ->select('users.id')
            ->from('users')
            ->join('roles', 'roles.id = users.id_roles', 'inner')
            ->where('roles.slug', 'customer')
            ->order_by('users.id', 'asc')
            ->limit(1)
            ->get()
            ->row_array();

        return (int) $row['id'];
    }

    private function createVictimAppointment(string $start, string $end, string $notes): int
    {
        $this->db->delete('appointments', ['start_datetime' => $start, 'id_users_provider' => $this->providerId]);

        $this->db->insert('appointments', [
            'start_datetime' => $start,
            'end_datetime' => $end,
            'notes' => $notes,
            'hash' => 'victimhash' . substr(md5($start), 0, 2),
            'is_unavailability' => 0,
            'id_users_provider' => $this->providerId,
            'id_users_customer' => $this->victimCustomerId(),
            'id_services' => $this->serviceId,
        ]);

        return (int) $this->db->insert_id();
    }

    /**
     * Fetch a fresh CSRF cookie/token pair from the booking page.
     *
     * @return array{cookie: string, token: string}
     */
    private function freshCsrf(): array
    {
        $ch = curl_init(self::$baseUrl . '/');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true]);
        $raw = curl_exec($ch);
        curl_close($ch);

        $token = '';
        if (preg_match('/^Set-Cookie:\s*csrf_cookie=([^;]+)/mi', $raw, $m)) {
            $token = $m[1];
        }

        return ['cookie' => 'csrf_cookie=' . $token, 'token' => $token];
    }

    /**
     * @return array<string,mixed> Decoded JSON response.
     */
    private function registerBooking(array $postData): array
    {
        $csrf = $this->freshCsrf();

        $ch = curl_init(self::$baseUrl . '/index.php/booking/register');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Cookie: ' . $csrf['cookie']],
            CURLOPT_POSTFIELDS => http_build_query([
                'csrf_token' => $csrf['token'],
                'post_data' => $postData,
            ]),
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : ['_raw' => $body];
    }

    private function registerBookingsConcurrently(array $payloads): void
    {
        $multi = curl_multi_init();
        $handles = [];

        foreach ($payloads as $postData) {
            $csrf = $this->freshCsrf();
            $ch = curl_init(self::$baseUrl . '/index.php/booking/register');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Cookie: ' . $csrf['cookie']],
                CURLOPT_POSTFIELDS => http_build_query([
                    'csrf_token' => $csrf['token'],
                    'post_data' => $postData,
                ]),
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[] = $ch;
        }

        do {
            $status = curl_multi_exec($multi, $running);
            if ($running) {
                curl_multi_select($multi);
            }
        } while ($running && $status === CURLM_OK);

        foreach ($handles as $ch) {
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);
    }
}
