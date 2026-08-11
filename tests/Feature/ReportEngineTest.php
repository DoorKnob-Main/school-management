<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReportEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\PermissionSeeder;
use Tests\TestCase;

class ReportEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    /**
     * @test
     */
    public function report_engine_service_compiles_html_with_branding_config()
    {
        $service = app(ReportEngineService::class);
        $html = $service->renderHtml('reports.templates.sample-base-report', [
            'reportConfig' => $service->getReportConfig()
        ]);

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('report-page', $html);
    }

    /**
     * @test
     */
    public function super_admin_can_preview_base_report()
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('reports.base.preview'));

        $response->assertSuccessful();
        $response->assertViewIs('reports.templates.sample-base-report');
    }

    /**
     * @test
     */
    public function super_admin_can_view_report_settings_tab()
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('settings.index', ['tab' => 'reports']));

        $response->assertSuccessful();
        $response->assertSee('Base Letterhead Configuration', false);
        $response->assertSee('report_watermark_text');
        $response->assertSee('report_school_stamp');
    }

    /**
     * @test
     */
    public function unauthenticated_user_cannot_access_report_engine_preview()
    {
        $response = $this->get(route('reports.base.preview'));
        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function super_admin_can_view_finance_reports_page()
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $superAdmin->givePermissionTo('view reports');

        $response = $this->actingAs($superAdmin)->get(route('finance.reports.index'));

        $response->assertSuccessful();
        $response->assertViewIs('finance.reports.index');
        $response->assertSee('Financial Analytics & Reports', false);
    }
}
