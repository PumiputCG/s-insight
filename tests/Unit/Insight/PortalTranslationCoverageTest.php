<?php

namespace Tests\Unit\Insight;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PortalTranslationCoverageTest extends TestCase
{
  private string $root;

  protected function setUp(): void
  {
    parent::setUp();

    $this->root = dirname(__DIR__, 3);
  }

  #[DataProvider('dictionaryProvider')]
  public function test_portal_dictionaries_have_identical_keys_without_duplicates(string $start, string $end): void
  {
    $source = file_get_contents($this->root.'/resources/views/layouts/portal.blade.php');
    $sets = $this->dictionaryKeys($source, $start, $end);

    foreach ($sets as $language => $keys) {
      $duplicates = array_keys(array_filter(array_count_values($keys), fn (int $count): bool => $count > 1));
      $this->assertSame([], $duplicates, "Duplicate {$language} keys: ".implode(', ', $duplicates));
    }

    $thaiKeys = array_values(array_unique($sets['th']));
    sort($thaiKeys);
    foreach (['en', 'my'] as $language) {
      $keys = array_values(array_unique($sets[$language]));
      sort($keys);
      $this->assertSame($thaiKeys, $keys, "{$language} dictionary keys do not match Thai");
    }
  }

  public static function dictionaryProvider(): array
  {
    return [
      'portal' => ['var copy = {', 'var area5sCopy = {'],
      '5S Area' => ['var area5sCopy = {', 'var otApprovalCopy = {'],
      'Time and Leave' => ['var otApprovalCopy = {', 'Object.keys(area5sCopy)'],
    ];
  }

  public function test_active_portal_views_only_reference_existing_static_translation_keys(): void
  {
    $portal = file_get_contents($this->root.'/resources/views/layouts/portal.blade.php');
    preg_match_all("/^\s*'([^']+)'\s*:/m", $portal, $matches);
    $available = array_fill_keys($matches[1], true);
    $missing = [];

    foreach (['area5s', 'assessment', 'insight', 'ot_approval'] as $directory) {
      $path = $this->root.'/resources/views/'.$directory;
      $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
      foreach ($iterator as $file) {
        $normalized = str_replace('\\', '/', $file->getPathname());
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php') || str_contains($file->getFilename(), '.bak')) {
          continue;
        }
        if (str_contains($normalized, '/recruit/') || str_ends_with($normalized, '/insight/landing.blade.php') || str_contains($normalized, '/insight/auth/')) {
          continue;
        }

        $source = file_get_contents($file->getPathname());
        preg_match_all('/data-i18n(?:-placeholder|-aria|-title|-tooltip)?="([^"]+)"/', $source, $references);
        foreach ($references[1] as $key) {
          if (! str_contains($key, '{{') && ! isset($available[$key])) {
            $missing[] = $normalized.': '.$key;
          }
        }
      }
    }

    $this->assertSame([], $missing, "Missing translation keys:\n".implode("\n", $missing));
  }

  public function test_language_switch_preserves_nested_icons_images_and_links(): void
  {
    $portal = file_get_contents($this->root.'/resources/views/layouts/portal.blade.php');

    $this->assertStringContainsString('function setTranslatedText(node, value)', $portal);
    $this->assertStringContainsString("if (dict[key]) setTranslatedText(node, dict[key]);", $portal);
    $this->assertStringNotContainsString("if (dict[key]) node.textContent = dict[key];", $portal);
    $this->assertStringContainsString("querySelectorAll('[data-loc-th]:not([data-loc-attr])')", $portal);
    $this->assertStringNotContainsString("querySelectorAll('[data-loc-th]').forEach", $portal);
  }

  public function test_recruit_is_hidden_by_default_but_can_be_reenabled(): void
  {
    $config = file_get_contents($this->root.'/config/insight.php');
    $systems = file_get_contents($this->root.'/resources/views/insight/systems.blade.php');

    $this->assertStringContainsString("env('INSIGHT_RECRUIT_VISIBLE', false)", $config);
    $this->assertStringContainsString('@if ($recruitVisible)', $systems);
  }

  public function test_selected_self_assessment_user_keeps_personal_menu_when_also_hr(): void
  {
    $portal = file_get_contents($this->root.'/resources/views/layouts/portal.blade.php');
    $overview = file_get_contents($this->root.'/resources/views/assessment/index.blade.php');
    $routes = file_get_contents($this->root.'/routes/web/assessment.php');

    $this->assertStringContainsString('@if ($asmCanSelf)', $portal);
    $this->assertStringNotContainsString('$asmCanSelf && ! ($asmIsAdmin || $asmIsHr)', $portal);
    $this->assertStringContainsString("route('assessment.self.form')", $portal);
    $this->assertStringContainsString("route('assessment.self.form')", $overview);
    $this->assertStringContainsString("name('self.form')", $routes);
  }

  public function test_ot_employee_month_uses_localized_employee_fields(): void
  {
    $view = file_get_contents($this->root.'/resources/views/ot_approval/employee-month.blade.php');

    $this->assertStringContainsString('$name_en = $employee->fullNameEn() ?: $name_th;', $view);
    $this->assertStringContainsString('data-loc-en="{{ $name_en }}"', $view);
    $this->assertStringContainsString('data-loc-attr="alt,data-caption-name"', $view);
    $this->assertStringContainsString('data-loc-en="{{ $snapshot?->shift_name_en', $view);
  }

  private function dictionaryKeys(string $source, string $start, string $end): array
  {
    $startAt = strpos($source, $start);
    $endAt = strpos($source, $end, $startAt + strlen($start));
    $this->assertNotFalse($startAt, "Dictionary start marker not found: {$start}");
    $this->assertNotFalse($endAt, "Dictionary end marker not found: {$end}");

    $section = substr($source, $startAt, $endAt - $startAt);
    $sets = ['th' => [], 'en' => [], 'my' => []];
    $language = null;
    foreach (preg_split('/\R/', $section) as $line) {
      if (preg_match('/^\s*(th|en|my):\s*\{\s*$/', $line, $match)) {
        $language = $match[1];
        continue;
      }
      if ($language && preg_match("/^\s*'([^']+)'\s*:/", $line, $match)) {
        $sets[$language][] = $match[1];
      }
    }

    return $sets;
  }
}
