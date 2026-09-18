@extends('layouts.portal')

@section('title', 'Time & Leave Approval')
@section('topbar-title')<span data-i18n="nav.otHome">หน้าหลัก</span>@endsection

@section('content')
  <style>
    /* หน้าแรกมีกล่องเดียว จัดให้อยู่กลางพื้นที่เนื้อหา */
    .ot-home {
      width: min(100%, 46rem);
      min-height: min(60vh, 34rem);
      margin: 0 auto;
      display: grid;
      align-content: center;
      gap: 1rem;
    }

    .ot-panel {
      padding: 1.5rem;
      border: 1px solid var(--line-light);
      border-radius: 4px;
      background: var(--panel);
    }
    .ot-panel h2 { font-size: 1rem; }
    .ot-panel-help { margin-top: .2rem; color: var(--muted-light); font-size: .86rem; }

    .ot-role-list {
      display: grid;
      gap: .1rem;
      margin-top: 1.15rem;
    }
    .ot-role {
      display: flex;
      flex-wrap: wrap;
      align-items: baseline;
      gap: .3rem .7rem;
      padding: .7rem 0;
      border-top: 1px solid var(--line-light);
    }
    .ot-role-name {
      min-width: 7.5rem;
      color: var(--moss);
      font-size: .88rem;
      font-weight: 800;
    }
    .ot-role-scope { color: var(--light-text); font-size: .86rem; }
    .ot-role-company { margin-left: auto; color: var(--muted-light); font-size: .76rem; }

    @media (max-width: 560px) {
      .ot-role-name { min-width: 100%; }
      .ot-role-company { margin-left: 0; }
    }
  </style>

  <main class="ot-home">
    <article class="ot-panel">
      <h2 data-i18n="ot.home.currentRole">สิทธิ์ของคุณ</h2>
      <p class="ot-panel-help" data-i18n="ot.home.currentRoleHelp">บทบาทที่ได้รับในระบบ Time & Leave Approval</p>

      <div class="ot-role-list">
        @foreach ($roleRows as $row)
          <div class="ot-role">
            <span class="ot-role-name" data-i18n="{{ $row['role_key'] }}">{{ $row['role_label'] }}</span>
            <span class="ot-role-scope" @if ($row['scope_key']) data-i18n="{{ $row['scope_key'] }}" @endif>
              @if ($row['scope_key'])
                {{ $row['scope_th'] }}
              @else
                <span data-val="th">{{ $row['scope_th'] }}</span><span data-val="en">{{ $row['scope_en'] }}</span>
              @endif
            </span>
            @if ($row['company'])
              <span class="ot-role-company">{{ $row['company'] }}</span>
            @endif
          </div>
        @endforeach
      </div>
    </article>
  </main>
@endsection
