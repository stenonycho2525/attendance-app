<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private AttendanceRecord $record;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 20:00:00');

        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->create(['name' => '山田 太郎']);
        $this->record = AttendanceRecord::create([
            'user_id' => $this->staff->id,
            'date' => '2026-10-13',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $this->record->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_general_user_cannot_access_admin_pages(): void
    {
        $this->get('/admin/attendance/list')->assertRedirect('/admin/login');
        $this->actingAs($this->staff)->get('/admin/attendance/list')->assertRedirect('/admin/login');
    }

    public function test_daily_list_shows_today_and_navigates(): void
    {
        $this->actingAs($this->admin)->get('/admin/attendance/list')
            ->assertSee('2026年10月13日の勤怠')
            ->assertSee('山田 太郎')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('1:00')
            ->assertSee('8:00')
            ->assertSee('?date=2026-10-12')
            ->assertSee('?date=2026-10-14')
            ->assertSee('/admin/attendance/' . $this->record->id);

        $this->get('/admin/attendance/list?date=2026-10-12')
            ->assertSee('2026/10/12')
            ->assertDontSee('山田 太郎');
    }

    public function test_admin_can_update_attendance_directly(): void
    {
        $this->actingAs($this->admin)->get('/admin/attendance/' . $this->record->id)
            ->assertSee('山田 太郎')
            ->assertSee('10月13日');

        $this->post('/admin/attendance/' . $this->record->id, [
            'new_clock_in' => '18:30',
            'new_clock_out' => '18:00',
            'comment' => '修正',
        ])->assertSessionHasErrors(['new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です']);

        $this->post('/admin/attendance/' . $this->record->id, [
            'new_clock_in' => '10:00',
            'new_clock_out' => '19:00',
            'new_break_in' => ['12:30', ''],
            'new_break_out' => ['13:30', ''],
            'comment' => '打刻漏れのため',
        ])->assertRedirect('/admin/attendance/' . $this->record->id);

        $this->record->refresh();
        $this->assertSame('10:00:00', $this->record->clock_in);
        $this->assertSame('19:00:00', $this->record->clock_out);
        $this->assertSame('打刻漏れのため', $this->record->comment);
        $this->assertSame(['12:30:00'], $this->record->breaks()->pluck('break_in')->all());

        $this->actingAs($this->staff)->get('/attendance/list')->assertSee('10:00')->assertSee('19:00');
    }

    public function test_staff_list_and_monthly_attendance(): void
    {
        $this->actingAs($this->admin)->get('/admin/staff/list')
            ->assertSee('山田 太郎')
            ->assertSee($this->staff->email)
            ->assertSee('/admin/attendance/staff/' . $this->staff->id)
            ->assertDontSee($this->admin->email);

        $this->get('/admin/attendance/staff/' . $this->staff->id)
            ->assertSee('山田 太郎さんの勤怠')
            ->assertSee('2026/10')
            ->assertSee('10/13(火)')
            ->assertSee('/admin/attendance/' . $this->record->id);

        $this->get('/admin/attendance/staff/' . $this->staff->id . '?date=2026-09')->assertSee('2026/09');
    }

    public function test_admin_can_approve_correction_request(): void
    {
        $application = StampCorrectionRequest::create([
            'attendance_record_id' => $this->record->id,
            'user_id' => $this->staff->id,
            'date' => $this->record->date,
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'comment' => '電車遅延のため',
            'status' => StampCorrectionRequest::STATUS_PENDING,
        ]);
        $application->breaks()->create(['break_in' => '11:30:00', 'break_out' => '12:30:00']);

        $this->actingAs($this->admin)->get('/admin/attendance/' . $this->record->id)
            ->assertSee('承認待ちのため修正はできません。');

        $this->get('/stamp_correction_request/list')
            ->assertSee('山田 太郎')
            ->assertSee('電車遅延のため')
            ->assertSee('/stamp_correction_request/approve/' . $application->id);

        $this->get('/stamp_correction_request/approve/' . $application->id)
            ->assertSee('08:00')
            ->assertSee('11:30')
            ->assertSee('承認');

        $this->post('/stamp_correction_request/approve/' . $application->id)
            ->assertRedirect('/stamp_correction_request/approve/' . $application->id);

        $this->assertSame(StampCorrectionRequest::STATUS_APPROVED, $application->fresh()->status);
        $this->record->refresh();
        $this->assertSame('08:00:00', $this->record->clock_in);
        $this->assertSame('17:00:00', $this->record->clock_out);
        $this->assertSame(['11:30:00'], $this->record->breaks()->pluck('break_in')->all());

        $this->actingAs($this->staff)->get('/stamp_correction_request/list')
            ->assertSeeInOrder(['id="content2"', '承認済み', '電車遅延のため'], false);
    }
}
