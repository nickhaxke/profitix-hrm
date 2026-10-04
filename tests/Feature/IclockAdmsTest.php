<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IclockAdmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_request_returns_stable_registry_code()
    {
        $sn = 'PYA8253800287';
        $response = $this->post("/iclock/registry?SN={$sn}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/push;charset=UTF-8');
        $this->assertEquals("RegistryCode=1234567890-{$sn}", $response->getContent());

        $this->assertDatabaseHas('devices', [
            'serial_number' => $sn,
        ]);
    }

    public function test_cdata_request_handshake()
    {
        $sn = 'PYA8253800287';
        $response = $this->get("/iclock/cdata?SN={$sn}");

        $response->assertStatus(200);
        $response->assertSee("GET OPTION FROM: {$sn}");
    }

    public function test_rtlog_attendance_import_and_duplicate_protection()
    {
        $sn = 'PYA8253800287';

        $payload = "1\t2026-10-03 14:45:40\t1\t1\t0\t0\t0\n".
                   "2\t2026-10-03 14:46:00\t0\t1\t0\t0\t0\n";

        $response = $this->post("/iclock/cdata?SN={$sn}&table=rtlog", [], [], [], [], $payload);
        $response->assertStatus(200);
        $response->assertSee('OK');

        // Auto-provisioning: users 1 and 2 should exist
        $this->assertDatabaseHas('employees', ['employee_code' => '1']);
        $this->assertDatabaseHas('employees', ['employee_code' => '2']);

        // Logs should be created
        $this->assertDatabaseCount('attendance_logs', 2);

        // Duplicate protection: resend the same payload
        $response = $this->post("/iclock/cdata?SN={$sn}&table=rtlog", [], [], [], [], $payload);
        $response->assertStatus(200);

        // Log count should still be 2
        $this->assertDatabaseCount('attendance_logs', 2);
    }

    public function test_user_auto_provisioning()
    {
        $sn = 'PYA8253800287';

        $payload = "3\tTest User 3\t1\t1234\n".
                   "4\tTest User 4\t0\t\n";

        $response = $this->post("/iclock/cdata?SN={$sn}&table=tabledata&tablename=user", [], [], [], [], $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('employees', [
            'employee_code' => '3',
            'full_name' => 'Test User 3',
        ]);

        $this->assertDatabaseHas('employees', [
            'employee_code' => '4',
            'full_name' => 'Test User 4',
        ]);
    }

    public function test_malformed_adms_payload_handling()
    {
        $sn = 'PYA8253800287';

        $payload = "\n\n\n   \t  \n";

        $response = $this->post("/iclock/cdata?SN={$sn}&table=rtlog", [], [], [], [], $payload);
        $response->assertStatus(200);

        $this->assertDatabaseCount('attendance_logs', 0);
    }

    public function test_biodata_and_biophoto_are_safely_ignored()
    {
        $sn = 'PYA8253800287';
        $payload = 'sensitive_fingerprint_data_here';

        $response = $this->post("/iclock/cdata?SN={$sn}&table=tabledata&tablename=biodata", [], [], [], [], $payload);
        $response->assertStatus(200);
        $response->assertSee('OK');

        $response = $this->post("/iclock/cdata?SN={$sn}&table=tabledata&tablename=biophoto", [], [], [], [], $payload);
        $response->assertStatus(200);
        $response->assertSee('OK');
    }
}
