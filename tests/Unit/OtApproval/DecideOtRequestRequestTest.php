<?php

namespace Tests\Unit\OtApproval;

use App\Http\Requests\OtApproval\BulkDecideOtRequestsRequest;
use App\Http\Requests\OtApproval\DecideOtRequestRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DecideOtRequestRequestTest extends TestCase
{
    public function test_rejection_requires_a_reason(): void
    {
        $request = DecideOtRequestRequest::create('/', 'POST', [
            'decision' => 'rejected',
            'note' => '   ',
        ]);
        $request->setContainer($this->app);
        $prepare = new \ReflectionMethod($request, 'prepareForValidation');
        $prepare->invoke($request);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame('กรุณาระบุเหตุผลที่ไม่อนุมัติ', $validator->errors()->first('note'));
    }

    public function test_approval_note_is_optional(): void
    {
        $request = DecideOtRequestRequest::create('/', 'POST', [
            'decision' => 'approved',
            'note' => '',
        ]);
        $request->setContainer($this->app);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_bulk_rejection_requires_one_reason_for_the_selected_requests(): void
    {
        $request = BulkDecideOtRequestsRequest::create('/', 'POST', [
            'request_ids' => [10, 11],
            'decision' => 'rejected',
            'note' => '   ',
        ]);
        $request->setContainer($this->app);
        $prepare = new \ReflectionMethod($request, 'prepareForValidation');
        $prepare->invoke($request);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame('กรุณาระบุเหตุผลที่ไม่อนุมัติ', $validator->errors()->first('note'));
    }

    public function test_bulk_rejection_accepts_a_reason_to_apply_to_every_selected_request(): void
    {
        $request = BulkDecideOtRequestsRequest::create('/', 'POST', [
            'request_ids' => [10, 11],
            'decision' => 'rejected',
            'note' => 'กำลังคนเพียงพอ',
        ]);
        $request->setContainer($this->app);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }
}
