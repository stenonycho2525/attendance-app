<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 09:00:00');
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_stamp_flow_changes_status(): void
    {
        $this->actingAs($this->user);

        $this->get('/attendance')->assertSee('勤務外')->assertSee('2026年10月13日(火)')->assertSee('出勤');

        $this->post('/attendance', ['action' => 'clock_in']);
        $this->get('/attendance')->assertSee('出勤中')->assertSee('休憩入')->assertSee('退勤');

        Carbon::setTestNow('2026-10-13 12:00:00');
        $this->post('/attendance', ['action' => 'break_in']);
        $this->get('/attendance')->assertSee('休憩中')->assertSee('休憩戻');

        Carbon::setTestNow('2026-10-13 13:00:00');
        $this->post('/attendance', ['action' => 'break_out']);
        $this->get('/attendance')->assertSee('出勤中');

        Carbon::setTestNow('2026-10-13 18:00:00');
        $this->post('/attendance', ['action' => 'clock_out']);
        $this->get('/attendance')->assertSee('退勤済')->assertSee('お疲れ様でした。');

        $this->post('/attendance', ['action' => 'clock_in']);
        $this->assertSame(1, AttendanceRecord::count());

        $record = AttendanceRecord::first();
        $this->assertSame('09:00:00', $record->clock_in);
        $this->assertSame('18:00:00', $record->clock_out);
        $this->assertSame(1, $record->breaks()->count());
    }

    public function test_monthly_list_and_navigation(): void
    {
        $record = $this->createRecord('2026-10-01');

        $this->actingAs($this->user)->get('/attendance/list')
            ->assertSee('2026/10')
            ->assertSee('10/01(木)')
            ->assertSee('09:00')
            ->assertSee('8:00')
            ->assertSee('/attendance/' . $record->id)
            ->assertSee('?date=2026-09')
            ->assertSee('?date=2026-11');

        $this->get('/attendance/list?date=2026-09')->assertSee('2026/09')->assertDontSee('10/01(木)');
    }

    public function test_detail_shows_own_record_only(): void
    {
        $record = $this->createRecord('2026-10-01');
        $other = AttendanceRecord::create(['user_id' => User::factory()->create()->id, 'date' => '2026-10-01', 'clock_in' => '09:00:00']);

        $this->actingAs($this->user)->get('/attendance/' . $record->id)
            ->assertSee($this->user->name)
            ->assertSee('2026年')
            ->assertSee('10月1日')
            ->assertSee('12:00')
            ->assertSee('new_break_in[1]', false);

        $this->get('/attendance/' . $other->id)->assertNotFound();
    }

    public function test_correction_validation_messages(): void
    {
        $record = $this->createRecord('2026-10-01');
        $url = '/attendance/' . $record->id;
        $this->actingAs($this->user);

        $this->post($url, $this->correction(['new_clock_in' => '19:00']))
            ->assertSessionHasErrors(['new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です']);

        $this->post($url, $this->correction(['new_break_in' => ['08:00'], 'new_break_out' => ['08:30']]))
            ->assertSessionHasErrors(['new_break_in.0' => '休憩時間が不適切な値です']);

        $this->post($url, $this->correction(['new_break_in' => ['19:00'], 'new_break_out' => ['19:30']]))
            ->assertSessionHasErrors(['new_break_in.0' => '休憩時間が不適切な値です']);

        $this->post($url, $this->correction(['new_break_in' => ['17:00'], 'new_break_out' => ['19:00']]))
            ->assertSessionHasErrors(['new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です']);

        $this->post($url, $this->correction(['comment' => '']))
            ->assertSessionHasErrors(['comment' => '備考を記入してください']);

        $this->assertSame(0, StampCorrectionRequest::count());
    }

    public function test_correction_request_is_listed_and_locks_detail(): void
    {
        $record = $this->createRecord('2026-10-01');
        $this->actingAs($this->user);

        $this->post('/attendance/' . $record->id, $this->correction())
            ->assertRedirect('/attendance/' . $record->id);

        $application = StampCorrectionRequest::first();
        $this->assertSame(StampCorrectionRequest::STATUS_PENDING, $application->status);
        $this->assertSame(2, $application->breaks()->count());

        $this->get('/attendance/' . $record->id)
            ->assertSee('承認待ちのため修正できません')
            ->assertSee('08:30')
            ->assertDontSee('type="submit"', false);

        $this->get('/stamp_correction_request/list')
            ->assertSee('承認待ち')
            ->assertSee('遅延のため')
            ->assertSee('2026/10/01')
            ->assertSee('/application/' . $application->id);

        $this->get('/application/' . $application->id)->assertRedirect('/attendance/' . $record->id);
    }

    private function createRecord(string $date): AttendanceRecord
    {
        $record = AttendanceRecord::create([
            'user_id' => $this->user->id,
            'date' => $date,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $record->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);

        return $record;
    }

    private function correction(array $overrides = []): array
    {
        return array_merge([
            'new_clock_in' => '08:30',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00', '15:00'],
            'new_break_out' => ['13:00', '15:15'],
            'comment' => '電車遅延のため',
        ], $overrides);
    }
}
