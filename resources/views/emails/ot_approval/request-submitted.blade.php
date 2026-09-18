@php
  /** @var \Illuminate\Support\Collection $requests */
  $thaiDate = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $workDate)->format('d/m/Y');
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>คำขอ OT รออนุมัติ</title>
</head>
{{-- อีเมลต้องใช้ inline style เพราะ mail client ส่วนใหญ่ตัด <style> ทิ้ง --}}
<body style="margin:0;padding:24px 12px;background:#f2f4f7;font-family:'Segoe UI',Tahoma,Arial,sans-serif;color:#1c2333;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e3e7ee;border-radius: 5px;overflow:hidden;">
    <tr>
      <td style="padding:20px 24px;background:#1e3a8a;color:#ffffff;">
        <div style="font-size:11px;font-weight:700;letter-spacing:.12em;opacity:.85;">SUPAVUT INSIGHT · TIME &amp; LEAVE APPROVAL</div>
        <div style="margin-top:4px;font-size:18px;font-weight:700;">มีคำขอ OT รออนุมัติ</div>
      </td>
    </tr>

    <tr>
      <td style="padding:22px 24px 6px;">
        <p style="margin:0 0 14px;font-size:14px;line-height:1.7;">
          เรียน {{ $supervisorName }}<br>
          <strong>{{ $foremanName }}</strong> ได้ส่งคำขอค่าล่วงเวลาจำนวน
          <strong>{{ $requests->count() }}</strong> รายการ เพื่อรอการอนุมัติจากท่าน
        </p>

        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom:16px;font-size:13px;">
          <tr>
            <td style="padding:4px 0;color:#5b6577;width:88px;">แผนก</td>
            <td style="padding:4px 0;font-weight:600;">{{ $departmentName }}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;color:#5b6577;">วันที่ทำ OT</td>
            <td style="padding:4px 0;font-weight:600;">{{ $thaiDate }}</td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding:0 24px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;font-size:12.5px;">
          <thead>
            <tr style="background:#f6f8fa;color:#5b6577;">
              <th align="left" style="padding:9px 10px;border:1px solid #e3e7ee;font-weight:700;">พนักงาน</th>
              <th align="left" style="padding:9px 10px;border:1px solid #e3e7ee;font-weight:700;">ประเภท OT</th>
              <th align="center" style="padding:9px 10px;border:1px solid #e3e7ee;font-weight:700;white-space:nowrap;">ช่วงเวลา</th>
              <th align="center" style="padding:9px 10px;border:1px solid #e3e7ee;font-weight:700;white-space:nowrap;">รวม</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($requests as $request)
              <tr>
                <td style="padding:9px 10px;border:1px solid #e3e7ee;">
                  <div style="font-weight:600;">{{ $request->employee_name ?: $request->employee_code }}</div>
                  <div style="color:#5b6577;font-size:11.5px;">{{ $request->employee_code }}{{ $request->position_name ? ' · '.$request->position_name : '' }}</div>
                </td>
                <td style="padding:9px 10px;border:1px solid #e3e7ee;">{{ $request->otTypeLabel() }}</td>
                <td align="center" style="padding:9px 10px;border:1px solid #e3e7ee;white-space:nowrap;">
                  {{ $request->requested_start_at?->format('H:i') }}–{{ $request->requested_end_at?->format('H:i') }}
                </td>
                <td align="center" style="padding:9px 10px;border:1px solid #e3e7ee;white-space:nowrap;">
                  {{ $request->requested_hours }} ชม. {{ $request->requested_minutes ?? 0 }} น.
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding:22px 24px;">
        <a href="{{ $actionUrl }}" style="display:inline-block;padding:11px 20px;background:#1e3a8a;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;border-radius: 4px;">
          เปิดหน้าอนุมัติ OT
        </a>
        <p style="margin:16px 0 0;color:#5b6577;font-size:11.5px;line-height:1.65;">
          อีเมลฉบับนี้ส่งอัตโนมัติจากระบบ SUPAVUT INSIGHT · Time &amp; Leave Approval กรุณาอย่าตอบกลับ<br>
          หากไม่ใช่ผู้รับผิดชอบแผนกนี้ กรุณาแจ้งฝ่าย IT
        </p>
      </td>
    </tr>
  </table>
</body>
</html>
