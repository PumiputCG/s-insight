<?php

namespace Tests\Feature\OtApproval;

use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtMember;
use App\Models\OtApproval\OtRequest;
use App\Models\OtApproval\OtRequestApproval;
use Tests\TestCase;

class OtApprovalRouteTest extends TestCase
{
    public function test_ot_approval_pages_require_an_insight_login(): void
    {
        $this->get('/ot-approval')->assertRedirect(route('login'));
        $this->get('/ot-approval/overview')->assertRedirect(route('login'));
        $this->get('/ot-approval/overview/data')->assertRedirect(route('login'));
        $this->get('/ot-approval/overview/employees')->assertRedirect(route('login'));
        $this->get('/ot-approval/exports/v74?scope=all')->assertRedirect(route('login'));
        $this->get('/ot-approval/requests')->assertRedirect(route('login'));
        $this->get('/ot-approval/requests/employees')->assertRedirect(route('login'));
        $this->post('/ot-approval/requests')->assertRedirect(route('login'));
        $this->post('/ot-approval/requests/submit')->assertRedirect(route('login'));
        $this->get('/ot-approval/approvals')->assertRedirect(route('login'));
        $this->get('/ot-approval/approvals/data')->assertRedirect(route('login'));
        $this->get('/ot-approval/settings')->assertRedirect(route('login'));
    }

    public function test_ot_access_models_use_the_module_database(): void
    {
        $this->assertSame('mysql_ot_approval', (new OtMember)->getConnectionName());
        $this->assertSame('mysql_ot_approval', (new OtDepartmentAssignment)->getConnectionName());
        $this->assertSame('mysql_ot_approval', (new OtRequest)->getConnectionName());
        $this->assertSame('mysql_ot_approval', (new OtRequestApproval)->getConnectionName());
        $this->assertTrue($this->app['router']->has('ot-approval.approvals.decision'));
        $this->assertTrue($this->app['router']->has('ot-approval.approvals.bulk-decision'));
        $this->assertTrue($this->app['router']->has('ot-approval.exports.v74'));
    }
}
