<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>คำขอลา 75% รออนุมัติ</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f2f4f7;font-family:'Segoe UI',Tahoma,Arial,sans-serif;color:#1c2333;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:680px;margin:0 auto;background:#fff;border:1px solid #e3e7ee;border-radius: 5px;overflow:hidden;">
    <tr>
      <td style="padding:20px 24px;background:#1e3a8a;color:#fff;">
        <div style="font-size:11px;font-weight:700;letter-spacing:.12em;opacity:.85;">SUPAVUT INSIGHT · TIME &amp; LEAVE APPROVAL</div>
        <div style="margin-top:4px;font-size:18px;font-weight:700;">มีคำขอลา 75% รออนุมัติ</div>
      </td>
    </tr>
    <tr>
      <td style="padding:22px 24px 12px;font-size:14px;line-height:1.7;">
        เรียน {{ $supervisorName }}<br>
        <strong>{{ $foremanName }}</strong> ส่งคำขอลา 75% จำนวน <strong>{{ $requests->count() }}</strong> รายการ
        จากแผนก <strong>{{ $departmentName }}</strong>
      </td>
    </tr>
    <tr>
      <td style="padding:0 24px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;font-size:12.5px;">
          <thead>
            <tr style="background:#f6f8fa;color:#5b6577;">
              <th align="left" style="padding:9px 10px;border:1px solid #e3e7ee;">พนักงาน</th>
              <th align="left" style="padding:9px 10px;border:1px solid #e3e7ee;">ประเภทการลา</th>
              <th align="center" style="padding:9px 10px;border:1px solid #e3e7ee;">วันที่ลา</th>
              <th align="center" style="padding:9px 10px;border:1px solid #e3e7ee;">จำนวน</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($requests as $request)
              <tr>
                <td style="padding:9px 10px;border:1px solid #e3e7ee;">
                  <strong>{{ $request->employee_name ?: $request->employee_code }}</strong><br>
                  <span style="color:#5b6577;font-size:11.5px;">{{ $request->employee_code }}</span>
                </td>
                <td style="padding:9px 10px;border:1px solid #e3e7ee;">{{ $request->leaveTypeLabel() }}</td>
                <td align="center" style="padding:9px 10px;border:1px solid #e3e7ee;white-space:nowrap;">{{ $request->leave_date->format('d/m/Y') }}</td>
                <td align="center" style="padding:9px 10px;border:1px solid #e3e7ee;white-space:nowrap;">1 วัน</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </td>
    </tr>
    <tr>
      <td style="padding:22px 24px;">
        <a href="{{ $actionUrl }}" style="display:inline-block;padding:11px 20px;background:#1e3a8a;color:#fff;font-size:13px;font-weight:700;text-decoration:none;border-radius: 4px;">เปิดหน้าอนุมัติลา</a>
        <p style="margin:16px 0 0;color:#5b6577;font-size:11.5px;line-height:1.65;">อีเมลนี้ส่งอัตโนมัติจาก SUPAVUT INSIGHT · Time &amp; Leave Approval กรุณาอย่าตอบกลับ</p>
      </td>
    </tr>
  </table>
</body>
</html>
