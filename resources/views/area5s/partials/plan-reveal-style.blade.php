{{-- CSS ของบล็อก "ภาพบริษัท → เผยอาคาร" ใช้ร่วมกันระหว่าง /area5s (ภาพรวม) และ /area5s/manage (จัดการพื้นที่)
     Manager สั่ง 2026-08-27 ให้ 2 หน้านี้แสดงผลเหมือนกัน — แก้ที่เดียวจบ ไม่ต้องกลัวหน้าตาเพี้ยนกัน
     include จาก @section('page-style') เท่านั้น --}}
    /* ---- ภาพบริษัท → กดแล้วเลื่อนหาย แล้วอาคารที่ crop ไว้โผล่ขึ้นมา ---- */
    /* หัวข้อชื่อผังจากหน้าจัดการพื้นที่ + ปุ่ม ‹ กลับภาพหลัก (Manager 2026-08-27) */
    .a5n-stage-head { position:relative; display:flex; align-items:center; justify-content:center; gap:.55rem; min-height:2.2rem; }
    .a5n-stage-title { margin:0; min-width:0; overflow:hidden; color:var(--light-text); font-size:1.05rem; font-weight:800; text-overflow:ellipsis; white-space:nowrap; }
    /* หน้าตาปุ่มมาจากคลาสกลาง .a5s-back — ที่นี่จัดตำแหน่งอย่างเดียว
       absolute เพื่อไม่ให้หัวข้อขยับตอนปุ่มโผล่/หาย */
    .a5n-back { position:absolute; left:0; top:50%; transform:translateY(-50%); }
    .a5n-back[hidden] { display:none !important; }
    .a5n-stage-action {
      position:absolute; right:0; top:50%; transform:translateY(-50%);
      display:inline-flex; align-items:center; height:2.1rem; padding:0 .9rem;
      border:1px solid var(--line-light); border-radius: 0.25rem;
      background:var(--menu-bg); color:var(--light-text);
      font-size:.78rem; font-weight:700; text-decoration:none;
      transition:border-color .16s ease, color .16s ease;
    }
    .a5n-stage-action:hover, .a5n-stage-action:focus-visible { border-color:var(--moss); color:var(--moss); outline:none; }
    @media (max-width: 720px) {
      .a5n-stage-head { flex-wrap:wrap; }
      .a5n-back, .a5n-stage-action { position:static; transform:none; }
      .a5n-stage-title { order:-1; flex:1 0 100%; text-align:center; }
    }

    .a5n-reveal { display:grid; gap:.75rem; }
    /* กรอบพอดีกับภาพ ไม่มีแถบว่างด้านข้าง — ความกว้างวิ่งตามสัดส่วนภาพจริง (Manager 2026-08-27) */
    .a5n-plan-shot {
      position:relative; display:block; overflow:hidden;
      justify-self:center; width:fit-content; max-width:100%;
      border:1px solid var(--line-light); border-radius: 0.36rem;
      padding:0; background:var(--panel-tint); cursor:pointer; line-height:0;
      transition:opacity .42s ease, transform .42s cubic-bezier(.4,0,.2,1), max-height .42s cubic-bezier(.4,0,.2,1), margin .42s ease;
      max-height:44rem;
    }
    /* เห็นภาพเต็มทุกสัดส่วน ไม่ crop — กำหนดความสูง ปล่อยความกว้างวิ่งตามสัดส่วน */
    .a5n-plan-shot img { display:block; width:auto; height:32rem; max-width:100%; object-fit:contain; }
    @media (max-width: 900px) {
      .a5n-plan-shot { width:100%; }
      .a5n-plan-shot img { width:100%; height:auto; }
    }
    .a5n-plan-shot:hover .a5n-plan-hint, .a5n-plan-shot:focus-visible .a5n-plan-hint { opacity:1; transform:translate(-50%, 0); }
    .a5n-plan-shot:focus-visible { outline:2px solid var(--moss); outline-offset:2px; }
    .a5n-plan-hint {
      position:absolute; left:50%; bottom:1rem; transform:translate(-50%, .4rem);
      padding:.4rem .9rem; border-radius:999px;
      background:rgb(17 21 13 / 72%); color:#fff; font-size:.78rem; font-weight:750;
      opacity:0; transition:opacity .2s ease, transform .2s ease; pointer-events:none;
    }
    /* ตอนเผยอาคาร: ภาพบริษัทยุบความสูงพร้อมเลื่อนขึ้นและจางหาย */
    .a5n-reveal.is-open .a5n-plan-shot {
      max-height:0; opacity:0; transform:translateY(-1.5rem); margin-bottom:-.75rem;
      border-width:0; pointer-events:none;
    }

    .a5n-areas { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:.85rem; }
    .a5n-areas[hidden] { display:none; }
    @media (max-width: 860px) { .a5n-areas { grid-template-columns:1fr; } }
    .a5n-area-card {
      position:relative; display:grid; grid-template-columns:minmax(0,1fr); gap:.65rem;
      overflow:hidden; border:1px solid var(--line-light); border-radius: 0.36rem;
      background:var(--panel-tint); padding:.75rem; color:inherit; text-decoration:none;
      opacity:0; transform:translateY(1.25rem);
      transition:border-color .16s ease, box-shadow .16s ease, transform .16s ease;
    }
    .a5n-reveal.is-open .a5n-area-card { animation:a5nAreaIn .45s cubic-bezier(.22,.72,.18,1) var(--a5n-delay, 0ms) both; }
    @keyframes a5nAreaIn { from { opacity:0; transform:translateY(1.25rem); } to { opacity:1; transform:none; } }
    .a5n-area-card:hover { border-color:var(--moss); box-shadow:0 .7rem 1.8rem rgb(0 0 0 / 12%); }
    .a5n-area-no {
      position:absolute; top:.75rem; left:.75rem; z-index:1;
      width:1.85rem; height:1.85rem; display:grid; place-items:center;
      border-radius:50%; background:var(--moss); color:#fff; font-size:.82rem; font-weight:800;
    }
    .a5n-area-shot {
      display:block; aspect-ratio:16 / 9; border-radius: 0.28rem;
      background-color:var(--menu-bg); background-repeat:no-repeat; background-size:cover; background-position:center;
    }
    .a5n-area-shot.is-empty { border:1px dashed var(--line-light); }
    .a5n-area-copy { display:grid; gap:.2rem; min-width:0; }
    .a5n-area-copy strong { overflow:hidden; color:var(--light-text); font-size:1rem; font-weight:760; text-overflow:ellipsis; white-space:nowrap; }
    .a5n-area-copy small { color:var(--muted-light); font-size:.74rem; }
    .a5n-area-copy small b { color:var(--light-text); font-weight:700; font-variant-numeric:tabular-nums; }
    .a5n-area-copy small i { font-style:normal; opacity:.5; }
    .a5n-area-card.is-empty-area { cursor:pointer; }

    /* modal กลางจอ: อาคารนี้ยังไม่มีข้อมูล (Manager 2026-08-27) */
    .a5n-modal[hidden] { display:none; }
    .a5n-modal { position:fixed; inset:0; z-index:2400; display:grid; place-items:center; padding:1.1rem; background:rgb(10 14 10 / 52%); }
    .a5n-modal-backdrop { position:absolute; inset:0; border:0; padding:0; background:transparent; cursor:pointer; }
    .a5n-modal-card {
      position:relative; z-index:1; width:min(100%, 26rem);
      display:grid; justify-items:center; gap:.45rem;
      border:1px solid var(--line-light); border-radius: 0.34rem;
      background:var(--panel-soft); padding:1.5rem 1.4rem 1.2rem; text-align:center;
      box-shadow:0 18px 54px rgb(0 0 0 / 32%);
      animation:a5nModalIn .2s cubic-bezier(.2,1.2,.35,1) both;
    }
    @keyframes a5nModalIn { from { opacity:0; transform:translateY(.5rem) scale(.97); } to { opacity:1; transform:none; } }
    .a5n-modal-card h3 { margin:0; color:var(--light-text); font-size:1.02rem; font-weight:800; }
    .a5n-modal-card p { margin:0; min-height:1rem; color:var(--muted-light); font-size:.82rem; }
    .a5n-modal-btn {
      margin-top:.85rem; min-width:7rem; height:2.4rem; padding:0 1.2rem;
      border:1px solid var(--moss); border-radius: 0.25rem;
      background:var(--moss); color:#fff; font:inherit; font-size:.84rem; font-weight:750; cursor:pointer;
    }
    .a5n-modal-btn:hover { filter:brightness(1.06); }
    @media (prefers-reduced-motion: reduce) {
      .a5n-plan-shot, .a5n-area-card { transition:none; }
      .a5n-reveal.is-open .a5n-area-card { animation:none; opacity:1; transform:none; }
      .a5n-modal-card { animation:none; }
    }
