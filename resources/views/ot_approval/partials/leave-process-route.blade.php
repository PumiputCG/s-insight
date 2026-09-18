{{--
  สถานะทั้งหมดของการลา

  ใช้ dialog เดียวกับ OT (`ot_approval.partials.process-route`) เพื่อให้ทั้ง 6 หน้าของแอป
  หน้าตาและพฤติกรรมตรงกัน — รูปพนักงานกดซูมได้ วงกลมกดดูรายละเอียดรายขั้นได้เหมือนกัน
  ที่นี่ทำแค่ติดธง kind=leave ให้ payload แล้วส่งต่อ ไม่มี dialog ของตัวเองอีก
--}}
@include('ot_approval.partials.process-route')

@once
  <script>
    (function () {
      'use strict';

      if (window.leaveProcessRoute) return;

      function tagged(row) {
        return Object.assign({}, row || {}, { kind: 'leave' });
      }

      /* หน้าลาส่งชื่อ/รูปมากับตัวคำขออยู่แล้ว จึงไม่ต้องส่ง employee แยกเหมือนหน้า OT */
      function create(row) {
        return window.otProcessRoute.create(tagged(row));
      }

      function open(row, trigger) {
        return window.otProcessRoute.open(tagged(row), trigger);
      }

      window.leaveProcessRoute = { create: create, open: open, close: function () { window.otProcessRoute.close(); } };
    })();
  </script>
@endonce
