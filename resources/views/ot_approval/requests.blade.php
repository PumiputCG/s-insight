@extends('layouts.portal')

@section('title', 'OT Requests')
@section('topbar-title')<span data-i18n="ot.requests.title">ขอ OT</span>@endsection

@section('side-foot-extra')
  <div class="otr-source" data-request-source aria-live="polite">
    <span class="otr-source-dot" aria-hidden="true"></span>
    <span data-request-source-copy>{{ match ($attendance['source_mode'] ?? 'bplus') {
      'local' => 'Local demo · ข้อมูลจำลอง',
      'snapshot' => 'Bplus · Snapshot ใน Local',
      default => 'Bplus · เวลาเข้า–ออก',
    } }}</span>
  </div>
@endsection

@section('content')
  @php
    $companies = collect($attendance['companies'] ?? []);
    $totals = [
      'total' => $companies->sum('total'),
      'clocked_in' => $companies->sum('clocked_in'),
      'ot_requested' => $companies->sum('ot_requested'),
      'completed' => $companies->sum('completed'),
    ];
  @endphp

  <style>
    .otr-page,
    .otr-modal {
      --otr-status-warning: #8a6200;
      --otr-status-warning-border: #e0a51c;
      --otr-status-success: #1d7a42;
      --otr-status-success-border: #35a863;
      --otr-status-danger: #9f2f26;
      --otr-status-danger-border: #da5a4e;
    }
    .otr-page {
      width: min(100%, 92rem);
      margin: 0 auto;
    }
    {{-- สีย้อมพื้นที่ตาม "กะที่คุณดูแล" (.main.is-myshift-*) และไอคอนดวงอาทิตย์/ดวงจันทร์
         (.otr-shift-mark) ย้ายไปอยู่ที่ partials/shift-filter แล้ว หน้าขอลาใช้ชุดเดียวกัน --}}

    .otr-head { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: end; gap: 1.25rem; margin-bottom: 1rem; }
    .otr-heading h1 { margin-top: .2rem; font-size: 1.8rem; font-weight: 650; line-height: 1.2; text-wrap: balance; }
    .otr-heading p { max-width: 42rem; margin-top: .35rem; color: var(--muted-light); font-size: .85rem; line-height: 1.6; }
    .otr-head-side { display: grid; justify-items: end; gap: .6rem; }
    .otr-summary { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .55rem 1rem; }
    .otr-summary-item { min-width: 4.5rem; padding-right: 1rem; border-right: 1px solid var(--line-light); }
    .otr-summary-item:last-child { padding-right: 0; border-right: 0; }
    .otr-summary-item span { display: block; color: var(--muted-light); font-size: .64rem; white-space: nowrap; }
    .otr-summary-item strong { display: block; margin-top: .08rem; font-size: .95rem; font-weight: 650; font-variant-numeric: tabular-nums; }

    /* ── ปุ่มนาฬิกา + โมดัลสรุปตัวเลข — สเปกเดียวกับหน้าภาพรวม OT ──────────
       ตัวเลขไม่ควรกินที่บนหัวหน้าตลอดเวลา ย่อไว้หลังไอคอนเดียว */
    .ot-summary-open { width: 2.4rem; height: 2.4rem; flex: 0 0 auto; display: grid; place-items: center; border: 1px solid var(--line-strong); border-radius: .3rem; background: var(--panel); color: var(--muted-light); cursor: var(--cursor-action); }
    .ot-summary-open:hover { border-color: var(--moss); background: var(--hover-soft); color: var(--moss); }
    .ot-summary-open svg { width: 1.15rem; height: 1.15rem; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .ot-summary-modal { position: fixed; inset: 0; z-index: 1200; display: grid; place-items: center; padding: 1rem; background: var(--overlay-bg); }
    .ot-summary-modal[hidden] { display: none; }
    .ot-summary-dialog { width: min(28rem, 100%); border: 1px solid var(--line-light); border-radius: .4rem; background: var(--panel); box-shadow: 0 24px 70px rgb(0 0 0 / 22%); }
    .ot-summary-dialog-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .9rem 1.1rem; border-bottom: 1px solid var(--line-light); }
    .ot-summary-close { width: 2.1rem; height: 2.1rem; border: 1px solid var(--line-light); border-radius: .3rem; background: transparent; color: inherit; cursor: var(--cursor-action); font-size: 1.1rem; line-height: 1; }
    /* ทั้งบล็อกอยู่กึ่งกลางการ์ด · แต่ละบรรทัดเป็น ป้ายซ้าย–ตัวเลขขวา
       ต้องล้าง justify-content: flex-end ของแถบแนวนอนด้วย ไม่งั้นคอลัมน์หดไปกองชิดขวา */
    .ot-summary-dialog .otr-summary { display: grid; gap: 0; width: min(22rem, 100%); margin: 0 auto; padding: 1.2rem 1.1rem; justify-content: stretch; justify-items: stretch; }
    .ot-summary-dialog .otr-summary-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: baseline; gap: 1rem; min-width: 0; padding: .38rem .2rem; border-right: 0; border-bottom: 1px dashed var(--line-light); }
    .ot-summary-dialog .otr-summary-item:last-child { border-bottom: 0; }
    .ot-summary-dialog .otr-summary-item span { font-size: .78rem; }
    .ot-summary-dialog .otr-summary-item strong { margin-top: 0; font-size: .95rem; text-align: right; }
    .otr-source { display: flex; align-items: center; gap: .5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--line-light); color: var(--muted-light); font-size: .7rem; }
    .otr-source-dot { width: .5rem; height: .5rem; border-radius: 50%; background: var(--success); box-shadow: 0 0 0 .22rem color-mix(in srgb, var(--success) 14%, transparent); }
    .otr-source.is-loading .otr-source-dot { animation: otr-pulse 1s ease-in-out infinite; }

    /* 3 ช่อง: ช่องเลือกวันที่ซ้าย · ป้ายกะกลางจอ · ช่องว่างขวาไว้ถ่วงให้กลางจริง */
    /* 4 ช่อง: ปุ่มกลับ · ปฏิทิน · ป้ายกะกลางจอ · ช่องว่างขวาถ่วงให้กลางจริง
       ปุ่มกลับกว้าง auto และซ่อนได้ ตอนซ่อนช่องแรกจะยุบเอง ป้ายกะจึงยังอยู่กลาง */
    .otr-date-panel { display: grid; grid-template-columns: auto 1fr auto 1fr; align-items: center; gap: .65rem; margin-bottom: 1rem; }
    .otr-date-panel > .otr-date-picker { justify-self: start; }
    .otr-back {
      display: inline-flex; align-items: center; gap: .3rem; flex: 0 0 auto;
      min-height: 2.4rem; padding: .4rem .8rem .4rem .6rem;
      border: 1px solid var(--line-strong); border-radius: 4px;
      background: var(--panel); color: var(--light-text);
      font-family: inherit; font-size: .74rem; font-weight: 700; cursor: var(--cursor-action);
    }
    .otr-back:hover { border-color: var(--moss); color: var(--moss); }
    .otr-back svg { width: 1rem; height: 1rem; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .otr-back[hidden] { display: none; }
    .otr-company-list[hidden] { display: none; }
    /* ป้ายกะใช้ .ot-my-shift จาก partials/shift-filter ร่วมกับหน้าขอลา */
    @media (max-width: 40rem) {
      .otr-date-panel { grid-template-columns: 1fr; justify-items: center; }
      .otr-date-panel > .otr-date-picker { justify-self: stretch; }
      .otr-date-spacer { display: none; }
    }
    .otr-date-picker { position: relative; display: flex; align-items: center; gap: .45rem; flex: 0 0 auto; color: var(--muted-light); }
    .otr-date-picker > span:not(.sr-only) { font-size: .74rem; font-weight: 600; }
    .otr-date-picker svg { width: 1.05rem; fill: none; stroke: currentColor; stroke-width: 1.7; }
    .otr-date-picker input { width: 8.9rem; padding: .45rem .55rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel-soft); color: var(--light-text); color-scheme: light dark; }
    .otr-date-picker input::-webkit-inner-spin-button,
    .otr-date-picker input::-webkit-outer-spin-button { -webkit-appearance: none; appearance: none; margin: 0; }

    .otr-company-list { display: grid; gap: .7rem; }
    .otr-company { border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel); overflow: clip; }
    /* แถวบริษัทใช้พาสเทลชุดเดียวกับหน้าภาพรวม เพื่อไล่ชั้น บริษัท → แผนก ให้เหมือนกันทุกหน้า */
    .otr-company > summary {
      display: grid; grid-template-columns: minmax(0, 1fr) auto auto; align-items: center;
      gap: 1rem; padding: .9rem 1rem; background: var(--ot-tint-company);
      list-style: none; cursor: pointer; transition: background .16s ease;
    }
    .otr-company > summary:hover { background: color-mix(in srgb, var(--moss) 12%, var(--panel)); }
    .otr-company > summary::-webkit-details-marker { display: none; }
    .otr-company-name strong { display: block; font-size: .92rem; }
    .otr-company-name small { color: var(--muted-light); font-size: .68rem; }
    .otr-company-meta { display: flex; gap: .8rem; color: var(--muted-light); font-size: .7rem; }
    .otr-company-meta b { color: var(--light-text); font-size: .82rem; }
    .otr-chevron { width: 1rem; fill: none; stroke: var(--muted-light); stroke-width: 1.8; transition: transform .18s ease; }
    .otr-company[open] .otr-chevron { transform: rotate(180deg); }
    .otr-company-body { padding: .75rem; border-top: 1px solid var(--line-light); }
    .otr-company-error { margin-bottom: .65rem; padding: .65rem .75rem; border: 1px solid color-mix(in srgb, var(--danger) 34%, transparent); border-radius: 4px; color: var(--danger); font-size: .76rem; }
    .otr-departments { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .55rem; }
    /* ใช้โทนพาสเทลชุดเดียวกับการ์ดแผนกหน้าภาพรวม (token กลางจาก layouts/portal) */
    .otr-department {
      min-height: 7rem; display: grid; gap: .5rem; align-content: start;
      padding: .85rem; border: 1px solid var(--line-light); border-radius: 5px;
      background: var(--ot-tint-card); color: var(--light-text); text-align: left;
      transition: transform .16s ease, border-color .16s ease, background .16s ease;
    }
    .otr-department:hover, .otr-department.is-active {
      border-color: color-mix(in srgb, var(--moss) 45%, var(--line-light));
      background: var(--ot-tint-card-hover);
    }
    .otr-department:hover { transform: translateY(-2px); }
    /* ชิดซ้ายเหมือนการ์ดหน้าภาพรวม — ชื่อแผนกเป็นรหัสอย่าง MFG/PF ไล่สายตาลงคอลัมน์เดียวได้เร็วกว่าจัดกึ่งกลาง */
    .otr-department strong { display: block; overflow: hidden; font-size: .86rem; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
    /* ขีดเน้นใต้ชื่อแผนกให้ตรงกับหน้าภาพรวม */
    .otr-department strong::after {
      content: '';
      display: block;
      width: 70%;
      height: 3px;
      margin: .4rem 0 0;
      border-radius: 999px;
      background: var(--moss);
      opacity: .85;
      transition: width .18s ease;
    }
    .otr-department:hover strong::after, .otr-department.is-active strong::after { width: 100%; }
    @media (prefers-reduced-motion: reduce) {
      .otr-department, .otr-department strong::after { transition: none; }
    }

    /* กระดานตัวเลขชุดเดียวกับการ์ดหน้าภาพรวม ต่างที่คอลัมน์ขวาเป็น "ขอ OT" แทน "ออก"
       คอลัมน์ตัวเลขกว้างคงที่ ไม่ให้ความยาวของเลขดันคอลัมน์เหลื่อมกันระหว่างการ์ด */
    .otr-board { display: grid; gap: .12rem; }
    .otr-board-head,
    .otr-board-row { display: grid; grid-template-columns: minmax(0, 1fr) 3.5rem 3.5rem; align-items: center; gap: .3rem; }
    .otr-board-head { padding: 0 .1rem .18rem; color: var(--muted-light); font-size: .61rem; font-weight: 700; letter-spacing: .03em; }
    .otr-board-head span + span { text-align: right; }
    .otr-board-row.is-total { padding: .1rem .1rem .38rem; border-bottom: 1px solid var(--line-light); margin-bottom: .18rem; }
    .otr-board-row.is-total .otr-board-label { color: var(--muted-light); font-size: .68rem; font-weight: 650; }
    .otr-board-row.is-total .otr-board-metric b { font-size: 1rem; }
    .otr-board-row.is-morning { --board-tone: #e8830c; }
    .otr-board-row.is-night { --board-tone: #6d28d9; }

    .otr-board-label { display: inline-flex; align-items: center; gap: .3rem; min-width: 0; overflow: hidden; font-size: .72rem; text-overflow: ellipsis; white-space: nowrap; }
    .otr-board-label svg { width: .85rem; height: .85rem; flex: 0 0 auto; color: var(--board-tone, var(--muted-light)); fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; }
    .otr-board-metric { display: inline-flex; align-items: baseline; justify-content: flex-end; gap: .04rem; font-variant-numeric: tabular-nums; }
    .otr-board-metric b { font-size: .84rem; font-weight: 700; line-height: 1.1; }
    .otr-board-metric i { color: var(--muted-light); font-size: .68rem; font-style: normal; }

    .otr-paper { margin-top: 1rem; border: 1px solid var(--line-strong); border-radius: 4px; background: var(--panel); box-shadow: 0 .55rem 1.6rem rgb(0 0 0 / 6%); overflow: clip; }
    .otr-paper[hidden] { display: none; }
    .otr-paper-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--line-strong); }
    .otr-paper-title small { color: var(--muted-light); font-size: .65rem; }
    .otr-paper-title h2 { margin-top: .12rem; font-size: 1rem; font-weight: 650; }
    .otr-paper-meta { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: .5rem; color: var(--muted-light); font-size: .68rem; }
    .otr-paper-meta .ot-shift-filter { flex: 0 0 auto; }
    /* ช่องเลือกวันในหัวโมดัล — ทรงเดียวกับตัวกรองกะและช่องค้นหา (ชุดเดียวกับหน้าภาพรวม)
       CSS ของหน้าอยู่ในไฟล์ใครไฟล์มัน จึงต้องประกาศซ้ำที่นี่ ไม่ได้สืบทอดจากหน้าภาพรวม */
    .ot-modal-date {
      min-height: 2.45rem; display: inline-flex; align-items: center; gap: .45rem; flex: 0 0 auto;
      padding: 0 .7rem; border: 1px solid var(--line-strong); border-radius: 4px; background: var(--panel);
    }
    .ot-modal-date:focus-within { border-color: var(--moss); }
    .ot-modal-date svg { width: 1rem; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.8; opacity: .7; }
    .ot-modal-date input {
      border: 0; outline: 0; background: transparent; color: var(--light-text);
      font: inherit; font-size: .74rem; font-weight: 650; color-scheme: light dark;
    }
    /* ตัวเรียงลำดับใช้โครงเดียวกับตัวกรองกะ/สาขา จะได้สูงเท่ากันทั้งแถว (ชุดเดียวกับหน้าภาพรวม) */
    .ot-sort-filter { position: relative; display: inline-flex; align-items: center; flex: 0 0 auto; }
    .ot-sort-filter svg { position: absolute; left: .55rem; width: .95rem; height: .95rem; color: var(--muted-light); fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; pointer-events: none; }
    .ot-sort-filter select { min-height: 2.45rem; padding: .4rem 1.7rem .4rem 1.95rem; border: 1px solid var(--line-strong); border-radius: 4px; background: var(--panel); color: var(--light-text); font-family: inherit; font-size: .74rem; font-weight: 650; cursor: var(--cursor-action); appearance: none; }
    .ot-sort-filter::after { content: ''; position: absolute; right: .6rem; width: .4rem; height: .4rem; border-right: 1.6px solid var(--muted-light); border-bottom: 1.6px solid var(--muted-light); transform: translateY(-.12rem) rotate(45deg); pointer-events: none; }
    .otr-bulk-open { min-height: 2.7rem; display: inline-flex; align-items: center; gap: .4rem; white-space: nowrap; }
    .otr-bulk-open[hidden] { display: none; }
    .otr-bulk-open svg { width: 1rem; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.8; }

    /* ── โมดัลขอ OT ทั้งกะ ───────────────────────────────────────── */
    /* ขั้นกรอกฟอร์มใช้ขนาดปกติ · ขั้นรายชื่อค่อยกางเกือบเต็มจอ เพราะมี 9 คอลัมน์ */
    .otr-bulk-dialog { width: min(100%, 46rem); max-height: 94vh; transition: width .22s ease; }
    .otr-bulk-dialog.is-wide { width: min(100%, 112rem); }
    @media (prefers-reduced-motion: reduce) { .otr-bulk-dialog { transition: none; } }
    .otr-bulk-head { align-items: center; gap: 1.25rem; }
    .otr-bulk-head .otr-modal-head-copy { flex: 0 0 auto; }
    .otr-bulk-meta { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; flex: 1 1 auto; gap: .4rem; }
    .otr-chip {
      display: inline-flex; align-items: center; gap: .25rem;
      padding: .3rem .65rem; border: 1px solid var(--line-light); border-radius: 999px;
      background: var(--panel-soft); color: var(--muted-light);
      font-size: .73rem; font-weight: 650; line-height: 1.3; white-space: nowrap;
    }
    .otr-chip.is-shift { border-color: var(--shift-tone, var(--line-light)); color: var(--light-text); background: color-mix(in srgb, var(--shift-tone, transparent) 14%, var(--panel-soft)); }
    .otr-chip.is-count { border-color: color-mix(in srgb, var(--moss) 45%, transparent); background: color-mix(in srgb, var(--moss) 12%, var(--panel-soft)); color: var(--light-text); }
    .otr-chip.is-count b { font-size: .84rem; font-weight: 800; }
    /* ชิปกะรับสีจากธีมกะที่หัวโมดัลตั้งไว้ (--shift-tone) */
    .otr-bulk-head.is-shift-morning { --shift-tone: #e8830c; }
    .otr-bulk-head.is-shift-night { --shift-tone: #6d28d9; }

    .otr-time-block.is-muted { opacity: .45; }
    /* อยู่นอก .otr-field เพราะกฎ .otr-field input จะยืดช่องติ๊กเป็นเต็มบรรทัดสูง 2.55rem */
    .otr-bulk-auto {
      grid-column: 1 / -1;
      display: flex;
      align-items: center;
      gap: .6rem;
      min-height: 2.75rem;
      padding: .5rem .8rem;
      border: 1px solid var(--line-light);
      border-radius: 4px;
      background: var(--panel-soft);
      cursor: pointer;
      transition: border-color .16s ease, background-color .16s ease;
    }
    .otr-bulk-auto:hover { border-color: var(--line-strong); }
    .otr-bulk-auto:has(input:checked) {
      border-color: color-mix(in srgb, var(--moss) 55%, transparent);
      background: color-mix(in srgb, var(--moss) 8%, var(--panel-soft));
    }
    .otr-bulk-auto:focus-within { outline: 2px solid color-mix(in srgb, var(--moss) 45%, transparent); outline-offset: 1px; }
    .otr-bulk-auto input {
      width: 1.15rem;
      height: 1.15rem;
      min-height: 0;
      flex: 0 0 auto;
      margin: 0;
      padding: 0;
      accent-color: var(--moss);
      cursor: pointer;
    }
    .otr-bulk-auto span { color: var(--light-text); font-size: .8rem; font-weight: 650; line-height: 1.35; }
    @media (prefers-reduced-motion: reduce) { .otr-bulk-auto { transition: none; } }
    @media (max-width: 60rem) {
      .otr-bulk-head { flex-wrap: wrap; }
      .otr-bulk-meta { justify-content: flex-start; gap: .4rem 1rem; }
    }
    .otr-bulk-step[hidden] { display: none; }
    /* ตัวกรองเกาะกลุ่มชิดซ้าย ตัวนับไปชิดขวาด้วย margin-left: auto
       ห้ามใช้ space-between เพราะพอมีตัวกรอง 5-6 ตัวจะกระจายห่างกันคนละมุมจนอ่านไม่เป็นชุดเดียว
       (กติกาเดียวกับแถบตัวกรองของหน้าภาพรวม) */
    .otr-bulk-toolbar { display: flex; align-items: center; justify-content: flex-start; gap: .5rem; flex-wrap: wrap; padding: .7rem 1.1rem; border-bottom: 1px solid var(--line-light); }
    .otr-bulk-toolbar .otr-bulk-count { margin-left: auto; }
    /* ช่องค้นหาในโมดัลหดได้ ไม่ให้ดันตัวกรองที่เหลือตกบรรทัด */
    .otr-bulk-toolbar .otr-bulk-search { flex: 0 1 14rem; }
    .otr-bulk-all { display: inline-flex; align-items: center; gap: .45rem; font-size: .78rem; font-weight: 650; }
    .otr-bulk-all input { width: 1.05rem; height: 1.05rem; accent-color: var(--moss); cursor: pointer; }
    /* ช่องค้นหาในโมดัลกินพื้นที่ตรงกลางระหว่าง "เลือกทั้งหมด" กับตัวนับ */
    .otr-bulk-search { position: relative; display: flex; align-items: center; flex: 1 1 12rem; max-width: 20rem; }
    .otr-bulk-search svg { position: absolute; left: .6rem; width: .9rem; height: .9rem; color: var(--muted-light); fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; }
    .otr-bulk-search input {
      width: 100%; min-height: 2.1rem; padding: .35rem .6rem .35rem 1.9rem;
      border: 1px solid var(--line-strong); border-radius: 4px;
      background: var(--panel); color: var(--light-text); font-family: inherit; font-size: .75rem;
    }
    .otr-bulk-search input:focus-visible { outline: 2px solid var(--moss); outline-offset: 1px; }
    .otr-bulk-empty { padding: 1.4rem .8rem; color: var(--muted-light); font-size: .78rem; text-align: center; }
    .otr-bulk-count { color: var(--muted-light); font-size: .74rem; white-space: nowrap; }
    .otr-bulk-count b { color: var(--light-text); font-weight: 800; }
    /* ป้ายเตือนว่ายังมีคนติ๊กไว้แต่ถูกตัวกรองซ่อนอยู่ — ใช้โทนเตือน ไม่ใช่สีเทากลืนไปกับตัวเลข */
    .otr-bulk-hidden { margin-left: .35rem; padding: .05rem .4rem; border-radius: 999px; background: color-mix(in srgb, #e0a800 18%, transparent); color: #8a6200; font-size: .66rem; font-weight: 700; }
    .otr-bulk-hidden[hidden] { display: none; }
    .otr-bulk-scroll { max-height: min(58vh, 34rem); overflow: auto; }
    /* ตรึงผังคอลัมน์ไว้ (ความกว้างเป็น % อยู่ที่ <th> ใน renderBulkTable)
       ไม่งั้นช่องหมายเหตุที่พิมพ์ยาว ๆ จะดันคอลัมน์อื่นให้เบี้ยวไปทั้งตาราง */
    .otr-bulk-table { width: 100%; min-width: 54rem; border-collapse: collapse; table-layout: fixed; }
    .otr-bulk-table th, .otr-bulk-table td { padding: .45rem .5rem; border-bottom: 1px solid var(--line-light); text-align: center; vertical-align: middle; }
    .otr-bulk-table th { position: sticky; top: 0; z-index: 1; background: var(--panel-soft); color: var(--muted-light); font-size: .66rem; font-weight: 700; }
    .otr-bulk-table td { font-size: .74rem; }
    .otr-bulk-table tbody tr.is-off td { opacity: .45; }
    .otr-bulk-table tbody tr.is-blocked td { background: color-mix(in srgb, var(--danger) 5%, transparent); }
    /* ข้อมูลพนักงานอยู่บรรทัดเดียว แถวจะได้เตี้ยเท่ากันทุกแถว */
    .otr-bulk-person-wrap { display: flex; align-items: center; gap: .5rem; min-width: 0; text-align: left; }
    .otr-bulk-person-wrap .otr-avatar { width: 1.95rem; height: 1.95rem; flex: 0 0 auto; }
    .otr-bulk-person-wrap strong { flex: 0 1 auto; overflow: hidden; font-size: .76rem; text-overflow: ellipsis; white-space: nowrap; }
    .otr-bulk-person-wrap small { flex: 0 1 auto; overflow: hidden; color: var(--muted-light); font-size: .66rem; text-overflow: ellipsis; white-space: nowrap; }
    .otr-bulk-blocked {
      flex: 0 0 auto; padding: .05rem .4rem; border-radius: 999px;
      background: color-mix(in srgb, var(--danger) 13%, transparent);
      color: var(--danger); font-size: .64rem; font-weight: 700;
    }
    .otr-bulk-time { display: inline-flex; align-items: center; gap: .15rem; }
    .otr-bulk-table select, .otr-bulk-table input[type="number"], .otr-bulk-table input[type="text"] {
      min-height: 1.95rem; padding: .15rem .3rem; border: 1px solid var(--line-light); border-radius: 4px;
      background: var(--panel); color: var(--light-text); font-size: .73rem; text-align: center;
    }
    .otr-bulk-table input[type="number"] { width: 3.1rem; -moz-appearance: textfield; }
    .otr-bulk-table input[type="number"]::-webkit-outer-spin-button,
    .otr-bulk-table input[type="number"]::-webkit-inner-spin-button { margin: 0; -webkit-appearance: none; }
    /* ปลด min-width ของช่องหมายเหตุ ไม่งั้นมันจะดันคอลัมน์ให้กว้างเกินสัดส่วนที่ตั้งไว้ */
    .otr-bulk-table input[type="text"] { width: 100%; min-width: 0; text-align: left; }
    /* เวลาสแกนอ่านเป็นตัวเลขเรียงหลัก ไม่ใช่ข้อความธรรมดา */
    .otr-bulk-table td { font-variant-numeric: tabular-nums; }
    .otr-bulk-time select { min-width: 3.1rem; padding: .15rem .1rem; }
    .otr-bulk-pick { width: 1.05rem; height: 1.05rem; accent-color: var(--moss); cursor: pointer; }
    .otr-paper-meta b { color: var(--light-text); font-weight: 600; }
    .otr-paper-scroll { overflow: auto; }
    /* ── ผังคอลัมน์: ต้องอ่านจบในจอเดียว ไม่ต้องเลื่อนซ้าย-ขวา (Manager สั่ง 2026-08-21) ──
       ของเดิมตรึงเป็น rem รวมกัน 113rem จึงล้นจอเสมอ · เปลี่ยนมาเป็น % ทั้งหมด
       ตารางจึงหดตามความกว้างจริงของพื้นที่เนื้อหา แล้วให้ข้อความยาวตัดบรรทัด/ใส่ … แทน
       เหลือ min-width ไว้เท่าที่จอมือถือยังอ่านออก (ต่ำกว่านั้นค่อยเลื่อน) */
    .otr-table { width: 100%; min-width: 58rem; border-collapse: collapse; table-layout: fixed; }
    .otr-table th, .otr-table td { padding: .55rem .4rem; border-right: 1px solid var(--line-light); border-bottom: 1px solid var(--line-light); vertical-align: middle; text-align: center; }
    /* หัวคอลัมน์ยาว (เช่น `ผลจากลักษณะการรูดบัตร`) ต้องขึ้นบรรทัดใหม่ในช่องตัวเอง
       ห้ามดันความกว้างคอลัมน์จนตารางล้นจอ */
    .otr-table th { line-height: 1.3; overflow-wrap: anywhere; }
    .otr-table th:last-child, .otr-table td:last-child { border-right: 0; }
    .otr-table tbody tr:last-child td { border-bottom: 0; }
    /* คอลัมน์พนักงานชิดซ้าย — อ้างด้วยคลาสไม่ใช่ nth-child เพราะพอแทรกคอลัมน์ `ลำดับ`
       เข้ามาเป็นช่องที่ 2 กฎเดิมไปจับเลขลำดับให้ชิดซ้ายแทนชื่อพนักงาน */
    .otr-table tbody td.otr-person-cell { text-align: left; }
    .otr-table th { position: sticky; z-index: 2; top: 0; background: var(--panel-soft); color: var(--muted-light); font-size: .65rem; font-weight: 700; }
    .otr-table td { font-size: .76rem; }
    .otr-table .is-center { text-align: center; }
    /* สัดส่วนคอลัมน์รวมกัน = 100% พอดี (12 คอลัมน์ · ไม่มีคอลัมน์ช่องติ๊กแล้ว)
       แก้ตรงนี้ที่เดียวเมื่อจะปรับผัง และผลรวมต้องเป็น 100 เสมอ */
    .otr-order-col { width: 3.5%; }
    .otr-person-col { width: 15%; }
    .otr-position-col { width: 11%; }
    .otr-shift-col { width: 8%; }
    .otr-time-col { width: 5.5%; }
    .otr-presence-col { width: 6%; }
    .otr-request-col { width: 11%; }
    .otr-type-col { width: 11.5%; }
    .otr-status-col { width: 9%; }
    .otr-reason-col { width: 5%; }
    .otr-actor-col { width: 9%; }
    /* ── 3 คอลัมน์ `กะ` + `เข้างาน` + `เวลาล่าสุด/ออกงาน` เป็นบล็อกสีเดียวกัน ──────────
       Manager สั่ง 2026-08-21 ให้ merge เป็นสีส้ม/ม่วงตามกะของแถวนั้น
       (เดิมกะเป็นส้ม/ม่วง แต่ 2 ช่องเวลาเป็นเหลือง จึงดูเป็นคนละก้อน)
       สีมาจาก "กะของแถวนั้น" ไม่ใช่จากกะที่กำลังเลือกในตัวกรอง
       แถวที่ไม่รู้กะ (ไม่ใช่ A/B) ปล่อยว่างไว้ เพราะไม่มีสีกะให้บอก */
    .otr-table td.is-shift-morning { background: color-mix(in srgb, #e8830c 30%, transparent); }
    .otr-table td.is-shift-night { background: color-mix(in srgb, #6d28d9 26%, transparent); }
    /* merge แค่ "สี" เท่านั้น — เส้นคั่นระหว่างคอลัมน์ยังต้องอยู่ครบ (Manager ย้ำ 2026-08-21)
       ห้ามซ่อน border-right ของ 3 ช่องนี้ให้กลายเป็นช่องเดียว */
    /* คอลัมน์ `การทำงาน` — ข้อความล้วนไม่ระบายพื้น ชุดเดียวกับหน้าภาพรวม OT
       วันหยุดจางไว้เพราะไม่ใช่วันทำงาน จึงไม่ควรเด่นเท่าสองค่าแรก */
    .otr-presence { font-size: .74rem; font-weight: 650; }
    .otr-presence.is-present { color: #146032; }
    .otr-presence.is-absent { color: #9f2f26; }
    .otr-presence.is-dayoff { color: var(--muted-light); font-weight: 500; }
    .otr-order { color: var(--muted-light); font-size: .74rem; font-variant-numeric: tabular-nums; }
    /* ปุ่มขอกับปุ่มยกเลิกอยู่คู่กันในคอลัมน์เดียว ขึ้นบรรทัดใหม่เองเมื่อคอลัมน์แคบ */
    .otr-row-actions { display: inline-flex; align-items: center; flex-wrap: wrap; gap: .3rem; }
    .otr-row-cancel {
      min-height: 1.8rem; padding: .25rem .55rem; border: 1px solid #da5a4e; border-radius: 4px;
      background: transparent; color: #da5a4e; font-family: inherit; font-size: .69rem; font-weight: 700; cursor: pointer;
    }
    .otr-row-cancel:hover { background: color-mix(in srgb, #da5a4e 12%, transparent); }
    /* ย่อรูปกับช่องไฟลง เพื่อให้ชื่อ-รหัสมีที่พอในคอลัมน์ที่แคบลงหลังจัดผังใหม่ */
    .otr-person { display: grid; grid-template-columns: 2.1rem minmax(0, 1fr); align-items: center; gap: .45rem; }
    .otr-avatar { width: 2.1rem; height: 2.1rem; display: grid; place-items: center; border: 1px solid var(--line-light); border-radius: 50%; background: var(--hover-soft); color: var(--muted-light); object-fit: cover; overflow: hidden; }
    .otr-person-copy { min-width: 0; text-align: left; }
    .otr-person-copy strong { display: block; overflow: hidden; font-size: .8rem; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
    .otr-person-copy small { color: var(--muted-light); font-size: .66rem; }
    .otr-ellipsis { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .otr-table td.otr-shift-cell { padding: .55rem .35rem; }
    /* ช่วงเวลากะ (เช่น 08:00–17:00) ต้องอยู่บรรทัดเดียว จึงย่อขนาดตัวอักษรแทนการขยายคอลัมน์ */
    .otr-shift { display: inline-block; font-size: .74rem; font-weight: 600; line-height: normal; letter-spacing: normal; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .otr-request-copy strong { display: block; font-size: .76rem; font-weight: 650; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .otr-request-copy small { display: block; color: var(--muted-light); font-size: .64rem; white-space: nowrap; }
    .otr-type-copy { display: block; line-height: 1.45; overflow-wrap: anywhere; text-align: center; }
    /* ช่วงเวลาที่ขอ อยู่ใต้ชื่อประเภท OT ในเซลล์เดียวกัน */
    .otr-type-label { display: block; }
    .otr-type-time { display: block; margin-top: .12rem; color: var(--muted-light); font-size: .68rem; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .otr-table .otr-status { display: inline-flex; flex-direction: column; align-items: center; gap: .18rem; }
    .otr-table .otr-status-detail { text-align: center; }
    .otr-request-button {
      min-height: 2.05rem;
      padding: .35rem .6rem;
      border: 1px solid var(--moss);
      border-radius: 4px;
      background: transparent;
      color: var(--moss);
      font-size: .69rem;
      font-weight: 700;
      white-space: nowrap;
    }
    .otr-request-button:hover { background: color-mix(in srgb, var(--moss) 10%, transparent); }
    .otr-request-button:disabled { opacity: .45; cursor: not-allowed; }

    /* ── กดรูปพนักงานเพื่อดูแบบขยาย ─────────────────────── */
    img.otr-avatar { cursor: zoom-in; transition: transform .18s ease; }
    img.otr-avatar:hover { transform: scale(1.08); }

    .otr-lightbox {
      position: fixed;
      z-index: 130;                 /* ต้องสูงกว่า .otr-modal (100) */
      inset: 0;
      display: grid;
      place-items: center;
      padding: clamp(1rem, 4vw, 3rem);
      background: rgb(0 0 0 / 78%);
      -webkit-backdrop-filter: blur(3px);
      backdrop-filter: blur(3px);
      cursor: zoom-out;
      animation: otr-lightbox-in .16s ease;
    }
    .otr-lightbox[hidden] { display: none; }

    @keyframes otr-lightbox-in {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .otr-lightbox-figure {
      max-width: min(100%, 34rem);
      display: grid;
      gap: .75rem;
      justify-items: center;
      margin: 0;
      cursor: default;
      animation: otr-lightbox-zoom .2s ease;
    }

    @keyframes otr-lightbox-zoom {
      from { opacity: 0; transform: scale(.94); }
      to { opacity: 1; transform: none; }
    }

    .otr-lightbox-figure img {
      max-width: 100%;
      max-height: min(72vh, 34rem);
      border-radius: 6px;
      background: var(--panel);
      box-shadow: 0 2rem 4rem rgb(0 0 0 / 45%);
      object-fit: contain;
    }

    .otr-lightbox-caption { display: grid; gap: .1rem; color: #fff; font-size: .9rem; text-align: center; }
    .otr-lightbox-caption small { color: rgb(255 255 255 / 68%); font-size: .78rem; }

    .otr-lightbox-close {
      position: absolute;
      top: 1rem;
      right: 1rem;
      width: 2.6rem;
      height: 2.6rem;
      display: grid;
      place-items: center;
      border: 1px solid rgb(255 255 255 / 28%);
      border-radius: 50%;
      background: rgb(255 255 255 / 10%);
      color: #fff;
      cursor: pointer;
    }
    .otr-lightbox-close:hover { background: rgb(255 255 255 / 20%); }
    .otr-lightbox-close svg { width: 1.1rem; fill: none; stroke: currentColor; stroke-width: 2; }

    /* ป้ายสถานะ = แท็กวงรี ความกว้างคงที่เท่ากันทุกสถานะ ตัวอักษรกึ่งกลาง
       6.6rem เผื่อคำยาวสุดของทั้ง 3 ภาษา (สเปกเดียวกันทั้ง 3 หน้า OT) */
    .otr-status-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 6.6rem;
      max-width: 100%;
      height: 1.5rem;
      flex: 0 0 auto;
      padding: 0 .4rem;
      border: 1px solid transparent;
      border-radius: 999px;
      background: transparent;
      font-size: .64rem;
      font-weight: 650;
      line-height: 1;
      overflow: hidden;
      text-align: center;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .otr-status-detail { display: block; min-width: 0; color: var(--light-text); font-size: .62rem; font-weight: 500; line-height: 1.3; }
    .otr-status[data-tone="warning"] .otr-status-badge {
      border-color: var(--otr-status-warning-border);
      background: var(--otr-status-warning-border);
      color: #fff;
    }
    .otr-status[data-tone="success"] .otr-status-badge {
      border-color: var(--otr-status-success-border);
      background: var(--otr-status-success-border);
      color: #fff;
    }
    .otr-status[data-tone="danger"] .otr-status-badge {
      border-color: var(--otr-status-danger-border);
      background: var(--otr-status-danger-border);
      color: #fff;
    }
    .otr-status[data-tone="neutral"] .otr-status-badge {
      width: auto;
      height: auto;
      display: inline;
      padding: 0;
      border: 0;
      border-radius: 0;
      background: transparent;
      color: var(--muted-light);
      font-weight: 500;
      overflow: visible;
      text-overflow: clip;
    }
    .otr-reason-button { min-height: 2rem; padding: .36rem .4rem; border: 1px solid var(--line-strong); border-radius: 4px; background: transparent; color: var(--light-text); font-size: .66rem; font-weight: 700; white-space: nowrap; }
    .otr-reason-button:hover { border-color: var(--moss); color: var(--moss); }
    .otr-loading, .otr-empty { min-height: 13rem; display: grid; place-items: center; padding: 2rem; color: var(--muted-light); font-size: .8rem; text-align: center; }
    .otr-spinner { width: 1.15rem; height: 1.15rem; margin: 0 auto .55rem; border: 2px solid var(--line-light); border-top-color: var(--moss); border-radius: 50%; animation: otr-spin .7s linear infinite; }

    /* ปุ่มหลักพื้นเขียว ใช้ตัวอักษรสีขาวเสมอทั้งธีมสว่างและมืด */
    .otr-primary { min-height: 2.45rem; padding: .55rem .85rem; border: 1px solid var(--moss); border-radius: 4px; background: var(--moss); color: #fff; font-size: .74rem; font-weight: 750; }
    .otr-primary:disabled { cursor: not-allowed; opacity: .45; }
    /* ปุ่ม `ส่งอนุมัติ` ในโมดัล `ขอ OT ทั้งหมด` เป็นเขียว #35a863 ชุดเดียวกับปุ่มอนุมัติของหน้าอนุมัติ
       (Manager สั่ง 2026-08-24) — เจาะจงเฉพาะปุ่มส่ง ไม่ให้กระทบปุ่ม `ไปต่อ`/`ขอ OT ทั้งหมด` ที่ใช้ .otr-primary ร่วมกัน */
    .otr-primary[data-bulk-submit] { border-color: #35a863; background: #35a863; }
    .otr-primary[data-bulk-submit]:hover:not(:disabled) { background: color-mix(in srgb, #35a863 86%, #000); }

    /* ── กล่องยืนยันก่อนแก้คำขอที่ส่งไปแล้ว ───────────────────────────── */
    .otr-confirm { z-index: 110; }              /* เหนือ .otr-modal (100) เพราะเปิดก่อนฟอร์ม */
    .otr-confirm-dialog { width: min(100%, 30rem); }
    .otr-confirm-body { padding: 1.6rem 1.4rem .4rem; text-align: center; }
    .otr-confirm-mark {
      width: 3.2rem; height: 3.2rem; margin: 0 auto .7rem;
      display: grid; place-items: center; border-radius: 50%;
      background: color-mix(in srgb, var(--otr-status-warning-border) 16%, transparent);
      color: var(--otr-status-warning);
    }
    .otr-confirm-mark svg { width: 1.5rem; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .otr-confirm-body h2 { font-size: 1rem; font-weight: 700; }
    .otr-confirm-body p { margin-top: .5rem; color: var(--muted-light); font-size: .78rem; line-height: 1.7; }
    .otr-confirm-mark.is-send { border-color: color-mix(in srgb, var(--moss) 45%, transparent); color: var(--moss); }
    /* ช่องเหตุผลอยู่ในกล่องยืนยัน จึงชิดซ้ายสวนทางกับข้อความที่จัดกึ่งกลาง */
    .otr-confirm-field { display: block; margin-top: .9rem; text-align: left; }
    .otr-confirm-field span { display: block; margin-bottom: .3rem; color: var(--muted-light); font-size: .7rem; font-weight: 650; }
    .otr-confirm-field select,
    .otr-confirm-field textarea {
      width: 100%; padding: .55rem .7rem; border: 1px solid var(--line-strong); border-radius: 4px;
      background: var(--panel); color: var(--light-text); font-family: inherit; font-size: .78rem; line-height: 1.6; resize: vertical;
    }
    .otr-confirm-field select { min-height: 2.35rem; }
    .otr-confirm-field textarea { margin-top: .5rem; }
    .otr-confirm-field select:focus-visible,
    .otr-confirm-field textarea:focus-visible { outline: 2px solid var(--moss); outline-offset: 1px; }
    .otr-confirm-error { margin-top: .5rem; color: #da5a4e; font-size: .72rem; text-align: left; }
    .otr-confirm-error[hidden] { display: none; }
    .otr-danger {
      min-height: 2.45rem; padding: .55rem .85rem; border: 1px solid #da5a4e; border-radius: 4px;
      background: #da5a4e; color: #fff; font-size: .74rem; font-weight: 750; cursor: pointer;
    }
    .otr-danger:disabled { cursor: not-allowed; opacity: .45; }
    .otr-confirm-actions { display: flex; justify-content: center; gap: .6rem; padding: 1.2rem 1.4rem 1.4rem; }
    .otr-confirm-actions button { min-width: 8rem; }
    @media (max-width: 30rem) {
      .otr-confirm-actions { flex-direction: column-reverse; }
      .otr-confirm-actions button { width: 100%; }
    }
    .otr-secondary { min-height: 2.45rem; padding: .55rem .85rem; border: 1px solid var(--line-strong); border-radius: 4px; background: transparent; color: var(--light-text); font-size: .74rem; font-weight: 650; }
    .otr-notice { margin-bottom: .75rem; padding: .7rem .8rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel-soft); color: var(--light-text); font-size: .76rem; }
    .otr-notice[hidden] { display: none; }
    .otr-notice.is-error { border-color: color-mix(in srgb, var(--danger) 40%, transparent); color: var(--danger); }

    .otr-modal { position: fixed; z-index: 100; inset: 0; display: grid; place-items: center; padding: 1rem; background: var(--overlay-bg); backdrop-filter: blur(4px); }
    .otr-modal[hidden] { display: none; }
    .otr-dialog { width: min(100%, 44rem); max-height: 92vh; display: flex; flex-direction: column; border: 1px solid var(--line-strong); border-radius: 4px; background: var(--menu-bg); box-shadow: 0 1.5rem 4rem rgb(0 0 0 / 32%); overflow: hidden; }
    .otr-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--line-light); }
    .otr-modal-head small { color: var(--moss); font-size: .65rem; font-weight: 800; }
    .otr-modal-head h2 { margin-top: .12rem; font-size: 1rem; font-weight: 650; }
    .otr-modal-head p { margin-top: .15rem; color: var(--muted-light); font-size: .68rem; }
    .otr-modal-head-main { display: flex; align-items: center; gap: .9rem; min-width: 0; }
    .otr-modal-head-copy { min-width: 0; }
    .otr-modal-photo { display: grid; place-items: center; flex: 0 0 auto; }
    .otr-modal-photo .otr-avatar { width: 3.6rem; height: 3.6rem; font-size: 1.05rem; font-weight: 650; }
    .otr-close { width: 2.4rem; height: 2.4rem; display: grid; place-items: center; border: 1px solid var(--line-light); border-radius: 50%; background: transparent; color: var(--muted-light); font-size: 1.1rem; }
    .otr-form { overflow: auto; }
    .otr-form-body { display: grid; gap: .9rem; padding: 1rem 1.1rem; }
    .otr-revision-note {
      margin-bottom: .7rem; padding: .6rem .75rem; border-radius: 4px;
      border: 1px solid color-mix(in srgb, #d69a12 45%, transparent);
      background: color-mix(in srgb, #d69a12 10%, transparent);
      color: #a97a0d; font-size: .74rem; line-height: 1.5;
    }

    .otr-revision-note[hidden] { display: none; }
    .otr-employee-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .55rem; padding: .75rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel-soft); }
    .otr-employee-summary span { min-width: 0; }
    .otr-employee-summary small { display: block; color: var(--muted-light); font-size: .61rem; }
    .otr-employee-summary strong { display: block; overflow: hidden; margin-top: .08rem; font-size: .73rem; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
    .otr-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
    .otr-field { display: grid; gap: .3rem; }
    .otr-field.is-wide { grid-column: 1 / -1; }
    .otr-field label, .otr-field > span { color: var(--muted-light); font-size: .68rem; font-weight: 650; }
    .otr-field input, .otr-field select, .otr-field textarea { width: 100%; min-height: 2.55rem; padding: .55rem .65rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel); color: var(--light-text); font: inherit; font-size: .78rem; }
    .otr-field textarea { min-height: 5.5rem; resize: vertical; }
    .otr-field input:focus-visible, .otr-field select:focus-visible, .otr-field textarea:focus-visible { outline: 2px solid color-mix(in srgb, var(--moss) 55%, transparent); outline-offset: 1px; }
    .otr-readonly { min-height: 2.55rem; display: flex; align-items: center; padding: .55rem .65rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel-soft); font-size: .8rem; font-weight: 650; font-variant-numeric: tabular-nums; }
    .otr-time-editor { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
    .otr-time-block { display: grid; align-content: start; gap: .3rem; }
    .otr-time-block:last-child { grid-column: 1 / -1; }
    .otr-time-block > label, .otr-time-block > span { color: var(--muted-light); font-size: .68rem; font-weight: 650; }
    .otr-time-parts { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); align-items: center; gap: .35rem; }
    .otr-time-parts select { width: 100%; min-height: 2.55rem; padding: .55rem .65rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel); color: var(--light-text); font: inherit; font-size: .78rem; font-variant-numeric: tabular-nums; }
    .otr-time-parts select:focus-visible { outline: 2px solid color-mix(in srgb, var(--moss) 55%, transparent); outline-offset: 1px; }
    .otr-time-separator { color: var(--muted-light); font-weight: 700; }
    .otr-duration { min-height: 2.55rem; display: grid; grid-template-columns: minmax(2.7rem, auto) auto minmax(2.7rem, auto) auto; align-items: center; gap: .4rem; padding: .42rem .55rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel-soft); }
    /* กรอกเองได้ ระบบเติมค่าตามช่วงเวลาให้ก่อน แล้วหักเวลาพักออกเองตามจริง */
    .otr-duration-input { width: 100%; min-height: 1.85rem; padding: .18rem .3rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel); color: var(--light-text); font-size: .8rem; font-weight: 700; font-variant-numeric: tabular-nums; text-align: center; -moz-appearance: textfield; }
    .otr-duration-input::-webkit-outer-spin-button, .otr-duration-input::-webkit-inner-spin-button { margin: 0; -webkit-appearance: none; }
    .otr-duration-input:focus { border-color: var(--moss); outline: 2px solid color-mix(in srgb, var(--moss) 28%, transparent); outline-offset: 1px; }
    .otr-duration span { color: var(--muted-light); font-size: .68rem; white-space: nowrap; }
    .otr-form-error { padding: .65rem .75rem; border: 1px solid color-mix(in srgb, var(--danger) 40%, transparent); border-radius: 4px; color: var(--danger); font-size: .72rem; }
    .otr-form-error[hidden] { display: none; }
    .otr-form-actions { display: flex; justify-content: flex-end; gap: .55rem; padding: .85rem 1.1rem; border-top: 1px solid var(--line-light); }
    .otr-reason-modal { z-index: 110; }
    .otr-reason-dialog { width: min(100%, 31rem); }
    .otr-reason-body { display: grid; gap: .75rem; padding: 1rem 1.1rem; }
    .otr-reason-item { padding: .75rem; border: 1px solid var(--line-light); border-radius: 4px; background: var(--panel-soft); }
    .otr-reason-item small { display: block; margin-bottom: .3rem; color: var(--muted-light); font-size: .66rem; font-weight: 700; }
    .otr-reason-item p { margin: 0; color: var(--light-text); font-size: .78rem; line-height: 1.65; overflow-wrap: anywhere; white-space: pre-wrap; }
    .otr-success-modal { z-index: 120; }
    .otr-success-dialog { width: min(100%, 22rem); }
    .otr-success-body { display: grid; justify-items: center; gap: .55rem; padding: 1.6rem 1.25rem 1.15rem; text-align: center; }
    .otr-success-icon {
      width: 3.75rem;
      height: 3.75rem;
      display: grid;
      place-items: center;
      border: 2px solid color-mix(in srgb, var(--otr-status-success-border) 72%, transparent);
      border-radius: 50%;
      background: color-mix(in srgb, var(--otr-status-success-border) 12%, transparent);
      color: var(--otr-status-success);
      animation: otr-success-in .2s cubic-bezier(.22, 1, .36, 1);
    }
    .otr-success-icon svg { width: 1.75rem; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 2.4; }
    .otr-success-body h2 { margin: .2rem 0 0; color: var(--light-text); font-size: 1.1rem; font-weight: 700; }
    .otr-success-body p { max-width: 28ch; margin: 0; color: var(--muted-light); font-size: .78rem; line-height: 1.6; }
    .otr-success-dialog .otr-form-actions { justify-content: center; }

    @keyframes otr-spin { to { transform: rotate(360deg); } }
    @keyframes otr-pulse { 50% { opacity: .42; transform: scale(.82); } }
    @keyframes otr-success-in { from { opacity: 0; transform: scale(.82); } to { opacity: 1; transform: none; } }
    @media (max-width: 70rem) { .otr-departments { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 48rem) {
      .otr-head { grid-template-columns: 1fr; align-items: start; }
      .otr-summary { justify-content: flex-start; }
      .otr-date-panel { align-items: stretch; justify-content: flex-start; }
      .otr-date-picker { justify-content: space-between; width: 100%; }
      .otr-date-picker input { width: min(12rem, 70vw); }
      .otr-company > summary { grid-template-columns: minmax(0, 1fr) auto; }
      .otr-company-meta { display: none; }
      .otr-departments { grid-template-columns: 1fr; }
      .otr-paper-head { display: grid; }
      .otr-paper-meta { justify-content: flex-start; }
      .otr-employee-summary, .otr-fields { grid-template-columns: 1fr 1fr; }
      .otr-time-editor { grid-template-columns: 1fr 1fr; }
      .otr-time-block:last-child { grid-column: 1 / -1; }
      .otr-modal { align-items: end; padding: .4rem; }
      .otr-dialog { max-height: 95vh; border-radius: 4px 10px 6px 6px; }
    }
    @media (max-width: 30rem) {
      .otr-employee-summary, .otr-fields { grid-template-columns: 1fr; }
      .otr-field.is-wide { grid-column: auto; }
      .otr-time-editor { grid-template-columns: 1fr; }
      .otr-time-block:last-child { grid-column: auto; }
      .otr-primary { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
      .otr-chevron { transition: none; }
      .otr-spinner, .otr-source.is-loading .otr-source-dot, .otr-success-icon { animation: none; }
    }
  </style>

  <main
    class="otr-page"
    data-ot-requests
    data-selected-date="{{ $selectedDate->format('Y-m-d') }}"
    data-source-mode="{{ $attendance['source_mode'] ?? 'bplus' }}"
    data-employees-url="{{ route('ot-approval.requests.employees') }}"
    data-store-url="{{ route('ot-approval.requests.store') }}"
    data-submit-url="{{ route('ot-approval.requests.submit') }}"
    data-bulk-url="{{ route('ot-approval.requests.bulk') }}"
    data-cancel-url="{{ route('ot-approval.requests.cancel') }}"
  >
    <header class="otr-head">
      <div class="otr-heading">
        {{-- ไม่มี kicker `OT APPROVAL` แล้ว (Manager สั่ง 2026-08-21) ให้ตรงกับหน้าภาพรวม --}}
        <h1 data-i18n="ot.requests.heading">ขอ OT</h1>
        <p data-i18n="ot.requests.description">เลือกพนักงาน ประเภท และช่วงเวลาที่ทำงานล่วงเวลา</p>
      </div>
      {{-- ปุ่มนาฬิกาเปิดสรุปตัวเลขรวม — ย่อแถบยาวให้เหลือไอคอนเดียว (ต้นแบบหน้าภาพรวม OT) --}}
      <div class="otr-head-side">
        <button type="button" class="ot-summary-open" data-summary-open aria-haspopup="dialog" data-i18n-aria="ot.attendance.summaryOpen" aria-label="ดูสรุปตัวเลขรวม" title="ดูสรุปตัวเลขรวม">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
        </button>

        <div class="ot-summary-modal" data-summary-modal hidden>
          <div class="ot-summary-dialog" role="dialog" aria-modal="true" aria-label="สรุปตัวเลขรวม">
            <header class="ot-summary-dialog-head">
              <strong data-i18n="ot.attendance.summaryTitle">สรุปตัวเลขรวม</strong>
              <button type="button" class="ot-summary-close" data-summary-close aria-label="ปิด">&times;</button>
            </header>
            <section class="otr-summary" aria-label="สรุปรายการ">
              @foreach ([
                ['total', 'ot.attendance.totalEmployees', 'พนักงาน'],
                ['clocked_in', 'ot.attendance.clockedIn', 'เข้างาน'],
                ['ot_requested', 'ot.attendance.otRequested', 'ขอ OT'],
                ['completed', 'ot.requests.completed', 'ผ่าน'],
              ] as [$key, $copyKey, $fallback])
                <div class="otr-summary-item">
                  <span data-i18n="{{ $copyKey }}">{{ $fallback }}</span>
                  <strong>{{ number_format($totals[$key]) }}</strong>
                </div>
              @endforeach
            </section>
          </div>
        </div>
      </div>
    </header>

    <div class="otr-notice" data-page-notice role="status" aria-live="polite" hidden></div>

    <section class="otr-date-panel" aria-label="เลือกวันทำ OT">
      {{-- ปุ่มกลับไปเลือกแผนก — โผล่เฉพาะตอนเปิดเอกสารของแผนกอยู่ (Manager สั่ง 2026-08-24)
           อยู่ซ้ายสุดของแถบ ก่อนปฏิทิน เหมือนหน้าขอลา --}}
      <button class="otr-back" type="button" data-back-to-departments hidden>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"></path></svg>
        <span data-i18n="ot.common.back">กลับ</span>
      </button>
      <label class="otr-date-picker">
        <span data-i18n="ot.attendance.selectDate">เลือกวันที่</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
        <input type="date" value="{{ $selectedDate->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" data-date-picker>
      </label>

      {{-- ป้ายบอกกะที่ Admin กำหนดให้คุณดูแล "ของแผนกที่เปิดอยู่"
           แสดงผลอย่างเดียว ไม่จำกัดสิทธิ์ ยังกดขอ OT ให้กะอื่นได้ --}}
      <span class="ot-my-shift" data-my-shift>
        <small><span data-i18n="ot.requests.myShift">กะที่คุณดูแล</span>:</small>
        {{-- ไอคอนบอกกะด้วยภาพ อ่านเร็วกว่าตัวหนังสือ · ซ่อนไว้จนกว่าจะรู้ว่ากะไหน --}}
        <b data-my-shift-label>-</b>
      </span>
      <span class="otr-date-spacer" aria-hidden="true"></span>
    </section>

    <section class="otr-company-list" data-department-list>
      @forelse ($attendance['companies'] as $company)
        <details class="otr-company">
          <summary>
            <span class="otr-company-name">
              <strong>{{ $company['label'] }}</strong>
              <small>{{ count($company['departments']) }} <span data-i18n="ot.attendance.departments">แผนก</span></small>
            </span>
            <span class="otr-company-meta">
              <span><b>{{ number_format($company['clocked_in']) }}</b> <span data-i18n="ot.attendance.clockedInShort">เข้า</span></span>
              <span><b>{{ number_format($company['ot_requested']) }}</b> <span data-i18n="ot.attendance.otRequested">ขอ OT</span></span>
            </span>
            <svg class="otr-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>
          </summary>
          <div class="otr-company-body">
            @if (! $company['available'])
              <p class="otr-company-error" data-i18n="ot.attendance.bplusUnavailable">ไม่สามารถอ่านข้อมูล Bplus ของบริษัทนี้ได้</p>
            @endif
            <div class="otr-departments">
              @foreach ($company['departments'] as $department)
                <button
                  class="otr-department"
                  type="button"
                  data-open-department
                  data-company-code="{{ $company['code'] }}"
                  data-company-label="{{ $company['label'] }}"
                  data-dept-code="{{ $department['code'] }}"
                  data-dept-name-th="{{ $department['name_th'] }}"
                  data-dept-name-en="{{ $department['name_en'] }}"
                  data-dept-name-my="{{ $department['name_my'] }}"
                >
                  {{-- ชื่อแผนกเป็นรูปแบบ `Accounting (ACC)` เหมือนกันทุกภาษาแล้ว จึงพิมพ์ครั้งเดียว --}}
                  <strong>{{ $department['name_th'] }}</strong>
                  {{-- กระดานตัวเลขชุดเดียวกับหน้าภาพรวม: หัวคอลัมน์บอกครั้งเดียว
                       แล้วไล่ลงเป็นแถว ทั้งแผนก → กะเวลา A → กะเวลา B ตัวเลขจึงตรงคอลัมน์กันทุกแถว --}}
                  @php
                    $shifts = $department['shifts'] ?? [];
                    $blank = ['total' => 0, 'clocked_in' => 0, 'ot_requested' => 0];
                    $morning = ($shifts['morning'] ?? []) + $blank;
                    $night = ($shifts['night'] ?? []) + $blank;
                  @endphp
                  <span class="otr-board">
                    <span class="otr-board-head" aria-hidden="true">
                      <span></span>
                      <span data-i18n="ot.attendance.clockedInShort">เข้า</span>
                      <span data-i18n="ot.attendance.otRequested">ขอ OT</span>
                    </span>
                    <span class="otr-board-row is-total">
                      <span class="otr-board-label" data-i18n="ot.attendance.wholeDepartment">ทั้งแผนก</span>
                      <span class="otr-board-metric"><b>{{ number_format($department['clocked_in']) }}</b><i>/{{ number_format($department['total']) }}</i></span>
                      <span class="otr-board-metric"><b>{{ number_format($department['ot_requested']) }}</b><i>/{{ number_format($department['total']) }}</i></span>
                    </span>
                    @if ($morning['total'] > 0)
                      <span class="otr-board-row is-morning">
                        <span class="otr-board-label">
                          <span data-i18n="ot.settings.shift.morning">กะเวลา A</span>
                        </span>
                        <span class="otr-board-metric"><b>{{ number_format($morning['clocked_in']) }}</b><i>/{{ number_format($morning['total']) }}</i></span>
                        <span class="otr-board-metric"><b>{{ number_format($morning['ot_requested']) }}</b><i>/{{ number_format($morning['total']) }}</i></span>
                      </span>
                    @endif
                    @if ($night['total'] > 0)
                      <span class="otr-board-row is-night">
                        <span class="otr-board-label">
                          <span data-i18n="ot.settings.shift.night">กะเวลา B</span>
                        </span>
                        <span class="otr-board-metric"><b>{{ number_format($night['clocked_in']) }}</b><i>/{{ number_format($night['total']) }}</i></span>
                        <span class="otr-board-metric"><b>{{ number_format($night['ot_requested']) }}</b><i>/{{ number_format($night['total']) }}</i></span>
                      </span>
                    @endif
                  </span>
                </button>
              @endforeach
            </div>
          </div>
        </details>
      @empty
        <div class="otr-empty" data-i18n="ot.attendance.noDepartments">ไม่พบแผนกที่คุณรับผิดชอบ</div>
      @endforelse
    </section>

    <section class="otr-paper" data-request-paper aria-live="polite" hidden>
      <header class="otr-paper-head">
        <div class="otr-paper-title">
          <small data-i18n="ot.requests.paperTitle">เอกสารขออนุมัติทำงานล่วงเวลา</small>
          <h2 data-paper-department>—</h2>
        </div>
        {{-- เหลือแค่ตัวกรองกะกับปุ่มขอ OT ทั้งกะ ส่วนบริษัท/วันที่/ชื่อ Foreman ตัดออก
             เพราะซ้ำกับหัวข้อการ์ดและแถบเลือกวันที่ด้านบนอยู่แล้ว --}}
        <div class="otr-paper-meta">
          {{-- เรียงลำดับตัวกรองให้เหมือนหน้าภาพรวม: ค้นหา · เรียงลำดับ · สาขา · กะ --}}
          @include('ot_approval.partials.employee-search')
          {{-- เรียงตามตำแหน่ง — ใช้ OtPositionRank ชุดเดียวกับหน้าภาพรวม OT --}}
          <label class="ot-sort-filter">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16M7 20l-3-3M7 20l3-3M14 7h6M14 12h5M14 17h4"></path></svg>
            <select data-sort-filter aria-label="เรียงลำดับ">
              <option value="code" data-i18n="ot.attendance.sortCode">ตามรหัสพนักงาน</option>
              <option value="rank" data-i18n="ot.attendance.sortRank">ตำแหน่งสูง → ต่ำ</option>
              <option value="rank_asc" data-i18n="ot.attendance.sortRankAsc">ตำแหน่งต่ำ → สูง</option>
            </select>
          </label>
          @include('ot_approval.partials.branch-filter')
          @include('ot_approval.partials.shift-filter')
          @include('ot_approval.partials.column-filter')
          <button class="otr-primary otr-bulk-open" type="button" data-open-bulk hidden>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"></path><path d="M17 15v6M14 18h6"></path></svg>
            <span data-i18n="ot.requests.bulkOpen">ขอ OT ทั้งหมด</span>
          </button>
        </div>
      </header>
      <div data-paper-content></div>
      {{-- ช่องติ๊กในตารางถูกถอดออกแล้ว (Manager สั่ง 2026-08-21 ว่าไม่ได้ใช้)
           แถบ "ยกเลิกที่เลือก" จึงถูกถอดตามไปด้วย — การยกเลิกใช้ปุ่มในแถวของแต่ละคนแทน --}}
    </section>
  </main>

  <div class="otr-modal" data-request-modal role="dialog" aria-modal="true" aria-labelledby="otrModalTitle" hidden>
    <div class="otr-dialog">
      <header class="otr-modal-head">
        <div class="otr-modal-head-main">
          {{-- รูปพนักงานของคำขอนี้ อยู่ซ้ายสุดของหัวโมดัล กดเพื่อดูแบบขยายได้ --}}
          <span class="otr-modal-photo" data-modal-photo></span>
          <div class="otr-modal-head-copy">
            <small data-i18n="ot.requests.formLabel">แบบคำขอ OT</small>
            <h2 id="otrModalTitle" data-modal-title>—</h2>
            <p data-modal-subtitle>—</p>
          </div>
        </div>
        <button class="otr-close" type="button" data-close-request aria-label="ปิด">×</button>
      </header>
      <form class="otr-form" data-request-form>
        <div class="otr-form-body">
          {{-- เตือนเมื่อกำลังแก้คำขอที่ส่ง/อนุมัติ/ดาวน์โหลดไปแล้ว --}}
          <p class="otr-revision-note" data-modal-revision-note hidden></p>
          <section class="otr-employee-summary">
            <span><small data-i18n="ot.requests.shift">กะ</small><strong data-form-shift>—</strong></span>
            <span><small data-i18n="ot.attendance.clockIn">เข้างาน</small><strong data-form-clock-in>—</strong></span>
            <span><small data-i18n="ot.attendance.clockOut">เวลาล่าสุด / ออกงาน</small><strong data-form-clock-out>—</strong></span>
            <span><small data-i18n="ot.requests.workDate">วันที่ทำ OT</small><strong>{{ $selectedDate->format('d/m/Y') }}</strong></span>
          </section>

          <div class="otr-fields">
            <div class="otr-field is-wide">
              <label for="otrType" data-i18n="ot.requests.otType">ผลจากลักษณะการรูดบัตร</label>
              <select id="otrType" name="ot_type" required data-form-type></select>
            </div>
            <div class="otr-time-editor">
              <div class="otr-time-block">
                <label for="otrStartHour" data-i18n="ot.requests.startTime">ตั้งแต่เวลา</label>
                <div class="otr-time-parts">
                  <select id="otrStartHour" required data-form-start-hour aria-label="ชั่วโมงเริ่มต้น"></select>
                  <span class="otr-time-separator" aria-hidden="true">:</span>
                  <select required data-form-start-minute aria-label="นาทีเริ่มต้น"></select>
                </div>
              </div>
              <div class="otr-time-block">
                <label for="otrEndHour" data-i18n="ot.requests.endTime">ถึงเวลา</label>
                <div class="otr-time-parts">
                  <select id="otrEndHour" required data-form-end-hour aria-label="ชั่วโมงสิ้นสุด"></select>
                  <span class="otr-time-separator" aria-hidden="true">:</span>
                  <select required data-form-end-minute aria-label="นาทีสิ้นสุด"></select>
                </div>
              </div>
              <div class="otr-time-block">
                <label for="otrDurationHours" data-i18n="ot.requests.dailyAmount">จำนวนชั่วโมง OT ที่ขอ</label>
                {{-- ระบบเติมให้ตามช่วงเวลาที่เลือก แต่แก้เองได้ เช่น ลบเวลาพักออกจาก OT เต็มกะ --}}
                <div class="otr-duration">
                  <input
                    id="otrDurationHours"
                    class="otr-duration-input"
                    type="number"
                    min="0"
                    max="24"
                    step="1"
                    inputmode="numeric"
                    required
                    data-form-duration-hours
                    aria-label="จำนวนชั่วโมง"
                  >
                  <span data-i18n="ot.requests.hour">ชั่วโมง</span>
                  <input
                    class="otr-duration-input"
                    type="number"
                    min="0"
                    max="59"
                    step="1"
                    inputmode="numeric"
                    required
                    data-form-duration-minutes
                    aria-label="จำนวนนาที"
                  >
                  <span data-i18n="ot.requests.minute">นาที</span>
                </div>
              </div>
            </div>
            <div class="otr-field is-wide">
              <label for="otrNote" data-i18n="ot.requests.note">หมายเหตุ</label>
              <textarea id="otrNote" name="note" maxlength="1000" data-form-note></textarea>
            </div>
          </div>
          <div class="otr-form-error" data-form-error role="alert" hidden></div>
        </div>
        <footer class="otr-form-actions">
          <button class="otr-secondary" type="button" data-close-request data-i18n="ot.common.cancel">ยกเลิก</button>
          {{-- คีย์ยังชื่อ saveRequest ตามเดิมเพื่อไม่ให้ต้องไล่แก้หลายจุด แต่ข้อความคือ "ส่งอนุมัติ"
               เพราะกดแล้วส่งถึง Supervisor ทันที ไม่ได้ค้างเป็นร่างเหมือนเดิมแล้ว --}}
          <button class="otr-primary" type="submit" data-save-request data-i18n="ot.requests.saveRequest">ส่งอนุมัติ</button>
        </footer>
      </form>
    </div>
  </div>

  {{-- ขอ OT ทั้งกะ 2 ขั้น: ขั้น 1 กรอกค่ากลางครั้งเดียว · ขั้น 2 กระจายลงทุกคนแล้วแก้รายคน
       จบด้วยปุ่มเดียว สร้างและส่งให้ Supervisor พร้อมกัน ไม่มีขั้นบันทึกร่าง --}}
  <div class="otr-modal otr-bulk-modal" data-bulk-modal role="dialog" aria-modal="true" aria-labelledby="otrBulkTitle" hidden>
    <div class="otr-dialog otr-bulk-dialog">
      <header class="otr-modal-head otr-bulk-head">
        <div class="otr-modal-head-copy">
          <small data-i18n="ot.requests.bulkLabel">ขอ OT ทั้งกะ</small>
          <h2 id="otrBulkTitle" data-bulk-title>—</h2>
        </div>
        {{-- ชิปบรรทัดเดียว ไม่ใส่ป้ายกำกับ เพราะค่าพวกนี้อ่านแล้วรู้เองว่าคืออะไร
             ป้ายกำกับ 4 อันทำให้หัวโมดัลรกโดยไม่ได้ข้อมูลเพิ่ม --}}
        <div class="otr-bulk-meta">
          {{-- ถอดชิป "ทุกกะ" ออก (Manager สั่ง 2026-08-24) — ซ้ำกับตัวกรองกะที่อยู่ในแถบเครื่องมือแล้ว
               และชิปนี้บอกค่าตอนเปิดโมดัลเท่านั้น ไม่ขยับตามตัวกรองข้างล่างจนสับสน --}}
          <span class="otr-chip">{{ $selectedDate->format('d/m/Y') }}</span>
          <span class="otr-chip is-count"><b data-bulk-people>—</b> <span data-i18n="ot.attendance.people">คน</span></span>
        </div>
        <button class="otr-close" type="button" data-close-bulk aria-label="ปิด">×</button>
      </header>

      {{-- ขั้นที่ 1 --}}
      <div class="otr-bulk-step" data-bulk-step="1">
        <div class="otr-form-body">
          <div class="otr-fields">
            <div class="otr-field is-wide">
              <label for="otrBulkType" data-i18n="ot.requests.otType">ผลจากลักษณะการรูดบัตร</label>
              <select id="otrBulkType" required data-bulk-type></select>
            </div>
            <div class="otr-time-editor">
              <div class="otr-time-block">
                <label for="otrBulkStartHour" data-i18n="ot.requests.startTime">ตั้งแต่เวลา</label>
                <div class="otr-time-parts">
                  <select id="otrBulkStartHour" required data-bulk-start-hour aria-label="ชั่วโมงเริ่มต้น"></select>
                  <span class="otr-time-separator" aria-hidden="true">:</span>
                  <select required data-bulk-start-minute aria-label="นาทีเริ่มต้น"></select>
                </div>
              </div>
              <div class="otr-time-block">
                <label for="otrBulkEndHour" data-i18n="ot.requests.endTime">ถึงเวลา</label>
                <div class="otr-time-parts">
                  <select id="otrBulkEndHour" required data-bulk-end-hour aria-label="ชั่วโมงสิ้นสุด"></select>
                  <span class="otr-time-separator" aria-hidden="true">:</span>
                  <select required data-bulk-end-minute aria-label="นาทีสิ้นสุด"></select>
                </div>
              </div>
              <div class="otr-time-block">
                <label for="otrBulkHours" data-i18n="ot.requests.dailyAmount">จำนวนชั่วโมง OT ที่ขอ</label>
                <div class="otr-duration">
                  <input id="otrBulkHours" class="otr-duration-input" type="number" min="0" max="24" step="1" inputmode="numeric" data-bulk-hours aria-label="จำนวนชั่วโมง">
                  <span data-i18n="ot.requests.hour">ชั่วโมง</span>
                  <input class="otr-duration-input" type="number" min="0" max="59" step="1" inputmode="numeric" data-bulk-minutes aria-label="จำนวนนาที">
                  <span data-i18n="ot.requests.minute">นาที</span>
                </div>
              </div>
            </div>
            {{-- กลุ่มกะเดียวกันยังมีเวลาเข้า-ออกต่างกันได้ (เช่น AC01 05:00-14:00 กับ AD03 08:00-17:00)
                 ถ้าใช้เวลาเดียวกันหมด OT หลังเลิกงานจะได้เวลาผิด และ OT ก่อนเข้างานจะตกกฎหายไปเลย --}}
            <label class="otr-bulk-auto">
              <input type="checkbox" checked data-bulk-auto-time>
              <span data-i18n="ot.requests.bulkAutoTime">คำนวณเวลาอัตโนมัติตามกะ</span>
            </label>
            <div class="otr-field is-wide">
              <label for="otrBulkNote" data-i18n="ot.requests.note">หมายเหตุ</label>
              <textarea id="otrBulkNote" maxlength="1000" data-bulk-note></textarea>
            </div>
          </div>
          <div class="otr-form-error" data-bulk-error-1 role="alert" hidden></div>
        </div>
        <footer class="otr-form-actions">
          <button class="otr-secondary" type="button" data-close-bulk data-i18n="ot.common.cancel">ยกเลิก</button>
          <button class="otr-primary" type="button" data-bulk-next data-i18n="ot.requests.bulkNext">ไปต่อ</button>
        </footer>
      </div>

      {{-- ขั้นที่ 2 --}}
      <div class="otr-bulk-step" data-bulk-step="2" hidden>
        <div class="otr-bulk-toolbar">
          <label class="otr-bulk-all">
            <input type="checkbox" checked data-bulk-select-all>
            <span data-i18n="ot.requests.bulkSelectAll">เลือกทั้งหมด</span>
          </label>
          {{-- ค้นหาในโมดัล: แผนกใหญ่มีเป็นร้อยคน เลื่อนหาทีละแถวไม่ไหว
               กรองแค่การแสดงผล ไม่แตะการติ๊ก คนที่ติ๊กไว้แล้วแต่ถูกกรองหายยังถูกส่งอยู่
               จึงต้องมีตัวนับ "เลือกแล้ว x/y" กำกับไว้ข้าง ๆ ให้เห็นตลอด --}}
          {{-- ตัวกรองชุดเดียวกับหน้าภาพรวมและตารางหลัก เรียงลำดับเหมือนกัน:
               ค้นหา · วันที่ · เรียงลำดับ · สาขา · กะ --}}
          <label class="otr-bulk-search">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.2-3.2"></path></svg>
            <input type="search" data-bulk-search data-i18n-placeholder="ot.search.placeholder" placeholder="ค้นหารหัสหรือชื่อพนักงาน">
          </label>
          {{-- ปฏิทินในโมดัล: เปลี่ยนวันแล้วโหลดรายชื่อของวันนั้นมาใหม่โดยไม่ปิดโมดัล
               (วันที่นี้คือวันที่จะขอ OT ให้ ไม่ใช่แค่วันที่ดูข้อมูล) --}}
          <label class="ot-modal-date">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"></path></svg>
            <span class="sr-only" data-i18n="ot.attendance.pickDate">เลือกวันที่</span>
            <input type="date" max="{{ now()->format('Y-m-d') }}" data-bulk-date>
          </label>
          <label class="ot-sort-filter">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16M7 20l-3-3M7 20l3-3M14 7h6M14 12h5M14 17h4"></path></svg>
            <select data-bulk-sort aria-label="เรียงลำดับ">
              <option value="code" data-i18n="ot.attendance.sortCode">ตามรหัสพนักงาน</option>
              <option value="rank" data-i18n="ot.attendance.sortRank">ตำแหน่งสูง → ต่ำ</option>
              <option value="rank_asc" data-i18n="ot.attendance.sortRankAsc">ตำแหน่งต่ำ → สูง</option>
            </select>
          </label>
          @include('ot_approval.partials.branch-filter')
          @include('ot_approval.partials.shift-filter')
          {{-- ตัวเลขนับตามตัวกรองที่เลือกอยู่ · ถ้ามีคนติ๊กไว้แต่ถูกกรองหาย จะมีป้าย "+N ที่ถูกกรองไว้"
               โผล่ต่อท้าย เพราะคนกลุ่มนั้นยังถูกส่งอยู่ ต้องไม่ให้หายไปเงียบ ๆ --}}
          <span class="otr-bulk-count">
            <b data-bulk-picked>0</b>/<span data-bulk-total>0</span> <span data-i18n="ot.common.people">คน</span>
            <small class="otr-bulk-hidden" data-bulk-hidden hidden></small>
          </span>
        </div>
        <div class="otr-bulk-scroll"><table class="otr-bulk-table" data-bulk-table></table></div>
        <div class="otr-form-error" data-bulk-error-2 role="alert" hidden></div>
        <footer class="otr-form-actions">
          <button class="otr-secondary" type="button" data-bulk-back data-i18n="ot.requests.bulkBack">ย้อนกลับ</button>
          <button class="otr-primary" type="button" data-bulk-submit><span data-i18n="ot.requests.bulkSubmit">ส่งอนุมัติ</span> (<span data-bulk-submit-count>0</span>)</button>
        </footer>
      </div>
    </div>
  </div>

  <div class="otr-modal otr-success-modal" data-submit-success-modal role="dialog" aria-modal="true" aria-labelledby="otrSubmitSuccessTitle" aria-describedby="otrSubmitSuccessCopy" hidden>
    <div class="otr-dialog otr-success-dialog" tabindex="-1">
      <div class="otr-success-body">
        <span class="otr-success-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="m5 12.5 4.25 4.25L19 7"></path></svg>
        </span>
        <h2 id="otrSubmitSuccessTitle" data-i18n="ot.requests.submitSuccessTitle">สำเร็จ</h2>
        <p id="otrSubmitSuccessCopy" data-i18n="ot.requests.submitted">ส่งคำขอให้ Supervisor แล้ว</p>
      </div>
      <footer class="otr-form-actions">
        <button class="otr-primary" type="button" data-close-submit-success data-i18n="ot.common.close">ปิด</button>
      </footer>
    </div>
  </div>

  <div class="otr-modal otr-reason-modal" data-reason-modal role="dialog" aria-modal="true" aria-labelledby="otrReasonTitle" hidden>
    <div class="otr-dialog otr-reason-dialog">
      <header class="otr-modal-head">
        <div>
          <small data-i18n="ot.reason.button">เหตุผล</small>
          <h2 id="otrReasonTitle" data-reason-title>—</h2>
        </div>
        <button class="otr-close" type="button" data-close-reason data-i18n-aria="ot.reason.close" aria-label="ปิด">×</button>
      </header>
      <div class="otr-reason-body">
        <section class="otr-reason-item">
          <small data-i18n="ot.reason.requestNote">หมายเหตุจาก Foreman</small>
          <p data-reason-request>—</p>
        </section>
        <section class="otr-reason-item">
          <small data-i18n="ot.reason.decisionNote">หมายเหตุจาก Supervisor</small>
          <p data-reason-decision>—</p>
        </section>
      </div>
      <footer class="otr-form-actions">
        <button class="otr-secondary" type="button" data-close-reason data-i18n="ot.common.close">ปิด</button>
      </footer>
    </div>
  </div>

  {{-- ยืนยันก่อนแก้คำขอที่ส่งไปแล้ว — การแก้เขียนทับของเดิมและรีเซ็ตการอนุมัติ จึงต้องให้ผู้ใช้รับทราบก่อน --}}
  <div class="otr-modal otr-confirm" data-otr-revise-confirm role="alertdialog" aria-modal="true" aria-labelledby="otrReviseTitle" hidden>
    <div class="otr-dialog otr-confirm-dialog">
      <div class="otr-confirm-body">
        <span class="otr-confirm-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M12 9v4.5M12 17h.01M10.3 3.9 2.4 17.4A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3.1L13.7 3.9a2 2 0 0 0-3.4 0Z"></path></svg>
        </span>
        <h2 id="otrReviseTitle" data-i18n="ot.requests.reviseConfirmTitle">ยืนยันการแก้ไขคำขอ</h2>
        <p data-otr-revise-message>—</p>
      </div>
      <footer class="otr-confirm-actions">
        <button class="otr-secondary" type="button" data-otr-revise-cancel data-i18n="ot.requests.reviseConfirmClose">ปิด</button>
        <button class="otr-primary" type="button" data-otr-revise-accept data-i18n="ot.requests.reviseConfirmAccept">ยินยอมเพื่อไปต่อ</button>
      </footer>
    </div>
  </div>

  {{-- ยืนยันก่อนส่งจริง — ใช้ร่วมกันทั้งขอรายคนและขอทั้งกะ
       เดิมกดแล้วส่งทันทีโดยไม่มีจังหวะให้ทาน ซึ่งย้อนไม่ได้เพราะอีเมลถึง Supervisor แล้ว --}}
  <div class="otr-modal otr-confirm" data-otr-send-confirm role="alertdialog" aria-modal="true" aria-labelledby="otrSendTitle" hidden>
    <div class="otr-dialog otr-confirm-dialog">
      <div class="otr-confirm-body">
        <span class="otr-confirm-mark is-send" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M21.5 2.5 11 13"></path><path d="M21.5 2.5 15 21l-4-8-8-4Z"></path></svg>
        </span>
        <h2 id="otrSendTitle" data-i18n="ot.requests.sendConfirmTitle">ยืนยันส่งขออนุมัติ</h2>
        <p data-otr-send-message>—</p>
      </div>
      <footer class="otr-confirm-actions">
        <button class="otr-secondary" type="button" data-otr-send-cancel data-i18n="ot.requests.sendConfirmCancel">ยกเลิก</button>
        <button class="otr-primary" type="button" data-otr-send-accept data-i18n="ot.requests.sendConfirmAccept">ยืนยันส่ง</button>
      </footer>
    </div>
  </div>

  {{-- ยกเลิกคำขอที่ส่งไปแล้ว — บังคับกรอกเหตุผลเพราะเก็บไว้เป็นประวัติให้ตรวจย้อนได้ --}}
  <div class="otr-modal otr-confirm" data-otr-cancel-confirm role="alertdialog" aria-modal="true" aria-labelledby="otrCancelTitle" hidden>
    <div class="otr-dialog otr-confirm-dialog">
      <div class="otr-confirm-body">
        <span class="otr-confirm-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M12 9v4.5M12 17h.01M10.3 3.9 2.4 17.4A2 2 0 0 0 4.1 20.5h15.8a2 2 0 0 0 1.7-3.1L13.7 3.9a2 2 0 0 0-3.4 0Z"></path></svg>
        </span>
        <h2 id="otrCancelTitle" data-i18n="ot.requests.cancelConfirmTitle">ยืนยันยกเลิกคำขอ</h2>
        <p data-otr-cancel-message>—</p>
        <label class="otr-confirm-field">
          <span data-i18n="ot.requests.cancelReasonLabel">เหตุผลที่ยกเลิก (บังคับกรอก)</span>
          <select data-otr-cancel-reason-select>
            <option value="" disabled hidden data-i18n="ot.cancel.selectReason">เลือกเหตุผล</option>
            <option value="พนักงานไม่สะดวก" data-i18n="ot.cancel.employeeUnavailable">พนักงานไม่สะดวก</option>
            <option value="ปรับแผนงาน" data-i18n="ot.cancel.workPlanChanged">ปรับแผนงาน</option>
            <option value="วันที่หรือเวลาไม่ถูกต้อง" data-i18n="ot.cancel.wrongSchedule">วันที่หรือเวลาไม่ถูกต้อง</option>
            <option value="ประเภทคำขอไม่ถูกต้อง" data-i18n="ot.cancel.wrongRequestType">ประเภทคำขอไม่ถูกต้อง</option>
            <option value="ส่งคำขอซ้ำ" data-i18n="ot.cancel.duplicateRequest">ส่งคำขอซ้ำ</option>
            <option value="__other__" data-i18n="ot.cancel.other">อื่นๆ</option>
          </select>
          <textarea rows="3" maxlength="500" placeholder="พิมพ์เหตุผลอื่นๆ" data-i18n-placeholder="ot.cancel.otherPlaceholder" data-otr-cancel-reason-other hidden></textarea>
        </label>
        <p class="otr-confirm-error" data-otr-cancel-error hidden></p>
      </div>
      <footer class="otr-confirm-actions">
        <button class="otr-secondary" type="button" data-otr-cancel-close data-i18n="ot.requests.reviseConfirmClose">ปิด</button>
        <button class="otr-danger" type="button" data-otr-cancel-accept data-i18n="ot.requests.cancelConfirmAccept">ยืนยันยกเลิก</button>
      </footer>
    </div>
  </div>

  {{-- ดูรูปพนักงานแบบขยาย --}}
  <div class="otr-lightbox" data-otr-lightbox role="dialog" aria-modal="true" aria-label="รูปพนักงาน" hidden>
    <button class="otr-lightbox-close" type="button" data-lightbox-close aria-label="ปิด">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
    </button>
    <figure class="otr-lightbox-figure">
      <img data-lightbox-image src="" alt="">
      <figcaption class="otr-lightbox-caption" data-lightbox-caption hidden></figcaption>
    </figure>
  </div>

  @include('ot_approval.partials.process-route')
  @include('ot_approval.partials.success-feedback')

  <script>
    'use strict';

    (function () {
      var root = document.querySelector('[data-ot-requests]');
      if (!root) return;

      var selectedDate = root.dataset.selectedDate;
      var sourceMode = root.dataset.sourceMode || 'bplus';
      var employeesUrl = root.dataset.employeesUrl;
      var storeUrl = root.dataset.storeUrl;
      var submitUrl = root.dataset.submitUrl;
      var bulkUrl = root.dataset.bulkUrl;
      var cancelUrl = root.dataset.cancelUrl;
      var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      var paper = root.querySelector('[data-request-paper]');
      var departmentList = root.querySelector('[data-department-list]');
      var backButton = root.querySelector('[data-back-to-departments]');
      var paperContent = root.querySelector('[data-paper-content]');
      var cancelBar = root.querySelector('[data-cancel-bar]');
      var modal = document.querySelector('[data-request-modal]');
      var form = modal.querySelector('[data-request-form]');
      var submitSuccessModal = document.querySelector('[data-submit-success-modal]');
      var reasonModal = document.querySelector('[data-reason-modal]');
      var source = document.querySelector('[data-request-source]');
      var activeDepartment = null;
      var activeEmployee = null;
      // พนักงานที่รอผู้ใช้กดยินยอมในกล่องยืนยันก่อนแก้คำขอ
      var pendingReviseEmployee = null;
      var lastPayload = null;
      var selectedIds = new Set();
      var sortMode = 'code';   // ตามรหัสพนักงาน (เริ่มต้น) / ตำแหน่งสูง→ต่ำ / ตำแหน่งต่ำ→สูง
      /* true = เปลี่ยนวันไปแล้วระหว่างเปิดเอกสารแผนก การ์ดแผนกด้านหลังจึงเป็นยอดของวันเดิม
         ต้องโหลดหน้าใหม่ตอนกดปุ่มกลับ ไม่งั้นตัวเลขบนการ์ดกับที่เพิ่งดูคนละวันกัน */
      var pendingDirectoryReload = false;
      /* ตัวกรองรายคอลัมน์แบบ Excel — helper กลางจาก partials/column-filter
         กดตกลงแล้วให้วาดตารางใหม่จาก payload ชุดเดิมที่โหลดมาแล้ว ไม่ยิงเซิร์ฟเวอร์ซ้ำ */
      var columnFilters = window.otColumnFilter.create(function () {
        if (lastPayload) renderEmployees(lastPayload, false);
      });
      var quietRefresh = false;
      var reasonPreviousFocus = null;
      var shiftFilterSelect = root.querySelector('[data-shift-filter]');
      var shiftFilterValue = window.otShiftFilter.ALL;   // จำค่าไว้ กันเด้งกลับตอนโหลดใหม่
      var employeeSearchInput = root.querySelector('[data-emp-search]');
      var employeeQuery = '';                            // คำค้นรหัส/ชื่อ กรองในเครื่องจากรายชื่อที่โหลดมาแล้ว
      var branchFilterValue = 'all';                     // สาขา (โรงงาน / โรงงาน-พม่า) กรองในเครื่องเช่นกัน
      var myShiftByDept = @json($myShiftByDept);         // กะที่ Admin เซ็ตไว้ แยกตามแผนก

      function text(key, fallback) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return (window.__portalCopy && window.__portalCopy[lang] && window.__portalCopy[lang][key]) || fallback || key;
      }

      function localized(item, field, fallback) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        return item[field + '_' + lang] || item[field + '_en'] || item[field + '_th'] || fallback || '—';
      }

      function shiftSummary(employee) {
        var lang = document.documentElement.getAttribute('data-lang') || 'th';
        var name = employee.attendance_source === 'local'
          ? ''
          : employee['shift_name_' + lang] || employee.shift_name_en || employee.shift_name_th || employee.shift_code || '';
        var time = employee.shift_in && employee.shift_out ? employee.shift_in + '–' + employee.shift_out : '';
        return [name, time].filter(Boolean).join(' ') || '-';
      }

      function compactShiftRange(employee) {
        if (!employee.shift_in || !employee.shift_out) return '-';
        var start = String(employee.shift_in).slice(0, 5).replace(':', '.');
        var end = String(employee.shift_out).slice(0, 5).replace(':', '.');
        return start + '–' + end;
      }

      function localAssetUrl(value) {
        if (!value) return '';
        try {
          var url = new URL(value, window.location.href);
          return ['localhost', '127.0.0.1'].indexOf(url.hostname) !== -1
            ? window.location.origin + url.pathname + url.search
            : url.href;
        } catch (error) {
          return value;
        }
      }

      async function fetchJson(url, options) {
        var response = await fetch(url, Object.assign({
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        }, options || {}));
        var payload = await response.json().catch(function () { return {}; });
        if (!response.ok) {
          var error = new Error(payload.message || 'HTTP ' + response.status);
          error.payload = payload;
          throw error;
        }
        return payload;
      }

      function showNotice(message, isError) {
        var notice = root.querySelector('[data-page-notice]');
        notice.textContent = message;
        notice.classList.toggle('is-error', Boolean(isError));
        notice.hidden = false;
        window.setTimeout(function () { notice.hidden = true; }, 4500);
      }

      function showSuccessFeedback(message) {
        if (window.otSuccessFeedback) {
          window.otSuccessFeedback.show(message || text('leave.done', 'ดำเนินการเรียบร้อยแล้ว'));
          return;
        }

        showNotice(message || text('leave.done', 'ดำเนินการเรียบร้อยแล้ว'), false);
      }

      function setLoading(loading) {
        source?.classList.toggle('is-loading', loading);
      }

      function openSubmitSuccess() {
        submitSuccessModal.hidden = false;
        document.body.style.overflow = 'hidden';
        window.requestAnimationFrame(function () {
          submitSuccessModal.querySelector('[data-close-submit-success]')?.focus();
        });
      }

      function closeSubmitSuccess() {
        submitSuccessModal.hidden = true;
        document.body.style.overflow = '';
        (root.querySelector('.otr-department.is-active') || root.querySelector('[data-open-department]'))?.focus();
      }

      function statusPresentation(key) {
        var map = {
          not_eligible: ['-', '-', '', '', 'neutral'],
          not_requested: ['ot.status.notRequested', 'ไม่มีการขอ OT', '', '', 'neutral'],
          not_worked_ot: ['ot.status.notWorkedOt', 'ไม่ได้ทำ OT', '', '', 'neutral'],
          draft: ['ot.status.pending', 'รอดำเนินการ', '', '', 'warning'],
          pending: ['ot.status.pending', 'รอดำเนินการ', 'ot.status.pendingDetail', '(รออนุมัติและสแกนล่าสุดหลังจบ OT)', 'warning'],
          waiting_approval: ['ot.status.waitingApproval', 'รอดำเนินการ', 'ot.status.waitingApprovalDetail', '(รออนุมัติ)', 'warning'],
          approved_waiting_scan: ['ot.status.approvedWaitingScan', 'รอดำเนินการ', 'ot.status.waitingScanDetail', '(รอสแกนล่าสุดหลังจบ OT)', 'warning'],
          success: ['ot.status.success', 'ผ่าน', '', '', 'success'],
          failed_time: ['ot.status.failedTime', 'ไม่ผ่าน', 'ot.status.failedTimeDetail', '(เวลา OT ไม่ครบ)', 'danger'],
          rejected: ['ot.status.rejected', 'ไม่อนุมัติ', '', '', 'danger'],
          exported: ['ot.status.exported', 'ส่งออกแล้ว', '', '', 'success']
        };
        var item = map[key] || map.not_requested;
        return {
          label: item[0] === '-' ? '-' : text(item[0], item[1]),
          detail: item[2] ? text(item[2], item[3]) : '',
          tone: item[4]
        };
      }

      function statusElement(key) {
        var presentation = statusPresentation(key);
        var status = document.createElement('span');
        status.className = 'otr-status';
        status.dataset.status = key;
        status.dataset.tone = presentation.tone;
        status.setAttribute('aria-label', [presentation.label, presentation.detail].filter(Boolean).join(' '));

        var badge = document.createElement('span');
        badge.className = 'otr-status-badge';
        badge.textContent = presentation.label;
        badge.title = presentation.label;
        status.appendChild(badge);

        if (presentation.detail) {
          var detail = document.createElement('small');
          detail.className = 'otr-status-detail';
          detail.textContent = presentation.detail;
          status.appendChild(detail);
        }

        return status;
      }

      function typeLabel(type) {
        return localized(type, 'label', type.key);
      }

      function addMinutes(time, minutes) {
        if (!time || !/^\d{2}:\d{2}$/.test(time)) return { time: '', nextDay: false };
        var parts = time.split(':').map(Number);
        var total = parts[0] * 60 + parts[1] + minutes;
        var normalized = ((total % 1440) + 1440) % 1440;
        return {
          time: String(Math.floor(normalized / 60)).padStart(2, '0') + ':' + String(normalized % 60).padStart(2, '0'),
          nextDay: total >= 1440
        };
      }

      function durationText(request) {
        return request.requested_hours + ' ' + text('ot.requests.hour', 'ชั่วโมง') + ' ' +
          (request.requested_minutes || 0) + ' ' + text('ot.requests.minute', 'นาที');
      }

      function actorCell(request, employee) {
        /* ยังไม่มีคำขอ OT ของวันนั้น = ไม่มีสถานะให้ไล่ดู แสดงขีดเดียวพอ (Manager สั่ง)
           ถ้าปล่อยให้วาดผังเปล่า ทุกแถวจะมีปุ่มเหมือนกันหมดจนแยกไม่ออกว่าใครยื่นจริง */
        if (!request) return '-';
        return window.otProcessRoute.create(request, employee);
      }

      function requestNote(request) {
        return request?.request_note || request?.note || '';
      }

      function decisionNote(request) {
        return request?.decision_note || '';
      }

      function openReasonModal(employee) {
        var request = employee.request || {};
        reasonPreviousFocus = document.activeElement;
        reasonModal.querySelector('[data-reason-title]').textContent = localized(employee, 'name', employee.code);
        reasonModal.querySelector('[data-reason-request]').textContent = requestNote(request) || '-';
        reasonModal.querySelector('[data-reason-decision]').textContent = decisionNote(request) || '-';
        reasonModal.hidden = false;
        document.body.style.overflow = 'hidden';
        reasonModal.querySelector('[data-close-reason]').focus();
      }

      function closeReasonModal() {
        reasonModal.hidden = true;
        document.body.style.overflow = '';
        if (reasonPreviousFocus && document.contains(reasonPreviousFocus)) reasonPreviousFocus.focus();
        reasonPreviousFocus = null;
      }

      function reasonButton(employee) {
        var request = employee.request;
        if (!request || (!requestNote(request) && !decisionNote(request))) return '-';
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'otr-reason-button';
        button.textContent = text('ot.reason.button', 'เหตุผล');
        button.addEventListener('click', function () { openReasonModal(employee); });
        return button;
      }

      /* สถานะของคอลัมน์ `ขอ OT` ของแถวนั้น — แหล่งความจริงเดียวที่ทั้งการวาดเซลล์
         และตัวกรองรายคอลัมน์ใช้ร่วมกัน จะได้ไม่มีทางหลุดไม่ตรงกัน
           request  = มีปุ่ม `ขอ OT` (ยังไม่เคยยื่น)
           edit     = มีปุ่ม `แก้ไขคำขอ` (ยื่นแล้วและยังแก้ได้)
           submitted= ยื่นแล้วแต่กดไม่ได้ ช่องนี้โชว์ช่วงเวลาที่ขอไว้
           none     = ไม่มีอะไรให้ทำ แสดงขีด */
      function requestActionKey(employee) {
        var canRequest = employee.can_request && (employee.presence || 'absent') !== 'absent';
        if (canRequest) return employee.request ? 'edit' : 'request';
        return employee.request ? 'submitted' : 'none';
      }

      function appendCell(row, content, className) {
        var cell = document.createElement('td');
        if (className) cell.className = className;
        if (content instanceof Node) cell.appendChild(content);
        else cell.textContent = content || '-';
        row.appendChild(cell);
        return cell;
      }

      function avatar(employee) {
        if (employee.avatar) {
          var image = document.createElement('img');
          image.className = 'otr-avatar';
          image.src = localAssetUrl(employee.avatar);
          image.alt = localized(employee, 'name', employee.code);
          image.loading = 'lazy';
          return image;
        }
        var fallback = document.createElement('span');
        fallback.className = 'otr-avatar';
        fallback.textContent = (localized(employee, 'name', employee.code).charAt(0) || '?').toUpperCase();
        return fallback;
      }

      function renderLoading() {
        paperContent.innerHTML = '<div class="otr-loading"><span><span class="otr-spinner" aria-hidden="true"></span>' + text('ot.attendance.loadingEmployees', 'กำลังดึงข้อมูลพนักงาน') + '</span></div>';
      }

      function renderEmployees(payload, initializeSelection) {
        lastPayload = payload;
        paperContent.replaceChildren();
        if (!payload.available) {
          var unavailable = document.createElement('div');
          unavailable.className = 'otr-empty';
          unavailable.textContent = text('ot.attendance.bplusUnavailable', 'ไม่สามารถอ่านข้อมูล Bplus ได้');
          paperContent.appendChild(unavailable);
          updateBatch();
          return;
        }
        if (!payload.employees || !payload.employees.length) {
          var empty = document.createElement('div');
          empty.className = 'otr-empty';
          empty.textContent = text('ot.attendance.noEmployees', 'ไม่พบพนักงานในแผนกนี้');
          paperContent.appendChild(empty);
          shiftFilterValue = window.otShiftFilter.build(shiftFilterSelect, [], shiftFilterValue);
          updateBatch();
          return;
        }

        /* สร้างตัวเลือกกะจากคนทั้งแผนกก่อนกรอง ไม่งั้นพอกรองแล้วตัวเลือกอื่นจะหายไป
           กลับมาเลือกกะเดิมไม่ได้ */
        shiftFilterValue = window.otShiftFilter.build(shiftFilterSelect, payload.employees, shiftFilterValue);
        /* ส่ง null แทน paperContent: พื้นตารางต้องขาวเสมอ (Manager สั่ง 2026-08-21)
           สีบอกกะเหลืออยู่แค่คอลัมน์ `กะ` กับ `เข้างาน/ออกงาน` · ช่องตัวกรองยังย้อมขอบได้ */
        window.otShiftFilter.paint(shiftFilterSelect, null, shiftFilterValue, payload.employees);
        paperContent.classList.remove('ot-shift-scene', 'is-shift-morning', 'is-shift-night', 'is-shift-all');
        root.querySelector('[data-open-bulk]').hidden = false;
        var visible = payload.employees.filter(function (employee) {
          return window.otShiftFilter.matches(employee, shiftFilterValue)
            && window.otBranchFilter.matches(employee, branchFilterValue)
            && window.otEmployeeSearch.matches(employee, employeeQuery)
            && columnFilters.matches(employee);
        });

        /* เรียงตามตัวเลือก: รหัสพนักงาน (ค่าเริ่มต้น) หรือ ตำแหน่งสูง→ต่ำ / ต่ำ→สูง
           ตำแหน่งเท่ากันให้เรียงรหัสต่อ จะได้ลำดับคงที่ไม่สลับไปมาทุกครั้งที่โหลด */
        if (sortMode === 'rank' || sortMode === 'rank_asc') {
          var sortDir = sortMode === 'rank' ? 1 : -1;
          visible = visible.slice().sort(function (a, b) {
            var diff = ((a.position_rank || 999) - (b.position_rank || 999)) * sortDir;
            return diff !== 0 ? diff : String(a.code).localeCompare(String(b.code));
          });
        }

        /* ช่องติ๊กมีไว้ยกเลิก จึงต้องเริ่มจากไม่เลือกอะไรเลยเสมอ
           ของเดิมติ๊กให้อัตโนมัติเพราะใช้ส่งร่าง ถ้าคงไว้จะกลายเป็นเลือกยกเลิกทั้งแผนกทันทีที่เปิดหน้า */
        if (initializeSelection) {
          selectedIds.clear();
        }

        if (!visible.length) {
          var none = document.createElement('div');
          none.className = 'otr-empty';
          none.textContent = text('ot.shiftFilter.empty', 'ไม่พบพนักงานในกะที่เลือก');
          paperContent.appendChild(none);
          updateBatch();
          return;
        }

        var scroll = document.createElement('div');
        scroll.className = 'otr-paper-scroll';
        // ลากด้วยเมาส์เพื่อเลื่อนดูคอลัมน์ที่ล้นจอ (ตัวจัดการอยู่ใน layouts/portal)
        scroll.setAttribute('data-drag-scroll', '');
        var table = document.createElement('table');
        table.className = 'otr-table';
        var head = document.createElement('thead');
        head.innerHTML = '<tr>' +
          /* ไม่มีคอลัมน์ช่องติ๊กแล้ว (Manager สั่ง 2026-08-21 ว่าไม่ได้ใช้) — 12 คอลัมน์ */
          '<th class="otr-order-col is-center" scope="col">' + text('ot.common.order', 'ลำดับ') + '</th>' +
          '<th class="otr-person-col" scope="col">' + text('ot.attendance.employee', 'พนักงาน') + '</th>' +
          '<th class="otr-position-col" scope="col">' + text('ot.attendance.position', 'ตำแหน่ง') + '</th>' +
          '<th class="otr-shift-col" scope="col">' + text('ot.requests.shift', 'กะ') + '</th>' +
          '<th class="otr-time-col" scope="col">' + text('ot.attendance.clockIn', 'เข้างาน') + '</th>' +
          '<th class="otr-time-col" scope="col">' + text('ot.attendance.clockOut', 'เวลาล่าสุด / ออกงาน') + '</th>' +
          '<th class="otr-presence-col is-center" scope="col">' + text('ot.attendance.presence', 'การทำงาน') + '</th>' +
          '<th class="otr-request-col" scope="col">' + text('ot.attendance.otRequested', 'ขอ OT') + '</th>' +
          '<th class="otr-type-col" scope="col">' + text('ot.requests.otType', 'ผลจากลักษณะการรูดบัตร') + '</th>' +
          '<th class="otr-status-col" scope="col">' + text('ot.attendance.status', 'สถานะ') + '</th>' +
          '<th class="otr-reason-col is-center" scope="col">' + text('ot.reason.button', 'เหตุผล') + '</th>' +
          '<th class="otr-actor-col" scope="col">' + text('ot.route.column', 'สถานะทั้งหมด') + '</th>' +
          '</tr>';
        table.appendChild(head);

        /* ตัวกรองรายคอลัมน์ — ใส่เฉพาะคอลัมน์ที่ค่าซ้ำกันเยอะพอจะกรองได้จริง
           สร้างตัวเลือกจาก payload ทั้งแผนก ไม่ใช่แถวที่เหลือหลังกรอง
           ไม่งั้นพอกรองแล้วค่าที่เหลือจะหายจนกลับมาเลือกใหม่ไม่ได้ */
        var headCells = head.querySelectorAll('th');
        columnFilters.attach(headCells[2], 'position', function (row) { return localized(row, 'position', '-'); }, payload.employees);
        columnFilters.attach(headCells[3], 'shift', function (row) { return compactShiftRange(row) || '-'; }, payload.employees);
        columnFilters.attach(headCells[6], 'presence', function (row) { return row.presence || 'absent'; }, payload.employees, function (value) {
          return text('ot.attendance.' + value, { present: 'เข้างาน', absent: 'ไม่เข้างาน', dayoff: 'วันหยุด' }[value] || value);
        });
        /* คอลัมน์ `ขอ OT` — กรองว่าใครมีปุ่มให้กดบ้าง (Manager สั่ง 2026-08-24)
           ต้องอ่านค่าด้วยตรรกะเดียวกับตอนวาดเซลล์เป๊ะ ๆ ไม่งั้นกรองแล้วได้คนละชุดกับที่ตาเห็น */
        columnFilters.attach(headCells[7], 'otRequest', function (row) { return requestActionKey(row); }, payload.employees, function (value) {
          return {
            request: text('ot.requests.requestOt', 'ขอ OT'),
            edit: text('ot.requests.editRequest', 'แก้ไขคำขอ'),
            submitted: text('ot.requests.bulkHasRequest', 'ขอ OT ไปแล้ว'),
            none: '-'
          }[value] || value;
        });
        columnFilters.attach(headCells[8], 'otType', function (row) {
          return row.request ? localized(row.request, 'ot_type_label', row.request.ot_type || '-') : '-';
        }, payload.employees);
        columnFilters.attach(headCells[9], 'status', function (row) { return row.status_key || 'not_requested'; }, payload.employees, function (value) {
          return statusPresentation(value).label;
        });

        var body = document.createElement('tbody');

        visible.forEach(function (employee, order) {
          var row = document.createElement('tr');
          row.dataset.employeeCode = employee.code;

          // ลำดับนับจากแถวที่มองเห็นจริง จึงเริ่มที่ 1 ใหม่ทุกครั้งที่เปลี่ยนตัวกรอง
          appendCell(row, String(order + 1), 'otr-order is-center');

          var person = document.createElement('span');
          person.className = 'otr-person';
          person.appendChild(avatar(employee));
          var personCopy = document.createElement('span');
          personCopy.className = 'otr-person-copy';
          var name = document.createElement('strong');
          name.textContent = localized(employee, 'name', employee.code);
          var code = document.createElement('small');
          code.textContent = employee.code;
          personCopy.append(name, code);
          person.appendChild(personCopy);
          appendCell(row, person, 'otr-person-cell');

          var position = document.createElement('span');
          position.className = 'otr-ellipsis';
          position.textContent = localized(employee, 'position', '-');
          position.title = position.textContent;
          appendCell(row, position);

          var shift = document.createElement('span');
          shift.className = 'otr-shift';
          shift.textContent = compactShiftRange(employee);
          /* ระบายสีตามกะของ "แถวนี้" (ไม่ใช่กะที่เลือกในตัวกรอง)
             `กะ` + `เข้างาน` + `เวลาล่าสุด/ออกงาน` ใช้สีเดียวกันทั้งบล็อก (Manager สั่ง 2026-08-21)
             merge แค่ "สี" เท่านั้น เส้นคั่นระหว่างคอลัมน์ยังอยู่ครบ (Manager ย้ำ 2026-08-21) */
          var shiftTone = employee.shift_group === 'morning' || employee.shift_group === 'night'
            ? ' is-shift-' + employee.shift_group
            : '';
          appendCell(row, shift, 'otr-shift-cell' + shiftTone);
          appendCell(row, employee.clock_in || '-', 'otr-time' + shiftTone);
          appendCell(row, employee.clock_out || '-', 'otr-time' + shiftTone);

          /* การทำงาน — ข้อความล้วน 3 ค่า อ่านคู่กับเวลาสแกนที่อยู่ติดกันทางซ้าย */
          var presence = document.createElement('span');
          var presenceKey = employee.presence || 'absent';
          presence.className = 'otr-presence is-' + presenceKey;
          presence.textContent = text('ot.attendance.' + presenceKey, { present: 'เข้างาน', absent: 'ไม่เข้างาน', dayoff: 'วันหยุด' }[presenceKey] || presenceKey);
          appendCell(row, presence, 'is-center');

          /* ไม่เข้างาน = ไม่มีเวลาสแกนให้คิดค่าล่วงเวลา จึงไม่ให้กดขอ OT (Manager สั่ง 2026-08-21)
             แต่ถ้าเคยยื่นไว้ก่อนหน้าแล้วยังต้องเห็นคำขอเดิมในช่องนี้ (ตกไปที่ else if ด้านล่าง)
             เงื่อนไขนี้ต้องตรงกับ requestActionKey() ที่ตัวกรองรายคอลัมน์ใช้ */
          if (requestActionKey(employee) === 'request' || requestActionKey(employee) === 'edit') {
            var actions = document.createElement('span');
            actions.className = 'otr-row-actions';
            var requestButton = document.createElement('button');
            requestButton.type = 'button';
            requestButton.className = 'otr-request-button';
            requestButton.textContent = employee.request ? text('ot.requests.editRequest', 'แก้ไขคำขอ') : text('ot.requests.requestOt', 'ขอ OT');
            requestButton.addEventListener('click', function () { startRequestEdit(employee); });
            actions.appendChild(requestButton);

            /* ปุ่มยกเลิกอยู่คู่กับปุ่มขอ ตามที่ Manager สั่ง — โผล่เฉพาะคำขอที่ยังไม่ถูกตัดสิน
               ที่อนุมัติ/ปฏิเสธไปแล้วต้องให้ Supervisor เป็นคนแก้ ไม่ใช่ Foreman ถอยเอง */
            if (employee.can_cancel && employee.request) {
              var cancelButton = document.createElement('button');
              cancelButton.type = 'button';
              cancelButton.className = 'otr-row-cancel';
              cancelButton.textContent = text('ot.requests.cancelOne', 'ยกเลิก');
              cancelButton.addEventListener('click', function () {
                openCancelConfirm([Number(employee.request.id)]);
              });
              actions.appendChild(cancelButton);
            }
            appendCell(row, actions);
          } else if (employee.request) {
            var requestCopy = document.createElement('span');
            requestCopy.className = 'otr-request-copy';
            var requestTime = document.createElement('strong');
            requestTime.textContent = employee.request.requested_start + '–' + employee.request.requested_end;
            var requestHours = document.createElement('small');
            requestHours.textContent = durationText(employee.request);
            requestCopy.append(requestTime, requestHours);
            appendCell(row, requestCopy);
          } else {
            appendCell(row, '-', 'is-center');
          }

          /* ประเภท OT + ช่วงเวลาที่ขอบรรทัดล่าง — คนที่กดขอ OT ได้จะเห็นคอลัมน์ "ขอ OT"
             เป็นปุ่มแทนเวลา ถ้าไม่โชว์ตรงนี้ก็จะไม่เห็นเวลาที่ขอไว้เลย */
          var otType = document.createElement('span');
          otType.className = 'otr-type-copy';
          if (employee.request) {
            var typeLabel = document.createElement('span');
            typeLabel.className = 'otr-type-label';
            typeLabel.textContent = localized(employee.request, 'ot_type_label', employee.request.ot_type || '-');
            otType.appendChild(typeLabel);

            if (employee.request.requested_start && employee.request.requested_end) {
              var typeTime = document.createElement('small');
              typeTime.className = 'otr-type-time';
              typeTime.textContent = employee.request.requested_start + '–' + employee.request.requested_end;
              otType.appendChild(typeTime);
            }
            otType.title = typeLabel.textContent;
          } else {
            otType.textContent = '-';
          }
          appendCell(row, otType, 'is-center');

          appendCell(row, statusElement(employee.status_key));
          appendCell(row, reasonButton(employee), 'is-center');
          appendCell(row, actorCell(employee.request, employee));
          body.appendChild(row);
        });

        table.appendChild(body);
        scroll.appendChild(table);
        paperContent.appendChild(scroll);

        updateBatch();
      }

      /* ช่องติ๊กและแถบ "ยกเลิกที่เลือก" ถูกถอดออกแล้ว (Manager สั่ง 2026-08-21)
         คงฟังก์ชันไว้เพราะมีจุดเรียกหลายที่ในไฟล์ เหลือหน้าที่แค่ล้างรายการที่ค้างอยู่
         การยกเลิกตอนนี้ใช้ปุ่มในแถวของแต่ละคน (openCancelConfirm) อย่างเดียว */
      function updateBatch() {
        selectedIds.clear();
      }

      /* ป้ายกะต้องตรงกับที่ Admin เซ็ตของ "แผนกที่เปิดอยู่"
         คนเดียวเป็น Foreman ได้หลายแผนกคนละกะ ถ้าเอามารวมกันจะขึ้นทั้งเช้าและดึกจนอ่านไม่รู้เรื่อง */
      function renderMyShift() {
        var group = activeShiftAssignment();

        // แสดงตลอด ยังไม่เลือกแผนกก็ขึ้น "-" ไว้ก่อน จะได้รู้ว่ามีช่องนี้อยู่
        root.querySelector('[data-my-shift-label]').textContent = group
          ? localized(group, 'label', group.label_th)
          : '-';

        /* Manager สั่ง 2026-08-21: เอาไอคอนดวงอาทิตย์/ดวงจันทร์ออก และเลิกย้อมพื้นที่เนื้อหา
           ตามกะที่ดูแล — สีบอกกะให้เหลืออยู่แค่คอลัมน์ `กะ` กับ `เข้างาน/ออกงาน` ในตาราง
           ป้ายนี้จึงเหลือแค่ข้อความ ส่วน .main ต้องล้าง class เก่าเผื่อค้างจากหน้าอื่น */
        var shell = document.querySelector('.main');
        if (shell) shell.classList.remove('is-myshift-morning', 'is-myshift-night');
      }

      /* เปิดแผนกใหม่ต้องเริ่มที่กะซึ่ง Admin กำหนดให้ Foreman คนนั้น
         แต่หลังจากเปิดแล้วผู้ใช้ยังเปลี่ยนตัวกรองเองได้ และค่าไม่เด้งกลับตอนรีเฟรช 30 วินาที */
      function activeShiftAssignment() {
        return activeDepartment
          ? myShiftByDept[activeDepartment.companyCode + '|' + activeDepartment.deptCode] || null
          : null;
      }

      function assignedShiftFilterValue() {
        var group = activeShiftAssignment();
        return group && group.filter_value
          ? group.filter_value
          : window.otShiftFilter.ALL;
      }

      async function loadDepartment(initializeSelection) {
        if (!activeDepartment) return;
        if (!quietRefresh) renderLoading();
        setLoading(true);
        var params = new URLSearchParams({
          date: selectedDate,
          company: activeDepartment.companyCode,
          dept_code: activeDepartment.deptCode
        });
        try {
          var response = await fetchJson(employeesUrl + '?' + params.toString());
          renderEmployees(response.attendance, initializeSelection);
        } catch (error) {
          if (!quietRefresh) {
            paperContent.innerHTML = '<div class="otr-empty">' + text('ot.attendance.bplusUnavailable', 'ไม่สามารถอ่านข้อมูล Bplus ได้') + '</div>';
          }
        } finally {
          setLoading(false);
        }
      }

      function openDepartment(button) {
        root.querySelectorAll('[data-open-department]').forEach(function (item) { item.classList.remove('is-active'); });
        button.classList.add('is-active');
        activeDepartment = {
          companyCode: button.dataset.companyCode,
          companyLabel: button.dataset.companyLabel,
          deptCode: button.dataset.deptCode,
          deptName_th: button.dataset.deptNameTh,
          deptName_en: button.dataset.deptNameEn,
          deptName_my: button.dataset.deptNameMy
        };
        selectedIds.clear();
        lastPayload = null;
        shiftFilterValue = assignedShiftFilterValue();
        paper.hidden = false;
        /* ตารางพนักงานมาแทนที่รายชื่อแผนก (Manager ชอบแบบหน้าขอลา สั่งให้ทำเหมือนกัน 2026-08-24)
           ปุ่ม `กลับ` ข้างปฏิทินคือทางเดียวที่จะกลับไปเลือกแผนกใหม่ จึงต้องโผล่พร้อมกันเสมอ */
        if (departmentList) departmentList.hidden = true;
        if (backButton) backButton.hidden = false;
        paper.querySelector('[data-paper-department]').textContent = localized(activeDepartment, 'deptName', '-');
        renderMyShift();
        loadDepartment(true);
        paper.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
      }

      /** กลับไปหน้ารายชื่อแผนก — ล้างแผนกที่เปิดอยู่ ไม่งั้นรีเฟรช 30 วิยังยิงโหลดของเดิมต่อ */
      function backToDepartments() {
        /* ถ้าเปลี่ยนวันไประหว่างเปิดเอกสารอยู่ ยอดบนการ์ดแผนกยังเป็นของวันเดิม
           (ข้อมูลส่วนนั้นเรนเดอร์จากฝั่ง server) จึงต้องโหลดหน้าใหม่ตอนกลับ */
        if (pendingDirectoryReload) {
          window.location.assign(@json(route('ot-approval.requests.index')) + '?date=' + encodeURIComponent(selectedDate));
          return;
        }

        activeDepartment = null;
        lastPayload = null;
        selectedIds.clear();
        paper.hidden = true;
        if (departmentList) departmentList.hidden = false;
        if (backButton) backButton.hidden = true;
        root.querySelectorAll('[data-open-department]').forEach(function (item) { item.classList.remove('is-active'); });
        renderMyShift();
      }

      function fillTypeOptions(types, selected) {
        var select = form.querySelector('[data-form-type]');
        select.replaceChildren();
        types.forEach(function (type) {
          var option = document.createElement('option');
          option.value = type.key;
          option.textContent = typeLabel(type);
          option.dataset.timing = type.timing;
          option.selected = type.key === selected;
          select.appendChild(option);
        });
      }

      function fillTimeOptions() {
        ['start', 'end'].forEach(function (prefix) {
          var hourSelect = form.querySelector('[data-form-' + prefix + '-hour]');
          var minuteSelect = form.querySelector('[data-form-' + prefix + '-minute]');
          hourSelect.replaceChildren();
          minuteSelect.replaceChildren();
          for (var hour = 0; hour < 24; hour += 1) {
            var hourOption = document.createElement('option');
            hourOption.value = String(hour).padStart(2, '0');
            hourOption.textContent = hourOption.value;
            hourSelect.appendChild(hourOption);
          }
          for (var minute = 0; minute < 60; minute += 1) {
            var minuteOption = document.createElement('option');
            minuteOption.value = String(minute).padStart(2, '0');
            minuteOption.textContent = minuteOption.value;
            minuteSelect.appendChild(minuteOption);
          }
        });
      }

      function setTime(prefix, value) {
        var parts = /^\d{2}:\d{2}$/.test(value || '') ? value.split(':') : ['00', '00'];
        form.querySelector('[data-form-' + prefix + '-hour]').value = parts[0];
        form.querySelector('[data-form-' + prefix + '-minute]').value = parts[1];
      }

      function selectedTime(prefix) {
        return form.querySelector('[data-form-' + prefix + '-hour]').value + ':' +
          form.querySelector('[data-form-' + prefix + '-minute]').value;
      }

      function defaultRange(employee, timing) {
        var start;
        var end;
        if (timing === 'after_shift' && employee.shift_out) {
          start = addMinutes(employee.shift_out, 60).time;
          end = addMinutes(start, 120).time;
        } else if (timing === 'before_shift' && employee.shift_in) {
          end = employee.shift_in;
          start = addMinutes(end, -120).time;
        } else if (timing === 'manual' && employee.shift_in && employee.shift_out) {
          // OT วันหยุดทำเต็มกะ ตั้งค่าเริ่มต้นตามเวลากะไปเลยจะได้ไม่ต้องกรอกเอง
          start = employee.shift_in;
          end = employee.shift_out;
        } else {
          start = employee.clock_out || employee.shift_out || '18:00';
          end = addMinutes(start, 120).time;
        }

        return { start: start, end: end };
      }

      /** นาทีของช่วงเวลาที่เลือก ใช้เป็นค่าตั้งต้นของ "จำนวนชั่วโมง OT ที่ขอ" */
      function rangeMinutes() {
        var startParts = selectedTime('start').split(':').map(Number);
        var endParts = selectedTime('end').split(':').map(Number);
        var startMinutes = startParts[0] * 60 + startParts[1];
        var endMinutes = endParts[0] * 60 + endParts[1];
        var total = endMinutes - startMinutes;
        if (total < 0) total += 1440;

        return total;
      }

      /** จำนวนที่กรอกอยู่ในช่อง ถ้าเว้นว่างนับเป็น 0 */
      function durationMinutes() {
        var hours = Number(form.querySelector('[data-form-duration-hours]').value || 0);
        var minutes = Number(form.querySelector('[data-form-duration-minutes]').value || 0);

        return (hours * 60) + minutes;
      }

      /* เติมจำนวนชั่วโมงให้อัตโนมัติตามช่วงเวลาที่เลือก แต่ Foreman แก้เองได้
         เช่น OT วันหยุดเต็มกะ 08:00-17:00 ระบบเติม 9 ชั่วโมง แล้วเขาลบเวลาพัก
         เหลือ 8 เอง เพราะเวลาพักแต่ละกะไม่เหมือนกันและมีข้อยกเว้นหน้างาน */
      function updateDuration() {
        var total = rangeMinutes();
        form.querySelector('[data-form-duration-hours]').value = String(Math.floor(total / 60));
        form.querySelector('[data-form-duration-minutes]').value = String(total % 60);

        return total > 0;
      }

      /**
       * ทางเข้าเดียวของปุ่มขอ/แก้คำขอ
       *
       * ขอใหม่ = เปิดฟอร์มเลย · แก้ของที่ส่งไปแล้ว = ต้องผ่านกล่องยืนยันก่อน
       * เพราะการแก้เขียนทับของเดิมและรีเซ็ตการอนุมัติ ผู้ใช้ต้องรับทราบก่อนเข้าไปแก้
       */
      function startRequestEdit(employee) {
        if (!employee.is_revision) {
          openRequestModal(employee);

          return;
        }

        var confirmBox = document.querySelector('[data-otr-revise-confirm]');
        if (!confirmBox) {
          openRequestModal(employee);

          return;
        }

        // เคยส่งไฟล์ให้ HR แล้วมีงานตามหลังเพิ่ม จึงต้องเตือนหนักกว่ากรณีแก้ธรรมดา
        confirmBox.querySelector('[data-otr-revise-message]').textContent = employee.was_exported
          ? text(
            'ot.requests.reviseConfirmExported',
            'คำขอนี้ได้ถูกดาวน์โหลดเพื่อส่งให้ฝ่ายบุคคลเรียบร้อยแล้ว การแก้ไขจะเขียนทับข้อมูลเดิมและต้องส่งขออนุมัติใหม่ทั้งหมด กรุณาแจ้งผู้ดูแลระบบให้ดาวน์โหลดเอกสารฉบับใหม่ทับของเดิม มิฉะนั้นชั่วโมงในระบบ Bplus จะไม่ตรงกับข้อมูลที่แก้ไข',
          )
          : text(
            'ot.requests.reviseConfirmNote',
            'คำขอนี้ได้ผ่านการส่งเพื่อขออนุมัติแล้ว การแก้ไขจะเขียนทับข้อมูลเดิมทั้งหมด และระบบจะนำคำขอกลับเข้าสู่ขั้นตอนขออนุมัติใหม่ตั้งแต่ต้น',
          );

        pendingReviseEmployee = employee;
        confirmBox.hidden = false;
        document.body.style.overflow = 'hidden';

        var accept = confirmBox.querySelector('[data-otr-revise-accept]');
        if (accept) {
          try {
            accept.focus({ preventScroll: true });
          } catch (error) {
            accept.focus();
          }
        }
      }

      function closeReviseConfirm() {
        var confirmBox = document.querySelector('[data-otr-revise-confirm]');
        if (!confirmBox || confirmBox.hidden) return;

        confirmBox.hidden = true;
        pendingReviseEmployee = null;
        // ฟอร์มคำขอจะล็อก scroll ต่อเอง ตรงนี้ปลดไว้ก่อนกันค้างเมื่อผู้ใช้กดปิด
        document.body.style.overflow = '';
      }

      function openRequestModal(employee) {
        activeEmployee = employee;
        var request = employee.request;

        /* คำขอที่ส่งไฟล์ให้ HR ไปแล้วยังแก้ได้ แต่ต้องบอกให้ชัดว่ามีงานตามหลัง
           คือ admin ต้องโหลดไฟล์ใหม่ทับ ไม่งั้นชั่วโมงใน Bplus จะไม่ตรงกับที่แก้ */
        var warn = modal.querySelector('[data-modal-revision-note]');
        if (warn) {
          if (employee.was_exported) {
            warn.textContent = text('ot.requests.revisionExported', 'คำขอนี้ถูกดาวน์โหลดส่ง HR ไปแล้ว — แก้ได้ แต่ต้องแจ้ง admin ให้โหลดไฟล์ใหม่ทับ');
            warn.hidden = false;
          } else if (employee.is_revision) {
            warn.textContent = text('ot.requests.revisionNote', 'คำขอนี้ผ่านการส่ง/อนุมัติแล้ว การแก้จะเขียนทับของเดิมและต้องส่งอนุมัติใหม่');
            warn.hidden = false;
          } else {
            warn.hidden = true;
          }
        }
        var types = lastPayload.ot_types || @json($otTypes);
        var selectedType = request?.ot_type || 'weekday_after_work';
        var employeeName = localized(employee, 'name', employee.code);
        modal.querySelector('[data-modal-title]').textContent = employeeName;
        modal.querySelector('[data-modal-subtitle]').textContent = employee.code + ' · ' + localized(employee, 'position', '-');

        // รูปพนักงานมุมขวาของหัวโมดัล ใช้ class เดิมจึงกดซูมได้เหมือนในตาราง
        var photo = modal.querySelector('[data-modal-photo]');
        photo.replaceChildren();
        var photoNode = avatar(employee);
        photoNode.dataset.captionName = employeeName;
        photoNode.dataset.captionCode = employee.code || '';
        photo.appendChild(photoNode);
        form.querySelector('[data-form-shift]').textContent = shiftSummary(employee);
        form.querySelector('[data-form-clock-in]').textContent = employee.clock_in || '-';
        form.querySelector('[data-form-clock-out]').textContent = employee.clock_out || '-';
        fillTypeOptions(types, selectedType);
        fillTimeOptions();
        var selectedOption = form.querySelector('[data-form-type]').selectedOptions[0];
        var range = request
          ? { start: request.requested_start, end: request.requested_end }
          : defaultRange(employee, selectedOption?.dataset.timing);
        setTime('start', range.start);
        setTime('end', range.end);
        form.querySelector('[data-form-note]').value = requestNote(request);
        form.querySelector('[data-form-error]').hidden = true;
        updateDuration();
        // แก้คำขอเดิม ต้องเห็นจำนวนที่เคยกรอกไว้ ไม่ใช่ค่า auto ตามช่วงเวลา
        if (request) {
          form.querySelector('[data-form-duration-hours]').value = String(request.requested_hours ?? 0);
          form.querySelector('[data-form-duration-minutes]').value = String(request.requested_minutes ?? 0);
        }
        /* เลิกย้อมโมดัลตามกะแล้ว (Manager สั่ง 2026-08-21) — พื้นโมดัลขาวเสมอ
           ต้องถอด class เก่าออกด้วย เผื่อค้างจากตอนก่อนเปลี่ยนสเปก */
        modal.querySelector('.otr-modal-head').classList.remove('ot-shift-scene', 'is-compact', 'is-shift-morning', 'is-shift-night', 'is-shift-all');
        modal.querySelector('.otr-form').classList.remove('ot-shift-tint', 'is-shift-morning', 'is-shift-night', 'is-shift-all');

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        form.querySelector('[data-form-type]').focus();
      }

      function closeRequestModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
        activeEmployee = null;
      }

      function validationMessage(error) {
        var errors = error.payload?.errors || {};
        var first = Object.keys(errors)[0];
        return first && errors[first]?.[0] ? errors[first][0] : (error.payload?.message || error.message);
      }

      form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!activeEmployee || !activeDepartment) return;
        var saveButton = form.querySelector('[data-save-request]');
        var errorNode = form.querySelector('[data-form-error]');
        saveButton.disabled = true;
        errorNode.hidden = true;
        try {
          if (rangeMinutes() <= 0) {
            throw new Error(text('ot.requests.invalidTimeRange', 'เวลาเริ่มและเวลาสิ้นสุดต้องไม่เท่ากัน'));
          }
          var minutes = durationMinutes();
          if (minutes <= 0) {
            throw new Error(text('ot.requests.durationRequired', 'กรุณากรอกจำนวนชั่วโมงที่ขอ OT'));
          }
          if (minutes > rangeMinutes()) {
            throw new Error(text('ot.requests.durationTooLong', 'จำนวนที่ขอต้องไม่เกินช่วงเวลาที่เลือก'));
          }
          /* ยืนยันก่อนยิงจริง เพราะกดแล้วส่งถึง Supervisor ทันที ถอยไม่ได้
             ปิด modal ฟอร์มไว้ชั่วคราวไม่ได้ จึงให้กล่องยืนยันซ้อนอยู่ด้านบน (z-index 110) */
          var agreed = await askSend(
            text('ot.requests.sendConfirmBody', 'ระบบจะส่งคำขอนี้ให้ Supervisor ทันทีและแจ้งทางอีเมล ต้องการส่งเลยหรือไม่')
          );
          if (!agreed) { saveButton.disabled = false; return; }

          await fetchJson(storeUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
              company: activeDepartment.companyCode,
              dept_code: activeDepartment.deptCode,
              employee_code: activeEmployee.code,
              work_date: selectedDate,
              ot_type: form.querySelector('[data-form-type]').value,
              start_time: selectedTime('start'),
              end_time: selectedTime('end'),
              // จำนวนที่ Foreman กรอกจริง อาจน้อยกว่าช่วงเวลาเพราะหักเวลาพักออกแล้ว
              requested_hours: Number(form.querySelector('[data-form-duration-hours]').value || 0),
              requested_minutes: Number(form.querySelector('[data-form-duration-minutes]').value || 0),
              note: form.querySelector('[data-form-note]').value
            })
          });
          closeRequestModal();
          quietRefresh = true;
          await loadDepartment(false);
          showSuccessFeedback(text('ot.requests.sent', 'ส่งคำขอ OT ให้ Supervisor แล้ว'));
        } catch (error) {
          errorNode.textContent = validationMessage(error);
          errorNode.hidden = false;
        } finally {
          quietRefresh = false;
          saveButton.disabled = false;
        }
      });

      form.querySelectorAll('[data-form-start-hour], [data-form-start-minute], [data-form-end-hour], [data-form-end-minute]').forEach(function (select) {
        select.addEventListener('change', updateDuration);
      });
      form.querySelector('[data-form-type]').addEventListener('change', function (event) {
        var timing = event.target.selectedOptions[0]?.dataset.timing;
        var range = defaultRange(activeEmployee, timing);
        setTime('start', range.start);
        setTime('end', range.end);
        updateDuration();
      });

      /* ── ยืนยันก่อนส่ง ────────────────────────────────────────────
         เดิมกดแล้วยิงทันที ย้อนไม่ได้เพราะอีเมลถึง Supervisor ไปแล้ว
         คืน Promise เพื่อให้ผู้เรียกรอผลได้เหมือน confirm() ปกติ */
      var sendConfirm = document.querySelector('[data-otr-send-confirm]');

      function askSend(message) {
        return new Promise(function (resolve) {
          sendConfirm.querySelector('[data-otr-send-message]').textContent = message;
          sendConfirm.hidden = false;
          document.body.style.overflow = 'hidden';

          function close(result) {
            sendConfirm.hidden = true;
            document.body.style.overflow = '';
            accept.removeEventListener('click', onAccept);
            cancel.removeEventListener('click', onCancel);
            resolve(result);
          }
          function onAccept() { close(true); }
          function onCancel() { close(false); }

          var accept = sendConfirm.querySelector('[data-otr-send-accept]');
          var cancel = sendConfirm.querySelector('[data-otr-send-cancel]');
          accept.addEventListener('click', onAccept);
          cancel.addEventListener('click', onCancel);
        });
      }

      /* ── ยกเลิกคำขอที่เลือก ───────────────────────────────────────
         เหตุผลบังคับกรอกเพราะเก็บลง audit ไว้ให้ตรวจย้อนได้ว่าใครยกเลิกเพราะอะไร */
      var cancelConfirm = document.querySelector('[data-otr-cancel-confirm]');

      function openCancelConfirm(ids) {
        if (!ids.length) return;
        var reasonSelect = cancelConfirm.querySelector('[data-otr-cancel-reason-select]');
        var reasonOther = cancelConfirm.querySelector('[data-otr-cancel-reason-other]');
        var errorNode = cancelConfirm.querySelector('[data-otr-cancel-error]');
        var accept = cancelConfirm.querySelector('[data-otr-cancel-accept]');
        var close = cancelConfirm.querySelector('[data-otr-cancel-close]');
        reasonSelect.value = '';
        reasonOther.value = '';
        reasonOther.hidden = true;
        errorNode.hidden = true;
        reasonSelect.onchange = function () {
          reasonOther.hidden = reasonSelect.value !== '__other__';
          if (!reasonOther.hidden) reasonOther.focus();
        };
        cancelConfirm.querySelector('[data-otr-cancel-message]').textContent =
          text('ot.requests.cancelConfirmBody', 'คำขอที่ยกเลิกจะถูกถอนออกจากคิวของ Supervisor และเก็บไว้เป็นประวัติพร้อมเหตุผล')
          + ' · ' + ids.length + ' ' + text('ot.requests.items', 'รายการ');
        cancelConfirm.hidden = false;
        document.body.style.overflow = 'hidden';
        reasonSelect.focus();

        function shut() {
          cancelConfirm.hidden = true;
          document.body.style.overflow = '';
          accept.removeEventListener('click', onAccept);
          close.removeEventListener('click', shut);
        }
        async function onAccept() {
          var value = reasonSelect.value === '__other__'
            ? reasonOther.value.trim()
            : reasonSelect.value.trim();
          if (value.length < 3) {
            errorNode.textContent = text('ot.requests.cancelReasonRequired', 'กรุณาระบุเหตุผลอย่างน้อย 3 ตัวอักษร');
            errorNode.hidden = false;
            (reasonSelect.value === '__other__' ? reasonOther : reasonSelect).focus();
            return;
          }
          accept.disabled = true;
          try {
            await fetchJson(cancelUrl, {
              method: 'POST',
              headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
              body: JSON.stringify({ request_ids: ids, reason: value })
            });
            selectedIds.clear();
            shut();
            quietRefresh = true;
            await loadDepartment(false);
            showSuccessFeedback(text('ot.requests.cancelled', 'ยกเลิกคำขอแล้ว'));
          } catch (error) {
            errorNode.textContent = validationMessage(error);
            errorNode.hidden = false;
          } finally {
            accept.disabled = false;
            quietRefresh = false;
            updateBatch();
          }
        }
        accept.addEventListener('click', onAccept);
        close.addEventListener('click', shut);
      }

      root.querySelectorAll('[data-open-department]').forEach(function (button) {
        button.addEventListener('click', function () { openDepartment(button); });
      });
      if (backButton) backButton.addEventListener('click', backToDepartments);
      modal.querySelectorAll('[data-close-request]').forEach(function (button) { button.addEventListener('click', closeRequestModal); });
      modal.addEventListener('click', function (event) { if (event.target === modal) closeRequestModal(); });
      submitSuccessModal.querySelectorAll('[data-close-submit-success]').forEach(function (button) {
        button.addEventListener('click', closeSubmitSuccess);
      });
      submitSuccessModal.addEventListener('click', function (event) {
        if (event.target === submitSuccessModal) closeSubmitSuccess();
      });
      /* ── กดรูปพนักงานเพื่อดูแบบขยาย ────────────────────────
         ใช้ delegation แบบ capture เพราะแถวพนักงานถูกสร้างด้วย JS หลังเลือกแผนก */
      var lightbox = document.querySelector('[data-otr-lightbox]');
      var lightboxImage = lightbox.querySelector('[data-lightbox-image]');
      var lightboxCaption = lightbox.querySelector('[data-lightbox-caption]');

      function closeLightbox() {
        if (lightbox.hidden) return;
        lightbox.hidden = true;
        lightboxImage.removeAttribute('src');
      }

      function openLightbox(image) {
        if (!image.getAttribute('src')) return;
        lightboxImage.src = image.currentSrc || image.src;
        lightboxImage.alt = image.alt || '';

        /* ชื่อ/รหัส: รูปในหัวโมดัลติดมากับ data-caption-* ส่วนรูปในตารางอ่านจาก
           บล็อกข้อความข้าง ๆ ในแถวเดียวกัน */
        var captionName = image.dataset.captionName || '';
        var captionCode = image.dataset.captionCode || '';

        if (!captionName) {
          var copy = image.parentElement ? image.parentElement.querySelector('.otr-person-copy') : null;
          if (copy) {
            var nameNode = copy.querySelector('strong');
            var codeNode = copy.querySelector('small');
            captionName = nameNode ? nameNode.textContent : '';
            captionCode = codeNode ? codeNode.textContent : '';
          }
        }

        lightboxCaption.replaceChildren();
        if (captionName.trim() !== '') {
          var nameEl = document.createElement('strong');
          nameEl.textContent = captionName;
          lightboxCaption.appendChild(nameEl);
        }
        if (captionCode.trim() !== '' && captionCode.trim() !== '-') {
          var codeEl = document.createElement('small');
          codeEl.textContent = captionCode;
          lightboxCaption.appendChild(codeEl);
        }
        lightboxCaption.hidden = !lightboxCaption.childNodes.length;
        lightbox.hidden = false;
      }

      document.addEventListener('click', function (event) {
        var image = event.target.closest('img.otr-avatar');
        if (!image) return;
        // กันไม่ให้คลิกทะลุไปติ๊กเลือกแถวหรือเปิดฟอร์มคำขอ
        event.preventDefault();
        event.stopPropagation();
        openLightbox(image);
      }, true);

      lightbox.addEventListener('click', function (event) {
        // คลิกพื้นหลังหรือปุ่มปิด = ปิด, คลิกที่ตัวรูปไม่ปิด
        if (event.target.closest('[data-lightbox-close]') || !event.target.closest('.otr-lightbox-figure')) {
          closeLightbox();
        }
      });

      reasonModal.querySelectorAll('[data-close-reason]').forEach(function (button) {
        button.addEventListener('click', closeReasonModal);
      });
      reasonModal.addEventListener('click', function (event) {
        if (event.target === reasonModal) closeReasonModal();
      });

      /* กล่องยืนยันก่อนแก้คำขอ — ปิดได้ทั้งปุ่มปิด คลิกพื้นหลัง และ Esc */
      var reviseConfirm = document.querySelector('[data-otr-revise-confirm]');
      if (reviseConfirm) {
        reviseConfirm.querySelector('[data-otr-revise-cancel]').addEventListener('click', closeReviseConfirm);
        reviseConfirm.querySelector('[data-otr-revise-accept]').addEventListener('click', function () {
          var employee = pendingReviseEmployee;
          closeReviseConfirm();
          if (employee) openRequestModal(employee);
        });
        reviseConfirm.addEventListener('click', function (event) {
          if (event.target === reviseConfirm) closeReviseConfirm();
        });
      }

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        // ถ้ากำลังดูรูปขยายอยู่ ให้ Esc ปิดแค่รูป ไม่ปิดฟอร์มคำขอข้างหลัง
        if (!lightbox.hidden) {
          closeLightbox();
          return;
        }
        if (reviseConfirm && !reviseConfirm.hidden) {
          closeReviseConfirm();
          return;
        }
        if (!submitSuccessModal.hidden) {
          closeSubmitSuccess();
          return;
        }
        if (!reasonModal.hidden) {
          closeReasonModal();
          return;
        }
        if (!modal.hidden) closeRequestModal();
      });
      /* ── ขอ OT ทั้งกะ ─────────────────────────────────────────────────
         ขั้น 1 กรอกค่ากลางครั้งเดียว · ขั้น 2 กระจายลงทุกคนแล้วแก้รายคน
         กด "ส่งอนุมัติ" = สร้างและส่งให้ Supervisor พร้อมกัน ไม่มีขั้นบันทึกร่าง */
      var bulkModal = document.querySelector('[data-bulk-modal]');
      var bulkRows = [];        // แถวที่กำลังแก้อยู่ในขั้นที่ 2
      var bulkQuery = '';      // คำค้นในโมดัล — กรองแค่การแสดงผล ไม่แตะการติ๊ก
      var bulkBranch = 'all';  // สาขาในโมดัล — กรองแค่การแสดงผลเหมือนกัน
      var bulkShift = 'all';   // กะในโมดัล — กรองแค่การแสดงผลเหมือนกัน
      var bulkSort = 'code';   // การเรียงในโมดัล
      /* ตัวกรองรายคอลัมน์ของตารางในโมดัล — helper กลางตัวเดียวกับตารางหลัก */
      var bulkColumnFilters = window.otColumnFilter.create(function () { renderBulkTable(); });

      function bulkTimeOptions() {
        [['start', 'hour'], ['start', 'minute'], ['end', 'hour'], ['end', 'minute']].forEach(function (pair) {
          var select = bulkModal.querySelector('[data-bulk-' + pair[0] + '-' + pair[1] + ']');
          var max = pair[1] === 'hour' ? 24 : 60;
          select.replaceChildren();
          for (var value = 0; value < max; value += 1) {
            var option = document.createElement('option');
            option.value = String(value).padStart(2, '0');
            option.textContent = option.value;
            select.appendChild(option);
          }
        });
      }

      function bulkSetTime(prefix, value) {
        var parts = /^\d{2}:\d{2}$/.test(value || '') ? value.split(':') : ['00', '00'];
        bulkModal.querySelector('[data-bulk-' + prefix + '-hour]').value = parts[0];
        bulkModal.querySelector('[data-bulk-' + prefix + '-minute]').value = parts[1];
      }

      function bulkTime(prefix) {
        return bulkModal.querySelector('[data-bulk-' + prefix + '-hour]').value + ':' +
          bulkModal.querySelector('[data-bulk-' + prefix + '-minute]').value;
      }

      /** คนที่อยู่ในกะที่กรองอยู่ ณ ตอนนี้ — ปุ่มขอ OT ทั้งกะทำงานกับชุดนี้เท่านั้น */
      function bulkCandidates() {
        if (!lastPayload || !lastPayload.employees) return [];
        return lastPayload.employees.filter(function (employee) {
          return window.otShiftFilter.matches(employee, shiftFilterValue);
        });
      }

      /** เหตุผลที่คนนี้ขอ OT ไม่ได้ — ต้องบอกให้เห็น ไม่ใช่ซ่อนคนหายไปเฉย ๆ */
      function bulkBlockReason(employee) {
        if (employee.ot_block_reason === 'position_not_ot_eligible') {
          return text('ot.requests.bulkPositionNotEligible', 'ตำแหน่งนี้ไม่มีสิทธิ์ขอ OT');
        }
        if (employee.request) return text('ot.requests.bulkHasRequest', 'ขอ OT ไปแล้ว');
        /* ไม่เข้างาน = ไม่มีเวลาสแกนให้คิดค่าล่วงเวลา จึงขอไม่ได้ (Manager สั่ง 2026-08-24)
           กติกาเดียวกับตารางหลักที่ซ่อนปุ่ม `ขอ OT` ของคนที่ไม่เข้างาน */
        if ((employee.presence || 'absent') === 'absent') {
          return text('ot.attendance.absent', 'ไม่เข้างาน');
        }
        return '';
      }

      function openBulk() {
        if (!activeDepartment) return;
        var people = bulkCandidates();
        if (!people.length) {
          showNotice(text('ot.shiftFilter.empty', 'ไม่พบพนักงานในกะที่เลือก'), true);
          return;
        }

        bulkModal.querySelector('[data-bulk-title]').textContent = localized(activeDepartment, 'deptName', '-');
        bulkModal.querySelector('[data-bulk-people]').textContent = people.length;

        /* ตัวกรองในโมดัลเริ่มใหม่ทุกครั้งที่เปิด ไม่ให้ค่าค้างจากรอบก่อนมาซ่อนคนโดยไม่รู้ตัว
           ต้องล้าง "ตัวควบคุมบนจอ" ด้วย ไม่ใช่แค่ตัวแปร ไม่งั้นช่องค้นหายังมีข้อความค้างอยู่
           แต่ตารางแสดงครบ = ผู้ใช้เห็นไม่ตรงกับสิ่งที่ช่องบอก */
        bulkShift = 'all';
        bulkSort = 'code';
        bulkQuery = '';
        bulkBranch = 'all';
        bulkColumnFilters.reset();
        if (bulkSearchInput) bulkSearchInput.value = '';
        if (bulkBranchSelect) bulkBranchSelect.value = 'all';
        if (bulkDateInput) bulkDateInput.value = selectedDate;
        if (bulkSortSelect) bulkSortSelect.value = 'code';

        // ค่าเริ่มต้นของฟอร์มกลาง ใช้กติกาเดียวกับการขอทีละคน
        var types = lastPayload.ot_types || @json($otTypes);
        var typeSelect = bulkModal.querySelector('[data-bulk-type]');
        typeSelect.replaceChildren();
        types.forEach(function (type) {
          var option = document.createElement('option');
          option.value = type.key;
          option.textContent = typeLabel(type);
          option.dataset.timing = type.timing;
          typeSelect.appendChild(option);
        });
        bulkTimeOptions();
        bulkApplyDefaultRange(people[0]);

        bulkModal.querySelector('[data-bulk-note]').value = '';
        bulkModal.querySelector('[data-bulk-error-1]').hidden = true;
        bulkModal.querySelector('[data-bulk-error-2]').hidden = true;
        syncBulkAutoTime();
        paintBulkTheme();
        bulkStep(1);
        bulkModal.hidden = false;
        document.body.style.overflow = 'hidden';
        typeSelect.focus();
      }

      function bulkApplyDefaultRange(sample) {
        var typeSelect = bulkModal.querySelector('[data-bulk-type]');
        var range = defaultRange(sample || {}, typeSelect.selectedOptions[0]?.dataset.timing);
        bulkSetTime('start', range.start);
        bulkSetTime('end', range.end);
        bulkFillAmount();
      }

      /** เติมจำนวนชั่วโมง OT ที่ขอตามช่วงเวลา — Foreman ลบเวลาพักออกเองได้เหมือนฟอร์มรายคน */
      function bulkFillAmount() {
        var start = bulkTime('start').split(':').map(Number);
        var end = bulkTime('end').split(':').map(Number);
        var total = (end[0] * 60 + end[1]) - (start[0] * 60 + start[1]);
        if (total < 0) total += 1440;
        bulkModal.querySelector('[data-bulk-hours]').value = String(Math.floor(total / 60));
        bulkModal.querySelector('[data-bulk-minutes]').value = String(total % 60);
      }

      /* เดิมยกธีมกะเข้ามาย้อมทั้งโมดัลขอ OT หลายคน — Manager สั่งเอาออก 2026-08-21
         เหลือหน้าที่แค่ล้าง class เก่าให้พื้นโมดัลกลับมาขาวเสมอ */
      function paintBulkTheme() {
        var stale = ['ot-shift-scene', 'is-compact', 'ot-shift-tint', 'is-shift-morning', 'is-shift-night', 'is-shift-all'];
        bulkModal.querySelector('.otr-modal-head').classList.remove.apply(
          bulkModal.querySelector('.otr-modal-head').classList, stale
        );
        bulkModal.querySelectorAll('[data-bulk-step]').forEach(function (pane) {
          pane.classList.remove.apply(pane.classList, stale);
        });
      }

      function bulkStep(step) {
        bulkModal.querySelectorAll('[data-bulk-step]').forEach(function (node) {
          node.hidden = Number(node.dataset.bulkStep) !== step;
        });
        // ขั้นกรอกฟอร์มไม่ต้องกว้าง กางเฉพาะตอนโชว์รายชื่อทั้งกะ
        bulkModal.querySelector('.otr-bulk-dialog').classList.toggle('is-wide', step === 2);
      }

      function closeBulk() {
        bulkModal.hidden = true;
        document.body.style.overflow = '';
        bulkRows = [];
        bulkQuery = '';
        bulkBranch = 'all';
        bulkShift = 'all';
        bulkSort = 'code';
      }

      /** ขั้นที่ 2 — กระจายค่ากลางลงทุกคน แล้วให้แก้รายคนได้ */
      function buildBulkRows() {
        var typeOption = bulkModal.querySelector('[data-bulk-type]').selectedOptions[0];
        var shared = {
          ot_type: bulkModal.querySelector('[data-bulk-type]').value,
          timing: typeOption?.dataset.timing,
          start_time: bulkTime('start'),
          end_time: bulkTime('end'),
          hours: Number(bulkModal.querySelector('[data-bulk-hours]').value || 0),
          minutes: Number(bulkModal.querySelector('[data-bulk-minutes]').value || 0),
          note: bulkModal.querySelector('[data-bulk-note]').value.trim()
        };
        var perPerson = bulkModal.querySelector('[data-bulk-auto-time]').checked;

        bulkRows = bulkCandidates().map(function (employee) {
          var blocked = bulkBlockReason(employee);

          /* กลุ่มกะเดียวกันยังมีเวลาเข้า-ออกต่างกัน (AC01 05:00-14:00 กับ AD03 08:00-17:00)
             ถ้าใช้เวลาเดียวกันหมด OT หลังเลิกงานจะได้เวลาผิด และ OT ก่อนเข้างานจะตกกฎหายไปเลย
             จึงคิดจากกะของแต่ละคน แล้วให้แก้รายคนได้ในตาราง */
          var range = { start: shared.start_time, end: shared.end_time };
          var amount = { hours: shared.hours, minutes: shared.minutes };

          if (perPerson && employee.shift_in && employee.shift_out) {
            range = defaultRange(employee, shared.timing);
            amount = spanOf(range.start, range.end);
          }

          return {
            employee: employee,
            blocked: blocked,
            picked: blocked === '',    // ติ๊กครบทุกคนที่ขอได้ เอาออกรายคนได้
            ot_type: shared.ot_type,
            start_time: range.start,
            end_time: range.end,
            hours: amount.hours,
            minutes: amount.minutes,
            note: shared.note
          };
        });

        renderBulkTable();
        bulkStep(2);
      }

      /** ความยาวของช่วงเวลา แยกเป็นชั่วโมง/นาที (ยังไม่หักเวลาพัก — Foreman ลบเองในตาราง) */
      function spanOf(start, end) {
        var from = String(start).split(':').map(Number);
        var to = String(end).split(':').map(Number);
        var total = (to[0] * 60 + to[1]) - (from[0] * 60 + from[1]);
        if (total < 0) total += 1440;

        return { hours: Math.floor(total / 60), minutes: total % 60 };
      }

      function renderBulkTable() {
        var table = bulkModal.querySelector('[data-bulk-table]');
        var types = lastPayload.ot_types || @json($otTypes);
        table.replaceChildren();

        /* ติ๊กต้องเดินตามตัวกรองเสมอ (Manager สั่ง 2026-08-24)
           เปิดโมดัลมาทุกคนถูกติ๊กไว้ก่อน พอกรองแล้วคนที่หายไปจากจอต้องถูกปลดติ๊กด้วย
           ไม่งั้น "เลือกทั้งหมด" จะหมายถึงคนละชุดกับที่ตาเห็น แล้วกดส่งได้คนที่ไม่ได้ดู */
        var stillVisible = visibleBulkRows();
        bulkRows.forEach(function (row) {
          if (stillVisible.indexOf(row) === -1) row.picked = false;
        });

        /* ตัวเลือกกะสร้างจากคนทั้งชุดก่อนกรอง ไม่งั้นพอกรองแล้วตัวเลือกอื่นหายจนกลับไม่ได้ */
        if (bulkShiftSelect && window.otShiftFilter) {
          bulkShift = window.otShiftFilter.build(
            bulkShiftSelect,
            bulkRows.map(function (row) { return row.employee; }),
            bulkShift
          );
        }

        /* ผังคอลัมน์เป็น % ทั้งหมด รวมกัน 100 พอดี (11 คอลัมน์) — ตรึงด้วย table-layout: fixed
           ที่ CSS จะได้ไม่ยืดตามความยาวของหมายเหตุจนคอลัมน์อื่นเบี้ยว
           `เข้างาน`/`ออกงาน` อยู่ถัดขวาของ `กะ` เพื่อให้อ่านเรียงกันได้ว่า
           กะกี่โมง → มาจริงกี่โมง → ขอ OT ช่วงไหน (Manager สั่ง 2026-08-24) */
        var head = document.createElement('thead');
        head.innerHTML = '<tr>' +
          '<th style="width:3%"></th>' +
          '<th style="width:3%">#</th>' +
          '<th style="width:20%;text-align:left">' + text('ot.attendance.employee', 'พนักงาน') + '</th>' +
          '<th style="width:8.5%">' + text('ot.requests.shift', 'กะ') + '</th>' +
          '<th style="width:6%">' + text('ot.attendance.clockIn', 'เข้างาน') + '</th>' +
          '<th style="width:6%">' + text('ot.attendance.clockOut', 'เวลาล่าสุด / ออกงาน') + '</th>' +
          '<th style="width:6.5%">' + text('ot.attendance.presence', 'การทำงาน') + '</th>' +
          '<th style="width:14%">' + text('ot.requests.otType', 'ประเภท OT') + '</th>' +
          '<th style="width:7.5%">' + text('ot.requests.startTime', 'ตั้งแต่เวลา') + '</th>' +
          '<th style="width:7.5%">' + text('ot.requests.endTime', 'ถึงเวลา') + '</th>' +
          '<th style="width:8.5%">' + text('ot.requests.dailyAmount', 'จำนวนชั่วโมง OT ที่ขอ') + '</th>' +
          '<th style="width:9.5%">' + text('ot.requests.note', 'หมายเหตุ') + '</th>' +
          '</tr>';
        table.appendChild(head);

        /* ตัวกรองรายคอลัมน์แบบ Excel — ชุดเดียวกับตารางหลัก (helper กลาง otColumnFilter)
           ใส่เฉพาะคอลัมน์ที่ค่าซ้ำกันเยอะพอจะกรองได้จริง: กะ · การทำงาน
           (`ประเภท OT`/เวลา/หมายเหตุ เป็นช่องแก้รายคน ค่าเริ่มเหมือนกันหมด กรองแล้วไม่ได้อะไร)
           สร้างตัวเลือกจาก bulkRows ทั้งชุดก่อนกรอง ไม่งั้นกรองแล้วเลือกกลับไม่ได้ */
        var bulkHeadCells = head.querySelectorAll('th');
        bulkColumnFilters.attach(bulkHeadCells[3], 'shift', function (row) {
          return compactShiftRange(row.employee) || '-';
        }, bulkRows);
        bulkColumnFilters.attach(bulkHeadCells[6], 'presence', function (row) {
          return row.employee.presence || 'absent';
        }, bulkRows, function (value) {
          return text('ot.attendance.' + value, { present: 'เข้างาน', absent: 'ไม่เข้างาน', dayoff: 'วันหยุด' }[value] || value);
        });

        var body = document.createElement('tbody');
        /* ตัวกรองตัดแค่ "การแสดงผล" ไม่ตัดออกจาก bulkRows จริง คนที่ติ๊กไว้แล้วจึงไม่หลุด
           (ตัวนับ "เลือกแล้ว x/y" ข้างบนเป็นตัวบอกว่ายังมีคนที่ติ๊กไว้แต่ถูกกรองหายอยู่) */
        var visibleRows = visibleBulkRows();

        if (!visibleRows.length) {
          var emptyRow = document.createElement('tr');
          var emptyCell = document.createElement('td');
          emptyCell.colSpan = 12;
          emptyCell.className = 'otr-bulk-empty';
          emptyCell.textContent = bulkQuery
            ? text('ot.search.empty', 'ไม่พบพนักงานที่ค้นหา')
            : text('ot.shiftFilter.empty', 'ไม่พบพนักงานในกะที่เลือก');
          emptyRow.appendChild(emptyCell);
          body.appendChild(emptyRow);
        }

        /* เลข # นับจากแถวที่มองเห็นจริง เริ่ม 1 ใหม่ทุกครั้งที่กรอง/เรียงใหม่
           (เดิมนับจาก bulkRows ทั้งชุด พอเพิ่มการเรียงลำดับแล้วเลขจะกระโดด 5, 2, 9 …) */
        visibleRows.forEach(function (row, index) {
          var tr = document.createElement('tr');
          tr.classList.toggle('is-blocked', Boolean(row.blocked));
          tr.classList.toggle('is-off', !row.picked);

          var pick = document.createElement('input');
          pick.type = 'checkbox';
          pick.className = 'otr-bulk-pick';
          pick.checked = row.picked;
          pick.disabled = Boolean(row.blocked);
          pick.setAttribute('aria-label', row.employee.name_th || row.employee.code);
          pick.addEventListener('change', function () {
            row.picked = pick.checked;
            tr.classList.toggle('is-off', !row.picked);
            syncBulkCount();
          });
          tr.appendChild(cellOf(pick));
          tr.appendChild(cellOf(document.createTextNode(String(index + 1))));

          /* รูปพนักงานใช้ class เดิม จึงกดซูมได้ทันทีจาก delegation ของ lightbox
             ที่ผูกไว้ที่ document แบบ capture (z-index 130 สูงกว่าโมดัลอยู่แล้ว) */
          /* ทุกอย่างอยู่บรรทัดเดียว แถวจะได้เตี้ยและกวาดตาอ่านได้เร็ว
             รูปใช้ class เดิม จึงกดซูมได้ทันทีจาก lightbox ที่ผูกไว้ที่ document */
          var personWrap = document.createElement('span');
          personWrap.className = 'otr-bulk-person-wrap';
          personWrap.appendChild(avatar(row.employee));

          var name = document.createElement('strong');
          name.textContent = localized(row.employee, 'name', row.employee.code);
          var meta = document.createElement('small');
          meta.textContent = [row.employee.code, localized(row.employee, 'position', '')].filter(Boolean).join(' · ');
          personWrap.append(name, meta);

          if (row.blocked) {
            var why = document.createElement('small');
            why.className = 'otr-bulk-blocked';
            why.textContent = row.blocked;
            personWrap.appendChild(why);
          }
          tr.appendChild(cellOf(personWrap));
          tr.appendChild(cellOf(document.createTextNode(compactShiftRange(row.employee))));
          /* เวลาสแกนจริงของวันนั้น อ่านคู่กับกะที่อยู่ติดกันทางซ้าย จะได้รู้ว่ามาสาย/ออกก่อนไหม
             ยังไม่สแกน = ขีด ไม่ใช่ช่องว่าง จะได้ต่างจาก "ไม่มีข้อมูล" ชัดเจน */
          tr.appendChild(cellOf(document.createTextNode(row.employee.clock_in || '-')));
          tr.appendChild(cellOf(document.createTextNode(row.employee.clock_out || '-')));

          /* การทำงาน — ข้อความล้วน 3 ค่า ชุดสีเดียวกับตารางหลักและหน้าภาพรวม
             `ไม่เข้างาน` คือเหตุผลที่แถวนี้ขอ OT ไม่ได้ จึงต้องเห็นคู่กับป้ายเหตุผลข้างชื่อ */
          var presenceKey = row.employee.presence || 'absent';
          var presenceTag = document.createElement('span');
          presenceTag.className = 'otr-presence is-' + presenceKey;
          presenceTag.textContent = text('ot.attendance.' + presenceKey, { present: 'เข้างาน', absent: 'ไม่เข้างาน', dayoff: 'วันหยุด' }[presenceKey] || presenceKey);
          tr.appendChild(cellOf(presenceTag));

          if (row.blocked) {
            // แถวที่ทำไม่ได้ ไม่ต้องให้แก้อะไร แสดงไว้ให้รู้ว่าทำไมหายไปเท่านั้น
            for (var blank = 0; blank < 5; blank += 1) tr.appendChild(cellOf(document.createTextNode('—')));
            body.appendChild(tr);
            return;
          }

          var typeSelect = document.createElement('select');
          types.forEach(function (type) {
            var option = document.createElement('option');
            option.value = type.key;
            option.textContent = typeLabel(type);
            option.dataset.timing = type.timing;
            typeSelect.appendChild(option);
          });
          typeSelect.value = row.ot_type;
          tr.appendChild(cellOf(typeSelect));

          var startBox = bulkRowTime(row, 'start_time');
          var endBox = bulkRowTime(row, 'end_time');
          tr.appendChild(cellOf(startBox));
          tr.appendChild(cellOf(endBox));

          var amount = document.createElement('span');
          amount.className = 'otr-bulk-time';
          var hours = numberBox(row.hours, 0, 24, function (value) { row.hours = value; });
          var minutes = numberBox(row.minutes, 0, 59, function (value) { row.minutes = value; });
          amount.append(hours, document.createTextNode(':'), minutes);
          tr.appendChild(cellOf(amount));

          /* เปลี่ยนประเภท OT รายคน ต้องคิดเวลาให้ใหม่ตามกะของคนนั้นด้วย
             ไม่งั้นเวลาจะค้างของประเภทเดิม เช่น สลับหลังเลิกงาน -> ก่อนเข้างาน
             แล้วยังเป็น 18:00-20:00 ซึ่งตกกฎทันที */
          typeSelect.addEventListener('change', function () {
            row.ot_type = typeSelect.value;
            if (!row.employee.shift_in || !row.employee.shift_out) return;

            var range = defaultRange(row.employee, typeSelect.selectedOptions[0]?.dataset.timing);
            var span = spanOf(range.start, range.end);
            row.start_time = range.start;
            row.end_time = range.end;
            row.hours = span.hours;
            row.minutes = span.minutes;

            startBox.applyValue(range.start);
            endBox.applyValue(range.end);
            hours.value = String(span.hours);
            minutes.value = String(span.minutes);
          });

          var note = document.createElement('input');
          note.type = 'text';
          note.maxLength = 1000;
          note.value = row.note;
          note.addEventListener('input', function () { row.note = note.value; });
          tr.appendChild(cellOf(note));

          body.appendChild(tr);
        });

        table.appendChild(body);
        syncBulkCount();
      }

      /* dropdown ชั่วโมง:นาที เหมือนฟอร์มขั้นแรก
         ห้ามใช้ input[type=time] เพราะเบราว์เซอร์จะเติม AM/PM ตามภาษาเครื่อง
         ทำให้หน้าตาไม่ตรงกับที่กรอกไว้ตอนแรกและอ่านยาก */
      function bulkRowTime(row, field) {
        var parts = String(row[field] || '00:00').split(':');
        var box = document.createElement('span');
        box.className = 'otr-bulk-time';

        var hour = timeSelect(24, parts[0]);
        var minute = timeSelect(60, parts[1]);
        var sync = function () { row[field] = hour.value + ':' + minute.value; };
        hour.addEventListener('change', sync);
        minute.addEventListener('change', sync);

        // ให้ตัวเรียกสั่งเปลี่ยนค่าได้ ใช้ตอนสลับประเภท OT แล้วต้องคิดเวลาใหม่
        box.applyValue = function (value) {
          var next = String(value || '00:00').split(':');
          hour.value = next[0];
          minute.value = next[1];
        };

        box.append(hour, document.createTextNode(':'), minute);
        return box;
      }

      function timeSelect(count, selected) {
        var select = document.createElement('select');
        for (var value = 0; value < count; value += 1) {
          var option = document.createElement('option');
          option.value = String(value).padStart(2, '0');
          option.textContent = option.value;
          select.appendChild(option);
        }
        select.value = String(selected || '00').padStart(2, '0');
        return select;
      }

      function numberBox(value, min, max, onChange) {
        var input = document.createElement('input');
        input.type = 'number';
        input.min = String(min);
        input.max = String(max);
        input.step = '1';
        input.value = String(value);
        input.addEventListener('input', function () { onChange(Number(input.value || 0)); });
        return input;
      }

      function cellOf(node) {
        var td = document.createElement('td');
        td.appendChild(node);
        return td;
      }

      /* แถวที่ผ่านตัวกรองอยู่ตอนนี้ — ใช้ร่วมกันทั้งตอนวาดตาราง
         ตอนกด "เลือกทั้งหมด" และตอนคำนวณสถานะของ master checkbox
         ชุดตัวกรองเท่ากับตารางหลัก: ค้นหา · สาขา · กะ แล้วเรียงตามตัวเลือก */
      function visibleBulkRows() {
        var rows = bulkRows.filter(function (row) {
          return window.otEmployeeSearch.matches(row.employee, bulkQuery)
            && window.otBranchFilter.matches(row.employee, bulkBranch)
            && (!window.otShiftFilter || window.otShiftFilter.matches(row.employee, bulkShift))
            && bulkColumnFilters.matches(row);
        });

        if (bulkSort === 'rank' || bulkSort === 'rank_asc') {
          var dir = bulkSort === 'rank' ? 1 : -1;
          rows = rows.slice().sort(function (a, b) {
            var diff = ((a.employee.position_rank || 999) - (b.employee.position_rank || 999)) * dir;
            return diff !== 0 ? diff : String(a.employee.code).localeCompare(String(b.employee.code));
          });
        }

        return rows;
      }

      function syncBulkCount() {
        var usable = bulkRows.filter(function (row) { return !row.blocked; });
        var picked = usable.filter(function (row) { return row.picked; });
        var visibleAll = visibleBulkRows();
        var visibleUsable = visibleAll.filter(function (row) { return !row.blocked; });
        var visiblePicked = visibleUsable.filter(function (row) { return row.picked; });

        /* ตัวเลขบนแถบ = "ตามตัวกรองที่เลือกอยู่" (Manager สั่ง 2026-08-24)
           แต่ตัวกรองตัดแค่การแสดงผล คนที่ติ๊กไว้แล้วถูกกรองหายยังถูกส่งอยู่
           ถ้าโชว์แค่ตัวเลขที่มองเห็น จะกลายเป็นส่งคนที่ไม่เห็นบนจอโดยไม่มีอะไรเตือน
           จึงต่อท้ายด้วย "+N ที่ถูกกรองไว้" เฉพาะตอนที่มีคนติ๊กค้างอยู่นอกตัวกรองจริง ๆ */
        /* เลขหน้า = คนที่เลือกไว้ · เลขหลัง = พนักงาน "ทั้งหมด" ในตัวกรองนี้
           รวมคนที่ขอ OT ไม่ได้ด้วย (ตำแหน่งไม่มีสิทธิ์ · ขอไปแล้ว · ไม่เข้างาน) — Manager สั่ง 2026-08-24
           ผู้ใช้อ่านเลขหลังเป็น "แผนกนี้กรองแล้วเหลือกี่คน" ไม่ใช่ "กี่คนที่ติ๊กได้" */
        bulkModal.querySelector('[data-bulk-picked]').textContent = visiblePicked.length;
        bulkModal.querySelector('[data-bulk-total]').textContent = visibleAll.length;

        var hidden = picked.length - visiblePicked.length;
        var hiddenNode = bulkModal.querySelector('[data-bulk-hidden]');
        if (hiddenNode) {
          hiddenNode.textContent = '+' + hidden + ' ' + text('ot.requests.bulkHiddenPicked', 'ที่ถูกกรองไว้');
          hiddenNode.hidden = hidden < 1;
        }

        /* ปุ่มส่งต้องบอก "จำนวนที่จะถูกส่งจริง" = ทุกคนที่ติ๊กไว้ รวมคนที่ถูกกรองหาย
           ไม่ใช่จำนวนที่เห็นบนจอ ไม่งั้นกดส่งแล้วได้คนละจำนวนกับที่ปุ่มบอก */
        bulkModal.querySelector('[data-bulk-submit-count]').textContent = picked.length;
        bulkModal.querySelector('[data-bulk-submit]').disabled = picked.length === 0;

        /* master สะท้อนเฉพาะแถวที่มองเห็นอยู่ ไม่ใช่ทั้งแผนก
           ไม่งั้นกรองเหลือโรงงาน-พม่าแล้วติ๊กครบ ช่องหัวจะยังไม่ติ๊กเพราะคนอื่นยังไม่ถูกเลือก */
        var master = bulkModal.querySelector('[data-bulk-select-all]');
        master.disabled = visibleUsable.length === 0;
        master.checked = visibleUsable.length > 0 && visiblePicked.length === visibleUsable.length;
        master.indeterminate = visiblePicked.length > 0 && visiblePicked.length < visibleUsable.length;
      }

      async function submitBulk() {
        var picked = bulkRows.filter(function (row) { return row.picked && !row.blocked; });
        var errorNode = bulkModal.querySelector('[data-bulk-error-2]');
        var button = bulkModal.querySelector('[data-bulk-submit]');
        errorNode.hidden = true;

        var invalid = picked.find(function (row) {
          return row.start_time === row.end_time || (row.hours * 60 + row.minutes) <= 0;
        });
        if (invalid) {
          errorNode.textContent = text('ot.requests.bulkInvalidRow', 'มีบางแถวที่เวลาหรือจำนวนไม่ถูกต้อง');
          errorNode.hidden = false;
          return;
        }

        // ยืนยันก่อนยิงจริง เพราะส่งทีเดียวทั้งกะแล้วถอยไม่ได้
        var agreed = await askSend(
          text('ot.requests.sendConfirmBulk', 'ระบบจะส่งคำขอทั้งหมดให้ Supervisor ทันทีและแจ้งทางอีเมล')
          + ' · ' + picked.length + ' ' + text('ot.requests.items', 'รายการ')
        );
        if (!agreed) return;

        button.disabled = true;
        try {
          var response = await fetchJson(bulkUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
              company: activeDepartment.companyCode,
              dept_code: activeDepartment.deptCode,
              work_date: selectedDate,
              items: picked.map(function (row) {
                return {
                  employee_code: row.employee.code,
                  ot_type: row.ot_type,
                  start_time: row.start_time,
                  end_time: row.end_time,
                  requested_hours: row.hours,
                  requested_minutes: row.minutes,
                  note: row.note || null
                };
              })
            })
          });

          if (!response.ok) {
            errorNode.textContent = response.message + describeSkipped(response.skipped);
            errorNode.hidden = false;
            button.disabled = false;
            return;
          }

          closeBulk();
          selectedIds.clear();
          await loadDepartment(false);
          if (response.skipped?.length) {
            showNotice(response.message + describeSkipped(response.skipped), true);
          } else {
            showSuccessFeedback(response.message + describeSkipped(response.skipped));
          }
        } catch (error) {
          errorNode.textContent = validationMessage(error);
          errorNode.hidden = false;
          button.disabled = false;
        }
      }

      function describeSkipped(skipped) {
        if (!skipped || !skipped.length) return '';
        return ' — ' + skipped.slice(0, 3).map(function (item) {
          return item.employee_code + ': ' + item.reason;
        }).join(' · ') + (skipped.length > 3 ? ' …' : '');
      }

      root.querySelector('[data-open-bulk]').addEventListener('click', openBulk);
      bulkModal.querySelectorAll('[data-close-bulk]').forEach(function (button) {
        button.addEventListener('click', closeBulk);
      });
      /* ตั้งใจไม่ปิดเมื่อคลิกพื้นที่ว่างรอบโมดัล — กรอกข้อมูลทั้งกะไว้เยอะ
         เผลอคลิกพลาดทีเดียวข้อมูลหายหมด ต้องกดกากบาทหรือปุ่มยกเลิกเท่านั้น */
      bulkModal.querySelector('[data-bulk-type]').addEventListener('change', function () {
        bulkApplyDefaultRange(bulkCandidates()[0]);
      });
      bulkModal.querySelectorAll('[data-bulk-start-hour], [data-bulk-start-minute], [data-bulk-end-hour], [data-bulk-end-minute]')
        .forEach(function (select) { select.addEventListener('change', bulkFillAmount); });
      /* ติ๊กอยู่ = ระบบคิดเวลาให้รายคน ช่องเวลากลางจึงไม่มีผล ทำให้จางไว้จะได้ไม่สับสน
         ว่าทำไมกรอกแล้วตัวเลขในตารางไม่ตรง */
      function syncBulkAutoTime() {
        var auto = bulkModal.querySelector('[data-bulk-auto-time]').checked;
        bulkModal.querySelectorAll('[data-bulk-start-hour], [data-bulk-start-minute], [data-bulk-end-hour], [data-bulk-end-minute], [data-bulk-hours], [data-bulk-minutes]')
          .forEach(function (field) {
            field.disabled = auto;
            field.closest('.otr-time-block')?.classList.toggle('is-muted', auto);
          });
      }
      bulkModal.querySelector('[data-bulk-auto-time]').addEventListener('change', syncBulkAutoTime);
      bulkModal.querySelector('[data-bulk-next]').addEventListener('click', buildBulkRows);
      bulkModal.querySelector('[data-bulk-back]').addEventListener('click', function () { bulkStep(1); });
      bulkModal.querySelector('[data-bulk-select-all]').addEventListener('change', function (event) {
        // อ่านค่าเก็บไว้ก่อนวนลูป เพราะ syncBulkCount เขียนทับ master.checked ตามจำนวนที่เลือก
        var pickAll = event.target.checked;
        /* เลือกเฉพาะแถวที่ผ่านตัวกรองอยู่ตอนนี้ — กรองโรงงาน-พม่าแล้วกดเลือกทั้งหมด
           ต้องได้เฉพาะคนพม่า ไม่ใช่ลากคนทั้งแผนกมาส่งด้วย */
        visibleBulkRows().forEach(function (row) { if (!row.blocked) row.picked = pickAll; });
        renderBulkTable();
      });
      bulkModal.querySelector('[data-bulk-submit]').addEventListener('click', submitBulk);

      /* ค้นหาในโมดัลขั้นที่ 2 — หน่วงไว้เล็กน้อยเพราะวาดตารางใหม่ทั้งใบทุกครั้งที่พิมพ์
         ตัวติ๊กไม่ถูกล้างเมื่อค้นหา ต่างจากตารางหน้าหลัก เพราะที่นี่ยังไม่ได้ส่งอะไรออกไป
         ผู้ใช้จึงเลือกทีละกลุ่มด้วยการค้นหลายรอบแล้วค่อยกดส่งครั้งเดียวได้ */
      /* ตัวกรองสาขาในโมดัลต้องอ้างผ่าน bulkModal ไม่ใช่ root
         เพราะหน้าเดียวมี [data-branch-filter] สองตัว (แถบหัวตาราง กับ ในโมดัล) */
      var bulkBranchSelect = bulkModal.querySelector('[data-branch-filter]');
      if (bulkBranchSelect) {
        bulkBranchSelect.addEventListener('change', function () {
          bulkBranch = bulkBranchSelect.value;
          renderBulkTable();
        });
      }

      var bulkSearchInput = bulkModal.querySelector('[data-bulk-search]');
      if (bulkSearchInput) {
        bulkSearchInput.addEventListener('input', window.otEmployeeSearch.debounce(function () {
          bulkQuery = bulkSearchInput.value;
          renderBulkTable();
        }, 200));
      }

      /* ── ตัวกรองที่เพิ่มให้เท่ากับตารางหลัก (Manager สั่ง 2026-08-21) ──────────
         กะ · เรียงลำดับ = กรองการแสดงผลเฉย ๆ ไม่แตะการติ๊ก คนที่ติ๊กไว้แล้วยังถูกส่งอยู่
         (มีตัวนับ "เลือกแล้ว x/y" กำกับอยู่ข้าง ๆ ให้เห็นตลอด) */
      var bulkShiftSelect = bulkModal.querySelector('[data-shift-filter]');
      if (bulkShiftSelect) {
        bulkShiftSelect.addEventListener('change', function () {
          bulkShift = bulkShiftSelect.value;
          renderBulkTable();
        });
      }

      var bulkSortSelect = bulkModal.querySelector('[data-bulk-sort]');
      if (bulkSortSelect) {
        bulkSortSelect.addEventListener('change', function () {
          bulkSort = bulkSortSelect.value;
          renderBulkTable();
        });
      }

      /* ปฏิทินในโมดัล = เปลี่ยน "วันที่จะขอ OT ให้" ไม่ใช่แค่วันที่ดูข้อมูล
         จึงต้องโหลดรายชื่อของวันนั้นมาใหม่ แล้วสร้างแถวขั้นที่ 2 ใหม่ทั้งชุด
         โดยไม่ปิดโมดัล · หน้าเบื้องหลังกับ URL sync ตามไปด้วยจะได้ไม่คนละวันกัน */
      var bulkDateInput = bulkModal.querySelector('[data-bulk-date]');
      if (bulkDateInput) {
        bulkDateInput.addEventListener('change', async function () {
          if (!bulkDateInput.value || !activeDepartment) return;
          selectedDate = bulkDateInput.value;
          var picker = root.querySelector('[data-date-picker]');
          if (picker && picker.value !== selectedDate) picker.value = selectedDate;
          try {
            var url = new URL(window.location.href);
            url.searchParams.set('date', selectedDate);
            window.history.replaceState({}, '', url);
          } catch (error) { /* URL API ใช้ไม่ได้ก็ข้ามไป ไม่ใช่สาระสำคัญ */ }

          await loadDepartment(true);
          bulkModal.querySelector('[data-bulk-people]').textContent = bulkCandidates().length;

          /* สร้างแถวใหม่เฉพาะตอนที่อยู่ขั้นที่ 2 อยู่แล้ว — buildBulkRows() จบด้วย bulkStep(2)
             ถ้าเรียกตอนยังกรอกฟอร์มขั้นที่ 1 อยู่ จะกระโดดข้ามไปหน้ารายชื่อทั้งที่ยังกรอกไม่เสร็จ */
          var onStep2 = !bulkModal.querySelector('[data-bulk-step="2"]').hidden;
          if (onStep2) buildBulkRows();
        });
      }

      /* เปลี่ยนวันแล้ว "ค้างอยู่ที่แผนกเดิม" (Manager สั่ง 2026-08-24)
         เดิมโหลดทั้งหน้าใหม่ ผู้ใช้จึงเด้งกลับไปหน้าเลือกแผนกทุกครั้งที่เปลี่ยนวัน
         - เปิดเอกสารแผนกอยู่ = โหลดเฉพาะตาราง แล้วแก้ URL ให้ตรงด้วย replaceState
         - ยังอยู่หน้ารายชื่อแผนก = โหลดทั้งหน้าเหมือนเดิม เพราะยอดบนการ์ดมาจากฝั่ง server */
      root.querySelector('[data-date-picker]').addEventListener('change', function (event) {
        if (!event.target.value) return;
        selectedDate = event.target.value;

        if (!activeDepartment) {
          window.location.assign(@json(route('ot-approval.requests.index')) + '?date=' + encodeURIComponent(selectedDate));
          return;
        }

        try {
          var url = new URL(window.location.href);
          url.searchParams.set('date', selectedDate);
          window.history.replaceState({}, '', url);
        } catch (error) { /* URL API ใช้ไม่ได้ก็ข้ามไป ไม่ใช่สาระสำคัญ */ }

        pendingDirectoryReload = true;   // การ์ดแผนกด้านหลังยังเป็นยอดของวันเดิม
        loadDepartment(true);
      });
      /* เปลี่ยนสาขาก็ล้างการติ๊กด้วยเหตุผลเดียวกับเปลี่ยนกะ:
         แถวที่ติ๊กไว้แล้วถูกกรองหายจากจอต้องไม่ถูกยกเลิกโดยที่ผู้ใช้มองไม่เห็น */
      var branchFilterSelect = root.querySelector('[data-branch-filter]');
      if (branchFilterSelect) {
        branchFilterSelect.addEventListener('change', function () {
          branchFilterValue = branchFilterSelect.value;
          selectedIds.clear();
          if (lastPayload) renderEmployees(lastPayload, false);
        });
      }

      /* เรียงลำดับ — วาดตารางใหม่จากข้อมูลในมือ ไม่ยิงเซิร์ฟเวอร์ซ้ำ
         ต้องเจาะจงตัวใน .otr-paper-meta เพราะโมดัลขอ OT ทั้งกะมีตัวเรียงของตัวเองอีกตัว */
      var sortFilterSelect = root.querySelector('.otr-paper-meta [data-sort-filter]');
      if (sortFilterSelect) {
        sortFilterSelect.addEventListener('change', function () {
          sortMode = sortFilterSelect.value;
          if (lastPayload) renderEmployees(lastPayload, false);
        });
      }

      shiftFilterSelect.addEventListener('change', function () {
        shiftFilterValue = shiftFilterSelect.value;
        /* ต้องล้างการเลือกทุกครั้งที่เปลี่ยนกะ ไม่งั้นแถวที่ติ๊กไว้แล้วถูกกรองหายจากจอ
           จะยังถูกส่งขออนุมัติไปด้วย = ส่งผิดคนโดยไม่มีอะไรเตือน */
        selectedIds.clear();
        if (lastPayload) renderEmployees(lastPayload, false);
      });
      if (employeeSearchInput) {
        employeeSearchInput.addEventListener('input', function () {
          employeeQuery = employeeSearchInput.value;
          /* ล้างการเลือกด้วยเหตุผลเดียวกับตัวกรองกะ — แถวที่ติ๊กไว้แล้วถูกกรองหายจากจอ
             ต้องไม่ถูกส่งขออนุมัติไปโดยที่ผู้ใช้มองไม่เห็น */
          selectedIds.clear();
          if (lastPayload) renderEmployees(lastPayload, false);
        });
      }
      document.addEventListener('insight:languagechange', function () {
        if (activeDepartment) paper.querySelector('[data-paper-department]').textContent = localized(activeDepartment, 'deptName', '-');
        renderMyShift();
        if (lastPayload) renderEmployees(lastPayload, false);
      });

      // Local demo และ Snapshot จะเปลี่ยนเมื่อสั่ง sync เท่านั้น จึงไม่โหลดรายชื่อและรูปพนักงานซ้ำโดยไม่จำเป็น
      if (selectedDate === @json(now()->format('Y-m-d')) && ['local', 'snapshot'].indexOf(sourceMode) === -1) {
        window.setInterval(function () {
          if (!activeDepartment || document.hidden || !modal.hidden) return;
          quietRefresh = true;
          loadDepartment(false).finally(function () { quietRefresh = false; });
        }, 30000);
      }
    })();

    /* ── สรุปตัวเลขรวม: เปิดจากปุ่มนาฬิกา (ต้นแบบหน้าภาพรวม OT) ──────────
       แยกเป็น IIFE ของตัวเองเพื่อไม่ให้พังไปกับก้อนใหญ่ด้านบน (กับดักข้อ 2) */
    (function otRequestsSummaryModal() {
      'use strict';
      var box = document.querySelector('[data-summary-modal]');
      var open = document.querySelector('[data-summary-open]');
      if (!box || !open) return;
      function close() { box.hidden = true; document.body.style.overflow = ''; }
      open.addEventListener('click', function () { box.hidden = false; document.body.style.overflow = 'hidden'; });
      box.querySelector('[data-summary-close]').addEventListener('click', close);
      box.addEventListener('click', function (event) { if (event.target === box) close(); });
      document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !box.hidden) close(); });
    })();
  </script>
@endsection
